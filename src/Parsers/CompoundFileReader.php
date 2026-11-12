<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CompoundFileReader.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Entities\CompoundFile\DirectoryEntry;
use CommonToolkit\Exceptions\Parsers\{DocumentLimitExceededException, MalformedDocumentException};
use CommonToolkit\Helper\Data\StringHelper;
use CommonToolkit\Helper\FileSystem\File;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;

/**
 * Leser für Compound File Binary (MS-CFB, „OLE2"-Container): das
 * Dateisystem-in-einer-Datei unter Outlook-.msg, alten Office-Dateien
 * (.doc/.xls/.ppt), Thumbs.db und MSI-Paketen. Reines PHP, nur lesend.
 *
 * Unterstützt Version 3 (512-Byte-Sektoren) und 4 (4096-Byte-Sektoren),
 * DIFAT-Ketten, MiniFAT/Mini-Stream (Streams unter 4096 Byte) und beliebig
 * tiefe Speicher. Streams werden erst beim Abruf ({@see readStream()})
 * zusammengesetzt.
 *
 * Beschädigte oder präparierte Dateien führen zu einer
 * {@see MalformedDocumentException} statt zu Warnungen oder Endlosschleifen:
 * falsche Signatur, abgeschnittene Datei, Sektorverweise außerhalb der Datei,
 * Zyklen in Sektorketten und im Verzeichnisbaum, Sektoren oder
 * Verzeichniseinträge, die zu zwei Strukturen gehören, Größenangaben jenseits
 * der Datei. Weil kein Sektor doppelt verwendet werden darf, kann die Summe
 * aller gelesenen Streams die Dateigröße nicht übersteigen.
 *
 * @example
 * ```php
 * $cfb = CompoundFileReader::fromFile('nachricht.msg');
 * $stream = $cfb->getChild($cfb->getRoot(), '__properties_version1.0');
 * $bytes = $stream !== null ? $cfb->readStream($stream) : '';
 * ```
 */
final class CompoundFileReader {
    use ErrorLog;

    /** Die ersten acht Byte jeder Compound File. */
    public const SIGNATURE = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";

    /** Vorgabe für die größte angenommene Datei (256 MiB). */
    public const DEFAULT_MAX_BYTES = 256 * 1024 * 1024;

    private const HEADER_SIZE = 512;

    private const ENTRY_SIZE = 128;

    private const HEADER_DIFAT_ENTRIES = 109;

    private const MINI_SECTOR_SIZE = 64;

    private const MINI_STREAM_CUTOFF = 4096;

    private const MAX_REGULAR_SECTOR = 0xFFFFFFFA;

    private const END_OF_CHAIN = 0xFFFFFFFE;

    private const FREE_SECTOR = 0xFFFFFFFF;

    private const NO_STREAM = 0xFFFFFFFF;

    /** Besitzer der Verwaltungssektoren (Streams besitzen ihre Sektoren mit ihrer Eintrags-ID). */
    private const OWNER_DIFAT = -1;

    private const OWNER_FAT = -2;

    private const OWNER_DIRECTORY = -3;

    private const OWNER_MINI_FAT = -4;

    private int $majorVersion = 0;

    private int $sectorSize = 512;

    /** Anzahl der Sektoren nach dem Kopfsektor (ein angeschnittener letzter Sektor zählt mit). */
    private int $sectorCount = 0;

    /** @var list<int> */
    private array $fat = [];

    /** @var list<int> */
    private array $miniFat = [];

    /** Rohbytes des Verzeichnisses (128 Byte je Eintrag). */
    private string $directory = '';

    private int $entryCount = 0;

    /** @var array<int, DirectoryEntry> */
    private array $entries = [];

    /** @var array<int, list<int>> Speicher-ID → IDs der Kinder */
    private array $children = [];

    /** @var array<int, array<string, int>> Speicher-ID → (Name in Großschreibung → Kind-ID) */
    private array $childIndex = [];

    /** @var array<int, int> Sektor → Besitzer */
    private array $sectorOwner = [];

    /** @var array<int, int> Mini-Sektor → Eintrags-ID */
    private array $miniSectorOwner = [];

