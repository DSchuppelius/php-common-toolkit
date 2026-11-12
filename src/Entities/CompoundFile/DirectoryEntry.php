<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DirectoryEntry.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\CompoundFile;

/**
 * Verzeichniseintrag einer Compound File Binary (MS-CFB): Speicher (Ordner),
 * Stream (Datei) oder der Wurzelspeicher. Unveränderlich; erzeugt von
 * {@see \CommonToolkit\Parsers\CompoundFileReader}, der auch die Kinder und
 * den Inhalt liefert.
 */
final class DirectoryEntry {
    public const TYPE_STORAGE = 1;

    public const TYPE_STREAM = 2;

    public const TYPE_ROOT = 5;

    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly int $type,
        private readonly int $startSector,
        private readonly int $size,
    ) {}

    /** Index im Verzeichnis (0 = Wurzel). */
    public function getId(): int {
        return $this->id;
    }

    /** Name in UTF-8 (z. B. "__properties_version1.0"). */
    public function getName(): string {
        return $this->name;
    }

    /** Eine der TYPE_*-Konstanten. */
    public function getType(): int {
        return $this->type;
    }

    /** Speicher oder Wurzelspeicher — kann Kinder haben. */
    public function isStorage(): bool {
        return $this->type === self::TYPE_STORAGE || $this->type === self::TYPE_ROOT;
    }

    public function isStream(): bool {
        return $this->type === self::TYPE_STREAM;
    }

    public function isRoot(): bool {
        return $this->type === self::TYPE_ROOT;
    }

    /** Erster Sektor des Inhalts (beim Wurzelspeicher: des Mini-Streams). */
    public function getStartSector(): int {
        return $this->startSector;
    }

    /** Größe des Inhalts in Byte laut Verzeichnis (bei Speichern 0). */
    public function getSize(): int {
        return $this->size;
    }
}
