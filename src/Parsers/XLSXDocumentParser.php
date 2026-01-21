<?php
/*
 * Created on   : Wed Jan 22 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XLSXDocumentParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Entities\XLSX\Cell;
use CommonToolkit\Entities\XLSX\Document;
use CommonToolkit\Entities\XLSX\Row;
use CommonToolkit\Entities\XLSX\Sheet;
use CommonToolkit\Helper\FileSystem\File;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/**
 * Parser für XLSX-Dateien (Office Open XML Format).
 * Analog zu CSVDocumentParser.
 */
class XLSXDocumentParser extends HelperAbstract {
    /** @var array<int, string> Shared Strings Cache */
    protected array $sharedStrings = [];

    /** @var array<int, string> Number Formats Cache */
    protected array $numberFormats = [];

    /** @var array<int, int> Cell Styles -> Number Format Mapping */
    protected array $cellStyles = [];

    /**
     * Parst eine XLSX-Datei in ein XLSX-Document.
     *
     * @param string    $file      Der Pfad zur XLSX-Datei
     * @param bool      $hasHeader Ob die erste Zeile als Header interpretiert werden soll
     * @param int|null  $sheetIndex Nur ein bestimmtes Sheet laden (0-basiert), null = alle
     * @return Document Das geparste XLSX-Dokument
     * @throws RuntimeException Bei Dateizugriffs- oder Parsing-Fehlern
     */
    public static function fromFile(string $file, bool $hasHeader = true, ?int $sheetIndex = null): Document {
        $file = File::resolveFile($file);

        $parser = new self();
        return $parser->parse($file, $hasHeader, $sheetIndex);
    }

    /**
     * Interne Parse-Methode.
     */
    protected function parse(string $file, bool $hasHeader, ?int $sheetIndex): Document {
        $zip = new ZipArchive();

        if ($zip->open($file) !== true) {
            self::logErrorAndThrow(RuntimeException::class, "Kann XLSX-Datei nicht öffnen: $file");
        }

        try {
            // Shared Strings laden
            $this->loadSharedStrings($zip);

            // Styles laden (für Datumsformate)
            $this->loadStyles($zip);

            // Metadaten laden
            $metadata = $this->loadMetadata($zip);

            // Workbook laden um Sheet-Namen zu bekommen
            $sheetInfos = $this->loadWorkbook($zip);

            // Sheets parsen
            $sheets = [];
            foreach ($sheetInfos as $idx => $info) {
                if ($sheetIndex !== null && $idx !== $sheetIndex) {
                    continue;
                }

                $sheet = $this->parseSheet($zip, $info['path'], $info['name'], $hasHeader, $idx);
                if ($sheet !== null) {
                    $sheets[] = $sheet;
                }
            }

            return new Document(
                $sheets,
                $metadata['creator'] ?? null,
                $metadata['title'] ?? null,
                $metadata['description'] ?? null,
                $metadata['created'] ?? null,
                $metadata['modified'] ?? null
            );
        } finally {
            $zip->close();
        }
    }

    /**
     * Lädt die Shared Strings aus dem XLSX-Archiv.
     */
    protected function loadSharedStrings(ZipArchive $zip): void {
        $this->sharedStrings = [];

        $content = $zip->getFromName('xl/sharedStrings.xml');
        if ($content === false) {
            return; // Keine Shared Strings vorhanden
        }

        $dom = new DOMDocument();
        $dom->loadXML($content);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $siNodes = $xpath->query('//s:si');
        if ($siNodes === false) {
            return;
        }

        foreach ($siNodes as $si) {
            $text = '';
            // Entweder <t> direkt oder <r><t> für Rich Text
            $tNodes = $xpath->query('.//s:t', $si);
            if ($tNodes !== false) {
                foreach ($tNodes as $t) {
                    $text .= $t->textContent;
                }
            }
            $this->sharedStrings[] = $text;
        }
    }

