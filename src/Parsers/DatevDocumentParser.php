<?php
/*
 * Created on   : Sun Dec 15 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DatevDocumentParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Entities\Common\CSV\{DataLine, HeaderLine};
use CommonToolkit\Entities\DATEV\{Document, MetaHeaderLine};
use CommonToolkit\Entities\DATEV\Header\BookingHeaderLine;
use CommonToolkit\Entities\DATEV\Header\V700\BookingHeaderDefinition;
use CommonToolkit\Registries\DATEV\HeaderRegistry;
use CommonToolkit\Contracts\Interfaces\DATEV\MetaHeaderInterface;
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;
use CommonToolkit\Enums\DATEV\V700\MetaHeaderField;
use CommonToolkit\Helper\FileSystem\File;
use Exception;
use RuntimeException;

/**
 * Parser für DATEV-CSV-Dokumente.
 * Erkennt automatisch Typ und Version der DATEV-Datei und erweitert den CSVDocumentParser.
 */
class DatevDocumentParser extends CSVDocumentParser {
    /**
     * Parst eine DATEV-CSV-Datei aus einem String.
     *
     * @param string $csv Der CSV-Inhalt
     * @param string $delimiter CSV-Trennzeichen (Standard: Semikolon)
     * @param string $enclosure CSV-Textbegrenzer (Standard: Anführungszeichen)
     * @param bool $hasHeader Ob ein Header vorhanden ist (bei DATEV immer true)
     * @return Document Das geparste DATEV-Dokument
     * @throws RuntimeException Bei Parsing-Fehlern oder unbekannten Formaten
     */
    public static function fromString(string $csv, string $delimiter = ';', string $enclosure = '"', bool $hasHeader = true): Document {
        $lines = explode("\n", trim($csv));

        if (count($lines) < 2) {
            static::logError('DATEV-CSV muss mindestens 2 Zeilen haben (MetaHeader + FieldHeader)');
            throw new RuntimeException('DATEV-CSV muss mindestens 2 Zeilen haben');
        }

        // 1. MetaHeader extrahieren
        $metaHeaderLine = self::parseMetaHeader($lines[0], $delimiter, $enclosure);

        // 2. Format-Unterstützung prüfen - typisierte Getter ohne Casts nutzen
        $categoryNumber = $metaHeaderLine->getFormatkategorie();
        $version = $metaHeaderLine->getVersionsnummer();
        $categoryEnum = Category::tryFrom($categoryNumber);
        $formatType = $categoryEnum?->nameValue() ?? 'Unbekannt';
        $isSupported = $formatType === 'Buchungsstapel' && $version === 700;

        if (!$isSupported) {
            throw new RuntimeException("Format '$formatType' v$version ist noch nicht implementiert");
        }

        // 3. CSV-Inhalt ohne MetaHeader an parent delegieren
        $csvWithoutMetaHeader = implode("\n", array_slice($lines, 1));
        $csvDocument = parent::fromString($csvWithoutMetaHeader, $delimiter, $enclosure, true);

        // 4. Für Buchungsstapel: BookingHeaderLine erstellen
        $bookingHeader = self::createBookingHeaderLine($csvDocument->getHeader(), $delimiter, $enclosure);

        // 5. DATEV-spezifisches Document mit MetaHeader und BookingHeader erstellen
        return new Document($metaHeaderLine, $bookingHeader, $csvDocument->getRows());
    }

    /**
     * Parst den DATEV MetaHeader (erste Zeile).
     *
     * @param string $metaHeaderLine Die MetaHeader-Zeile
     * @param string $delimiter CSV-Trennzeichen
     * @param string $enclosure CSV-Textbegrenzer
     * @return MetaHeaderLine Die geparste MetaHeaderLine
     * @throws RuntimeException Bei ungültigem MetaHeader
     */
    private static function parseMetaHeader(string $metaHeaderLine, string $delimiter, string $enclosure): MetaHeaderLine {
        // DataLine für CSV-Parsing nutzen - nur einmal!
        $dataLine = DataLine::fromString($metaHeaderLine, $delimiter, $enclosure);

        if (count($dataLine->getFields()) < 4) {
            static::logError('Ungültiger DATEV MetaHeader: MetaHeader muss mindestens 4 Felder haben');
            throw new RuntimeException('Ungültiger DATEV MetaHeader');
        }

        // HeaderRegistry direkt mit DataLine - keine raw value extraction nötig!
        $metaDefinition = HeaderRegistry::detectFromDataLine($dataLine);
        if (!$metaDefinition) {
            static::logError('Ungültiger DATEV MetaHeader: Ungültige DATEV-Version erkannt');
            throw new RuntimeException('Ungültiger DATEV MetaHeader');
        }

        return self::createMetaHeaderLineFromDataLine($dataLine, $metaDefinition);
    }

