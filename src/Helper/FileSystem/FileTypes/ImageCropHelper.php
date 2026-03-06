<?php
/*
 * Created on   : Thu Jul 17 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ImageCropHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\FileSystem\FileTypes;

use CommonToolkit\Contracts\Abstracts\ConfiguredHelperAbstract;
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Helper\Shell;
use RuntimeException;

/**
 * Helper-Klasse für Bild-Zuschnitt (Cropping).
 * 
 * Nutzt ImageMagick um Bilder auf definierte Bereiche zuzuschneiden.
 * Typische Anwendung: Versandetiketten aus Bilddateien extrahieren.
 * 
 * Koordinatensystem: Ursprung oben links (ImageMagick-Standard).
 * Geometrie-Format: WxH+X+Y (Width x Height + X-Offset + Y-Offset)
 */
class ImageCropHelper extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../../../../config/image_executables.json';

    /** Unterstützte Bildformate für Crop-Operationen */
    private const SUPPORTED_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'tif', 'tiff', 'webp'];

    /**
     * Schneidet ein Bild auf einen definierten Bereich zu.
     * 
     * Koordinatenursprung ist oben links (ImageMagick-Standard).
     * 
     * @param string $inputPath Pfad zur Quell-Bilddatei
     * @param string $outputPath Pfad zur Ziel-Bilddatei
     * @param int $x Linke Kante in Pixeln
     * @param int $y Obere Kante in Pixeln
     * @param int $width Breite in Pixeln
     * @param int $height Höhe in Pixeln
     * @return bool true bei Erfolg
     */
    public static function cropToBox(
        string $inputPath,
        string $outputPath,
        int $x,
        int $y,
        int $width,
        int $height
    ): bool {
        if (!File::exists($inputPath)) {
            self::logError('Bilddatei nicht gefunden', ['path' => $inputPath]);
            return false;
        }

        if (!self::isAvailable()) {
            self::logError('ImageMagick (convert) ist nicht konfiguriert oder nicht verfügbar');
            return false;
        }

        $geometry = sprintf('%dx%d+%d+%d', $width, $height, $x, $y);

        $command = self::getConfiguredCommand('image-crop', [
            '[INPUT]' => $inputPath,
            '[GEOMETRY]' => $geometry,
            '[OUTPUT]' => $outputPath,
        ]);

        if ($command === null) {
            self::logError('Konnte image-crop Befehl nicht erstellen');
            return false;
        }

        $output = [];
        $returnCode = 0;
        if (!Shell::executeShellCommand($command . ' 2>&1', $output, $returnCode) || $returnCode !== 0) {
            self::logError('ImageMagick-Cropping fehlgeschlagen', [
                'returnCode' => $returnCode,
                'output' => implode("\n", $output),
            ]);
            return false;
        }

        if (!File::exists($outputPath)) {
            self::logError('Zugeschnittenes Bild wurde nicht erstellt', ['path' => $outputPath]);
            return false;
        }

        self::logInfo('Bild erfolgreich zugeschnitten', [
            'input' => $inputPath,
            'output' => $outputPath,
            'geometry' => $geometry,
        ]);

        return true;
    }

    /**
     * Schneidet die obere Hälfte eines Bildes aus.
     */
    public static function cropUpperHalf(string $inputPath, string $outputPath): bool {
        return self::cropUpperPercent($inputPath, $outputPath, 50.0);
    }

    /**
     * Schneidet die untere Hälfte eines Bildes aus.
     */
    public static function cropLowerHalf(string $inputPath, string $outputPath): bool {
        return self::cropLowerPercent($inputPath, $outputPath, 50.0);
    }

    /**
     * Schneidet den oberen Bereich eines Bildes mit prozentualer Angabe aus.
     * 
     * @param string $inputPath Pfad zur Quell-Bilddatei
     * @param string $outputPath Pfad zur Ziel-Bilddatei
     * @param float $percent Prozent der Bildhöhe von oben (z.B. 50.0 = obere Hälfte)
     * @return bool true bei Erfolg
     */
    public static function cropUpperPercent(
        string $inputPath,
        string $outputPath,
        float $percent
    ): bool {
        $dimensions = self::getImageDimensions($inputPath);
        if ($dimensions === null) {
            return false;
        }

        $cropHeight = (int) round($dimensions['height'] * ($percent / 100));

        return self::cropToBox($inputPath, $outputPath, 0, 0, $dimensions['width'], $cropHeight);
    }

    /**
     * Schneidet den unteren Bereich eines Bildes mit prozentualer Angabe aus.
     * 
     * @param string $inputPath Pfad zur Quell-Bilddatei
     * @param string $outputPath Pfad zur Ziel-Bilddatei
     * @param float $percent Prozent der Bildhöhe von unten (z.B. 50.0 = untere Hälfte)
     * @return bool true bei Erfolg
     */
    public static function cropLowerPercent(
        string $inputPath,
        string $outputPath,
        float $percent
    ): bool {
        $dimensions = self::getImageDimensions($inputPath);
        if ($dimensions === null) {
            return false;
        }

        $cropHeight = (int) round($dimensions['height'] * ($percent / 100));
        $yOffset = $dimensions['height'] - $cropHeight;

        return self::cropToBox($inputPath, $outputPath, 0, $yOffset, $dimensions['width'], $cropHeight);
    }

    /**
     * Ermittelt die Dimensionen einer Bilddatei.
     * 
     * Nutzt PHP's getimagesize() für Standard-Formate.
     * Fällt auf ImageMagick identify zurück wenn nötig.
     * 
     * @return array{width: int, height: int}|null null bei Fehler
     */
    public static function getImageDimensions(string $inputPath): ?array {
        if (!File::exists($inputPath)) {
            self::logError('Bilddatei nicht gefunden', ['path' => $inputPath]);
            return null;
        }

        // Standard-Formate: PHP getimagesize() (schnell, kein externes Tool)
        $size = @getimagesize($inputPath);
        if ($size !== false) {
            return [
                'width' => $size[0],
                'height' => $size[1],
            ];
        }

        // Fallback: ImageMagick identify
        $command = self::getConfiguredCommand('image-identify', [
            '[FORMAT]' => '%w %h',
            '[INPUT]' => $inputPath,
        ]);

        if ($command !== null) {
            $output = [];
            $returnCode = 0;
            if (Shell::executeShellCommand($command . ' 2>/dev/null', $output, $returnCode) && !empty($output)) {
                $parts = explode(' ', trim($output[0]));
                if (count($parts) === 2) {
                    return [
                        'width' => (int) $parts[0],
                        'height' => (int) $parts[1],
                    ];
                }
            }
        }

        self::logError('Konnte Bilddimensionen nicht ermitteln', ['path' => $inputPath]);
        return null;
    }

    /**
     * Prüft ob ImageMagick für Cropping verfügbar ist.
     */
    public static function isAvailable(): bool {
        return self::isExecutableAvailable('image-crop');
    }

    /**
     * Prüft ob die Dateiendung ein unterstütztes Bildformat ist.
     */
    public static function isSupportedFormat(string $filePath): bool {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($extension, self::SUPPORTED_EXTENSIONS, true);
    }
}
