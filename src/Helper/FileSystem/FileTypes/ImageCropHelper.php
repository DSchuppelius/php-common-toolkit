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

/**
 * Helper-Klasse für Bild-Zuschnitt (Cropping).
 *
 * Nutzt ImageMagick um Bilder auf definierte Bereiche zuzuschneiden.
 * Typische Anwendung: Versandetiketten aus Bilddateien extrahieren.
 *
 * Koordinatensystem: Ursprung oben links (ImageMagick-Standard).
 * Geometrie-Format: WxH+X+Y (Width x Height + X-Offset + Y-Offset)
 *
 * Dateien aus nicht vertrauenswuerdiger Quelle: Ohne Vorgabe waehlt ImageMagick
 * den Coder aus Inhalt und Endung der Datei - eine Datei mit Bild-Endung kann so
 * als Zeichen-, Skript- oder Dokumentformat gelesen werden. Der optionale
 * Parameter $coder (z. B. "png") nagelt das Format fest: die Eingabe geht als
 * "png:<pfad>" an ImageMagick und wird ausschliesslich als dieses Format gelesen.
 * Der Aufrufer bestimmt den Coder aus dem geprueften Typ der Datei (nicht aus
 * ihrem Namen) und setzt zusaetzlich eine restriktive policy.xml
 * (MAGICK_CONFIGURE_PATH), siehe README.
 */
