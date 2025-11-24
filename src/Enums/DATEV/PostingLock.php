<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PostingLock.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

enum PostingLock: string {
    case UNDEFINED = '';  // leer = DATEV entscheidet
    case NONE      = '0'; // keine Festschreibung
    case LOCKED    = '1'; // Festschreibung

    /**
     * Deutsche Bezeichnung.
     */
    public function getLabel(): string {
        return match ($this) {
            self::UNDEFINED => 'Nicht definiert / Standard (automatische Festschreibung)',
            self::NONE      => 'Keine Festschreibung',
            self::LOCKED    => 'Festschreibung',
        };
    }

    /**
     * Factory für CSV/DATEV-Parsing.
     */
    public static function fromStringValue(?string $value): self {
        $value = trim((string)$value);

        return match ($value) {
            ''  => self::UNDEFINED,
            '0' => self::NONE,
            '1' => self::LOCKED,
            default => throw new InvalidArgumentException("Ungültige Festschreibungsangabe: '$value'"),
        };
    }

    public function isLocked(): bool {
        return $this === self::LOCKED;
    }

    public function isNone(): bool {
        return $this === self::NONE;
    }

    public function isUndefined(): bool {
        return $this === self::UNDEFINED;
    }
}