    /**
     * Lädt die Styles (für Datumsformate) aus dem XLSX-Archiv.
     */
    protected function loadStyles(ZipArchive $zip): void {
        $this->numberFormats = [];
        $this->cellStyles = [];

        // Standard-Datumsformate nach ECMA-376
        $builtInFormats = [
            14 => 'mm-dd-yy',
            15 => 'd-mmm-yy',
            16 => 'd-mmm',
            17 => 'mmm-yy',
            18 => 'h:mm AM/PM',
            19 => 'h:mm:ss AM/PM',
            20 => 'h:mm',
            21 => 'h:mm:ss',
            22 => 'm/d/yy h:mm',
            45 => 'mm:ss',
            46 => '[h]:mm:ss',
            47 => 'mmss.0',
        ];
        $this->numberFormats = $builtInFormats;

        $content = $zip->getFromName('xl/styles.xml');
        if ($content === false) {
            return;
        }

        $dom = new DOMDocument();
        $dom->loadXML($content);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        // Custom Number Formats
        $numFmts = $xpath->query('//s:numFmts/s:numFmt');
        if ($numFmts !== false) {
            foreach ($numFmts as $numFmt) {
                if ($numFmt instanceof DOMElement) {
                    $id = (int) $numFmt->getAttribute('numFmtId');
                    $code = $numFmt->getAttribute('formatCode');
                    $this->numberFormats[$id] = $code;
                }
            }
        }

        // Cell XFs (Zellformate)
        $cellXfs = $xpath->query('//s:cellXfs/s:xf');
        if ($cellXfs !== false) {
            $idx = 0;
            foreach ($cellXfs as $xf) {
                if ($xf instanceof DOMElement) {
                    $numFmtId = (int) $xf->getAttribute('numFmtId');
                    $this->cellStyles[$idx] = $numFmtId;
                }
                $idx++;
            }
        }
    }

    /**
     * Lädt die Metadaten aus dem XLSX-Archiv.
     *
     * @return array{creator: ?string, title: ?string, description: ?string, created: ?DateTimeImmutable, modified: ?DateTimeImmutable}
     */
    protected function loadMetadata(ZipArchive $zip): array {
        $metadata = [
            'creator' => null,
            'title' => null,
            'description' => null,
            'created' => null,
            'modified' => null,
        ];

        // Core Properties
        $content = $zip->getFromName('docProps/core.xml');
        if ($content !== false) {
            $dom = new DOMDocument();
            $dom->loadXML($content);

            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');
            $xpath->registerNamespace('cp', 'http://schemas.openxmlformats.org/package/2006/metadata/core-properties');
            $xpath->registerNamespace('dcterms', 'http://purl.org/dc/terms/');

            $metadata['creator'] = $this->getXPathValue($xpath, '//dc:creator');
            $metadata['title'] = $this->getXPathValue($xpath, '//dc:title');
            $metadata['description'] = $this->getXPathValue($xpath, '//dc:description');

            $created = $this->getXPathValue($xpath, '//dcterms:created');
            if ($created !== null) {
                try {
                    $metadata['created'] = new DateTimeImmutable($created);
                } catch (\Exception) {
                    // Ignorieren
                }
            }

            $modified = $this->getXPathValue($xpath, '//dcterms:modified');
            if ($modified !== null) {
                try {
                    $metadata['modified'] = new DateTimeImmutable($modified);
                } catch (\Exception) {
                    // Ignorieren
                }
            }
        }

        return $metadata;
    }

