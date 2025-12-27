<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankOperationCode.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking;

/**
 * Bank Operation Code für MT10x-Überweisungen (Feld :23B:).
 * 
 * Definiert die Art der Transaktion.
 */
enum BankOperationCode: string {
    /**
     * CRED - Credit Transfer
     * Normale Gutschrift/Überweisung
     */
    case CRED = 'CRED';

    /**
     * CRTS - Credit Transfer for Test
     * Testüberweisung
     */
    case CRTS = 'CRTS';

    /**
     * SPAY - Special Payment
     * Sonderzahlung
     */
    case SPAY = 'SPAY';

    /**
     * SPRI - Priority Payment
     * Prioritätszahlung
     */
    case SPRI = 'SPRI';

    /**
     * SSTD - Standard Settlement
     * Standard-Abwicklung
     */
    case SSTD = 'SSTD';

    /**
     * Gibt die deutsche Beschreibung zurück.
     */
    public function description(): string {
        return match ($this) {
            self::CRED => 'Überweisung',
            self::CRTS => 'Testüberweisung',
            self::SPAY => 'Sonderzahlung',
            self::SPRI => 'Prioritätszahlung',
            self::SSTD => 'Standard-Abwicklung',
        };
    }

    /**
     * Gibt die englische Beschreibung zurück.
     */
    public function descriptionEn(): string {
        return match ($this) {
            self::CRED => 'Credit Transfer',
            self::CRTS => 'Credit Transfer for Test',
            self::SPAY => 'Special Payment',
            self::SPRI => 'Priority Payment',
            self::SSTD => 'Standard Settlement',
        };
    }

    /**
     * Prüft ob es sich um eine Testüberweisung handelt.
     */
    public function isTest(): bool {
        return $this === self::CRTS;
    }

    /**
     * Factory-Methode aus String.
     */
    public static function fromString(string $value): ?self {
        return self::tryFrom(strtoupper(trim($value)));
    }
}
