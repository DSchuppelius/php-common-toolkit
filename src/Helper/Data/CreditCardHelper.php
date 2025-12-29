<?php
/*
 * Created on   : Sun Dec 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CreditCardHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;

/**
 * Helper-Klasse für Kreditkarten-Validierung und -Formatierung.
 *
 * Bietet Funktionen für:
 * - Luhn-Algorithmus Validierung
 * - Kartentyp-Erkennung (Visa, Mastercard, etc.)
 * - Ablaufdatum-Validierung
 * - Kartennummer-Formatierung
 */
class CreditCardHelper extends HelperAbstract {
    use ErrorLog;

    /** @var array<string, array{pattern: string, length: int[]}> Kartennummer-Patterns */
    private const CARD_PATTERNS = [
        'Visa' => [
            'pattern' => '/^4[0-9]/',
            'length' => [13, 16, 19]
        ],
        'Mastercard' => [
            'pattern' => '/^5[1-5]|^2[2-7]/',
            'length' => [16]
        ],
        'American Express' => [
            'pattern' => '/^3[47]/',
            'length' => [15]
        ],
        'Diners Club' => [
            'pattern' => '/^3[0689]/',
            'length' => [14]
        ],
        'Discover' => [
            'pattern' => '/^6(?:011|5)/',
            'length' => [16]
        ],
        'JCB' => [
            'pattern' => '/^35/',
            'length' => [16]
        ],
        'Maestro' => [
            'pattern' => '/^(?:5[0678]|6304|6390|67)/',
            'length' => [12, 13, 14, 15, 16, 17, 18, 19]
        ],
    ];

    /**
     * Validiert eine Kreditkartennummer mit dem Luhn-Algorithmus.
     *
     * @param string|null $cardNumber Die zu validierende Kartennummer
     * @return bool True wenn gültig, false andernfalls
     */
    public static function isValidCardNumber(?string $cardNumber): bool {
        if ($cardNumber === null || $cardNumber === '') {
            return false;
        }

        // Entferne alle Nicht-Ziffern
        $cardNumber = preg_replace('/\D/', '', $cardNumber);

        if (empty($cardNumber) || strlen($cardNumber) < 12 || strlen($cardNumber) > 19) {
            return false;
        }

        return self::validateLuhn($cardNumber);
    }

    /**
     * Implementierung des Luhn-Algorithmus zur Kreditkarten-Validierung.
     *
     * @param string $number Die zu validierende Nummer (nur Ziffern)
     * @return bool True wenn Luhn-Checksum korrekt ist
     */
    public static function validateLuhn(string $number): bool {
        $sum = 0;
        $length = strlen($number);
        $parity = $length % 2;

        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int)$number[$i];

            if (($i % 2) !== $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return ($sum % 10) === 0;
    }

    /**
     * Erkennt den Kartentyp anhand der Kartennummer.
     *
     * @param string $cardNumber Die Kartennummer
     * @return string|null Der Kartentyp oder null wenn unbekannt
     */
    public static function getCardType(string $cardNumber): ?string {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        $length = strlen($cardNumber);

        foreach (self::CARD_PATTERNS as $type => $config) {
            if (preg_match($config['pattern'], $cardNumber) && in_array($length, $config['length'], true)) {
                return $type;
            }
        }

        self::logError("Unbekannter Kartentyp für Nummer: " . substr($cardNumber, 0, 4) . "****");
        return null;
    }

    /**
     * Formatiert eine Kartennummer für die Anzeige (maskiert).
     *
     * @param string $cardNumber Die Kartennummer
     * @param bool $maskMiddle Ob die mittleren Ziffern maskiert werden sollen
     * @return string Die formatierte Kartennummer
     */
    public static function formatCardNumber(string $cardNumber, bool $maskMiddle = true): string {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);

        if (strlen($cardNumber) < 8) {
            return $cardNumber;
        }

