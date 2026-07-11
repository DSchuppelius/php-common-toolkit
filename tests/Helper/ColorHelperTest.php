<?php
/*
 * Created on   : Sat Jul 11 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ColorHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\ColorHelper;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für ColorHelper (WCAG-Luminanz/Kontrast + HEX-Normalisierung).
 */
class ColorHelperTest extends BaseTestCase {
    public function test_normalize_hex_accepts_only_six_digit_hex(): void {
        $this->assertSame('#a1b2c3', ColorHelper::normalizeHex('A1B2C3'));
        $this->assertSame('#a1b2c3', ColorHelper::normalizeHex('#A1B2C3'));
        $this->assertSame('#ffffff', ColorHelper::normalizeHex(' #FFFFFF '));

        $this->assertNull(ColorHelper::normalizeHex(null));
        $this->assertNull(ColorHelper::normalizeHex(''));
        $this->assertNull(ColorHelper::normalizeHex('#fff'));           // Kurzform bewusst ungültig
        $this->assertNull(ColorHelper::normalizeHex('#a1b2c3d4'));      // 8-stellig ungültig
        $this->assertNull(ColorHelper::normalizeHex('#a1b2cg'));        // kein Hex
        $this->assertNull(ColorHelper::normalizeHex('red'));            // CSS-Keyword ungültig
        $this->assertNull(ColorHelper::normalizeHex('#a1b2c3;evil'));   // Injection-Versuch
    }

    public function test_relative_luminance_matches_wcag_reference_values(): void {
        $this->assertSame(1.0, ColorHelper::relativeLuminance('#ffffff'));
        $this->assertSame(0.0, ColorHelper::relativeLuminance('#000000'));
        // sRGB-Rot nach WCAG: 0.2126
        $this->assertEqualsWithDelta(0.2126, ColorHelper::relativeLuminance('#ff0000'), 0.0001);
        // sRGB-Grün: 0.7152
        $this->assertEqualsWithDelta(0.7152, ColorHelper::relativeLuminance('00ff00'), 0.0001);
        // sRGB-Blau: 0.0722
        $this->assertEqualsWithDelta(0.0722, ColorHelper::relativeLuminance('#0000ff'), 0.0001);
    }

    public function test_relative_luminance_of_invalid_input_is_zero(): void {
        $this->assertSame(0.0, ColorHelper::relativeLuminance('#fff'));
        $this->assertSame(0.0, ColorHelper::relativeLuminance('nope'));
        $this->assertSame(0.0, ColorHelper::relativeLuminance(''));
    }

    public function test_contrast_ratio_black_white_is_21(): void {
        $this->assertEqualsWithDelta(21.0, ColorHelper::contrastRatio('#000000', '#ffffff'), 0.0001);
        $this->assertEqualsWithDelta(1.0, ColorHelper::contrastRatio('#808080', '#808080'), 0.0001);
    }

    public function test_contrast_ratio_is_symmetric(): void {
        $this->assertSame(
            ColorHelper::contrastRatio('#1f2937', '#fde047'),
            ColorHelper::contrastRatio('#fde047', '#1f2937'),
        );
    }

    public function test_readable_foreground_picks_dark_on_light_and_light_on_dark(): void {
        $this->assertSame('#1f2937', ColorHelper::readableForeground('#ffffff'));
        $this->assertSame('#ffffff', ColorHelper::readableForeground('#000000'));
        $this->assertSame('#ffffff', ColorHelper::readableForeground('#1d4ed8')); // dunkles Blau
    }

    public function test_readable_foreground_honors_custom_colors_and_threshold(): void {
        $this->assertSame('dark', ColorHelper::readableForeground('#ffffff', 'dark', 'light'));
        $this->assertSame('light', ColorHelper::readableForeground('#222222', 'dark', 'light'));
        // Schwellwert 0.0: alles außer Schwarz gilt als hell.
        $this->assertSame('dark', ColorHelper::readableForeground('#0000ff', 'dark', 'light', 0.0));
    }
}
