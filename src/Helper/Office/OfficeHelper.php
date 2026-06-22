<?php
/*
 * Created on   : Mon Jun 22 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : OfficeHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Office;

use CommonToolkit\Contracts\Abstracts\ConfiguredHelperAbstract;
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Helper\Shell;

/**
 * Dokument-Konvertierung via LibreOffice (headless, --convert-to).
 *
 * Generisch für beliebige von LibreOffice unterstützte Zielformate
 * (z.B. txt→docx/odt, docx→pdf, odt→pdf, …). Das Executable wird über
 * office_executables.json aufgelöst.
 */
final class OfficeHelper extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../../../config/office_executables.json';

    public static function isAvailable(): bool {
        return self::isExecutableAvailable('libreoffice');
    }

    /**
     * Konvertiert eine Datei mit LibreOffice in das Zielformat.
     *
     * Die Ausgabedatei landet im $outputDir mit gleichem Basisnamen und der
     * dem Zielformat entsprechenden Endung (LibreOffice-Verhalten).
     *
     * @param string $inputFile    Pfad zur Eingabedatei
     * @param string $targetFormat LibreOffice-Zielformat (z.B. 'docx', 'odt', 'pdf')
     * @param string $outputDir    Ausgabeverzeichnis
     * @param array $output        Referenz: Shell-Ausgabe (stdout+stderr)
     * @return bool true bei Erfolg (Exit 0)
     */
    public static function convert(string $inputFile, string $targetFormat, string $outputDir, array &$output = [], int &$returnCode = 0): bool {
        $command = self::getConfiguredCommand('libreoffice', [
            '[FORMAT]' => $targetFormat,
            '[OUTPUT_DIR]' => $outputDir,
            '[INPUT]' => $inputFile,
        ]);
        if ($command === null) {
            return self::logErrorAndReturn(false, 'LibreOffice ist nicht verfügbar (office_executables.json).');
        }

        if (!Shell::executeShellCommand($command, $output, $returnCode)) {
            return self::logErrorAndReturn(false, 'LibreOffice-Konvertierung fehlgeschlagen: ' . implode("\n", $output));
        }

        return true;
    }

    /**
     * Konvertiert und liefert den vollständigen Pfad der erzeugten Datei zurück.
     *
     * @return string|null Pfad zur Ausgabedatei oder null bei Fehler
     */
    public static function convertToFile(string $inputFile, string $targetFormat, string $outputDir): ?string {
        if (!self::convert($inputFile, $targetFormat, $outputDir)) {
            return null;
        }

        $basename = pathinfo($inputFile, PATHINFO_FILENAME);
        $outputFile = $outputDir . '/' . $basename . '.' . $targetFormat;

        return File::exists($outputFile) ? $outputFile : null;
    }
}