    private ?string $miniStream = null;

    private function __construct(
        private readonly string $bytes,
        private readonly ?string $documentName,
    ) {
        $this->load();
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Öffnet eine Compound File aus Bytes (Upload, Mail-Anhang, Datenbank).
     *
     * @param string      $bytes        Der Dateiinhalt.
     * @param string|null $documentName Name für Meldungen und Exceptions (nie ein Serverpfad).
     * @param int|null    $maxBytes     Größte angenommene Eingabe; null = unbegrenzt.
     * @throws InvalidArgumentException       Bei $maxBytes <= 0.
     * @throws DocumentLimitExceededException Wenn die Eingabe größer als $maxBytes ist.
     * @throws MalformedDocumentException     Bei ungültiger oder beschädigter Datei.
     */
    public static function fromString(string $bytes, ?string $documentName = null, ?int $maxBytes = self::DEFAULT_MAX_BYTES): self {
        self::assertSizeLimit(strlen($bytes), $maxBytes, $documentName);

        return new self($bytes, $documentName);
    }

    /**
     * Öffnet eine Compound File von der Platte. Die Größe wird vor dem Lesen
     * geprüft, eine zu große Datei kommt nicht in den Speicher.
     *
     * @throws InvalidArgumentException       Bei $maxBytes <= 0.
     * @throws DocumentLimitExceededException Wenn die Datei größer als $maxBytes ist.
     * @throws MalformedDocumentException     Bei ungültiger oder beschädigter Datei.
     * @throws \ERRORToolkit\Exceptions\FileSystem\FileNotFoundException Wenn die Datei fehlt.
     */
    public static function fromFile(string $path, ?int $maxBytes = self::DEFAULT_MAX_BYTES): self {
        $name = basename($path);
        self::assertSizeLimit(File::size($path), $maxBytes, $name);

        return new self(File::read($path), $name);
    }

    /** Beginnt der Inhalt mit der CFB-Signatur? Prüft nur die ersten acht Byte. */
    public static function isCompoundFile(string $bytes): bool {
        return str_starts_with($bytes, self::SIGNATURE);
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /** 3 (512-Byte-Sektoren) oder 4 (4096-Byte-Sektoren). */
    public function getMajorVersion(): int {
        return $this->majorVersion;
    }

    public function getSectorSize(): int {
        return $this->sectorSize;
    }

    /** Der Wurzelspeicher (Eintrag 0). */
    public function getRoot(): DirectoryEntry {
        return $this->getEntry(0);
    }

    /**
     * Ein Verzeichniseintrag über seine ID.
     *
     * @throws InvalidArgumentException   Wenn es keinen belegten Eintrag mit dieser ID gibt.
     * @throws MalformedDocumentException Wenn der Eintrag beschädigt ist.
     */
    public function getEntry(int $id): DirectoryEntry {
        if (isset($this->entries[$id])) {
            return $this->entries[$id];
        }

        $type = $id >= 0 && $id < $this->entryCount ? $this->entryType($id) : 0;
        if (!in_array($type, [DirectoryEntry::TYPE_STORAGE, DirectoryEntry::TYPE_STREAM, DirectoryEntry::TYPE_ROOT], true)) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Compound File: kein belegter Verzeichniseintrag mit ID $id.");
        }

        $offset = $id * self::ENTRY_SIZE;
        $nameLength = self::uint16($this->directory, $offset + 0x40);
        if ($nameLength > 64 || $nameLength % 2 !== 0) {
            $this->malformed("Name von Verzeichniseintrag $id hat die ungültige Länge $nameLength.");
        }
        $name = StringHelper::convertToUtf8(substr($this->directory, $offset, max(0, $nameLength - 2)), 'UTF-16LE');

        $size = self::uint32($this->directory, $offset + 0x78);
        $sizeHigh = self::uint32($this->directory, $offset + 0x7C);
        // Version 3 kennt nur 32 Bit; ältere Schreiber lassen im oberen Wort Müll stehen.
        if ($this->majorVersion === 4 && $sizeHigh !== 0) {
            if ($sizeHigh >= 0x80000000) {
                $this->malformed("Verzeichniseintrag $id hat eine unplausible Größe.");
            }
            $size += $sizeHigh * 0x100000000;
        }

        return $this->entries[$id] = new DirectoryEntry($id, $name, $type, self::uint32($this->directory, $offset + 0x74), $size);
    }

    /**
     * Die Kinder eines Speichers in Verzeichnisreihenfolge (Streams ergeben eine leere Liste).
     *
     * @return list<DirectoryEntry>
     */
    public function getChildren(DirectoryEntry $storage): array {
        return array_map(fn (int $id): DirectoryEntry => $this->getEntry($id), $this->children[$storage->getId()] ?? []);
    }

    /**
     * Ein Kind eines Speichers über seinen Namen. Groß-/Kleinschreibung zählt
     * nicht; verglichen wird wie in MS-CFB mit einfacher Großschreibung
     * (ein "ß" bleibt "ß").
     */
    public function getChild(DirectoryEntry $storage, string $name): ?DirectoryEntry {
        $storageId = $storage->getId();
        if (!isset($this->childIndex[$storageId])) {
            $index = [];
            foreach ($this->getChildren($storage) as $child) {
                $index['n' . mb_convert_case($child->getName(), MB_CASE_UPPER_SIMPLE, 'UTF-8')] ??= $child->getId();
            }
            $this->childIndex[$storageId] = $index;
        }
        $id = $this->childIndex[$storageId]['n' . mb_convert_case($name, MB_CASE_UPPER_SIMPLE, 'UTF-8')] ?? null;

        return $id === null ? null : $this->getEntry($id);
    }

    /** Ein Eintrag über seinen Pfad ab der Wurzel ("Speicher/Unterspeicher/Stream"). */
    public function getEntryByPath(string $path): ?DirectoryEntry {
        $entry = $this->getRoot();
        foreach (explode('/', trim($path, '/')) as $part) {
            if ($part === '') {
                continue;
            }
            $entry = $this->getChild($entry, $part);
            if ($entry === null) {
                return null;
            }
        }

        return $entry;
    }

    /**
     * Inhalt eines Streams.
     *
     * @param int|null $maxBytes Größter angenommener Stream; null = nur durch die Datei begrenzt.
     * @throws InvalidArgumentException       Wenn der Eintrag kein Stream ist.
     * @throws DocumentLimitExceededException Wenn der Stream größer als $maxBytes ist.
     * @throws MalformedDocumentException     Bei beschädigter Sektorkette.
     */
    public function readStream(DirectoryEntry $entry, ?int $maxBytes = null): string {
        if (!$entry->isStream()) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Compound File: Eintrag ' . $entry->getId() . ' ist kein Stream.');
        }

        $size = $entry->getSize();
        if ($maxBytes !== null && $size > $maxBytes) {
            $message = sprintf('Stream #%d ist %d Byte groß, erlaubt sind %d.', $entry->getId(), $size, $maxBytes);
            self::logError($message);

            throw new DocumentLimitExceededException($message, DocumentLimitExceededException::KIND_BYTES, $maxBytes, $size, $this->documentName);
        }
        if ($size === 0) {
            return '';
        }
        if ($size > strlen($this->bytes)) {
            $this->malformed(sprintf('Stream #%d gibt %d Byte an, die Datei hat nur %d.', $entry->getId(), $size, strlen($this->bytes)));
        }

        return $size < self::MINI_STREAM_CUTOFF
            ? $this->readMiniChain($entry->getStartSector(), $size, $entry->getId())
            : $this->readChain($entry->getStartSector(), $size, $entry->getId());
    }