        if ($maskMiddle) {
            $first4 = substr($cardNumber, 0, 4);
            $last4 = substr($cardNumber, -4);
            $middleLength = strlen($cardNumber) - 8;
            $masked = str_repeat('*', $middleLength);

            return $first4 . ' ' . $masked . ' ' . $last4;
        }

        // Formatiere ohne Maskierung (4er-Gruppen)
        return rtrim(chunk_split($cardNumber, 4, ' '));
    }

    /**
     * Prüft ob ein Ablaufdatum gültig ist.
     *
     * @param int $month Monat (1-12)
     * @param int $year Jahr (4-stellig oder 2-stellig)
     * @return bool True wenn nicht abgelaufen
     */
    public static function isValidExpiry(int $month, int $year): bool {
        if ($month < 1 || $month > 12) {
            self::logError("Ungültiger Monat: {$month}");
            return false;
        }

        // 2-stelliges Jahr zu 4-stellig konvertieren
        if ($year < 100) {
            $currentYear = (int)date('Y');
            $currentCentury = (int)($currentYear / 100) * 100;
            $year += $currentCentury;

            // Wenn das Jahr in der Vergangenheit liegt, zum nächsten Jahrhundert
            if ($year < $currentYear) {
                $year += 100;
            }
        }

        $currentMonth = (int)date('n');
        $currentYear = (int)date('Y');

        // Karte ist abgelaufen wenn Monat und Jahr in der Vergangenheit liegen
        return !($year < $currentYear || ($year === $currentYear && $month < $currentMonth));
    }

    /**
     * Parst ein Ablaufdatum im Format MM/YY oder MM/YYYY.
     *
     * @param string $expiryDate Das Ablaufdatum als String
     * @return array{month: int, year: int, valid: bool}|null Geparste Daten oder null bei Fehler
     */
    public static function parseExpiryDate(string $expiryDate): ?array {
        if (!preg_match('/^(\d{2})\/(\d{2,4})$/', trim($expiryDate), $matches)) {
            self::logError("Ungültiges Ablaufdatum-Format: {$expiryDate}");
            return null;
        }

        $month = (int)$matches[1];
        $year = (int)$matches[2];

        $valid = self::isValidExpiry($month, $year);

        return [
            'month' => $month,
            'year' => $year,
            'valid' => $valid,
        ];
    }

    /**
     * Generiert eine Luhn-gültige Testkartennummer für den angegebenen Typ.
     *
     * @param string $cardType Der gewünschte Kartentyp
     * @return string|null Eine gültige Testkartennummer oder null bei unbekanntem Typ
     */
    public static function generateTestCardNumber(string $cardType): ?string {
        $testNumbers = [
            'Visa' => '4111111111111111',
            'Mastercard' => '5555555555554444',
            'American Express' => '378282246310005',
            'Diners Club' => '30569309025904',
            'Discover' => '6011111111111117',
            'JCB' => '3530111333300000',
        ];

        return $testNumbers[$cardType] ?? null;
    }

    /**
     * Validiert eine komplette Kreditkarteninformation.
     *
     * @param string $cardNumber Die Kartennummer
     * @param int $expiryMonth Ablaufmonat
     * @param int $expiryYear Ablaufjahr
     * @param string|null $cvv Der CVV-Code (optional)
     * @return array{valid: bool, errors: string[], cardType: string|null}
     */
    public static function validateCard(string $cardNumber, int $expiryMonth, int $expiryYear, ?string $cvv = null): array {
        $errors = [];
        $cardType = null;

        // Kartennummer validieren
        if (!self::isValidCardNumber($cardNumber)) {
            $errors[] = 'Ungültige Kartennummer';
        } else {
            $cardType = self::getCardType($cardNumber);
        }

        // Ablaufdatum validieren
        if (!self::isValidExpiry($expiryMonth, $expiryYear)) {
            $errors[] = 'Ungültiges oder abgelaufenes Datum';
        }

        // CVV validieren (falls angegeben)
        if ($cvv !== null && !preg_match('/^\d{3,4}$/', $cvv)) {
            $errors[] = 'Ungültiger CVV-Code';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'cardType' => $cardType,
        ];
    }
}