    /**
     * Analysiert eine DATEV-CSV-Datei und gibt Format-Informationen zurück.
     * Nutzt die HeaderRegistry für direkte und effiziente Format-Erkennung.
     */
    public static function analyzeFormat(string $csvContent, string $delimiter = ';', string $enclosure = '"'): array {
        $lines = explode("\n", trim($csvContent));

        if (empty($lines)) {
            return ['error' => 'Leere Datei'];
        }

        // LineAbstract CSV-Parsing für konsistente Feldextraktion nutzen
        $dataLine = DataLine::fromString($lines[0], $delimiter, $enclosure);

        if (count($dataLine->getFields()) < 4) {
            return ['error' => 'Ungültiger DATEV MetaHeader: zu wenige Felder'];
        }

        // HeaderRegistry direkt mit DataLine - konsistent mit parseMetaHeader!
        $metaDefinition = HeaderRegistry::detectFromDataLine($dataLine);

        if ($metaDefinition === null) {
            $versionField = $dataLine->getFields()[1] ?? null;
            return [
                'format_type' => null,
                'version' => $versionField ? (int)$versionField->getValue() : 0,
                'supported' => false,
                'line_count' => count($lines),
                'error' => 'Unbekanntes oder ungültiges DATEV-Format'
            ];
        }

        // Format-Informationen direkt aus DataLine - keine raw value extraction nötig!
        $version = $metaDefinition->getVersion();
        $categoryField = $dataLine->getFields()[2] ?? null;
        $categoryNumber = $categoryField ? (int)$categoryField->getValue() : 0;
        $categoryEnum = Category::tryFrom($categoryNumber);

        // Nur Buchungsstapel ist aktuell unterstützt
        $formatType = $categoryEnum?->nameValue() ?? 'Unbekannt';
        $isSupported = $formatType === 'Buchungsstapel' && $version === 700;

        return [
            'format_type' => $formatType,
            'version' => $version,
            'supported' => $isSupported,
            'line_count' => count($lines),
            'format_info' => $metaDefinition
        ];
    }

    /**
     * Parst eine DATEV-CSV-Datei aus einer Datei.
     * Nutzt File Helper für effizienten und sicheren Dateizugriff.
     */
    public static function fromFile(
        string $file,
        string $delimiter = ';',
        string $enclosure = '"',
        bool $hasHeader = true,
        int $startLine = 1,
        ?int $maxLines = null,
        bool $skipEmpty = false
    ): Document {
        // File Helper für Validierung und Zugriff nutzen
        if (!File::isReadable($file)) {
            static::logError("DATEV-Datei nicht lesbar: $file");
            throw new RuntimeException("DATEV-Datei nicht lesbar: $file");
        }

        // File Helper für effizienten Zeilen-basierten Zugriff
        $lines = File::readLinesAsArray($file, $skipEmpty, $maxLines, $startLine);

        if (empty($lines)) {
            static::logError("Keine Zeilen in DATEV-Datei gefunden: $file");
            throw new RuntimeException("Keine Zeilen in DATEV-Datei gefunden: $file");
        }

        $content = implode("\n", $lines);
        return self::fromString($content, $delimiter, $enclosure);
    }