    /**
     * Lädt die Workbook-Informationen (Sheet-Namen und Pfade).
     *
     * @return array<int, array{name: string, path: string}>
     */
    protected function loadWorkbook(ZipArchive $zip): array {
        $sheets = [];

        $content = $zip->getFromName('xl/workbook.xml');
        if ($content === false) {
            self::logErrorAndThrow(RuntimeException::class, 'Workbook.xml nicht gefunden');
        }

        $dom = new DOMDocument();
        $dom->loadXML($content);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $sheetNodes = $xpath->query('//s:sheets/s:sheet');
        if ($sheetNodes === false) {
            return [];
        }

        // Relationships laden für Sheet-Pfade
        $rels = $this->loadRelationships($zip, 'xl/_rels/workbook.xml.rels');

        foreach ($sheetNodes as $idx => $node) {
            if ($node instanceof DOMElement) {
                $name = $node->getAttribute('name');
                $rId = $node->getAttribute('r:id');

                $path = $rels[$rId] ?? "xl/worksheets/sheet" . ($idx + 1) . ".xml";
                if (!str_starts_with($path, 'xl/')) {
                    $path = 'xl/' . $path;
                }

                $sheets[] = [
                    'name' => $name,
                    'path' => $path,
                ];
            }
        }

        return $sheets;
    }

    /**
     * Lädt die Relationships aus einer .rels-Datei.
     *
     * @return array<string, string> rId => Target
     */
    protected function loadRelationships(ZipArchive $zip, string $path): array {
        $rels = [];

        $content = $zip->getFromName($path);
        if ($content === false) {
            return [];
        }

        $dom = new DOMDocument();
        $dom->loadXML($content);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $relNodes = $xpath->query('//r:Relationship');
        if ($relNodes !== false) {
            foreach ($relNodes as $rel) {
                if ($rel instanceof DOMElement) {
                    $id = $rel->getAttribute('Id');
                    $target = $rel->getAttribute('Target');
                    $rels[$id] = $target;
                }
            }
        }

        return $rels;
    }

    /**
     * Parst ein einzelnes Worksheet.
     */
    protected function parseSheet(ZipArchive $zip, string $path, string $name, bool $hasHeader, int $sheetIndex): ?Sheet {
        $content = $zip->getFromName($path);
        if ($content === false) {
            self::logWarning("Sheet nicht gefunden: $path");
            return null;
        }

        $dom = new DOMDocument();
        $dom->loadXML($content);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rowNodes = $xpath->query('//s:sheetData/s:row');
        if ($rowNodes === false || $rowNodes->length === 0) {
            return new Sheet($name, null, [], $sheetIndex);
        }

        $rows = [];
        $header = null;
        $isFirstRow = true;

        foreach ($rowNodes as $rowNode) {
            if (!$rowNode instanceof DOMElement) {
                continue;
            }

            $rowIndex = (int) $rowNode->getAttribute('r');
            $cells = $this->parseRowCells($xpath, $rowNode);
            $row = new Row($cells, $rowIndex);

            if ($hasHeader && $isFirstRow) {
                $header = $row;
                $isFirstRow = false;
            } else {
                $rows[] = $row;
                $isFirstRow = false;
            }
        }

        return new Sheet($name, $header, $rows, $sheetIndex);
    }

    /**
     * Parst die Zellen einer Zeile.
     *
     * @return Cell[]
     */
    protected function parseRowCells(DOMXPath $xpath, DOMElement $rowNode): array {
        $cells = [];
        $cellNodes = $xpath->query('s:c', $rowNode);

        if ($cellNodes === false) {
            return [];
        }

        $expectedCol = 0;

        foreach ($cellNodes as $cellNode) {
            if (!$cellNode instanceof DOMElement) {
                continue;
            }

            // Zellreferenz (z.B. "A1", "B2") parsen
            $ref = $cellNode->getAttribute('r');
            $colIndex = $this->columnRefToIndex($ref);

            // Leere Zellen für übersprungene Spalten einfügen
            while ($expectedCol < $colIndex) {
                $cells[] = new Cell(null);
                $expectedCol++;
            }

            $cell = $this->parseCell($xpath, $cellNode);
            $cells[] = $cell;
            $expectedCol = $colIndex + 1;
        }

        return $cells;
    }