class ImageCropHelper extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../../../../config/image_executables.json';

    /** Unterstützte Bildformate für Crop-Operationen */
    private const SUPPORTED_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'tif', 'tiff', 'webp'];

    /** Schlichter ImageMagick-Formatname ("png", "JPEG", "tiff64"); alles andere ist kein Coder. */
    private const CODER_PATTERN = '/^[A-Za-z0-9]{1,16}$/';

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
     * @param string|null $coder ImageMagick-Coder der Eingabe ("png" oder "png:"); null = ImageMagick
     *                           waehlt den Coder selbst (bisheriges Verhalten). Ein ungueltiger Coder
     *                           fuehrt zu false, es wird nichts ausgefuehrt.
     * @return bool true bei Erfolg
     */
    public static function cropToBox(
        string $inputPath,
        string $outputPath,
        int $x,
        int $y,
        int $width,
        int $height,
        ?string $coder = null
    ): bool {
        if (!File::exists($inputPath)) {
            self::logError('Bilddatei nicht gefunden', ['path' => $inputPath]);
            return false;
        }

        $input = self::inputArgument($inputPath, $coder);
        if ($input === null) {
            return false;
        }

        if (!self::isAvailable()) {
            self::logError('ImageMagick (convert) ist nicht konfiguriert oder nicht verfügbar');
            return false;
        }

        $geometry = sprintf('%dx%d+%d+%d', $width, $height, $x, $y);

        $command = self::getConfiguredCommand('image-crop', [
            '[INPUT]' => $input,
            '[GEOMETRY]' => $geometry,
            '[OUTPUT]' => $outputPath,
        ]);

        if ($command === null) {
            self::logError('Konnte image-crop Befehl nicht erstellen');
            return false;
        }

        $output = [];
        $returnCode = 0;
        if (!Shell::executeShellCommand($command, $output, $returnCode) || $returnCode !== 0) {
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
     *
     * @param string|null $coder ImageMagick-Coder der Eingabe, siehe {@see cropToBox()}
     */
    public static function cropUpperHalf(string $inputPath, string $outputPath, ?string $coder = null): bool {
        return self::cropUpperPercent($inputPath, $outputPath, 50.0, $coder);
    }

    /**
     * Schneidet die untere Hälfte eines Bildes aus.
     *
     * @param string|null $coder ImageMagick-Coder der Eingabe, siehe {@see cropToBox()}
     */
    public static function cropLowerHalf(string $inputPath, string $outputPath, ?string $coder = null): bool {
        return self::cropLowerPercent($inputPath, $outputPath, 50.0, $coder);
    }

    /**
     * Schneidet den oberen Bereich eines Bildes mit prozentualer Angabe aus.
     *
     * @param string $inputPath Pfad zur Quell-Bilddatei
     * @param string $outputPath Pfad zur Ziel-Bilddatei
     * @param float $percent Prozent der Bildhöhe von oben (z.B. 50.0 = obere Hälfte)
     * @param string|null $coder ImageMagick-Coder der Eingabe, siehe {@see cropToBox()}
     * @return bool true bei Erfolg
     */
    public static function cropUpperPercent(
        string $inputPath,
        string $outputPath,
        float $percent,
        ?string $coder = null
    ): bool {
        $dimensions = self::getImageDimensions($inputPath, $coder);
        if ($dimensions === null) {
            return false;
        }

        $cropHeight = (int) round($dimensions['height'] * ($percent / 100));

        return self::cropToBox($inputPath, $outputPath, 0, 0, $dimensions['width'], $cropHeight, $coder);
    }

    /**
     * Schneidet den unteren Bereich eines Bildes mit prozentualer Angabe aus.
     *
     * @param string $inputPath Pfad zur Quell-Bilddatei
     * @param string $outputPath Pfad zur Ziel-Bilddatei
     * @param float $percent Prozent der Bildhöhe von unten (z.B. 50.0 = untere Hälfte)
     * @param string|null $coder ImageMagick-Coder der Eingabe, siehe {@see cropToBox()}
     * @return bool true bei Erfolg
     */
    public static function cropLowerPercent(
        string $inputPath,
        string $outputPath,
        float $percent,
        ?string $coder = null
    ): bool {
        $dimensions = self::getImageDimensions($inputPath, $coder);
        if ($dimensions === null) {
            return false;
        }

        $cropHeight = (int) round($dimensions['height'] * ($percent / 100));
        $yOffset = $dimensions['height'] - $cropHeight;

        return self::cropToBox($inputPath, $outputPath, 0, $yOffset, $dimensions['width'], $cropHeight, $coder);
    }

    /**
     * Ermittelt die Dimensionen einer Bilddatei.
     *
     * Nutzt PHP's getimagesize() für Standard-Formate.
     * Fällt auf ImageMagick identify zurück wenn nötig.
     *
     * @param string|null $coder ImageMagick-Coder der Eingabe fuer den identify-Rueckfall,
     *                           siehe {@see cropToBox()}; getimagesize() startet kein ImageMagick.
     * @return array{width: int, height: int}|null null bei Fehler
     */
    public static function getImageDimensions(string $inputPath, ?string $coder = null): ?array {
        if (!File::exists($inputPath)) {
            self::logError('Bilddatei nicht gefunden', ['path' => $inputPath]);
            return null;
        }

        $input = self::inputArgument($inputPath, $coder);
        if ($input === null) {
            return null;
        }

        // Standard-Formate: PHP getimagesize() (schnell, kein externes Tool).
        // getimagesize() kennt keinen Coder und erkennt je nach PHP-Version auch
        // Vektorformate (SVG ab 8.5) - mit Coder zaehlt das Ergebnis nur, wenn
        // das erkannte Format zum Coder passt.
        $size = @getimagesize($inputPath);
        if ($size !== false && ($coder === null || self::imageTypeMatchesCoder((int) $size[2], $coder))) {
            return [
                'width' => $size[0],
                'height' => $size[1],
            ];
        }

        // Fallback: ImageMagick identify
        $command = self::getConfiguredCommand('image-identify', [
            '[FORMAT]' => '%w %h',
            '[INPUT]' => $input,
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
     * Eingabe-Argument fuer ImageMagick: mit Coder "<coder>:<pfad>" (ImageMagick liest
     * die Datei dann nur als dieses Format), ohne Coder der Pfad wie uebergeben.
     *
     * @return string|null null bei einem Coder, der kein schlichter Formatname ist
     */
    private static function inputArgument(string $inputPath, ?string $coder): ?string {
        if ($coder === null) {
            return $inputPath;
        }

        $name = trim($coder);
        if (str_ends_with($name, ':')) {
            $name = substr($name, 0, -1);
        }
        if (preg_match(self::CODER_PATTERN, $name) !== 1) {
            self::logError('Ungueltiger ImageMagick-Coder', ['coder' => $coder, 'path' => $inputPath]);
            return null;
        }

        return $name . ':' . $inputPath;
    }

    /**
     * Passt der von getimagesize() erkannte Bildtyp zum Coder? Namensvarianten
     * (jpg/jpeg, tif/tiff) zaehlen als dasselbe Format.
     */
    private static function imageTypeMatchesCoder(int $imageType, string $coder): bool {
        $normalize = static fn (string $name): string => match ($name) {
            'jpg', 'jpe' => 'jpeg',
            'tif' => 'tiff',
            default => $name,
        };

        $detected = image_type_to_extension($imageType, false);
        if ($detected === false) {
            return false;
        }

        return $normalize(strtolower($detected)) === $normalize(strtolower(rtrim(trim($coder), ':')));
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
