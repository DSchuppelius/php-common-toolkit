<?php
/*
 * Created on   : Fri Oct 31 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVDocumentParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Builders\CSVDocumentBuilder;
use CommonToolkit\Entities\Common\CSV\CSVHeaderLine;
use CommonToolkit\Entities\Common\CSV\CSVDataLine;
use CommonToolkit\Helper\Data\StringHelper\CSVStringHelper;
use CommonToolkit\Contracts\Interfaces\Common\CSVLineInterface;
use CommonToolkit\Contracts\Interfaces\Common\CSVFieldInterface;
use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;
use Throwable;

final class CSVDocumentParser {
    use ErrorLog;

    /**
     * Parst eine CSV-Zeichenkette in ein CSVDocument.
     */
    public static function fromString(string $csv, string $delimiter = CSVLineInterface::DEFAULT_DELIMITER, string $enclosure = CSVFieldInterface::DEFAULT_ENCLOSURE, bool $hasHeader = true): object {
        $csv = trim($csv);
        if ($csv === '') {
            throw new RuntimeException('Leere CSV-Zeichenkette');
        }

        $lines = CSVStringHelper::splitCsvByLogicalLine($csv, $enclosure);
        if ($lines === [] || $lines === false) {
            static::logError('CSVDocumentParser::fromString() – keine gültigen Zeilen erkannt');
            throw new RuntimeException('Keine gültigen CSV-Zeilen erkannt');
        }

        $builder = new CSVDocumentBuilder($delimiter, $enclosure);

        try {
            if ($hasHeader) {
                $headerLine = array_shift($lines);
                if ($headerLine === null) {
                    static::logError('Header-Zeile fehlt');
                    throw new RuntimeException('Header-Zeile fehlt');
                } elseif (!CSVStringHelper::canParseCompleteCSVDataLine($headerLine, $delimiter, $enclosure)) {
                    static::logError('Inkonsistente Quote-Struktur erkannt');
                    throw new RuntimeException('Inkonsistente Quote-Struktur erkannt');
                }
                $builder->setHeader(CSVHeaderLine::fromString($headerLine, $delimiter, $enclosure));
            }

            foreach ($lines as $line) {
                if (trim($line) === '') continue;
                elseif (!CSVStringHelper::canParseCompleteCSVDataLine($line, $delimiter, $enclosure)) {
                    static::logError('Inkonsistente Quote-Struktur erkannt');
                    throw new RuntimeException('Inkonsistente Quote-Struktur erkannt');
                }

                $builder->addRow(CSVDataLine::fromString($line, $delimiter, $enclosure));
            }
        } catch (Throwable $e) {
            static::logError("Fehler beim Parsen der CSV: " . $e->getMessage());
            throw new RuntimeException("Fehler beim Parsen der CSV: " . $e->getMessage(), 0, $e);
        }

        return $builder->build();
    }

    /**
     * Parst eine CSV-Datei in ein CSVDocument.
     */
    public static function fromFile(string $file, string $delimiter = CSVLineInterface::DEFAULT_DELIMITER, string $enclosure = CSVFieldInterface::DEFAULT_ENCLOSURE, bool $hasHeader = true): object {
        if (!is_file($file) || !is_readable($file)) {
            static::logError("CSV-Datei nicht lesbar: $file");
            throw new RuntimeException("CSV-Datei nicht lesbar: $file");
        }

        $content = file_get_contents($file);
        if ($content === false) {
            static::logError("Fehler beim Lesen der CSV-Datei: $file");
            throw new RuntimeException("Fehler beim Lesen der CSV-Datei: $file");
        }

        return self::fromString($content, $delimiter, $enclosure, $hasHeader);
    }
}
