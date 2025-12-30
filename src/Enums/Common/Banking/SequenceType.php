<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SequenceType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking;

/**
 * Sequence Type für SEPA-Lastschriften (SeqTp).
 * 
 * Definiert die Sequenz der Lastschrift im Mandatskontext.
 * 
 * @package CommonToolkit\Enums\Common\Banking
 */
enum SequenceType: string {
    case FIRST = 'FRST';      // Erstlastschrift
    case RECURRING = 'RCUR';  // Wiederkehrende Lastschrift
    case FINAL = 'FNAL';      // Letzte Lastschrift
    case ONE_OFF = 'OOFF';    // Einmalige Lastschrift

    /**
     * Gibt die Beschreibung zurück.
     */
    public function description(): string {
        return match ($this) {
            self::FIRST => 'Erstlastschrift',
            self::RECURRING => 'Wiederkehrende Lastschrift',
            self::FINAL => 'Letzte Lastschrift',
            self::ONE_OFF => 'Einmalige Lastschrift',
        };
    }

    /**
     * SEPA-Standardwert für einmalige Lastschriften.
     */
    public static function defaultSepa(): self {
        return self::ONE_OFF;
    }

    /**
     * Prüft, ob Mandat erforderlich ist.
     */
    public function requiresMandate(): bool {
        return true; // Alle SEPA-Lastschriften erfordern ein Mandat
    }
}
