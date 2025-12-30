<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : LocalInstrument.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking;

/**
 * Local Instrument für SEPA-Zahlungen (LclInstrm).
 * 
 * Definiert den SEPA-Zahlungstyp (Core, B2B, etc.).
 * 
 * @package CommonToolkit\Enums\Common\Banking
 */
enum LocalInstrument: string {
    case SEPA_CORE = 'CORE';          // SEPA Core
    case SEPA_B2B = 'B2B';            // SEPA B2B
    case SEPA_COR1 = 'COR1';          // SEPA Core (1 Tag)
    case SEPA_INST = 'INST';          // SEPA Instant

    /**
     * Gibt die Beschreibung zurück.
     */
    public function description(): string {
        return match ($this) {
            self::SEPA_CORE => 'SEPA Core Lastschrift',
            self::SEPA_B2B => 'SEPA B2B Lastschrift (Firmenlastschrift)',
            self::SEPA_COR1 => 'SEPA Core Lastschrift (verkürzte Frist)',
            self::SEPA_INST => 'SEPA Instant',
        };
    }

    /**
     * Standard für Verbraucher-Lastschriften.
     */
    public static function defaultConsumer(): self {
        return self::SEPA_CORE;
    }

    /**
     * Standard für Firmen-Lastschriften.
     */
    public static function defaultBusiness(): self {
        return self::SEPA_B2B;
    }

    /**
     * Prüft, ob B2B-Lastschrift.
     */
    public function isBusiness(): bool {
        return $this === self::SEPA_B2B;
    }

    /**
     * Prüft, ob Core-Lastschrift.
     */
    public function isCore(): bool {
        return match ($this) {
            self::SEPA_CORE,
            self::SEPA_COR1 => true,
            default => false,
        };
    }
}
