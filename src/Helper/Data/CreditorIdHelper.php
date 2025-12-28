<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CreditorIdHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Enums\CountryCode;
use ERRORToolkit\Traits\ErrorLog;

/**
 * Helper-Klasse für SEPA Gläubiger-Identifikationsnummer (Creditor Identifier / CI).
 *
 * Die Gläubiger-ID ist für SEPA-Lastschriften erforderlich und identifiziert
 * den Gläubiger eindeutig. Format: Ländercode + 2 Prüfziffern + Geschäftsbereich + nationale ID.
 *
 * @see https://www.bundesbank.de/de/aufgaben/unbarer-zahlungsverkehr/serviceangebot/glaeubiger-identifikationsnummer
 *
 * @package CommonToolkit\Helper\Data
 */
class CreditorIdHelper {
    use ErrorLog;

    /**
     * Länderspezifische Längen der Gläubiger-ID.
     *
     * Format: Ländercode => maximale Länge
     *
     * @var array<string, int>
     */
    private const CREDITOR_ID_LENGTHS = [
        'DE' => 18, // Deutschland: DE + 2 Prüfziffern + 3 Geschäftsbereich + 11 nationale Kennung
        'AT' => 18, // Österreich
        'BE' => 18, // Belgien
        'NL' => 19, // Niederlande
        'FR' => 13, // Frankreich
        'ES' => 16, // Spanien
        'IT' => 23, // Italien
        'PT' => 23, // Portugal
    ];

    /**
     * Prüft, ob eine Gläubiger-ID das korrekte Format hat.
     *
     * @param string|null $creditorId Die zu prüfende Gläubiger-ID.
     * @return bool True, wenn das Format korrekt ist.
     */
    public static function isCreditorId(?string $creditorId): bool {
        if ($creditorId === null || $creditorId === '') {
            return false;
        }

        // Minimale Länge: 8 Zeichen (Ländercode + Prüfziffern + mindestens 4 Zeichen)
        if (strlen($creditorId) < 8) {
            return false;
        }

        // Format: Ländercode (2) + Prüfziffern (2) + Geschäftsbereich (3) + nationale Kennung (variabel)
        // Nur alphanumerische Zeichen erlaubt
        return preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{3,}[A-Z0-9]+$/i', $creditorId) === 1;
    }

    /**
     * Validiert eine Gläubiger-ID mit Prüfziffernberechnung (ISO 7064 MOD 97-10).
     *
     * @param string|null $creditorId Die zu prüfende Gläubiger-ID.
     * @return bool True, wenn die Gläubiger-ID gültig ist.
     */
    public static function validateCreditorId(?string $creditorId): bool {
        if (!self::isCreditorId($creditorId)) {
            return false;
        }

        $normalized = self::normalize($creditorId);

        // Für Prüfsummenberechnung: Geschäftsbereich ignorieren (Position 4-6)
        // Format: CC PP BBB NNNNNNNNNN -> für Prüfung: CC PP NNNNNNNNNN
        $country = substr($normalized, 0, 2);
        $checkDigits = substr($normalized, 2, 2);
        $nationalId = substr($normalized, 7); // Nach dem 3-stelligen Geschäftsbereich

        // Numerische Umwandlung für MOD 97-10
        // Reihenfolge: nationale ID + Ländercode + Prüfziffern
        $checkString = $nationalId . $country . $checkDigits;

        // Buchstaben in Zahlen umwandeln (A=10, B=11, ..., Z=35)
        $numericString = '';
        foreach (str_split(strtoupper($checkString)) as $char) {
            if (ctype_alpha($char)) {
                $numericString .= (string)(ord($char) - ord('A') + 10);
            } else {
                $numericString .= $char;
            }
        }

        // MOD 97 Prüfung
        return bcmod($numericString, '97') === '1';
    }

