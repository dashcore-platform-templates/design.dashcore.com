<?php

namespace App\Design;

use InvalidArgumentException;

/**
 * Colour arithmetic the resolver needs: hex to and from RGB and HSL, lightness
 * steps, alpha over a surface, and WCAG contrast. Nothing here knows about the
 * standard; it is pure maths.
 */
final class Color
{
    /** @return array{0:int,1:int,2:int} */
    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            throw new InvalidArgumentException("Not a hex colour: {$hex}");
        }

        return [(int) hexdec(substr($hex, 0, 2)), (int) hexdec(substr($hex, 2, 2)), (int) hexdec(substr($hex, 4, 2))];
    }

    public static function rgbToHex(int $r, int $g, int $b): string
    {
        $clamp = fn (int $v): int => max(0, min(255, $v));

        return sprintf('#%02x%02x%02x', $clamp($r), $clamp($g), $clamp($b));
    }

    /** @return array{0:float,1:float,2:float} hue 0-360, saturation 0-100, lightness 0-100 */
    public static function hexToHsl(string $hex): array
    {
        [$r, $g, $b] = array_map(fn (int $v): float => $v / 255, self::hexToRgb($hex));
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            return [0.0, 0.0, $l * 100];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        $h = match ($max) {
            $r => ($g - $b) / $d + ($g < $b ? 6 : 0),
            $g => ($b - $r) / $d + 2,
            default => ($r - $g) / $d + 4,
        };

        return [$h * 60, $s * 100, $l * 100];
    }

    public static function hslToHex(float $h, float $s, float $l): string
    {
        $h = fmod(fmod($h, 360) + 360, 360) / 360;
        $s = max(0, min(100, $s)) / 100;
        $l = max(0, min(100, $l)) / 100;

        if ($s == 0) {
            $v = (int) round($l * 255);

            return self::rgbToHex($v, $v, $v);
        }

        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;

        $channel = function (float $t) use ($p, $q): int {
            if ($t < 0) {
                $t += 1;
            }
            if ($t > 1) {
                $t -= 1;
            }
            $v = match (true) {
                $t < 1 / 6 => $p + ($q - $p) * 6 * $t,
                $t < 1 / 2 => $q,
                $t < 2 / 3 => $p + ($q - $p) * (2 / 3 - $t) * 6,
                default => $p,
            };

            return (int) round($v * 255);
        };

        return self::rgbToHex($channel($h + 1 / 3), $channel($h), $channel($h - 1 / 3));
    }

    /** Same hue and saturation, lightness moved by $delta percentage points. */
    public static function shiftLightness(string $hex, float $delta): string
    {
        [$h, $s, $l] = self::hexToHsl($hex);

        return self::hslToHex($h, $s, $l + $delta);
    }

    /** The colour you see when $fg at $alpha sits over an opaque $bg. */
    public static function over(string $fg, string $bg, float $alpha): string
    {
        [$fr, $fg_, $fb] = self::hexToRgb($fg);
        [$br, $bg_, $bb] = self::hexToRgb($bg);

        return self::rgbToHex(
            (int) round($fr * $alpha + $br * (1 - $alpha)),
            (int) round($fg_ * $alpha + $bg_ * (1 - $alpha)),
            (int) round($fb * $alpha + $bb * (1 - $alpha)),
        );
    }

    /** Eight-digit hex with an alpha channel, for CSS. */
    public static function withAlpha(string $hex, float $alpha): string
    {
        return strtolower(ltrim($hex, '#') === ltrim($hex, '#') ? '#'.ltrim($hex, '#') : $hex)
            .sprintf('%02x', (int) round(max(0, min(1, $alpha)) * 255));
    }

    public static function luminance(string $hex): float
    {
        $lin = array_map(function (int $v): float {
            $c = $v / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::hexToRgb($hex));

        return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
    }

    /** WCAG 2 contrast ratio, rounded to two places. */
    public static function contrast(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);
        [$hi, $lo] = $la > $lb ? [$la, $lb] : [$lb, $la];

        return round(($hi + 0.05) / ($lo + 0.05), 2);
    }

    /**
     * Move $hex's lightness in $direction (-1 darker, +1 lighter) two points at a
     * time until it reaches $min contrast against $bg, or runs out of room.
     */
    public static function untilContrast(string $hex, string $bg, float $min, int $direction): string
    {
        $current = $hex;

        for ($i = 0; $i < 50; $i++) {
            if (self::contrast($current, $bg) >= $min) {
                return $current;
            }
            $next = self::shiftLightness($current, 2 * $direction);
            if ($next === $current) {
                break;
            }
            $current = $next;
        }

        return $current;
    }
}
