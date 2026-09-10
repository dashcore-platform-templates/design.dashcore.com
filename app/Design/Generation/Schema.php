<?php

namespace App\Design\Generation;

use App\Design\Neutrals;
use App\Design\Pairings;
use App\Design\Resolver;

/**
 * The JSON schema the model must fill. Every enumeration is read from the same
 * lists the resolver uses, so the schema and the standard cannot drift apart.
 * Structured output guarantees the shape; Rules enforces what a schema cannot
 * say, such as hue distance and word counts.
 */
final class Schema
{
    /** @return array<string,mixed> */
    public static function decisions(): array
    {
        $decision = fn (array $value, string $what): array => [
            'type' => 'object',
            'properties' => [
                'value' => $value + ['description' => $what],
                'why' => ['type' => 'string', 'description' => 'One plain sentence naming the thing in the brief that led to this choice.'],
            ],
            'required' => ['value', 'why'],
            'additionalProperties' => false,
        ];

        return [
            'type' => 'object',
            'properties' => [
                'accent' => $decision(['type' => 'string'], 'Six-digit lowercase hex such as #0f766e. Hue at least 30 degrees from 0 and from 40; saturation 45 to 85 percent; unless the brief gives a brand_colour, which wins.'),
                'temperature' => $decision(['type' => 'string', 'enum' => Neutrals::TEMPERATURES], 'The neutral grey temperature.'),
                'pairing' => $decision(['type' => 'integer', 'enum' => array_keys(Pairings::all())], 'The number of an approved type pairing.'),
                'radius' => $decision(['type' => 'string', 'enum' => Resolver::RADII], 'The corner radius step.'),
                'density' => $decision(['type' => 'string', 'enum' => Resolver::DENSITIES], 'Compact only for trained staff working all day.'),
                'motif' => $decision(['type' => 'string', 'enum' => Resolver::MOTIFS], 'Exactly one structural motif.'),
                'copy' => [
                    'type' => 'object',
                    'properties' => [
                        'eyebrow' => ['type' => ['string', 'null'], 'description' => 'Up to four words, or null.'],
                        'headline' => ['type' => 'string', 'description' => 'Up to eight words.'],
                        'tagline' => ['type' => 'string', 'description' => 'One sentence, up to sixteen words.'],
                        'footer' => ['type' => ['string', 'null'], 'description' => 'Null unless the brief asks for a footer line.'],
                        'why' => ['type' => 'string', 'description' => 'One plain sentence on why this copy fits the client.'],
                    ],
                    'required' => ['eyebrow', 'headline', 'tagline', 'footer', 'why'],
                    'additionalProperties' => false,
                ],
            ],
            'required' => ['accent', 'temperature', 'pairing', 'radius', 'density', 'motif', 'copy'],
            'additionalProperties' => false,
        ];
    }
}
