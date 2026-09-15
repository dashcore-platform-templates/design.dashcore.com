<?php

namespace Tests\Unit;

use App\Design\Artboard;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtboardTest extends TestCase
{
    #[Test]
    public function the_chosen_frames_come_back_in_layout_order_and_nothing_else_does(): void
    {
        // The list arrives from the URL and reaches a width, a height and an
        // iframe title, so anything not in the map has to fall out here.
        $this->assertSame(['desktop', 'mobile'], Artboard::shown(['mobile', 'desktop']));
        $this->assertSame(['desktop'], Artboard::shown(['desktop', 'nonsense', '<script>']));
        $this->assertSame(['tablet'], Artboard::shown(['tablet']));

        // Empty is not a canvas, so it is the default rather than nothing.
        $this->assertSame(Artboard::DEFAULT_SHOWN, Artboard::shown([]));
        $this->assertSame(Artboard::DEFAULT_SHOWN, Artboard::shown(['nothing', 'real']));
    }

    #[Test]
    public function the_two_ends_of_the_range_take_the_first_row(): void
    {
        // Ticking a middle width adds a row rather than pushing the default
        // pair apart, which is the whole point of layout order not being size
        // order. Menu order is size order, and that is a different list.
        $this->assertSame(['desktop', 'mobile', 'tablet'], Artboard::shown(['desktop', 'tablet', 'mobile']));
        $this->assertSame(['desktop', 'mobile', 'laptop', 'tablet'], Artboard::shown(array_keys(Artboard::VIEWPORTS)));
        $this->assertSame(['Desktop', 'Laptop', 'Tablet', 'Mobile'], array_column(Artboard::VIEWPORTS, 'label'));
    }

    #[Test]
    public function none_ticked_is_a_thing_the_checkboxes_can_say(): void
    {
        // valid() filters and stops. shown() filters and then falls back. The
        // menu needs the first, or unticking the last box would tick two more.
        $this->assertSame([], Artboard::valid([]));
        $this->assertSame([], Artboard::valid(['bogus']));
        $this->assertSame(['desktop'], Artboard::valid(['desktop', 'bogus']));

        $this->assertSame(Artboard::DEFAULT_SHOWN, Artboard::shown([]));
        $this->assertSame(Artboard::DEFAULT_SHOWN, Artboard::shown(['bogus']));
    }

    #[Test]
    public function the_grid_is_as_wide_as_its_two_columns_not_the_sum_of_its_frames(): void
    {
        // Two columns, so four frames are not four frames wide. A column is as
        // wide as the widest frame in it.
        $this->assertSame(1440 + 32 + 390, Artboard::gridWidth(['desktop', 'mobile'], 32));
        $this->assertSame(1440 + 32 + 390, Artboard::gridWidth(['desktop', 'mobile', 'tablet'], 32), 'tablet sits under desktop and is narrower, so it adds nothing');
        $this->assertSame(1440 + 32 + 768, Artboard::gridWidth(['desktop', 'mobile', 'laptop', 'tablet'], 32), 'tablet is now in the second column and is wider than mobile');

        // One frame has no second column, so no gap either.
        $this->assertSame(390, Artboard::gridWidth(['mobile'], 32));
        $this->assertSame(1440, Artboard::gridWidth(['desktop'], 32));
    }

    #[Test]
    public function every_frame_carries_a_label_and_real_pixels(): void
    {
        foreach (Artboard::VIEWPORTS as $name => $frame) {
            $this->assertSame(['label', 'width', 'height'], array_keys($frame), $name);
            $this->assertGreaterThan(300, $frame['width'], $name);
            $this->assertGreaterThan(300, $frame['height'], $name);
        }

        // 02 caps content at 80rem, which is 1280. Laptop meets the cap exactly
        // and desktop is wider than it, which is the only thing separating them.
        $this->assertSame(1280, Artboard::VIEWPORTS['laptop']['width']);
        $this->assertGreaterThan(1280, Artboard::VIEWPORTS['desktop']['width']);
    }
}