    /**
     * Parst einen Bereich einer DATEV-CSV-Datei.
     * Nutzt File Helper für effizienten Bereichs-Zugriff.
     */
    public static function fromFileRange(
        string $file,
        int $fromLine,
        int $toLine,
        string $delimiter = ';',
        string $enclosure = '"',
        bool $includeHeader = true
    ): Document {
        if ($fromLine < 3) {
            throw new RuntimeException("DATEV-Dateien benötigen MetaHeader (Zeile 1) und FieldHeader (Zeile 2). Startzeile muss >= 3 sein.");
        }

        // File Helper für Validierung nutzen
        if (!File::isReadable($file)) {
            static::logError("DATEV-Datei nicht lesbar: $file");
            throw new RuntimeException("DATEV-Datei nicht lesbar: $file");
        }

        // MetaHeader und FieldHeader lesen (Zeilen 1-2)
        $headerLines = File::readLinesAsArray($file, false, 2, 1);

        // Datenbereich lesen
        $maxLines = $toLine - $fromLine + 1;
        $dataLines = File::readLinesAsArray($file, false, $maxLines, $fromLine);

        $selectedLines = array_merge($headerLines, $dataLines);

        if (empty($selectedLines)) {
            static::logError("Keine Zeilen im angegebenen Bereich gefunden: $file (Zeilen $fromLine-$toLine)");
            throw new RuntimeException("Keine Zeilen im angegebenen Bereich gefunden");
        }

        return self::fromString(implode("\n", $selectedLines), $delimiter, $enclosure);
    }

    /**
     * Gibt die unterstützten DATEV-Formate zurück.
     * 
     * @return array<string, bool> Format-Name => Unterstützt
     */
    public static function getSupportedFormats(): array {
        return [
            'Buchungsstapel' => true,
            'Debitoren/Kreditoren' => false,
            'Kontenbeschriftungen' => false,
            'Wiederkehrende Buchungen' => false,
            'Zahlungsbedingungen' => false,
            'Diverse Adressen' => false,
            'Natural-Stapel' => false,
        ];
    }

    /**
     * Erstellt eine BookingHeaderLine für Buchungsstapel.
     * 
     * @param HeaderLine|null $header Der Standard CSV-Header
     * @param string $delimiter CSV-Trennzeichen
     * @param string $enclosure CSV-Textbegrenzer
     * @return BookingHeaderLine Die DATEV BookingHeaderLine
     */
    private static function createBookingHeaderLine(?HeaderLine $header, string $delimiter, string $enclosure): BookingHeaderLine {
        if (!$header) {
            throw new RuntimeException('Header fehlt für BookingHeaderLine-Erstellung');
        }

        // BookingHeaderDefinition für V700 Buchungsstapel
        $definition = new BookingHeaderDefinition();

        return new BookingHeaderLine($definition, $delimiter, $enclosure);
    }

    /**
     * Erstellt eine MetaHeaderLine aus einer bereits geparsten DataLine mit gegebener Definition.
     * Eliminiert doppeltes CSV-Parsing durch direkte Verwendung der geparsten DataLine.
     *
     * @param DataLine $dataLine Die bereits geparste CSV-Zeile
     * @param MetaHeaderInterface $definition Die MetaHeader-Definition
     * @return MetaHeaderLine Die erstellte MetaHeaderLine
     */
    private static function createMetaHeaderLineFromDataLine(
        DataLine $dataLine,
        MetaHeaderInterface $definition
    ): MetaHeaderLine {
        // Feldwerte direkt aus bereits geparster DataLine extrahieren - kein doppeltes Parsing!
        $rawFieldValues = [];
        foreach ($dataLine->getFields() as $field) {
            $rawFieldValues[] = $field->getValue();
        }

        // Validate field count against expected MetaHeader structure
        $expectedFields = $definition->getFields();
        $expectedFieldCount = count($expectedFields);
        $actualFieldCount = count($rawFieldValues);

        if ($actualFieldCount !== $expectedFieldCount) {
            static::logError(sprintf('MetaHeader field count mismatch: expected %d fields, got %d fields', $expectedFieldCount, $actualFieldCount));
        }

        // Create MetaHeaderLine with definition and populate with parsed values
        $metaHeaderLine = new MetaHeaderLine($definition, $dataLine->getDelimiter(), $dataLine->getEnclosure());

        // Transfer values from parsed fields to structured MetaHeaderLine
        foreach ($expectedFields as $index => $fieldDef) {
            if (isset($rawFieldValues[$index]) && $rawFieldValues[$index] !== '') {
                try {
                    $metaHeaderLine->set($fieldDef, $rawFieldValues[$index]);
                } catch (Exception $e) {
                    // Log parsing errors but continue - parsing robustness is important
                    static::logError("Field {$fieldDef->name} could not be set: " . $e->getMessage());
                }
            }
        }

        return $metaHeaderLine;
    }
}