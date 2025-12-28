<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PhoneNumberHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Enums\CountryCode;
use ERRORToolkit\Traits\ErrorLog;

/**
 * Helper-Klasse für die Validierung und Formatierung von Telefonnummern.
 *
 * Unterstützt:
 * - E.164 Format (internationales Format)
 * - Deutsche Telefonnummern
 * - Internationale Telefonnummern
 * - Mobilfunknummern
 *
 * @package CommonToolkit\Helper\Data
 */
class PhoneNumberHelper {
    use ErrorLog;

    /**
     * Internationale Ländervorwahlen.
     *
     * @var array<string, string>
     */
    private const COUNTRY_CODES = [
        'DE' => '49',   // Deutschland
        'AT' => '43',   // Österreich
        'CH' => '41',   // Schweiz
        'FR' => '33',   // Frankreich
        'IT' => '39',   // Italien
        'ES' => '34',   // Spanien
        'NL' => '31',   // Niederlande
        'BE' => '32',   // Belgien
        'PL' => '48',   // Polen
        'GB' => '44',   // Großbritannien
        'US' => '1',    // USA
        'LU' => '352',  // Luxemburg
        'LI' => '423',  // Liechtenstein
        'CZ' => '420',  // Tschechien
        'DK' => '45',   // Dänemark
        'SE' => '46',   // Schweden
        'NO' => '47',   // Norwegen
        'FI' => '358',  // Finnland
    ];

    /**
     * Deutsche Mobilfunk-Vorwahlen.
     *
     * @var array<string>
     */
    private const GERMAN_MOBILE_PREFIXES = [
        '151',
        '152',
        '153',
        '155',
        '156',
        '157',
        '159', // Telekom
        '160',
        '162',
        '163',
        '170',
        '171',
        '172',
        '173',
        '174',
        '175', // Vodafone
        '176',
        '177',
        '178',
        '179', // O2/E-Plus
        '15',
        '16',
        '17', // Kurzform
    ];

    /**
     * Prüft, ob eine Telefonnummer im E.164 Format vorliegt.
     *
     * E.164: + gefolgt von max. 15 Ziffern (inkl. Ländervorwahl)
     * Minimum: Ländervorwahl (1-3 Ziffern) + mindestens 4 Ziffern Teilnehmernummer
     *
     * @param string|null $phone Die zu prüfende Telefonnummer.
     * @return bool True, wenn das Format gültig ist.
     */
    public static function isE164(?string $phone): bool {
        if ($phone === null || $phone === '') {
            return false;
        }

        // E.164: +[Ländervorwahl][Nummer], min 7 Ziffern (z.B. +1234567), max 15 Ziffern
        return preg_match('/^\+[1-9][0-9]{6,14}$/', $phone) === 1;
    }

    /**
     * Prüft, ob eine Telefonnummer ein gültiges Format hat.
     *
     * Akzeptiert verschiedene Formate (national und international).
     *
     * @param string|null $phone Die zu prüfende Telefonnummer.
     * @return bool True, wenn das Format grundsätzlich gültig ist.
     */
    public static function isPhoneNumber(?string $phone): bool {
        if ($phone === null || $phone === '') {
            return false;
        }

        $normalized = self::normalize($phone);

        // Mindestens 3 Ziffern
        if (strlen($normalized) < 3) {
            return false;
        }

        // Maximal 15 Ziffern (E.164 Limit)
        if (strlen($normalized) > 15) {
            return false;
        }

        return true;
    }

    /**
     * Prüft, ob eine deutsche Telefonnummer gültig ist.
     *
     * @param string|null $phone Die zu prüfende Telefonnummer.
     * @return bool True, wenn es eine gültige deutsche Nummer ist.
     */
    public static function isGermanPhoneNumber(?string $phone): bool {
        if ($phone === null || $phone === '') {
            return false;
        }

        $normalized = self::normalize($phone);

        // Deutsche Nummern: 10-12 Ziffern ohne Ländervorwahl, 11-14 mit
        if (strlen($normalized) < 10 || strlen($normalized) > 14) {
            return false;
        }

        // Prüfe auf deutsche Vorwahl
        if (str_starts_with($normalized, '49')) {
            // Mit Ländervorwahl
            $national = '0' . substr($normalized, 2);
            return self::isValidGermanNationalNumber($national);
        } elseif (str_starts_with($normalized, '0')) {
            // Nationale Vorwahl
            return self::isValidGermanNationalNumber($normalized);
        }

        return false;
    }

