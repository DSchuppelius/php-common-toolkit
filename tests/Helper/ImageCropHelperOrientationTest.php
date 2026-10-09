<?php
/*
 * Created on   : Fri Oct 09 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ImageCropHelperOrientationTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\FileSystem\FileTypes\ImageCropHelper;
use Tests\Contracts\BaseTestCase;

/**
 * Zuschnitt in Anzeigeausrichtung: Ein Handyfoto liegt quer in der Datei und
 * traegt die Drehung nur als EXIF-Orientierung. Gezaehlt wird im Bild, wie es
 * angezeigt wird.
 *
 * Probe: 80x40 Rohpixel, linke Haelfte rot, rechte blau, Orientierung 6
 * (um 90 Grad im Uhrzeigersinn anzeigen) - angezeigt 40x80, Rot oben.
 */
class ImageCropHelperOrientationTest extends BaseTestCase {
    private string $dir;

    protected function setUp(): void {
        parent::setUp();

        if (!function_exists('imagecreatetruecolor') || !ImageCropHelper::isAvailable()) {
            $this->markTestSkipped('GD oder ImageMagick fehlt');
        }

        $this->dir = sys_get_temp_dir() . '/ctk-crop-orient-' . bin2hex(random_bytes(4));
        mkdir($this->dir, 0700, true);
    }

    protected function tearDown(): void {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);
        parent::tearDown();
    }

    public function test_masse_in_anzeigeausrichtung(): void {
        $this->assertSame(['width' => 40, 'height' => 80], ImageCropHelper::getImageDimensions($this->photo(6), 'jpeg'));
        $this->assertSame(['width' => 80, 'height' => 40], ImageCropHelper::getImageDimensions($this->photo(3), 'jpeg'));
        $this->assertSame(['width' => 80, 'height' => 40], ImageCropHelper::getImageDimensions($this->photo(null), 'jpeg'));
    }

    public function test_obere_haelfte_ist_die_angezeigte(): void {
        $output = $this->dir . '/oben.jpg';

        $this->assertTrue(ImageCropHelper::cropUpperHalf($this->photo(6), $output, 'jpeg'));

        // Das Ergebnis ist aufgerichtet: 40x40, rot, ohne Drehung
        $this->assertSame(['width' => 40, 'height' => 40], ImageCropHelper::getImageDimensions($output, 'jpeg'));
        $this->assertColor($output, 'red');
    }

    public function test_untere_haelfte_ist_die_angezeigte(): void {
        $output = $this->dir . '/unten.jpg';

        $this->assertTrue(ImageCropHelper::cropLowerHalf($this->photo(6), $output, 'jpeg'));

        $this->assertColor($output, 'blue');
    }

    public function test_rahmen_zaehlt_im_angezeigten_bild(): void {
        $output = $this->dir . '/rahmen.jpg';

        // Angezeigt 40 breit, 80 hoch: unterste 20 Zeilen
        $this->assertTrue(ImageCropHelper::cropToBox($this->photo(6), $output, 0, 60, 40, 20, 'jpeg'));

        $this->assertSame(['width' => 40, 'height' => 20], ImageCropHelper::getImageDimensions($output, 'jpeg'));
        $this->assertColor($output, 'blue');
    }

    public function test_ohne_drehung_bleibt_alles_wie_bisher(): void {
        $output = $this->dir . '/links.jpg';

        $this->assertTrue(ImageCropHelper::cropToBox($this->photo(null), $output, 0, 0, 40, 40, 'jpeg'));

        $this->assertColor($output, 'red');
    }

    /**
     * Rohbild 80x40 (links rot, rechts blau), optional mit EXIF-Orientierung.
     */
    private function photo(?int $orientation): string {
        $image = imagecreatetruecolor(80, 40);
        imagefilledrectangle($image, 0, 0, 39, 39, (int) imagecolorallocate($image, 255, 0, 0));
        imagefilledrectangle($image, 40, 0, 79, 39, (int) imagecolorallocate($image, 0, 0, 255));
        ob_start();
        imagejpeg($image, null, 100);
        $jpeg = (string) ob_get_clean();

        if ($orientation !== null) {
            // Minimales EXIF-APP1 direkt hinter SOI: TIFF-Kopf, ein Eintrag Orientation
            $tiff = 'MM' . pack('n', 42) . pack('N', 8) . pack('n', 1)
                . pack('nnNnn', 0x0112, 3, 1, $orientation, 0) . pack('N', 0);
            $payload = "Exif\0\0" . $tiff;
            $jpeg = substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($payload) + 2) . $payload . substr($jpeg, 2);
        }

        $path = $this->dir . '/foto-' . ($orientation ?? 0) . '.jpg';
        file_put_contents($path, $jpeg);

        return $path;
    }

    private function assertColor(string $path, string $expected): void {
        $image = imagecreatefromjpeg($path);
        $this->assertNotFalse($image);
        $rgb = imagecolorat($image, intdiv(imagesx($image), 2), intdiv(imagesy($image), 2));
        $red = ($rgb >> 16) & 0xFF;
        $blue = $rgb & 0xFF;

        if ($expected === 'red') {
            $this->assertGreaterThan(200, $red, "Mitte von $path ist nicht rot");
            $this->assertLessThan(60, $blue, "Mitte von $path ist nicht rot");
        } else {
            $this->assertGreaterThan(200, $blue, "Mitte von $path ist nicht blau");
            $this->assertLessThan(60, $red, "Mitte von $path ist nicht blau");
        }
    }
}
