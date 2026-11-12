<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CompoundFileWriter.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Support;

use InvalidArgumentException;

/**
 * Test-Hilfe: schreibt synthetische Compound Files (MS-CFB) — Version 3
 * (512-Byte-Sektoren) oder 4 (4096-Byte-Sektoren), mit MiniFAT/Mini-Stream
 * für Streams unter 4096 Byte und DIFAT-Sektoren, sobald mehr als 109
 * FAT-Sektoren nötig sind. Kinder eines Speichers werden als ausgeglichener
 * Binärbaum verknüpft.
 *
 * {@see layout()} beschreibt nach {@see build()}, wo was liegt, damit Tests
 * gezielt Bytes beschädigen können ({@see patchFat()}, {@see patchEntry()}).
 */
final class CompoundFileWriter {
    public const END_OF_CHAIN = 0xFFFFFFFE;

    public const FREE_SECTOR = 0xFFFFFFFF;

    public const FAT_SECTOR = 0xFFFFFFFD;

    public const DIFAT_SECTOR = 0xFFFFFFFC;

    public const NO_STREAM = 0xFFFFFFFF;

    /** Feld-Offsets in einem Verzeichniseintrag. */
    public const ENTRY_LEFT = 0x44;

    public const ENTRY_RIGHT = 0x48;

    public const ENTRY_CHILD = 0x4C;

    public const ENTRY_START = 0x74;

    public const ENTRY_SIZE = 0x78;

    private const SIGNATURE = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";

    /** @var array<string, array{type: int, data: string, children: list<string>}> Pfad => Knoten ('' = Wurzel) */
    private array $nodes = ['' => ['type' => 5, 'data' => '', 'children' => []]];

    /** @var array{sectorSize: int, ids: array<string, int>, starts: array<string, int>, fatSectors: list<int>, difatSectors: list<int>, directorySectors: list<int>, miniFatSectors: list<int>, miniStreamStart: int} */
    private array $layout = [
        'sectorSize' => 512, 'ids' => [], 'starts' => [], 'fatSectors' => [], 'difatSectors' => [],
        'directorySectors' => [], 'miniFatSectors' => [], 'miniStreamStart' => self::END_OF_CHAIN,
    ];

    public function __construct(private readonly int $majorVersion = 3) {
        if ($majorVersion !== 3 && $majorVersion !== 4) {
            throw new InvalidArgumentException('Version 3 oder 4.');
        }
    }

    public function addStorage(string $path): self {
        $this->ensureStorage(trim($path, '/'));

        return $this;
    }

    public function addStream(string $path, string $data): self {
        $path = trim($path, '/');
        $this->ensureStorage(self::parentOf($path));
        if (!isset($this->nodes[$path])) {
            $this->nodes[self::parentOf($path)]['children'][] = $path;
        }
        $this->nodes[$path] = ['type' => 2, 'data' => $data, 'children' => []];

        return $this;
    }

