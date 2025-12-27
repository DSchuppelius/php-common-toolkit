<?php
/*
 * Created on   : Thu Apr 17 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DataValidator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

/**
 * Zentrale Validierungsklasse für Finanzdaten.
 *
 * Bietet einheitliche Validierungsmethoden für:
 * - Bankdaten (IBAN, BIC, BLZ, Kontonummer)
 * - Währungsbeträge
 * - Datumsformate
 *
 * @package CommonToolkit\Helper\Data
 */
class DataValidator {
    /**
     * Prüft, ob der Wert ein gültiges Datum ist.
     *
     * @param string $value
     * @return boolean
     */
    public static function isDate(string $value): bool {
        return DateHelper::isValidDate($value);
    }

    /**
     * Prüft, ob der Wert ein gültiger Betrag ist.
     *
     * @param string $value
     * @return boolean
     */
    public static function isAmount(string $value): bool {
        $format = '';
        return CurrencyHelper::isCurrency($value, $format);
    }

    /**
     * Prüft, ob der Wert eine IBAN ist.
     *
     * @param string $value
     * @return boolean
     */
    public static function isIBAN(string $value): bool {
        return self::isMaskedIBAN($value) || self::isRealIBAN($value);
    }

    /**
     * Prüft, ob der Wert eine maskierte IBAN ist.
     *
     * @param string $value
     * @return boolean
     */
    public static function isMaskedIBAN(string $value): bool {
        // z. B. DE4430020900XXXXXX123
        return BankHelper::isIBANAnon($value);
    }

    /**
     * Prüft, ob der Wert eine echte IBAN ist.
     *
     * @param string $value
     * @return boolean
     */
    public static function isRealIBAN(string $value): bool {
        // z. B. DE44300209001234567890
        return BankHelper::isIBAN($value);
    }

    /**
     * Prüft, ob der Wert eine gültige BIC ist.
     *
     * @param string $value
     * @return boolean
     */
    public static function isBIC(string $value): bool {
        return BankHelper::isBIC($value);
    }

    /**
     * Prüft, ob der Wert ein Bankleitzahl ist.
     *
     * @param string $value
     * @return boolean
     */
    public static function isBankCode(string $value): bool {
        return BankHelper::isBLZ($value);
    }

    /**
     * Prüft, ob der Wert eine Kontonummer ist.
     *
     * @param string $value
     * @return boolean
     */
    public static function isAccountNumber(string $value): bool {
        return BankHelper::isKTO($value);
    }

    /**
     * Prüft, ob der Wert ein Text ist.
     *
     * @param string $value
     * @return boolean
     */
    public static function isText(string $value): bool {
        return !self::isAccountNumber($value) && !self::isBankCode($value) && !self::isIBAN($value) && !self::isBIC($value) && !self::isAmount($value) && !self::isDate($value);
    }

    /**
     * Validiert den Wert basierend auf dem Symbol.
     *
     * @param string $symbol Das Symbol, das den Typ angibt.
     * @param string $value Der zu validierende Wert.
     * @return bool True, wenn der Wert gültig ist, andernfalls false.
     */
    public static function validateBySymbol(string $symbol, string $value): bool {
        return match ($symbol) {
            'd', 'D' => self::isDate($value),
            'b'      => self::isAmount($value),
            'B'      => self::isBankCode($value),
            'k'      => self::isAccountNumber($value),
            'i'      => self::isRealIBAN($value),
            'I'      => self::isMaskedIBAN($value),
            'c'      => self::isBIC($value),
            't'      => self::isText($value),
            '_'      => true,
            default  => false,
        };
    }
}