    /**
     * Prüft, ob eine deutsche Mobilfunknummer vorliegt.
     *
     * @param string|null $phone Die zu prüfende Telefonnummer.
     * @return bool True, wenn es eine deutsche Mobilfunknummer ist.
     */
    public static function isGermanMobileNumber(?string $phone): bool {
        if (!self::isGermanPhoneNumber($phone)) {
            return false;
        }

        $normalized = self::normalize($phone);

        // Entferne Ländervorwahl falls vorhanden
        if (str_starts_with($normalized, '49')) {
            $normalized = '0' . substr($normalized, 2);
        }

        // Prüfe auf Mobilfunk-Vorwahl
        foreach (self::GERMAN_MOBILE_PREFIXES as $prefix) {
            if (str_starts_with($normalized, '0' . $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalisiert eine Telefonnummer (entfernt alle nicht-numerischen Zeichen außer +).
     *
     * @param string $phone Die zu normalisierende Telefonnummer.
     * @return string Die normalisierte Nummer (nur Ziffern).
     */
    public static function normalize(string $phone): string {
        // Entferne alles außer Ziffern
        $normalized = preg_replace('/[^0-9]/', '', $phone);

        // Wenn mit 00 beginnend, ersetze durch nichts (Ländervorwahl folgt)
        if (str_starts_with($normalized, '00')) {
            $normalized = substr($normalized, 2);
        }

        return $normalized;
    }

    /**
     * Konvertiert eine Telefonnummer ins E.164 Format.
     *
     * @param string $phone Die Telefonnummer.
     * @param string $defaultCountry Der Standard-Ländercode (z.B. 'DE').
     * @return string|null Die Nummer im E.164 Format oder null bei Fehler.
     */
    public static function toE164(string $phone, string $defaultCountry = 'DE'): ?string {
        // Wenn bereits E.164
        if (self::isE164($phone)) {
            return $phone;
        }

        $phone = trim($phone);

        // Wenn mit + beginnend, normalisiere
        if (str_starts_with($phone, '+')) {
            $normalized = '+' . preg_replace('/[^0-9]/', '', substr($phone, 1));
            return self::isE164($normalized) ? $normalized : null;
        }

        $normalized = self::normalize($phone);

        // Wenn mit 00 begann (wurde bereits entfernt in normalize)
        if (str_starts_with($phone, '00')) {
            $result = '+' . $normalized;
            return self::isE164($result) ? $result : null;
        }

        // Nationale Nummer - füge Ländervorwahl hinzu
        if (!isset(self::COUNTRY_CODES[$defaultCountry])) {
            self::logError("Unbekannter Ländercode: {$defaultCountry}");
            return null;
        }

        $countryCode = self::COUNTRY_CODES[$defaultCountry];

        // Entferne führende 0 bei nationaler Vorwahl
        if (str_starts_with($normalized, '0')) {
            $normalized = substr($normalized, 1);
        }

        $result = '+' . $countryCode . $normalized;

        return self::isE164($result) ? $result : null;
    }

    /**
     * Formatiert eine Telefonnummer für die Anzeige.
     *
     * @param string $phone Die Telefonnummer.
     * @param string $format Das Ausgabeformat ('national', 'international', 'e164').
     * @param string $defaultCountry Der Standard-Ländercode.
     * @return string Die formatierte Nummer.
     */
    public static function format(string $phone, string $format = 'international', string $defaultCountry = 'DE'): string {
        $e164 = self::toE164($phone, $defaultCountry);

        if ($e164 === null) {
            return $phone; // Unverändert zurückgeben wenn Konvertierung fehlschlägt
        }

        return match ($format) {
            'e164' => $e164,
            'national' => self::formatNational($e164, $defaultCountry),
            'international' => self::formatInternational($e164),
            default => $e164,
        };
    }

    /**
     * Formatiert eine E.164 Nummer im internationalen Format.
     *
     * @param string $e164 Die E.164 Nummer.
     * @return string Die formatierte Nummer (z.B. "+49 30 12345678").
     */
    public static function formatInternational(string $e164): string {
        if (!self::isE164($e164)) {
            return $e164;
        }

        // Entferne +
        $number = substr($e164, 1);

        // Ermittle Ländervorwahl
        foreach (self::COUNTRY_CODES as $country => $code) {
            if (str_starts_with($number, $code)) {
                $national = substr($number, strlen($code));

                // Spezielle Formatierung für Deutschland
                if ($country === 'DE') {
                    return self::formatGermanInternational($code, $national);
                }

                // Standard-Formatierung
                return '+' . $code . ' ' . $national;
            }
        }

        return $e164;
    }

    /**
     * Formatiert eine E.164 Nummer im nationalen Format.
     *
     * @param string $e164 Die E.164 Nummer.
     * @param string $country Der Ländercode.
     * @return string Die formatierte nationale Nummer.
     */
    public static function formatNational(string $e164, string $country = 'DE'): string {
        if (!self::isE164($e164)) {
            return $e164;
        }

        $countryCode = self::COUNTRY_CODES[$country] ?? null;
        if ($countryCode === null) {
            return $e164;
        }

        $number = substr($e164, 1);

        if (!str_starts_with($number, $countryCode)) {
            return $e164; // Andere Ländervorwahl
        }

        $national = substr($number, strlen($countryCode));

        // Formatiere als nationale Nummer
        if ($country === 'DE') {
            return self::formatGermanNational($national);
        }

        return '0' . $national;
    }

    /**
     * Extrahiert die Ländervorwahl aus einer E.164 Nummer.
     *
     * @param string $e164 Die E.164 Nummer.
     * @return string|null Die Ländervorwahl oder null.
     */
    public static function extractCountryCode(string $e164): ?string {
        if (!self::isE164($e164)) {
            return null;
        }

        $number = substr($e164, 1);

        // Prüfe auf bekannte Ländervorwahlen (längste zuerst)
        $codes = self::COUNTRY_CODES;
        uasort($codes, fn($a, $b) => strlen($b) <=> strlen($a));

        foreach ($codes as $country => $code) {
            if (str_starts_with($number, $code)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Extrahiert den Ländercode (ISO 3166-1 alpha-2) aus einer E.164 Nummer.
     *
     * @param string $e164 Die E.164 Nummer.
     * @return string|null Der Ländercode oder null.
     */
    public static function extractCountry(string $e164): ?string {
        if (!self::isE164($e164)) {
            return null;
        }

        $number = substr($e164, 1);

        // Prüfe auf bekannte Ländervorwahlen (längste zuerst)
        $codes = self::COUNTRY_CODES;
        uasort($codes, fn($a, $b) => strlen($b) <=> strlen($a));

        foreach ($codes as $country => $code) {
            if (str_starts_with($number, $code)) {
                return $country;
            }
        }

        return null;
    }

    /**
     * Gibt alle unterstützten Länder mit Vorwahlen zurück.
     *
     * @return array<string, string> Ländercode => Vorwahl
     */
    public static function getSupportedCountries(): array {
        return self::COUNTRY_CODES;
    }

    /**
     * Gibt die Vorwahl für ein Land zurück.
     *
     * @param CountryCode $country Das Land.
     * @return string|null Die Vorwahl oder null.
     */
    public static function getCountryCallingCode(CountryCode $country): ?string {
        return self::COUNTRY_CODES[$country->value] ?? null;
    }

    /**
     * Prüft, ob eine Telefonnummer zu einem bestimmten Land gehört.
     *
     * @param string $phone Die Telefonnummer.
     * @param CountryCode $country Das Land.
     * @return bool True, wenn die Nummer zum Land gehört.
     */
    public static function matchesCountry(string $phone, CountryCode $country): bool {
        $e164 = self::toE164($phone, $country->value);

        if ($e164 === null) {
            return false;
        }

        $extractedCountry = self::extractCountry($e164);

        return $extractedCountry === $country->value;
    }

    /**
     * Extrahiert das Land als CountryCode-Enum aus einer E.164 Nummer.
     *
     * @param string $e164 Die E.164 Nummer.
     * @return CountryCode|null Das Land oder null.
     */
    public static function extractCountryEnum(string $e164): ?CountryCode {
        $countryCode = self::extractCountry($e164);

        if ($countryCode === null) {
            return null;
        }

        return CountryCode::tryFrom($countryCode);
    }

    /**
     * Konvertiert eine Telefonnummer ins E.164 Format mit CountryCode-Enum.
     *
     * @param string $phone Die Telefonnummer.
     * @param CountryCode $defaultCountry Das Standard-Land.
     * @return string|null Die Nummer im E.164 Format oder null bei Fehler.
     */
    public static function toE164WithCountryCode(string $phone, CountryCode $defaultCountry): ?string {
        return self::toE164($phone, $defaultCountry->value);
    }

    /**
     * Formatiert eine Telefonnummer für die Anzeige mit CountryCode-Enum.
     *
     * @param string $phone Die Telefonnummer.
     * @param string $format Das Ausgabeformat ('national', 'international', 'e164').
     * @param CountryCode $defaultCountry Das Standard-Land.
     * @return string Die formatierte Nummer.
     */
    public static function formatWithCountryCode(string $phone, string $format, CountryCode $defaultCountry): string {
        return self::format($phone, $format, $defaultCountry->value);
    }

    // ========================================
    // Private Methoden
    // ========================================

    /**
     * Prüft, ob eine deutsche nationale Nummer gültig ist.
     *
     * @param string $national Die nationale Nummer (mit führender 0).
     * @return bool True wenn gültig.
     */
    private static function isValidGermanNationalNumber(string $national): bool {
        // Muss mit 0 beginnen
        if (!str_starts_with($national, '0')) {
            return false;
        }

        // Deutsche Nummern haben 10-12 Ziffern (inkl. führender 0)
        // Festnetz: 10-11 Ziffern, Mobil: 11-12 Ziffern
        $length = strlen($national);

        return $length >= 10 && $length <= 12;
    }

    /**
     * Formatiert eine deutsche Nummer im internationalen Format.
     *
     * @param string $countryCode Die Ländervorwahl.
     * @param string $national Die nationale Nummer.
     * @return string Die formatierte Nummer.
     */
    private static function formatGermanInternational(string $countryCode, string $national): string {
        $length = strlen($national);

        // Versuche Ortsvorwahl zu erkennen (2-5 Ziffern)
        // Vereinfachte Logik: Erste 2-4 Ziffern als Vorwahl
        if ($length >= 9) {
            // Mobilfunk (3-stellige Vorwahl)
            if (preg_match('/^1[567][0-9]/', $national)) {
                return '+' . $countryCode . ' ' .
                    substr($national, 0, 3) . ' ' .
                    substr($national, 3);
            }

            // Berlin (30), Hamburg (40), etc. (2-stellige Vorwahl)
            if (preg_match('/^[234][0-9]/', $national)) {
                return '+' . $countryCode . ' ' .
                    substr($national, 0, 2) . ' ' .
                    substr($national, 2);
            }

            // Andere (3-4-stellige Vorwahl)
            return '+' . $countryCode . ' ' .
                substr($national, 0, 3) . ' ' .
                substr($national, 3);
        }

        return '+' . $countryCode . ' ' . $national;
    }

    /**
     * Formatiert eine deutsche nationale Nummer.
     *
     * @param string $national Die nationale Nummer (ohne führende 0).
     * @return string Die formatierte Nummer.
     */
    private static function formatGermanNational(string $national): string {
        $length = strlen($national);

        if ($length >= 9) {
            // Mobilfunk
            if (preg_match('/^1[567][0-9]/', $national)) {
                return '0' . substr($national, 0, 3) . ' ' . substr($national, 3);
            }

            // Großstädte
            if (preg_match('/^[234][0-9]/', $national)) {
                return '0' . substr($national, 0, 2) . ' ' . substr($national, 2);
            }

            // Andere
            return '0' . substr($national, 0, 3) . ' ' . substr($national, 3);
        }

        return '0' . $national;
    }
}