    public function build(): string {
        $sectorSize = $this->majorVersion === 3 ? 512 : 4096;
        $perSector = intdiv($sectorSize, 4);

        // IDs in Breitensuche ab der Wurzel
        $ids = ['' => 0];
        $order = [''];
        $queue = [''];
        while (($path = array_shift($queue)) !== null) {
            foreach ($this->nodes[$path]['children'] as $child) {
                $ids[$child] = count($order);
                $order[] = $child;
                if ($this->nodes[$child]['type'] === 1) {
                    $queue[] = $child;
                }
            }
        }

        $count = count($order);
        $left = array_fill(0, $count, self::NO_STREAM);
        $right = array_fill(0, $count, self::NO_STREAM);
        $child = array_fill(0, $count, self::NO_STREAM);
        foreach ($order as $path) {
            if ($this->nodes[$path]['type'] !== 2) {
                $sorted = $this->nodes[$path]['children'];
                usort($sorted, static fn (string $a, string $b): int => self::compareNames(self::nameOf($a), self::nameOf($b)));
                $child[$ids[$path]] = self::balance($sorted, $ids, $left, $right);
            }
        }

        // Kleine Streams in den Mini-Stream
        $miniStream = '';
        $miniFat = [];
        $starts = [];
        $large = [];
        foreach ($order as $path) {
            $node = $this->nodes[$path];
            if ($node['type'] !== 2) {
                continue;
            }
            $size = strlen($node['data']);
            if ($size === 0) {
                $starts[$path] = self::END_OF_CHAIN;
            } elseif ($size < 4096) {
                $start = intdiv(strlen($miniStream), 64);
                $sectors = intdiv($size + 63, 64);
                for ($k = 0; $k < $sectors; $k++) {
                    $miniFat[] = $k < $sectors - 1 ? $start + $k + 1 : self::END_OF_CHAIN;
                }
                $miniStream .= str_pad($node['data'], $sectors * 64, "\0");
                $starts[$path] = $start;
            } else {
                $large[] = $path;
            }
        }

        // Normale Sektoren: große Streams, Mini-Stream, MiniFAT, Verzeichnis
        $sectors = [];
        $links = [];
        foreach ($large as $path) {
            $starts[$path] = self::allocate($this->nodes[$path]['data'], $sectorSize, $sectors, $links);
        }
        $miniStreamStart = self::allocate($miniStream, $sectorSize, $sectors, $links);
        $firstMiniFat = self::END_OF_CHAIN;
        $miniFatSectorCount = 0;
        $miniFatSectors = [];
        if ($miniFat !== []) {
            $miniFatBytes = pack('V*', ...$miniFat);
            $miniFatBytes .= str_repeat("\xFF", ($sectorSize - strlen($miniFatBytes) % $sectorSize) % $sectorSize);
            $before = count($sectors);
            $firstMiniFat = self::allocate($miniFatBytes, $sectorSize, $sectors, $links);
            $miniFatSectorCount = count($sectors) - $before;
            $miniFatSectors = range($before, count($sectors) - 1);
        }

        $directory = '';
        foreach ($order as $path) {
            $node = $this->nodes[$path];
            $id = $ids[$path];
            $isRoot = $path === '';
            $directory .= self::entry(
                $isRoot ? 'Root Entry' : self::nameOf($path),
                $node['type'],
                $left[$id],
                $right[$id],
                $child[$id],
                $isRoot ? $miniStreamStart : ($node['type'] === 2 ? $starts[$path] : 0),
                $isRoot ? strlen($miniStream) : ($node['type'] === 2 ? strlen($node['data']) : 0),
            );
        }
        while (strlen($directory) % $sectorSize !== 0) {
            $directory .= self::emptyEntry();
        }
        $before = count($sectors);
        $firstDirectory = self::allocate($directory, $sectorSize, $sectors, $links);
        $directorySectors = range($before, count($sectors) - 1);

        // FAT und DIFAT dimensionieren (sie belegen selbst Sektoren)
        $base = count($sectors);
        $fatCount = 1;
        $difatCount = 0;
        while (true) {
            $total = $base + $fatCount + $difatCount;
            $needFat = intdiv($total + $perSector - 1, $perSector);
            $needDifat = $needFat > 109 ? intdiv($needFat - 109 + $perSector - 2, $perSector - 1) : 0;
            if ($needFat === $fatCount && $needDifat === $difatCount) {
                break;
            }
            $fatCount = $needFat;
            $difatCount = $needDifat;
        }
        $fatSectors = range($base, $base + $fatCount - 1);
        $difatSectors = $difatCount > 0 ? range($base + $fatCount, $base + $fatCount + $difatCount - 1) : [];

        $fat = array_fill(0, $fatCount * $perSector, self::FREE_SECTOR);
        foreach ($links as $sector => $next) {
            $fat[$sector] = $next;
        }
        foreach ($fatSectors as $sector) {
            $fat[$sector] = self::FAT_SECTOR;
        }
        foreach ($difatSectors as $sector) {
            $fat[$sector] = self::DIFAT_SECTOR;
        }
        foreach (str_split(pack('V*', ...$fat), $sectorSize) as $fatSector) {
            $sectors[] = $fatSector;
        }

        $extraFat = array_slice($fatSectors, 109);
        foreach (array_keys($difatSectors) as $index) {
            $values = array_slice($extraFat, $index * ($perSector - 1), $perSector - 1);
            $values = array_pad($values, $perSector - 1, self::FREE_SECTOR);
            $values[] = $difatSectors[$index + 1] ?? self::END_OF_CHAIN;
            $sectors[] = pack('V*', ...$values);
        }

        $header = self::SIGNATURE . str_repeat("\0", 16)
            . pack('vvvvv', 0x003E, $this->majorVersion, 0xFFFE, $this->majorVersion === 3 ? 9 : 12, 6)
            . str_repeat("\0", 6)
            . pack(
                'VVVVVVVVV',
                $this->majorVersion === 4 ? count($directorySectors) : 0,
                $fatCount,
                $firstDirectory,
                0,
                4096,
                $firstMiniFat,
                $miniFatSectorCount,
                $difatSectors[0] ?? self::END_OF_CHAIN,
                $difatCount,
            )
            . pack('V*', ...array_pad(array_slice($fatSectors, 0, 109), 109, self::FREE_SECTOR));
        $header = str_pad($header, $sectorSize, "\0");

        $this->layout = [
            'sectorSize' => $sectorSize,
            'ids' => $ids,
            'starts' => $starts,
            'fatSectors' => $fatSectors,
            'difatSectors' => $difatSectors,
            'directorySectors' => $directorySectors,
            'miniFatSectors' => $miniFatSectors,
            'miniStreamStart' => $miniStreamStart,
        ];

        return $header . implode('', $sectors);
    }

    /**
     * Wo was liegt (nach {@see build()}).
     *
     * @return array{sectorSize: int, ids: array<string, int>, starts: array<string, int>, fatSectors: list<int>, difatSectors: list<int>, directorySectors: list<int>, miniFatSectors: list<int>, miniStreamStart: int}
     */
    public function layout(): array {
        return $this->layout;
    }