    // ========================================================================
    // Laden
    // ========================================================================

    /** @throws MalformedDocumentException */
    private function load(): void {
        $length = strlen($this->bytes);
        if ($length < self::HEADER_SIZE) {
            $this->malformed(sprintf('Datei ist %d Byte lang, ein CFB-Kopf braucht %d.', $length, self::HEADER_SIZE));
        }
        if (!self::isCompoundFile($this->bytes)) {
            $this->malformed('Keine Compound-File-Signatur (D0 CF 11 E0 A1 B1 1A E1).');
        }
        if (self::uint16($this->bytes, 0x1C) !== 0xFFFE) {
            $this->malformed('Kennung der Byte-Reihenfolge ist nicht FFFE.');
        }

        $this->majorVersion = self::uint16($this->bytes, 0x1A);
        $sectorShift = self::uint16($this->bytes, 0x1E);
        $expectedShift = match ($this->majorVersion) {
            3 => 9,
            4 => 12,
            default => $this->malformed("Unbekannte Hauptversion {$this->majorVersion} (erwartet 3 oder 4)."),
        };
        if ($sectorShift !== $expectedShift) {
            $this->malformed("Sektorgröße 2^$sectorShift passt nicht zu Version {$this->majorVersion}.");
        }
        if (self::uint16($this->bytes, 0x20) !== 6) {
            $this->malformed('Mini-Sektorgröße ist nicht 64 Byte.');
        }
        if (self::uint32($this->bytes, 0x38) !== self::MINI_STREAM_CUTOFF) {
            $this->malformed('Grenze für den Mini-Stream ist nicht 4096 Byte.');
        }

        $this->sectorSize = 1 << $sectorShift;
        if ($length < $this->sectorSize) {
            $this->malformed(sprintf('Datei ist %d Byte lang, der Kopfsektor allein hat %d.', $length, $this->sectorSize));
        }
        $this->sectorCount = intdiv($length - 1, $this->sectorSize);

        $this->readFat(self::uint32($this->bytes, 0x2C), self::uint32($this->bytes, 0x44), self::uint32($this->bytes, 0x48));
        $this->readDirectory(self::uint32($this->bytes, 0x30));
        $this->readMiniFat(self::uint32($this->bytes, 0x3C), self::uint32($this->bytes, 0x40));
        $this->buildTree();
    }

