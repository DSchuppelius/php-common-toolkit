<?php
/*
 * Created on   : Mon Oct 05 2026
 * Author       : Daniel Joerg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ImageCropHelperCoderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\FileSystem\FileTypes\ImageCropHelper;
use ReflectionMethod;
use Tests\Contracts\BaseTestCase;

/**
 * Der optionale Coder nagelt das Format der Eingabe fest: ImageMagick liest die
 * Datei dann ausschliesslich als dieses Format ("png:<pfad>") und waehlt den
 * Coder nicht selbst aus Inhalt und Endung. Ohne Coder bleibt der Aufruf wie
 * bisher.
 */
class ImageCropHelperCoderTest extends BaseTestCase {
    /** 8x6-PNG (Farbverlauf). */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAgAAAAGCAIAAABxZ0isAAAAGElEQVQI12P8z4AdsJxlMKa1BCMDA3brAQmYBhFFIUB0AAAAAElFTkSuQmCC';

    private string $dir;

    private string $png;

    protected function setUp(): void {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/ctk-crop-coder-' . bin2hex(random_bytes(4));
        mkdir($this->dir, 0700, true);
        $this->png = $this->dir . '/bild.png';
        file_put_contents($this->png, (string) base64_decode(self::PNG, true));
    }

    protected function tearDown(): void {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);
        parent::tearDown();
    }

    public function test_eingabe_argument_traegt_den_coder_nur_wenn_er_gesetzt_ist(): void {
        $inputArgument = new ReflectionMethod(ImageCropHelper::class, 'inputArgument');

        $this->assertSame('/tmp/a.png', $inputArgument->invoke(null, '/tmp/a.png', null), 'Ohne Coder bleibt die Eingabe der Pfad.');
        $this->assertSame('png:/tmp/a.png', $inputArgument->invoke(null, '/tmp/a.png', 'png'));
        $this->assertSame('png:/tmp/a.png', $inputArgument->invoke(null, '/tmp/a.png', 'png:'), 'Die Schreibweise mit Doppelpunkt ist gleichwertig.');
        $this->assertSame('JPEG:/tmp/a.jpg', $inputArgument->invoke(null, '/tmp/a.jpg', ' JPEG '));
        $this->assertSame('tiff64:/tmp/a.tif', $inputArgument->invoke(null, '/tmp/a.tif', 'tiff64'));
        // Ein Doppelpunkt im Pfad aendert das Format nicht mehr: der erste Abschnitt ist der Coder.
        $this->assertSame('png:msl:/tmp/a.png', $inputArgument->invoke(null, 'msl:/tmp/a.png', 'png'));
    }

    public function test_ungueltiger_coder_wird_abgelehnt(): void {
        $inputArgument = new ReflectionMethod(ImageCropHelper::class, 'inputArgument');

        foreach (['', ':', '::', 'png:msl', 'msl:/tmp/x', '../png', 'p ng', 'png;id', 'png$(id)', '-png', 'sparse-color', str_repeat('a', 17)] as $coder) {
            $this->assertNull($inputArgument->invoke(null, '/tmp/a.png', $coder), "'$coder' ist kein Coder");
        }
    }

    public function test_ungueltiger_coder_fuehrt_nichts_aus(): void {
        $output = $this->dir . '/aus.png';
        $marker = $this->dir . '/darf-nicht-entstehen';

        $this->assertFalse(ImageCropHelper::cropToBox($this->png, $output, 0, 0, 4, 3, 'png; touch ' . $marker));
        $this->assertFalse(ImageCropHelper::cropUpperHalf($this->png, $output, ''));
        $this->assertNull(ImageCropHelper::getImageDimensions($this->png, 'png:msl'));

        $this->assertFileDoesNotExist($output);
        $this->assertFileDoesNotExist($marker);
    }

    public function test_zuschnitt_mit_passendem_coder_gleicht_dem_ohne_coder(): void {
        $this->requireImageMagick();

        $plain = $this->dir . '/ohne.png';
        $pinned = $this->dir . '/mit.png';

        $this->assertTrue(ImageCropHelper::cropToBox($this->png, $plain, 2, 1, 4, 3));
        $this->assertTrue(ImageCropHelper::cropToBox($this->png, $pinned, 2, 1, 4, 3, 'png'));

        $this->assertSame(['width' => 4, 'height' => 3], ImageCropHelper::getImageDimensions($plain));
        $this->assertSame(['width' => 4, 'height' => 3], ImageCropHelper::getImageDimensions($pinned, 'png:'));
    }

    public function test_prozent_zuschnitte_reichen_den_coder_durch(): void {
        $this->requireImageMagick();

        $upper = $this->dir . '/oben.png';
        $lower = $this->dir . '/unten.png';

        $this->assertTrue(ImageCropHelper::cropUpperHalf($this->png, $upper, 'png'));
        $this->assertTrue(ImageCropHelper::cropLowerPercent($this->png, $lower, 33.4, 'png'));
        $this->assertSame(['width' => 8, 'height' => 3], ImageCropHelper::getImageDimensions($upper));
        $this->assertSame(['width' => 8, 'height' => 2], ImageCropHelper::getImageDimensions($lower));

        // Mit dem falschen Coder scheitert jeder Weg - der Coder kommt also wirklich bei ImageMagick an.
        $wrong = $this->dir . '/falsch.png';
        $this->assertFalse(ImageCropHelper::cropUpperHalf($this->png, $wrong, 'jpeg'));
        $this->assertFalse(ImageCropHelper::cropLowerHalf($this->png, $wrong, 'jpeg'));
        $this->assertFalse(ImageCropHelper::cropUpperPercent($this->png, $wrong, 50.0, 'jpeg'));
        $this->assertFalse(ImageCropHelper::cropToBox($this->png, $wrong, 0, 0, 4, 3, 'jpeg'));
        $this->assertFileDoesNotExist($wrong);
    }

    public function test_datei_mit_bild_endung_und_fremdem_inhalt_wird_mit_coder_nicht_gelesen(): void {
        $this->requireImageMagick();

        // Vektorformat unter Bild-Endung, das auf eine zweite Datei verweist.
        $disguised = $this->dir . '/getarnt.png';
        file_put_contents(
            $disguised,
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="8" height="6">'
            . '<image x="0" y="0" width="8" height="6" xlink:href="' . $this->png . '"/></svg>',
        );
        $output = $this->dir . '/getarnt-aus.png';

        $this->assertNull(ImageCropHelper::getImageDimensions($disguised, 'png'));
        $this->assertFalse(ImageCropHelper::cropToBox($disguised, $output, 0, 0, 4, 3, 'png'));
        $this->assertFalse(ImageCropHelper::cropUpperHalf($disguised, $output, 'png'));
        $this->assertFileDoesNotExist($output);
    }

    public function test_identify_rueckfall_liest_mit_dem_coder(): void {
        $this->requireImageMagick();

        // PPM kennt getimagesize() nicht - die Abmessungen kommen von identify.
        $ppm = $this->dir . '/bild.ppm';
        file_put_contents($ppm, "P3\n2 3\n255\n" . str_repeat("255 0 0\n", 6));

        $expected = ImageCropHelper::getImageDimensions($ppm);
        if ($expected === null) {
            $this->markTestSkipped('ImageMagick identify ist nicht verfuegbar.');
        }

        $this->assertSame(['width' => 2, 'height' => 3], $expected);
        $this->assertSame($expected, ImageCropHelper::getImageDimensions($ppm, 'ppm'));
        $this->assertNull(ImageCropHelper::getImageDimensions($ppm, 'png'), 'Als PNG gelesen ist die Datei kein Bild.');
    }

    private function requireImageMagick(): void {
        if (!ImageCropHelper::isAvailable()) {
            $this->markTestSkipped('ImageMagick (convert) ist nicht verfuegbar.');
        }
    }
}