    /** FAT-Eintrag von $sector in einer gebauten Datei überschreiben. */
    public function patchFat(string $bytes, int $sector, int $next): string {
        $perSector = intdiv($this->layout['sectorSize'], 4);
        $fatSector = $this->layout['fatSectors'][intdiv($sector, $perSector)];
        $offset = ($fatSector + 1) * $this->layout['sectorSize'] + ($sector % $perSector) * 4;

        return substr_replace($bytes, pack('V', $next), $offset, 4);
    }

    /** MiniFAT-Eintrag von $miniSector überschreiben. */
    public function patchMiniFat(string $bytes, int $miniSector, int $next): string {
        $perSector = intdiv($this->layout['sectorSize'], 4);
        $sector = $this->layout['miniFatSectors'][intdiv($miniSector, $perSector)];
        $offset = ($sector + 1) * $this->layout['sectorSize'] + ($miniSector % $perSector) * 4;

        return substr_replace($bytes, pack('V', $next), $offset, 4);
    }

    /** Ein 32-Bit-Feld (ENTRY_*) des Verzeichniseintrags zu $path überschreiben. */
    public function patchEntry(string $bytes, string $path, int $field, int $value): string {
        $id = $this->layout['ids'][trim($path, '/')];
        $perSector = intdiv($this->layout['sectorSize'], 128);
        $sector = $this->layout['directorySectors'][intdiv($id, $perSector)];
        $offset = ($sector + 1) * $this->layout['sectorSize'] + ($id % $perSector) * 128 + $field;

        return substr_replace($bytes, pack('V', $value), $offset, 4);
    }

    private function ensureStorage(string $path): void {
        if ($path === '' || isset($this->nodes[$path])) {
            return;
        }
        $parent = self::parentOf($path);
        $this->ensureStorage($parent);
        $this->nodes[$parent]['children'][] = $path;
        $this->nodes[$path] = ['type' => 1, 'data' => '', 'children' => []];
    }

    private static function parentOf(string $path): string {
        $position = strrpos($path, '/');

        return $position === false ? '' : substr($path, 0, $position);
    }

    private static function nameOf(string $path): string {
        $position = strrpos($path, '/');

        return $position === false ? $path : substr($path, $position + 1);
    }

    /** MS-CFB-Reihenfolge: kürzere Namen zuerst, sonst nach Großschreibung. */
    private static function compareNames(string $a, string $b): int {
        $lengthA = mb_strlen($a, 'UTF-8');
        $lengthB = mb_strlen($b, 'UTF-8');

        return $lengthA !== $lengthB ? $lengthA <=> $lengthB : strcmp(mb_strtoupper($a, 'UTF-8'), mb_strtoupper($b, 'UTF-8'));
    }

    /**
     * @param list<string>       $sorted
     * @param array<string, int> $ids
     * @param array<int, int>    $left
     * @param array<int, int>    $right
     */
    private static function balance(array $sorted, array $ids, array &$left, array &$right): int {
        if ($sorted === []) {
            return self::NO_STREAM;
        }
        $middle = intdiv(count($sorted), 2);
        $id = $ids[$sorted[$middle]];
        $left[$id] = self::balance(array_slice($sorted, 0, $middle), $ids, $left, $right);
        $right[$id] = self::balance(array_slice($sorted, $middle + 1), $ids, $left, $right);

        return $id;
    }

    /**
     * Daten in neue Sektoren legen und verketten.
     *
     * @param positive-int    $sectorSize
     * @param list<string>    $sectors
     * @param array<int, int> $links
     */
    private static function allocate(string $data, int $sectorSize, array &$sectors, array &$links): int {
        if ($data === '') {
            return self::END_OF_CHAIN;
        }
        $start = count($sectors);
        $chunks = str_split($data, $sectorSize);
        foreach ($chunks as $index => $chunk) {
            $sector = $start + $index;
            $sectors[] = str_pad($chunk, $sectorSize, "\0");
            $links[$sector] = $index < count($chunks) - 1 ? $sector + 1 : self::END_OF_CHAIN;
        }

        return $start;
    }

    private static function entry(string $name, int $type, int $left, int $right, int $child, int $start, int $size): string {
        $utf16 = mb_convert_encoding($name, 'UTF-16LE', 'UTF-8');

        return str_pad($utf16 . "\0\0", 64, "\0")
            . pack('vCC', strlen($utf16) + 2, $type, 1)
            . pack('VVV', $left, $right, $child)
            . str_repeat("\0", 16)
            . pack('V', 0)
            . str_repeat("\0", 16)
            . pack('VVV', $start, $size & 0xFFFFFFFF, $size >> 32);
    }

    private static function emptyEntry(): string {
        return str_repeat("\0", 64) . pack('vCC', 0, 0, 0) . pack('VVV', self::NO_STREAM, self::NO_STREAM, self::NO_STREAM) . str_repeat("\0", 48);
    }
}