    /**
     * FAT aus den Sektoren, die der DIFAT nennt (109 im Kopf, Rest in DIFAT-Sektoren).
     *
     * @throws MalformedDocumentException
     */
    private function readFat(int $fatSectorCount, int $nextDifatSector, int $difatSectorCount): void {
        if ($fatSectorCount === 0 || $fatSectorCount > $this->sectorCount) {
            $this->malformed(sprintf('Kopf nennt %d FAT-Sektoren, die Datei hat %d Sektoren.', $fatSectorCount, $this->sectorCount));
        }

        $locations = self::uint32List(substr($this->bytes, 0x4C, self::HEADER_DIFAT_ENTRIES * 4));
        $seen = [];
        for ($i = 0; $i < $difatSectorCount && count($locations) < $fatSectorCount; $i++) {
            if ($nextDifatSector === self::END_OF_CHAIN || $nextDifatSector === self::FREE_SECTOR) {
                break;
            }
            $this->assertSector($nextDifatSector, 'DIFAT');
            if (isset($seen[$nextDifatSector])) {
                $this->malformed("DIFAT: Zyklus in der Sektorkette (Sektor $nextDifatSector).");
            }
            $seen[$nextDifatSector] = true;
            $this->claim($nextDifatSector, self::OWNER_DIFAT);

            $values = self::uint32List($this->sector($nextDifatSector));
            $nextDifatSector = array_pop($values) ?? self::END_OF_CHAIN;
            array_push($locations, ...$values);
        }
        if (count($locations) < $fatSectorCount) {
            $this->malformed(sprintf('DIFAT nennt %d von %d FAT-Sektoren.', count($locations), $fatSectorCount));
        }

        // Nur so viele Einträge, wie die Datei Sektoren hat: ein Eintrag jenseits
        // davon kann nie verfolgt werden ({@see walk()} weist den Verweis vorher
        // ab). Ungekürzt kostete eine Datei aus lauter FAT-Sektoren das
        // Vielfache ihrer Größe an Speicher (128 Einträge je 512-Byte-Sektor).
        $fat = [];
        foreach (array_slice($locations, 0, $fatSectorCount) as $sector) {
            $this->assertSector($sector, 'FAT');
            $this->claim($sector, self::OWNER_FAT);
            $data = $this->sector($sector);
            $missing = $this->sectorCount - count($fat);
            if ($missing > 0) {
                array_push($fat, ...self::uint32List(substr($data, 0, $missing * 4)));
            }
        }
        $this->fat = $fat;
    }

