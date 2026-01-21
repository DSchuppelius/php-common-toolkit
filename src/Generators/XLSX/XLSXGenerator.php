<?php
/*
 * Created on   : Wed Jan 22 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XLSXGenerator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Generators\XLSX;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Entities\XLSX\Cell;
use CommonToolkit\Entities\XLSX\Document;
use CommonToolkit\Entities\XLSX\Row;
use CommonToolkit\Entities\XLSX\Sheet;
use DateTimeInterface;
use RuntimeException;
use ZipArchive;

/**
 * Generator zum Erstellen von XLSX-Dateien aus Document-Objekten.
 */
class XLSXGenerator extends HelperAbstract {
    /** @var array<string> Shared Strings für das Dokument */
    protected array $sharedStrings = [];

    /** @var array<string, int> Index-Map für Shared Strings */
    protected array $sharedStringIndex = [];

    /**
     * Generiert eine XLSX-Datei aus einem Document.
     *
     * @param Document $document Das zu exportierende Dokument
     * @param string   $outputPath Der Ausgabepfad
     * @return bool True bei Erfolg
     * @throws RuntimeException Bei Fehlern
     */
    public static function toFile(Document $document, string $outputPath): bool {
        $generator = new self();
        return $generator->generate($document, $outputPath);
    }

    /**
     * Generiert eine XLSX-Datei.
     */
    protected function generate(Document $document, string $outputPath): bool {
        $this->sharedStrings = [];
        $this->sharedStringIndex = [];

        // Shared Strings sammeln
        $this->collectSharedStrings($document);

        $zip = new ZipArchive();

        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            self::logErrorAndThrow(RuntimeException::class, "Kann XLSX-Datei nicht erstellen: $outputPath");
        }

        try {
            // [Content_Types].xml
            $zip->addFromString('[Content_Types].xml', $this->generateContentTypes($document));

            // _rels/.rels
            $zip->addFromString('_rels/.rels', $this->generateRootRels());

            // docProps/app.xml
            $zip->addFromString('docProps/app.xml', $this->generateAppProps($document));

            // docProps/core.xml
            $zip->addFromString('docProps/core.xml', $this->generateCoreProps($document));

            // xl/workbook.xml
            $zip->addFromString('xl/workbook.xml', $this->generateWorkbook($document));

            // xl/_rels/workbook.xml.rels
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->generateWorkbookRels($document));

            // xl/styles.xml
            $zip->addFromString('xl/styles.xml', $this->generateStyles());

            // xl/sharedStrings.xml
            if (!empty($this->sharedStrings)) {
                $zip->addFromString('xl/sharedStrings.xml', $this->generateSharedStrings());
            }

            // xl/worksheets/sheet{n}.xml
            foreach ($document->getSheets() as $idx => $sheet) {
                $zip->addFromString(
                    "xl/worksheets/sheet" . ($idx + 1) . ".xml",
                    $this->generateSheet($sheet)
                );
            }