    /**
     * Normalisiert eine Gläubiger-ID (entfernt Leerzeichen, Großbuchstaben).
     *
     * @param string $creditorId Die zu normalisierende Gläubiger-ID.
     * @return string Die normalisierte Gläubiger-ID.
     */
    public static function normalize(string $creditorId): string {
        // Entferne Leerzeichen und konvertiere zu Großbuchstaben
        return strtoupper(preg_replace('/\s+/', '', $creditorId) ?? $creditorId);
    }

    /**
     * Formatiert eine Gläubiger-ID in lesbarer Form.
     *
     * @param string $creditorId Die zu formatierende Gläubiger-ID.
     * @param string $separator Das Trennzeichen (Standard: Leerzeichen).
     * @return string Die formatierte Gläubiger-ID.
     */
    public static function format(string $creditorId, string $separator = ' '): string {
        $normalized = self::normalize($creditorId);

        // Gruppierung: CC PP BBB NNNNNNNNNN...
        $formatted = substr($normalized, 0, 4) . $separator .
            substr($normalized, 4, 3);

        // Rest in 4er-Gruppen
        $rest = substr($normalized, 7);
        if ($rest !== false && $rest !== '') {
            $chunks = str_split($rest, 4);
            $formatted .= $separator . implode($separator, $chunks);
        }

        return $formatted;
    }

    /**
     * Extrahiert den Ländercode aus einer Gläubiger-ID.
     *
     * @param string $creditorId Die Gläubiger-ID.
     * @return string|null Der Ländercode oder null.
     */
    public static function extractCountryCode(string $creditorId): ?string {
        $normalized = self::normalize($creditorId);

        if (strlen($normalized) < 2) {
            return null;
        }

        $code = substr($normalized, 0, 2);

        // Prüfe, ob es ein gültiger ISO-Ländercode ist
        if (!preg_match('/^[A-Z]{2}$/', $code)) {
            return null;
        }

        return $code;
    }