    /** @throws MalformedDocumentException */
    private function readDirectory(int $firstSector): void {
        $parts = [];
        foreach ($this->walk($firstSector, $this->fat, $this->sectorCount, null, 'Verzeichnis') as $sector) {
            $this->claim($sector, self::OWNER_DIRECTORY);
            $parts[] = $this->sector($sector);
        }
        $this->directory = implode('', $parts);
        $this->entryCount = intdiv(strlen($this->directory), self::ENTRY_SIZE);

        if ($this->entryCount === 0 || $this->entryType(0) !== DirectoryEntry::TYPE_ROOT) {
            $this->malformed('Erster Verzeichniseintrag ist kein Wurzelspeicher.');
        }
    }

    /**
     * MiniFAT, gekürzt auf die Mini-Sektoren, die der Mini-Stream haben kann
     * (Größe des Wurzeleintrags, höchstens die Dateigröße) — wie bei der FAT
     * würde eine lange Kette sonst 128 Einträge je Sektor kosten.
     *
     * @throws MalformedDocumentException
     */
    private function readMiniFat(int $firstSector, int $sectorCount): void {
        if ($firstSector === self::END_OF_CHAIN || $sectorCount === 0) {
            return;
        }

        $miniStreamBytes = min($this->getRoot()->getSize(), strlen($this->bytes));
        $limit = intdiv($miniStreamBytes + self::MINI_SECTOR_SIZE - 1, self::MINI_SECTOR_SIZE);
        $miniFat = [];
        foreach ($this->walk($firstSector, $this->fat, $this->sectorCount, null, 'MiniFAT') as $sector) {
            $this->claim($sector, self::OWNER_MINI_FAT);
            $data = $this->sector($sector);
            $missing = $limit - count($miniFat);
            if ($missing > 0) {
                array_push($miniFat, ...self::uint32List(substr($data, 0, $missing * 4)));
            }
        }
        $this->miniFat = $miniFat;
    }

    /**
     * Verzeichnisbaum ab der Wurzel aufbauen. Jeder Eintrag darf genau einen
     * Elternteil haben — ein zweiter Verweis ist ein Zyklus oder ein geteilter
     * Teilbaum, beides wird abgewiesen.
     *
     * @throws MalformedDocumentException
     */
    private function buildTree(): void {
        $parentOf = [0 => -1];
        $storages = [0];
        while (($storageId = array_pop($storages)) !== null) {
            // In-Order durch den Geschwisterbaum: links, Knoten, rechts.
            $children = [];
            $pending = [];
            $node = $this->entryField($storageId, 0x4C);
            while (true) {
                while ($node !== self::NO_STREAM) {
                    $this->assertLinkedEntry($node);
                    if (isset($parentOf[$node])) {
                        $this->malformed("Verzeichniseintrag $node ist mehrfach verknüpft (Zyklus oder geteilter Eintrag).");
                    }
                    $parentOf[$node] = $storageId;
                    $pending[] = $node;
                    $node = $this->entryField($node, 0x44);
                }
                $visited = array_pop($pending);
                if ($visited === null) {
                    break;
                }
                $children[] = $visited;
                if ($this->entryType($visited) === DirectoryEntry::TYPE_STORAGE) {
                    $storages[] = $visited;
                }
                $node = $this->entryField($visited, 0x48);
            }
            $this->children[$storageId] = $children;
        }
    }

    // ========================================================================
    // Sektoren und Ketten
    // ========================================================================