            return true;
        } finally {
            $zip->close();
        }
    }

    /**
     * Sammelt alle String-Werte für Shared Strings.
     */
    protected function collectSharedStrings(Document $document): void {
        foreach ($document->getSheets() as $sheet) {
            $this->collectRowStrings($sheet->getHeader());
            foreach ($sheet->getRows() as $row) {
                $this->collectRowStrings($row);
            }
        }
    }

    /**
     * Sammelt Strings aus einer Zeile.
     */
    protected function collectRowStrings(?Row $row): void {
        if ($row === null) {
            return;
        }

        foreach ($row->getCells() as $cell) {
            $value = $cell->getValue();
            if (is_string($value) && $value !== '') {
                if (!isset($this->sharedStringIndex[$value])) {
                    $this->sharedStringIndex[$value] = count($this->sharedStrings);
                    $this->sharedStrings[] = $value;
                }
            }
        }
    }

    /**
     * Generiert [Content_Types].xml
     */
    protected function generateContentTypes(Document $document): string {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $xml .= '
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml" />';
        $xml .= '
    <Default Extension="xml" ContentType="application/xml" />';
        $xml .= '
    <Override PartName="/xl/workbook.xml"
        ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml" />';

        foreach ($document->getSheets() as $idx => $sheet) {
            $xml .= '
    <Override PartName="/xl/worksheets/sheet' . ($idx + 1) . '.xml"
        ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml" />';
        }

        $xml .= '
    <Override PartName="/xl/styles.xml"
        ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml" />';

        if (!empty($this->sharedStrings)) {
            $xml .= '
    <Override PartName="/xl/sharedStrings.xml"
        ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml" />';
        }

        $xml .= '
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml" />
    ';
        $xml .= '
    <Override PartName="/docProps/app.xml"
        ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml" />';
        $xml .= '
</Types>';

        return $xml;
    }

    /**
     * Generiert _rels/.rels
     */
    protected function generateRootRels(): string {
        $xml = '
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $xml .= '
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
        Target="xl/workbook.xml" />';
        $xml .= '
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties"
        Target="docProps/core.xml" />';
        $xml .= '
    <Relationship Id="rId3"
        Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties"
        Target="docProps/app.xml" />';
        $xml .= '
</Relationships>';

        return $xml;
    }

    /**
     * Generiert docProps/app.xml
     */
    protected function generateAppProps(Document $document): string {
        $xml = '
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">';
        $xml .= '<Application>CommonToolkit</Application>';
        $xml .= '<DocSecurity>0</DocSecurity>';
        $xml .= '<ScaleCrop>false</ScaleCrop>';
        $xml .= '<LinksUpToDate>false</LinksUpToDate>';
        $xml .= '<SharedDoc>false</SharedDoc>';
        $xml .= '<HyperlinksChanged>false</HyperlinksChanged>';
        $xml .= '<AppVersion>1.0</AppVersion>';
        $xml .= '</Properties>';

        return $xml;
    }

    /**
     * Generiert docProps/core.xml
     */
    protected function generateCoreProps(Document $document): string {
        $now = (new \DateTimeImmutable())->format('Y-m-d\TH:i:s\Z');

        $xml = '
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" ';
        $xml .= ' xmlns:dc="http://purl.org/dc/elements/1.1/" ';
        $xml .= ' xmlns:dcterms="http://purl.org/dc/terms/" ';
        $xml .= ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';

        if ($document->getCreator() !== null) {
            $xml .= '<dc:creator>' . $this->escapeXml($document->getCreator()) . '</dc:creator>';
        }

        if ($document->getTitle() !== null) {
            $xml .= '<dc:title>' . $this->escapeXml($document->getTitle()) . '</dc:title>';
        }

        if ($document->getDescription() !== null) {
            $xml .= '<dc:description>' . $this->escapeXml($document->getDescription()) . '</dc:description>';
        }

        $created = $document->getCreated()?->format('Y-m-d\TH:i:s\Z') ?? $now;
        $modified = $document->getModified()?->format('Y-m-d\TH:i:s\Z') ?? $now;

        $xml .= '<dcterms:created xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:created>';
        $xml .= '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $modified . '</dcterms:modified>';
        $xml .= '</cp:coreProperties>';

        return $xml;
    }

    /**
     * Generiert xl/workbook.xml
     */
    protected function generateWorkbook(Document $document): string {
        $xml = '
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ';
        $xml .= ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheets>';

        foreach ($document->getSheets() as $idx => $sheet) {
            $xml .= '
        <sheet name="' . $this->escapeXml($sheet->getName()) . '" sheetId="' . ($idx + 1) . '"
            r:id="rId' . ($idx + 1) . '" />';
        }

        $xml .= '
    </sheets>';
        $xml .= '</workbook>';

        return $xml;
    }

    /**
     * Generiert xl/_rels/workbook.xml.rels
     */
    protected function generateWorkbookRels(Document $document): string {
        $xml = '
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

        $rId = 1;

        foreach ($document->getSheets() as $idx => $sheet) {
            $xml .= '
    <Relationship Id="rId' . $rId . '"
        Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"
        Target="worksheets/sheet' . ($idx + 1) . '.xml" />';
            $rId++;
        }

        $xml .= '
    <Relationship Id="rId' . $rId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"
        Target="styles.xml" />';
        $rId++;

        if (!empty($this->sharedStrings)) {
            $xml .= '
    <Relationship Id="rId' . $rId . '"
        Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings"
        Target="sharedStrings.xml" />';
        }

        $xml .= '
</Relationships>';

        return $xml;
    }

    /**
     * Generiert xl/styles.xml
     */
    protected function generateStyles(): string {
        $xml = '
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        // Fonts
        $xml .= '<fonts count="1">';
        $xml .= '<font>
            <sz val="11" />
            <name val="Calibri" />
        </font>';
        $xml .= '</fonts>';

        // Fills
        $xml .= '<fills count="2">';
        $xml .= '<fill>
            <patternFill patternType="none" />
        </fill>';
        $xml .= '<fill>
            <patternFill patternType="gray125" />
        </fill>';
        $xml .= '</fills>';

        // Borders
        $xml .= '<borders count="1">';
        $xml .= '<border>
            <left />
            <right />
            <top />
            <bottom />
            <diagonal />
        </border>';
        $xml .= '</borders>';

        // Cell Style XFs
        $xml .= '<cellStyleXfs count="1">';
        $xml .= '
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" />';
        $xml .= '
    </cellStyleXfs>';

        // Cell XFs
        $xml .= '<cellXfs count="1">';
        $xml .= '
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" />';
        $xml .= '
    </cellXfs>';

        $xml .= '</styleSheet>';

        return $xml;
    }

    /**
     * Generiert xl/sharedStrings.xml
     */
    protected function generateSharedStrings(): string {
        $count = count($this->sharedStrings);

        $xml = '
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $count . '"
    uniqueCount="' . $count . '">';

        foreach ($this->sharedStrings as $str) {
            $xml .= '<si>
        <t>' . $this->escapeXml($str) . '</t>
    </si>';
        }

        $xml .= '</sst>';

        return $xml;
    }

    /**
     * Generiert ein Worksheet.
     */
    protected function generateSheet(Sheet $sheet): string {
        $xml = '
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<sheetData>';

        $rowIndex = 1;

        // Header
        if ($sheet->hasHeader()) {
            $xml .= $this->generateRow($sheet->getHeader(), $rowIndex);
            $rowIndex++;
        }

        // Data Rows
        foreach ($sheet->getRows() as $row) {
            $xml .= $this->generateRow($row, $rowIndex);
            $rowIndex++;
        }

        $xml .= '</sheetData>';
        $xml .= '</worksheet>';

        return $xml;
    }

    /**
     * Generiert eine Zeile.
     */
    protected function generateRow(?Row $row, int $rowIndex): string {
        if ($row === null) {
            return '';
        }

        $xml = '<row r="' . $rowIndex . '">';

        foreach ($row->getCells() as $colIndex => $cell) {
            $xml .= $this->generateCell($cell, $rowIndex, $colIndex);
        }

        $xml .= '</row>';

        return $xml;
    }

    /**
     * Generiert eine Zelle.
     */
    protected function generateCell(Cell $cell, int $rowIndex, int $colIndex): string {
        $ref = $this->indexToColumnRef($colIndex) . $rowIndex;
        $value = $cell->getValue();

        if ($value === null || $value === '') {
            return '';
        }

        $xml = '<c r="' . $ref . '"';

        // Typ bestimmen
        if (is_string($value)) {
            // Shared String verwenden
            $idx = $this->sharedStringIndex[$value] ?? null;
            if ($idx !== null) {
                $xml .= ' t="s">
    <v>' . $idx . '</v>
</c>';
                return $xml;
            }
            $xml .= ' t="inlineStr"><is>
    <t>' . $this->escapeXml($value) . '</t>
</is>
</c>';
            return $xml;
        }

        if (is_bool($value)) {
            $xml .= ' t="b"><v>' . ($value ? '1' : '0') . '</v>
</c>';
            return $xml;
        }

        if (is_int($value) || is_float($value)) {
            $xml .= '><v>' . $value . '</v>
</c>';
            return $xml;
        }

        if ($value instanceof DateTimeInterface) {
            // Excel-Datum berechnen
            $excelDate = $this->dateTimeToExcel($value);
            $xml .= ' s="1"><v>' . $excelDate . '</v>
</c>';
            return $xml;
        }

        // Fallback: als String
        $xml .= ' t="inlineStr"><is>
    <t>' . $this->escapeXml((string) $value) . '</t>
</is>
</c>';
        return $xml;
    }

    /**
     * Konvertiert einen 0-basierten Spaltenindex in eine Spaltenreferenz (A, B, ..., AA, AB, ...).
     */
    protected function indexToColumnRef(int $index): string {
        $ref = '';
        $index++;

        while ($index > 0) {
            $index--;
            $ref = chr(ord('A') + ($index % 26)) . $ref;
            $index = intdiv($index, 26);
        }

        return $ref;
    }

    /**
     * Konvertiert ein DateTime in einen Excel-Datumswert.
     */
    protected function dateTimeToExcel(DateTimeInterface $date): float {
        $epoch = new \DateTimeImmutable('1899-12-30');
        $diff = $epoch->diff($date);

        $days = (int) $diff->format('%a');
        if ($diff->invert) {
            $days = -$days;
        }

        // Zeitanteil
        $seconds = $date->format('H') * 3600 + $date->format('i') * 60 + $date->format('s');
        $fraction = $seconds / 86400;

        // Lotus-Bug korrigieren (1900 war kein Schaltjahr)
        if ($days >= 60) {
            $days++;
        }

        return $days + $fraction;
    }

    /**
     * Escaped einen String für XML.
     */
    protected function escapeXml(string $value): string {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