    /**
     * Parst eine einzelne Zelle.
     */
    protected function parseCell(DOMXPath $xpath, DOMElement $cellNode): Cell {
        $type = $cellNode->getAttribute('t');
        $style = $cellNode->getAttribute('s');
        $format = null;

        // Wert extrahieren
        $valueNode = $xpath->query('s:v', $cellNode)->item(0);
        $value = $valueNode?->textContent;

        // Format bestimmen
        if ($style !== '') {
            $styleIdx = (int) $style;
            if (isset($this->cellStyles[$styleIdx])) {
                $numFmtId = $this->cellStyles[$styleIdx];
                $format = $this->numberFormats[$numFmtId] ?? null;
            }
        }

        // Typ-spezifische Verarbeitung
        switch ($type) {
            case 's': // Shared String
                $idx = (int) $value;
                $value = $this->sharedStrings[$idx] ?? '';
                break;

            case 'inlineStr': // Inline String
                $tNode = $xpath->query('.//s:t', $cellNode)->item(0);
                $value = $tNode?->textContent ?? '';
                break;

            case 'b': // Boolean
                $value = $value === '1';
                break;

            case 'e': // Error
                $value = '#ERROR!';
                break;

            case 'str': // Formula String
                // Wert bleibt wie er ist
                break;

            case 'n': // Number (explizit)
            case '':  // Number (implizit)
            default:
                if ($value !== null && $value !== '') {
                    // Prüfen ob es ein Datum ist
                    if ($this->isDateFormat($format)) {
                        $type = 'd';
                        $value = $this->excelDateToDateTime($value);
                    } elseif (is_numeric($value)) {
                        $type = 'n';
                        // Float oder Int?
                        $value = str_contains($value, '.') ? (float) $value : (int) $value;
                    }
                }
                break;
        }

        return new Cell($value, $type ?: null, $format);
    }

    /**
     * Konvertiert eine Spaltenreferenz (z.B. "A", "AB") in einen 0-basierten Index.
     */
    protected function columnRefToIndex(string $ref): int {
        // Nur die Buchstaben extrahieren
        preg_match('/^([A-Z]+)/', strtoupper($ref), $matches);
        $letters = $matches[1] ?? 'A';

        $index = 0;
        $len = strlen($letters);

        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }

        return $index - 1;
    }

    /**
     * Prüft ob ein Format ein Datumsformat ist.
     */
    protected function isDateFormat(?string $format): bool {
        if ($format === null) {
            return false;
        }

        // Typische Datumsformat-Zeichen
        $datePatterns = ['yy', 'mm', 'dd', 'h:', ':ss', 'AM/PM'];

        foreach ($datePatterns as $pattern) {
            if (stripos($format, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Konvertiert einen Excel-Datumswert in ein DateTime-Objekt.
     */
    protected function excelDateToDateTime(string $value): ?DateTimeImmutable {
        if (!is_numeric($value)) {
            return null;
        }

        $excelDate = (float) $value;

        // Excel-Epoche ist 1899-12-30 (mit dem berühmten Lotus-Bug)
        // Für Werte < 60 muss ein Tag abgezogen werden (1900 war kein Schaltjahr)
        $epoch = new DateTimeImmutable('1899-12-30');

        if ($excelDate < 60) {
            $excelDate -= 1;
        }

        $days = (int) floor($excelDate);
        $fraction = $excelDate - $days;

        $date = $epoch->modify("+{$days} days");

        // Zeitanteil hinzufügen
        if ($fraction > 0) {
            $seconds = (int) round($fraction * 86400);
            $date = $date->modify("+{$seconds} seconds");
        }

        return $date;
    }

    /**
     * Hilfsmethode: Wert aus XPath-Query extrahieren.
     */
    protected function getXPathValue(DOMXPath $xpath, string $query): ?string {
        $node = $xpath->query($query)->item(0);
        return $node?->textContent;
    }
}