    /**
     * Sektorkette ab $start. Bricht ab bei Zyklus, ungültigem Verweis oder
     * fehlendem Tabelleneintrag; mit $maxLength nach so vielen Sektoren.
     *
     * @param list<int> $table FAT oder MiniFAT
     * @return list<int>
     * @throws MalformedDocumentException
     */
    private function walk(int $start, array $table, int $sectorLimit, ?int $maxLength, string $what): array {
        $chain = [];
        $seen = [];
        $current = $start;
        while ($current !== self::END_OF_CHAIN) {
            if ($current > self::MAX_REGULAR_SECTOR || $current >= $sectorLimit) {
                $this->malformed(sprintf('%s: Sektorkette verweist auf Sektor %s, vorhanden sind %d.', $what, self::sectorLabel($current), $sectorLimit));
            }
            if (isset($seen[$current])) {
                $this->malformed("$what: Zyklus in der Sektorkette (Sektor $current).");
            }
            $seen[$current] = true;
            $chain[] = $current;
            if ($maxLength !== null && count($chain) >= $maxLength) {
                break;
            }
            $current = $table[$current] ?? $this->malformed("$what: Zuordnungstabelle kennt Sektor $current nicht.");
        }

        return $chain;
    }

    /** @throws MalformedDocumentException */
    private function readChain(int $start, int $size, int $owner): string {
        $needed = intdiv($size + $this->sectorSize - 1, $this->sectorSize);
        $chain = $this->walk($start, $this->fat, $this->sectorCount, $needed, "Stream #$owner");
        foreach ($chain as $sector) {
            $this->claim($sector, $owner);
        }
        if (count($chain) < $needed) {
            $this->malformed(sprintf('Stream #%d: Sektorkette endet nach %d von %d Sektoren.', $owner, count($chain), $needed));
        }

        $parts = [];
        $remaining = $size;
        foreach ($chain as $sector) {
            $take = min($this->sectorSize, $remaining);
            $parts[] = $this->sector($sector, $take);
            $remaining -= $take;
        }

        return implode('', $parts);
    }

    /** @throws MalformedDocumentException */
    private function readMiniChain(int $start, int $size, int $owner): string {
        $miniStream = $this->miniStream();
        $needed = intdiv($size + self::MINI_SECTOR_SIZE - 1, self::MINI_SECTOR_SIZE);
        $miniSectorCount = intdiv(strlen($miniStream) + self::MINI_SECTOR_SIZE - 1, self::MINI_SECTOR_SIZE);
        $chain = $this->walk($start, $this->miniFat, $miniSectorCount, $needed, "Mini-Stream #$owner");
        foreach ($chain as $miniSector) {
            if (isset($this->miniSectorOwner[$miniSector]) && $this->miniSectorOwner[$miniSector] !== $owner) {
                $this->malformed("Mini-Sektor $miniSector gehört zu zwei Streams.");
            }
            $this->miniSectorOwner[$miniSector] = $owner;
        }
        if (count($chain) < $needed) {
            $this->malformed(sprintf('Mini-Stream #%d: Sektorkette endet nach %d von %d Mini-Sektoren.', $owner, count($chain), $needed));
        }

        $parts = [];
        $remaining = $size;
        foreach ($chain as $miniSector) {
            $take = min(self::MINI_SECTOR_SIZE, $remaining);
            $offset = $miniSector * self::MINI_SECTOR_SIZE;
            if ($offset + $take > strlen($miniStream)) {
                $this->malformed("Mini-Sektor $miniSector liegt hinter dem Ende des Mini-Streams.");
            }
            $parts[] = substr($miniStream, $offset, $take);
            $remaining -= $take;
        }

        return implode('', $parts);
    }

    /** Der Mini-Stream (Inhalt des Wurzelspeichers), beim ersten Bedarf gelesen. */
    private function miniStream(): string {
        if ($this->miniStream === null) {
            $root = $this->getRoot();
            $size = $root->getSize();
            if ($size > strlen($this->bytes)) {
                $this->malformed(sprintf('Mini-Stream gibt %d Byte an, die Datei hat nur %d.', $size, strlen($this->bytes)));
            }
            $this->miniStream = $size === 0 ? '' : $this->readChain($root->getStartSector(), $size, 0);
        }

        return $this->miniStream;
    }

