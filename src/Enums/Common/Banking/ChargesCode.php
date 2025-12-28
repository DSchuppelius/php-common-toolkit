<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ChargesCode.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking;

/**
 * Gebührencode für MT10x-Überweisungen (Feld :71A:).
 * 
 * Definiert wer die Überweisungsgebühren trägt.
 */
enum ChargesCode: string {
    /**
     * BEN - Beneficiary (Begünstigter trägt alle Gebühren)
     */
    case BEN = 'BEN';

    /**
     * OUR - Ordering Customer (Auftraggeber trägt alle Gebühren)
     */
    case OUR = 'OUR';

    /**
     * SHA - Shared (Gebühren werden geteilt)
     */
    case SHA = 'SHA';

    /**
     * Gibt die deutsche Beschreibung zurück.
     */
    public function description(): string {
        return match ($this) {
            self::BEN => 'Begünstigter trägt alle Gebühren',
            self::OUR => 'Auftraggeber trägt alle Gebühren',
            self::SHA => 'Gebühren werden geteilt',
        };
    }

    /**
     * Gibt die englische Beschreibung zurück.
     */
    public function descriptionEn(): string {
        return match ($this) {
            self::BEN => 'All charges borne by beneficiary',
            self::OUR => 'All charges borne by ordering customer',
            self::SHA => 'Charges shared',
        };
    }

    /**
     * Factory-Methode aus String.
     */
    public static function fromString(string $value): ?self {
        return self::tryFrom(strtoupper(trim($value)));
    }
}
