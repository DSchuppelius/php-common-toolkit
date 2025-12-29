<?php
/*
 * Created on   : Mon Dec 15 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DatevDocumentParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Entities\Common\CSV\{DataLine, HeaderLine};
use CommonToolkit\Contracts\Abstracts\DATEV\Document;
use CommonToolkit\Entities\DATEV\MetaHeaderLine;
use CommonToolkit\Entities\DATEV\Documents\{
    BookingBatch,
    DebitorsCreditors,
    VariousAddresses,
    GLAccountDescription,
    RecurringBookings,
    PaymentTerms,
    NaturalStack
};
use CommonToolkit\Entities\DATEV\Header\{
    BookingBatchHeaderLine,
    DebitorsCreditorsHeaderLine,
    VariousAddressesHeaderLine,
    GLAccountDescriptionHeaderLine,
    RecurringBookingsHeaderLine,
    PaymentTermsHeaderLine,
    NaturalStackHeaderLine
};
use CommonToolkit\Registries\DATEV\HeaderRegistry;
use CommonToolkit\Contracts\Interfaces\DATEV\MetaHeaderDefinitionInterface;
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Entities\Common\CSV\Document as CSVDocument;
use CommonToolkit\Helper\Data\StringHelper;
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
     * @param string|null $encoding Das Quell-Encoding. Wenn null, wird UTF-8 angenommen.
     * @return Document Das geparste DATEV-Dokument
     * @throws RuntimeException Bei Parsing-Fehlern oder unbekannten Formaten
     */
    public static function fromString(string $csv, string $delimiter = ';', string $enclosure = '"', bool $hasHeader = true, ?string $encoding = null): Document {
        // Encoding-Erkennung und Konvertierung nach UTF-8 für internes Parsing
        $sourceEncoding = $encoding ?? CSVDocument::DEFAULT_ENCODING;
        if ($sourceEncoding !== CSVDocument::DEFAULT_ENCODING) {
            $csv = StringHelper::convertEncoding($csv, $sourceEncoding, CSVDocument::DEFAULT_ENCODING);
        }

        $lines = explode("\n", trim($csv));

        if (count($lines) < 2) {
            static::logError('DATEV-CSV muss mindestens 2 Zeilen haben (MetaHeader + FieldHeader)');
            throw new RuntimeException('DATEV-CSV muss mindestens 2 Zeilen haben');
        }

        // 1. MetaHeader extrahieren
        $metaHeaderLine = self::parseMetaHeader($lines[0], $delimiter, $enclosure);

        // 2. Format-Unterstützung prüfen - automatische Klassenerkennung
        $category = $metaHeaderLine->getFormatkategorie();
        $version = $metaHeaderLine->getVersionsnummer();
        $formatType = $category?->nameValue() ?? 'Unbekannt';
        $isSupported = self::isFormatSupported($category, $version);

        if (!$isSupported) {
            throw new RuntimeException("Format '$formatType' v$version ist noch nicht implementiert");
        }

        // 3. CSV-Inhalt ohne MetaHeader an parent delegieren
        $csvWithoutMetaHeader = implode("\n", array_slice($lines, 1));
        $csvDocument = parent::fromString($csvWithoutMetaHeader, $delimiter, $enclosure, true);

        // 4. Format-spezifische HeaderLine erstellen
        $formatHeader = self::createFormatHeaderLine($metaHeaderLine, $csvDocument->getHeader(), $delimiter, $enclosure);

        // 5. DATEV-spezifisches Document mit MetaHeader und Format-Header erstellen
        return self::createDocument($category, $metaHeaderLine, $formatHeader, $csvDocument->getRows(), $sourceEncoding);
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

        // Format-Informationen über typisierte MetaHeaderLine - konsistent mit fromString!
        $metaHeaderLine = self::createMetaHeaderLineFromDataLine($dataLine, $metaDefinition);
        $version = $metaHeaderLine->getVersionsnummer();
        $category = $metaHeaderLine->getFormatkategorie();

        // Format-Unterstützung automatisch erkennen
        // Use enum name for consistency with test format mappings
        $formatType = $category->name;
        $isSupported = self::isFormatSupported($category, $version);

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
     *
     * @param bool $detectEncoding Automatische Encoding-Erkennung aktivieren
     */
    public static function fromFile(
        string $file,
        string $delimiter = ';',
        string $enclosure = '"',
        bool $hasHeader = true,
        int $startLine = 1,
        ?int $maxLines = null,
        bool $skipEmpty = false,
        bool $detectEncoding = true
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

        // Encoding-Erkennung wenn aktiviert - nutze File::chardet für Datei-basierte Erkennung
        $encoding = null;
        if ($detectEncoding) {
            $encoding = File::chardet($file);
            if ($encoding === false) {
                $encoding = null;
                static::logWarning("Encoding konnte nicht erkannt werden für: $file");
            } else {
                static::logDebug("Erkanntes Encoding für $file: $encoding");
            }
        }

        return self::fromString($content, $delimiter, $enclosure, $hasHeader, $encoding);
    }

    /**
     * Parst einen Bereich einer DATEV-CSV-Datei.
     * Nutzt File Helper für effizienten Bereichs-Zugriff.
     *
     * @param bool $detectEncoding Automatische Encoding-Erkennung aktivieren
     */
    public static function fromFileRange(
        string $file,
        int $fromLine,
        int $toLine,
        string $delimiter = ';',
        string $enclosure = '"',
        bool $includeHeader = true,
        bool $detectEncoding = true
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

        $content = implode("\n", $selectedLines);

        // Encoding-Erkennung wenn aktiviert - nutze File::chardet für Datei-basierte Erkennung
        $encoding = null;
        if ($detectEncoding) {
            $encoding = File::chardet($file);
            if ($encoding === false) {
                $encoding = null;
                static::logWarning("Encoding konnte nicht erkannt werden für: $file");
            }
        }

        return self::fromString($content, $delimiter, $enclosure, $includeHeader, $encoding);
    }

    /**
     * Prüft automatisch, ob ein DATEV-Format unterstützt wird.
     * Basiert auf der Existenz entsprechender Document-Klassen.
     */
    private static function isFormatSupported(?Category $category, int $version): bool {
        if (!$category) {
            return false;
        }

        // Verwende Registry um zu prüfen, ob Version/Kategorie unterstützt wird
        if (!HeaderRegistry::isFormatSupported($category, $version)) {
            return false;
        }

        // Prüfe ob entsprechende Document-Klasse existiert
        $className = self::getDocumentClassName($category);
        return class_exists($className);
    }

    /**
     * Leitet den Klassennamen der entsprechenden Document-Klasse ab.
     */
    private static function getDocumentClassName(Category $category): string {
        $formatName = match ($category) {
            Category::Buchungsstapel => 'BookingBatch',
            Category::DebitorenKreditoren => 'DebitorsCreditors',
            Category::Sachkontenbeschriftungen => 'GLAccountDescription',
            Category::Zahlungsbedingungen => 'PaymentTerms',
            Category::DiverseAdressen => 'VariousAddresses',
            Category::WiederkehrendeBuchungen => 'RecurringBookings',
            Category::NaturalStapel => 'NaturalStack',
        };

        return "CommonToolkit\\Entities\\DATEV\\Documents\\{$formatName}";
    }

    /**
     * Gibt die unterstützten DATEV-Formate dynamisch zurück.
     * Prüft automatisch alle verfügbaren Document-Klassen.
     * 
     * @return array<string, bool> Format-Name => Unterstützt
     */
    public static function getSupportedFormats(): array {
        $formats = [];
        $supportedVersions = HeaderRegistry::getSupportedVersions();

        foreach (Category::cases() as $category) {
            $formatName = $category->nameValue();

            // Prüfe für alle verfügbaren Versionen
            $isSupported = false;
            foreach ($supportedVersions as $version) {
                if (self::isFormatSupported($category, $version)) {
                    $isSupported = true;
                    break;
                }
            }

            $formats[$formatName] = $isSupported;
        }
        return $formats;
    }

    /**
     * Erstellt eine format-spezifische HeaderLine basierend auf MetaHeader-Informationen.
     * 
     * @param MetaHeaderLine $metaHeaderLine Der geparste MetaHeader
     * @param HeaderLine|null $header Der Standard CSV-Header
     * @param string $delimiter CSV-Trennzeichen
     * @param string $enclosure CSV-Textbegrenzer
     * @return HeaderLine Die DATEV format-spezifische HeaderLine
     */
    private static function createFormatHeaderLine(MetaHeaderLine $metaHeaderLine, ?HeaderLine $header, string $delimiter, string $enclosure): HeaderLine {
        if (!$header) {
            throw new RuntimeException('Header fehlt für Format-HeaderLine-Erstellung');
        }

        $category = $metaHeaderLine->getFormatkategorie();
        $version = $metaHeaderLine->getVersionsnummer();

        // Versionsbasierte Header-Definition-Auswahl
        return self::createVersionedHeaderLine($category, $version, $delimiter, $enclosure);
    }

    /**
     * Erstellt versionsabhängige Header-Lines basierend auf Kategorie und Version.
     */
    private static function createVersionedHeaderLine(?Category $category, int $version, string $delimiter, string $enclosure): HeaderLine {
        if ($version === 700) {
            return self::createV700HeaderLine($category, $delimiter, $enclosure);
        }

        throw new RuntimeException("Version {$version} ist noch nicht implementiert");
    }

    /**
     * Erstellt V700-spezifische Header-Lines.
     */
    private static function createV700HeaderLine(?Category $category, string $delimiter, string $enclosure): HeaderLine {
        if (!$category) {
            throw new RuntimeException('Ungültige Kategorie für Header-Erstellung');
        }

        // Verwende Registry für versionsabhängige Header-Definition  
        $definition = HeaderRegistry::getFormatDefinition($category, 700);

        // Erstelle Header-Line basierend auf Kategorie
        return match ($category) {
            Category::Buchungsstapel => new BookingBatchHeaderLine($definition, $delimiter, $enclosure),
            Category::DebitorenKreditoren => new DebitorsCreditorsHeaderLine($definition, $delimiter, $enclosure),
            Category::DiverseAdressen => new VariousAddressesHeaderLine($definition, $delimiter, $enclosure),
            Category::Sachkontenbeschriftungen => new GLAccountDescriptionHeaderLine($definition, $delimiter, $enclosure),
            Category::WiederkehrendeBuchungen => new RecurringBookingsHeaderLine($definition, $delimiter, $enclosure),
            Category::Zahlungsbedingungen => new PaymentTermsHeaderLine($definition, $delimiter, $enclosure),
            Category::NaturalStapel => new NaturalStackHeaderLine($definition, $delimiter, $enclosure),
        };
    }

    /**
     * Erstellt eine MetaHeaderLine aus einer bereits geparsten DataLine mit gegebener Definition.
     * Eliminiert doppeltes CSV-Parsing durch direkte Verwendung der geparsten DataLine.
     *
     * @param DataLine $dataLine Die bereits geparste CSV-Zeile
     * @param MetaHeaderDefinitionInterface $definition Die MetaHeader-Definition
     * @return MetaHeaderLine Die erstellte MetaHeaderLine
     */
    private static function createMetaHeaderLineFromDataLine(
        DataLine $dataLine,
        MetaHeaderDefinitionInterface $definition
    ): MetaHeaderLine {
        // Validate field count against expected MetaHeader structure
        $actualFields = $dataLine->getFields();
        $actualFieldCount = count($actualFields);
        $expectedFields = $definition->getFields();

        if ($actualFieldCount !== $definition->countFields()) {
            static::logError(sprintf('MetaHeader field count mismatch: expected %d fields, got %d fields', $definition->countFields(), $actualFieldCount));
        }

        // Create MetaHeaderLine with definition and populate with parsed values
        $metaHeaderLine = new MetaHeaderLine($definition, $dataLine->getDelimiter(), $dataLine->getEnclosure());

        // Transfer ALL values from parsed fields to structured MetaHeaderLine with quote info
        // Dies überschreibt Default-Werte mit den tatsächlichen Werten aus der Datei
        foreach ($expectedFields as $index => $fieldDef) {
            if (isset($actualFields[$index])) {
                $field = $actualFields[$index];
                $value = $field->getValue();
                try {
                    // Verwende setWithQuoteInfo für ALLE Felder um korrektes Roundtrip zu gewährleisten
                    $metaHeaderLine->setWithQuoteInfo($fieldDef, $value, $field->isQuoted());
                } catch (Exception $e) {
                    // Log parsing errors but continue - parsing robustness is important
                    static::logError("Field {$fieldDef->name} could not be set: " . $e->getMessage());

                    // For critical fields like Formatkategorie, throw exception if validation fails
                    if ($fieldDef->name === 'Formatkategorie') {
                        throw new RuntimeException("Format 'Unbekannt' v{$metaHeaderLine->getVersionsnummer()} ist noch nicht implementiert");
                    }
                }
            }
        }

        return $metaHeaderLine;
    }

    /**
     * Erstellt das korrekte Document-Objekt basierend auf der DATEV-Kategorie.
     *
     * @param Category|null $category Die DATEV-Kategorie
     * @param MetaHeaderLine $metaHeader Der MetaHeader
     * @param HeaderLine $header Der Format-spezifische Header
     * @param array $rows Die Datenzeilen
     * @param string $encoding Das Encoding des Dokuments
     * @return Document Das korrekte Document-Objekt
     * @throws RuntimeException Wenn die Kategorie nicht unterstützt wird
     */
    private static function createDocument(?Category $category, MetaHeaderLine $metaHeader, HeaderLine $header, array $rows, string $encoding = CSVDocument::DEFAULT_ENCODING): Document {
        return match ($category) {
            Category::Buchungsstapel => new BookingBatch($metaHeader, $header, $rows, null, $encoding),
            Category::DebitorenKreditoren => new DebitorsCreditors($metaHeader, $header, $rows, null, $encoding),
            Category::DiverseAdressen => new VariousAddresses($metaHeader, $header, $rows, null, $encoding),
            Category::Sachkontenbeschriftungen => new GLAccountDescription($metaHeader, $header, $rows, null, $encoding),
            Category::WiederkehrendeBuchungen => new RecurringBookings($metaHeader, $header, $rows, null, $encoding),
            Category::Zahlungsbedingungen => new PaymentTerms($metaHeader, $header, $rows, null, $encoding),
            Category::NaturalStapel => new NaturalStack($metaHeader, $header, $rows, null, $encoding),
            default => throw new RuntimeException(sprintf('Unsupported DATEV category: %s', $category?->nameValue() ?? 'null')),
        };
    }
}