    /**
     * Bytes eines Sektors; mit $length nur der Anfang (letzter Sektor eines Streams).
     *
     * @throws MalformedDocumentException
     */
    private function sector(int $sector, ?int $length = null): string {
        $length ??= $this->sectorSize;
        $offset = ($sector + 1) * $this->sectorSize;
        if ($offset + $length > strlen($this->bytes)) {
            $this->malformed("Sektor $sector ist abgeschnitten (Datei endet vorher).");
        }

        return substr($this->bytes, $offset, $length);
    }

    /** @throws MalformedDocumentException */
    private function assertSector(int $sector, string $what): void {
        if ($sector > self::MAX_REGULAR_SECTOR || $sector >= $this->sectorCount) {
            $this->malformed(sprintf('%s: Verweis auf Sektor %s, vorhanden sind %d.', $what, self::sectorLabel($sector), $this->sectorCount));
        }
    }

    /**
     * Ein Sektor gehört genau einer Struktur. Zwei Streams auf denselben
     * Sektoren würden den Inhalt vervielfachen (Speicher-Bombe).
     *
     * @throws MalformedDocumentException
     */
    private function claim(int $sector, int $owner): void {
        if (isset($this->sectorOwner[$sector]) && $this->sectorOwner[$sector] !== $owner) {
            $this->malformed("Sektor $sector gehört zu zwei Strukturen.");
        }
        $this->sectorOwner[$sector] = $owner;
    }

    /** @throws MalformedDocumentException */
    private function assertLinkedEntry(int $id): void {
        if ($id >= $this->entryCount) {
            $this->malformed(sprintf('Verweis auf Verzeichniseintrag %d, das Verzeichnis hat %d Einträge.', $id, $this->entryCount));
        }
        $type = $this->entryType($id);
        if ($type !== DirectoryEntry::TYPE_STORAGE && $type !== DirectoryEntry::TYPE_STREAM) {
            $this->malformed("Verzeichniseintrag $id ist leer oder hat den unzulässigen Typ $type.");
        }
    }

    private function entryType(int $id): int {
        return ord($this->directory[$id * self::ENTRY_SIZE + 0x42]);
    }

    private function entryField(int $id, int $offset): int {
        return self::uint32($this->directory, $id * self::ENTRY_SIZE + $offset);
    }

    // ========================================================================
    // Hilfen
    // ========================================================================

    /**
     * @throws InvalidArgumentException       Bei $maxBytes <= 0.
     * @throws DocumentLimitExceededException
     */
    private static function assertSizeLimit(int $size, ?int $maxBytes, ?string $documentName): void {
        if ($maxBytes === null) {
            return;
        }
        if ($maxBytes <= 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "maxBytes muss > 0 sein (oder null für unbegrenzt), erhalten: $maxBytes");
        }
        if ($size > $maxBytes) {
            $message = sprintf('Compound File ist %d Byte groß, erlaubt sind %d.', $size, $maxBytes);
            self::logError($message);

            throw new DocumentLimitExceededException($message, DocumentLimitExceededException::KIND_BYTES, $maxBytes, $size, $documentName);
        }
    }

    /** @throws MalformedDocumentException */
    private function malformed(string $message): never {
        $text = 'Compound File' . ($this->documentName !== null ? ' ' . $this->documentName : '') . ': ' . $message;
        self::logError($text);

        throw new MalformedDocumentException($text, $this->documentName);
    }

    private static function sectorLabel(int $sector): string {
        return match ($sector) {
            self::FREE_SECTOR => 'FREESECT',
            self::END_OF_CHAIN => 'ENDOFCHAIN',
            0xFFFFFFFD => 'FATSECT',
            0xFFFFFFFC => 'DIFSECT',
            default => (string) $sector,
        };
    }

    private static function uint16(string $data, int $offset): int {
        return ord($data[$offset]) | (ord($data[$offset + 1]) << 8);
    }

    private static function uint32(string $data, int $offset): int {
        return ord($data[$offset]) | (ord($data[$offset + 1]) << 8) | (ord($data[$offset + 2]) << 16) | (ord($data[$offset + 3]) << 24);
    }

    /** @return list<int> */
    private static function uint32List(string $data): array {
        $values = $data === '' ? false : unpack('V*', $data);

        return $values === false ? [] : array_values(array_map('intval', $values));
    }
}