    /**
     * Extrahiert die Prüfziffern aus einer Gläubiger-ID.
     *
     * @param string $creditorId Die Gläubiger-ID.
     * @return string|null Die Prüfziffern oder null.
     */
    public static function extractCheckDigits(string $creditorId): ?string {
        $normalized = self::normalize($creditorId);

        if (strlen($normalized) < 4) {
            return null;
        }

        $digits = substr($normalized, 2, 2);

        // Prüfe, ob es Ziffern sind
        if (!preg_match('/^[0-9]{2}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    /**
     * Extrahiert den Geschäftsbereichscode aus einer Gläubiger-ID.
     *
     * Der Geschäftsbereich (3 Zeichen) kann frei vergeben werden und dient
     * der internen Unterscheidung verschiedener Geschäftsbereiche.
     *
     * @param string $creditorId Die Gläubiger-ID.
     * @return string|null Der Geschäftsbereichscode oder null.
     */
    public static function extractBusinessAreaCode(string $creditorId): ?string {
        $normalized = self::normalize($creditorId);

        if (strlen($normalized) < 7) {
            return null;
        }

        return substr($normalized, 4, 3);
    }

    /**
     * Extrahiert die nationale Kennung aus einer Gläubiger-ID.
     *
     * @param string $creditorId Die Gläubiger-ID.
     * @return string|null Die nationale Kennung oder null.
     */
    public static function extractNationalId(string $creditorId): ?string {
        $normalized = self::normalize($creditorId);

        if (strlen($normalized) < 8) {
            return null;
        }

        return substr($normalized, 7);
    }

    /**
     * Prüft, ob eine Gläubiger-ID zu einem bestimmten Land gehört.
     *
     * @param string $creditorId Die Gläubiger-ID.
     * @param CountryCode $country Das Land.
     * @return bool True, wenn die ID zum Land gehört.
     */
    public static function matchesCountry(string $creditorId, CountryCode $country): bool {
        $extractedCode = self::extractCountryCode($creditorId);

        if ($extractedCode === null) {
            return false;
        }

        return $extractedCode === $country->value;
    }

    /**
     * Prüft, ob eine deutsche Gläubiger-ID gültig ist.
     *
     * Format: DE + 2 Prüfziffern + 3 Geschäftsbereich + 11 nationale Kennung = 18 Zeichen
     *
     * @param string|null $creditorId Die zu prüfende Gläubiger-ID.
     * @return bool True, wenn die Gläubiger-ID gültig ist.
     */
    public static function isGermanCreditorId(?string $creditorId): bool {
        if ($creditorId === null || $creditorId === '') {
            return false;
        }

        $normalized = self::normalize($creditorId);

        // Deutsche Gläubiger-ID: DE + 2 Prüfziffern + 3 Geschäftsbereich + 11 nationale Kennung
        if (strlen($normalized) !== 18) {
            return false;
        }

        // Muss mit DE beginnen
        if (!str_starts_with($normalized, 'DE')) {
            return false;
        }

        // Format: DE[0-9]{2}[A-Z0-9]{3}[0-9]{11}
        if (!preg_match('/^DE[0-9]{2}[A-Z0-9]{3}[0-9]{11}$/', $normalized)) {
            return false;
        }

        return self::validateCreditorId($creditorId);
    }

    /**
     * Berechnet die Prüfziffern für eine Gläubiger-ID.
     *
     * @param string $countryCode Der Ländercode (2 Zeichen).
     * @param string $nationalId Die nationale Kennung.
     * @return string|null Die berechneten Prüfziffern oder null bei Fehler.
     */
    public static function calculateCheckDigits(string $countryCode, string $nationalId): ?string {
        $countryCode = strtoupper($countryCode);
        $nationalId = strtoupper(preg_replace('/\s+/', '', $nationalId) ?? $nationalId);

        if (strlen($countryCode) !== 2 || !preg_match('/^[A-Z]{2}$/', $countryCode)) {
            self::logError("Ungültiger Ländercode: {$countryCode}");
            return null;
        }

        if (strlen($nationalId) < 1) {
            self::logError("Nationale Kennung fehlt");
            return null;
        }

        // Berechnung: nationale ID + Ländercode + 00 → MOD 97
        $checkString = $nationalId . $countryCode . '00';

        // Buchstaben in Zahlen umwandeln
        $numericString = '';
        foreach (str_split($checkString) as $char) {
            if (ctype_alpha($char)) {
                $numericString .= (string)(ord($char) - ord('A') + 10);
            } else {
                $numericString .= $char;
            }
        }

        // Prüfziffer = 98 - (Zahl MOD 97)
        $remainder = bcmod($numericString, '97');
        $checkDigit = 98 - (int)$remainder;

        return str_pad((string)$checkDigit, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Generiert eine Gläubiger-ID.
     *
     * @param string $countryCode Der Ländercode (2 Zeichen).
     * @param string $businessArea Der Geschäftsbereich (3 Zeichen).
     * @param string $nationalId Die nationale Kennung.
     * @return string|null Die generierte Gläubiger-ID oder null bei Fehler.
     */
    public static function generate(string $countryCode, string $businessArea, string $nationalId): ?string {
        $countryCode = strtoupper($countryCode);
        $businessArea = strtoupper(preg_replace('/\s+/', '', $businessArea) ?? $businessArea);
        $nationalId = strtoupper(preg_replace('/\s+/', '', $nationalId) ?? $nationalId);

        // Geschäftsbereich auf 3 Zeichen auffüllen
        $businessArea = str_pad($businessArea, 3, '0', STR_PAD_LEFT);

        // Prüfziffern berechnen
        $checkDigits = self::calculateCheckDigits($countryCode, $nationalId);

        if ($checkDigits === null) {
            return null;
        }

        return $countryCode . $checkDigits . substr($businessArea, 0, 3) . $nationalId;
    }

    /**
     * Gibt die erwartete Länge für ein Land zurück.
     *
     * @param string $country Der Ländercode.
     * @return int|null Die erwartete Länge oder null.
     */
    public static function getExpectedLength(string $country): ?int {
        return self::CREDITOR_ID_LENGTHS[strtoupper($country)] ?? null;
    }
}
