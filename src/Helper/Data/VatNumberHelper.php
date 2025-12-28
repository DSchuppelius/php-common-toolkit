<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VatNumberHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Enums\CountryCode;
use ERRORToolkit\Traits\ErrorLog;

/**
 * Helper-Klasse für die Validierung von Umsatzsteuer-Identifikationsnummern (USt-ID / VAT-ID).
 *
 * Unterstützt Validierung für alle EU-Mitgliedsstaaten sowie einige Drittländer.
 * Implementiert sowohl Format- als auch Prüfsummenvalidierung, wo verfügbar.
 *
 * @package CommonToolkit\Helper\Data
 */
class VatNumberHelper {
    use ErrorLog;

    /**
     * Länderspezifische Regex-Muster für USt-ID-Formate.
     * Format: Ländercode => [Länge(n), Regex-Pattern]
     *
     * @var array<string, array{lengths: int[], pattern: string}>
     */
    private const VAT_PATTERNS = [
        // EU-Mitgliedsstaaten
        'AT' =>  ['lengths' => [9],                          'pattern' => '/^ATU[0-9]{8}$/'],                                    // Österreich
        'BE' =>  ['lengths' => [10],                         'pattern' => '/^BE[01][0-9]{9}$/'],                                 // Belgien
        'BG' =>  ['lengths' => [9, 10],                      'pattern' => '/^BG[0-9]{9,10}$/'],                                  // Bulgarien
        'CY' =>  ['lengths' => [9],                          'pattern' => '/^CY[0-9]{8}[A-Z]$/'],                                // Zypern
        'CZ' =>  ['lengths' => [8, 9, 10],                   'pattern' => '/^CZ[0-9]{8,10}$/'],                                  // Tschechien
        'DE' =>  ['lengths' => [9],                          'pattern' => '/^DE[0-9]{9}$/'],                                     // Deutschland
        'DK' =>  ['lengths' => [8],                          'pattern' => '/^DK[0-9]{8}$/'],                                     // Dänemark
        'EE' =>  ['lengths' => [9],                          'pattern' => '/^EE[0-9]{9}$/'],                                     // Estland
        'EL' =>  ['lengths' => [9],                          'pattern' => '/^EL[0-9]{9}$/'],                                     // Griechenland
        'ES' =>  ['lengths' => [9],                          'pattern' => '/^ES[A-Z0-9][0-9]{7}[A-Z0-9]$/'],                     // Spanien
        'FI' =>  ['lengths' => [8],                          'pattern' => '/^FI[0-9]{8}$/'],                                     // Finnland
        'FR' =>  ['lengths' => [11],                         'pattern' => '/^FR[A-Z0-9]{2}[0-9]{9}$/'],                          // Frankreich
        'HR' =>  ['lengths' => [11],                         'pattern' => '/^HR[0-9]{11}$/'],                                    // Kroatien
        'HU' =>  ['lengths' => [8],                          'pattern' => '/^HU[0-9]{8}$/'],                                     // Ungarn
        'IE' =>  ['lengths' => [8, 9],                       'pattern' => '/^IE([0-9]{7}[A-Z]{1,2}|[0-9][A-Z][0-9]{5}[A-Z])$/'], // Irland
        'IT' =>  ['lengths' => [11],                         'pattern' => '/^IT[0-9]{11}$/'],                                    // Italien
        'LT' =>  ['lengths' => [9, 12],                      'pattern' => '/^LT([0-9]{9}|[0-9]{12})$/'],                         // Litauen
        'LU' =>  ['lengths' => [8],                          'pattern' => '/^LU[0-9]{8}$/'],                                     // Luxemburg
        'LV' =>  ['lengths' => [11],                         'pattern' => '/^LV[0-9]{11}$/'],                                    // Lettland
        'MT' =>  ['lengths' => [8],                          'pattern' => '/^MT[0-9]{8}$/'],                                     // Malta
        'NL' =>  ['lengths' => [12],                         'pattern' => '/^NL[0-9]{9}B[0-9]{2}$/'],                            // Niederlande
        'PL' =>  ['lengths' => [10],                         'pattern' => '/^PL[0-9]{10}$/'],                                    // Polen
        'PT' =>  ['lengths' => [9],                          'pattern' => '/^PT[0-9]{9}$/'],                                     // Portugal
        'RO' =>  ['lengths' => [2, 3, 4, 5, 6, 7, 8, 9, 10], 'pattern' => '/^RO[0-9]{2,10}$/'],                                  // Rumänien
        'SE' =>  ['lengths' => [12],                         'pattern' => '/^SE[0-9]{12}$/'],                                    // Schweden
        'SI' =>  ['lengths' => [8],                          'pattern' => '/^SI[0-9]{8}$/'],                                     // Slowenien
        'SK' =>  ['lengths' => [10],                         'pattern' => '/^SK[0-9]{10}$/'],                                    // Slowakei

        // Nordirland (spezielle Regelung nach Brexit)
        'XI' =>  ['lengths' => [9, 12],                      'pattern' => '/^XI([0-9]{9}|[0-9]{12}|GD[0-9]{3}|HA[0-9]{3})$/'],   // Nordirland

        // Drittländer mit häufiger Verwendung
        'CHE' => ['lengths' => [9],                          'pattern' => '/^CHE[0-9]{9}(MWST|TVA|IVA)$/'],                      // Schweiz
        'GB' =>  ['lengths' => [9, 12],                      'pattern' => '/^GB([0-9]{9}|[0-9]{12}|GD[0-9]{3}|HA[0-9]{3})$/'],   // UK (historisch)
        'NO' =>  ['lengths' => [9],                          'pattern' => '/^NO[0-9]{9}MVA$/'],                                  // Norwegen
    ];

    /**
     * Prüft, ob eine USt-ID ein gültiges Format hat.
     *
     * @param string|null $vatId Die zu prüfende USt-ID.
     * @return bool True, wenn das Format gültig ist.
     */
    public static function isVatId(?string $vatId): bool {
        if ($vatId === null || $vatId === '') {
            return false;
        }

        $vatId = self::normalize($vatId);
        $countryCode = self::extractCountryCode($vatId);

        if ($countryCode === null || !isset(self::VAT_PATTERNS[$countryCode])) {
            return false;
        }

        return preg_match(self::VAT_PATTERNS[$countryCode]['pattern'], $vatId) === 1;
    }

    /**
     * Validiert eine USt-ID mit optionaler Prüfsummenvalidierung.
     *
     * @param string|null $vatId Die zu validierende USt-ID.
     * @param bool $strict Bei true wird auch die Prüfsumme validiert (falls verfügbar).
     * @return bool True, wenn die USt-ID gültig ist.
     */
    public static function validateVatId(?string $vatId, bool $strict = false): bool {
        if (!self::isVatId($vatId)) {
            return false;
        }

        if (!$strict) {
            return true;
        }

        $vatId = self::normalize($vatId);
        $countryCode = self::extractCountryCode($vatId);

        return match ($countryCode) {
            'DE' => self::validateDEVatId($vatId),
            'AT' => self::validateATVatId($vatId),
            'BE' => self::validateBEVatId($vatId),
            'NL' => self::validateNLVatId($vatId),
            'IT' => self::validateITVatId($vatId),
            'FR' => self::validateFRVatId($vatId),
            'ES' => self::validateESVatId($vatId),
            'PL' => self::validatePLVatId($vatId),
            'PT' => self::validatePTVatId($vatId),
            'FI' => self::validateFIVatId($vatId),
            'DK' => self::validateDKVatId($vatId),
            'LU' => self::validateLUVatId($vatId),
            'HU' => self::validateHUVatId($vatId),
            'SI' => self::validateSIVatId($vatId),
            default => true, // Keine Prüfsummenvalidierung verfügbar
        };
    }

    /**
     * Normalisiert eine USt-ID (entfernt Leerzeichen, Punkte, Bindestriche, konvertiert zu Großbuchstaben).
     *
     * @param string $vatId Die zu normalisierende USt-ID.
     * @return string Die normalisierte USt-ID.
     */
    public static function normalize(string $vatId): string {
        // Entferne Leerzeichen, Punkte und Bindestriche
        $vatId = preg_replace('/[\s.\-]/', '', $vatId);
        // Konvertiere zu Großbuchstaben
        return strtoupper($vatId);
    }

    /**
     * Extrahiert den Ländercode aus einer USt-ID.
     *
     * @param string $vatId Die USt-ID.
     * @return string|null Der Ländercode oder null bei ungültigem Format.
     */
    public static function extractCountryCode(string $vatId): ?string {
        if (strlen($vatId) < 2) {
            return null;
        }

        // Schweiz hat 3-Buchstaben-Präfix
        if (str_starts_with($vatId, 'CHE')) {
            return 'CHE';
        }

        $code = substr($vatId, 0, 2);

        // Prüfe ob gültiger Ländercode
        if (!preg_match('/^[A-Z]{2}$/', $code)) {
            return null;
        }

        return $code;
    }

    /**
     * Extrahiert die Nummer ohne Ländercode.
     *
     * @param string $vatId Die USt-ID.
     * @return string Die Nummer ohne Ländercode.
     */
    public static function extractNumber(string $vatId): string {
        $vatId = self::normalize($vatId);
        $countryCode = self::extractCountryCode($vatId);

        if ($countryCode === null) {
            return $vatId;
        }

        return substr($vatId, strlen($countryCode));
    }

    /**
     * Formatiert eine USt-ID mit Leerzeichen für bessere Lesbarkeit.
     *
     * @param string $vatId Die zu formatierende USt-ID.
     * @return string Die formatierte USt-ID.
     */
    public static function format(string $vatId): string {
        $vatId = self::normalize($vatId);
        $countryCode = self::extractCountryCode($vatId);

        if ($countryCode === null) {
            return $vatId;
        }

        $number = self::extractNumber($vatId);

        return match ($countryCode) {
            'DE' => $countryCode . ' ' . substr($number, 0, 3) . ' ' . substr($number, 3, 3) . ' ' . substr($number, 6),
            'AT' => $countryCode . ' ' . substr($number, 0, 1) . ' ' . substr($number, 1, 4) . ' ' . substr($number, 5),
            default => $countryCode . ' ' . $number,
        };
    }

    /**
     * Gibt das erwartete Ländercode-Muster für einen CountryCode zurück.
     *
     * @param CountryCode $country Das Land.
     * @return string|null Der USt-ID-Ländercode oder null wenn nicht unterstützt.
     */
    public static function getVatPrefix(CountryCode $country): ?string {
        return match ($country) {
            CountryCode::Austria                                       => 'AT',
            CountryCode::Belgium                                       => 'BE',
            CountryCode::Bulgaria                                      => 'BG',
            CountryCode::Croatia                                       => 'HR',
            CountryCode::Cyprus                                        => 'CY',
            CountryCode::Czechia                                       => 'CZ',
            CountryCode::Denmark                                       => 'DK',
            CountryCode::Estonia                                       => 'EE',
            CountryCode::Finland                                       => 'FI',
            CountryCode::France                                        => 'FR',
            CountryCode::Germany                                       => 'DE',
            CountryCode::Greece                                        => 'EL', // Griechenland verwendet EL statt GR
            CountryCode::Hungary                                       => 'HU',
            CountryCode::Ireland                                       => 'IE',
            CountryCode::Italy                                         => 'IT',
            CountryCode::Latvia                                        => 'LV',
            CountryCode::Lithuania                                     => 'LT',
            CountryCode::Luxembourg                                    => 'LU',
            CountryCode::Malta                                         => 'MT',
            CountryCode::Netherlands                                   => 'NL',
            CountryCode::Poland                                        => 'PL',
            CountryCode::Portugal                                      => 'PT',
            CountryCode::Romania                                       => 'RO',
            CountryCode::Slovakia                                      => 'SK',
            CountryCode::Slovenia                                      => 'SI',
            CountryCode::Spain                                         => 'ES',
            CountryCode::Sweden                                        => 'SE',
            CountryCode::Switzerland                                   => 'CHE',
            CountryCode::UnitedKingdomOfGreatBritainAndNorthernIreland => 'GB',
            CountryCode::Norway                                        => 'NO',
            default => null,
        };
    }

    /**
     * Gibt alle unterstützten Ländercodes zurück.
     *
     * @return array<string> Liste der unterstützten Ländercodes.
     */
    public static function getSupportedCountries(): array {
        return array_keys(self::VAT_PATTERNS);
    }

    // ========================================
    // Private Validierungsmethoden
    // ========================================

    /**
     * Validiert deutsche USt-ID mit Prüfziffer (Modulo 11).
     *
     * @param string $vatId Die normalisierte USt-ID.
     * @return bool True wenn gültig.
     */
    private static function validateDEVatId(string $vatId): bool {
        $number = substr($vatId, 2); // Entferne "DE"

        if (strlen($number) !== 9) {
            return false;
        }

        // Die erste Ziffer darf nicht 0 sein
        if ($number[0] === '0') {
            return false;
        }

        // Modulo 11 Prüfsummenberechnung
        $product = 10;

        for ($i = 0; $i < 8; $i++) {
            $sum = ((int)$number[$i] + $product) % 10;
            if ($sum === 0) {
                $sum = 10;
            }
            $product = (2 * $sum) % 11;
        }

        $checkDigit = 11 - $product;
        if ($checkDigit === 10) {
            $checkDigit = 0;
        }

        return (int)$number[8] === $checkDigit;
    }

    /**
     * Validiert österreichische USt-ID (ATU + 8 Ziffern mit Prüfziffer).
     *
     * @param string $vatId Die normalisierte USt-ID.
     * @return bool True wenn gültig.
     */
    private static function validateATVatId(string $vatId): bool {
        $number = substr($vatId, 3); // Entferne "ATU"

        if (strlen($number) !== 8) {
            return false;
        }

        $weights = [1, 2, 1, 2, 1, 2, 1];
        $sum = 0;

        for ($i = 0; $i < 7; $i++) {
            $digit = (int)$number[$i] * $weights[$i];
            $sum += ($digit > 9) ? $digit - 9 : $digit;
        }

        $checkDigit = (10 - (($sum + 4) % 10)) % 10;

        return (int)$number[7] === $checkDigit;
    }

    /**
     * Validiert belgische USt-ID (Modulo 97).
     *
     * @param string $vatId Die normalisierte USt-ID.
     * @return bool True wenn gültig.
     */
    private static function validateBEVatId(string $vatId): bool {
        $number = substr($vatId, 2); // Entferne "BE"

        if (strlen($number) !== 10) {
            return false;
        }

        $base = (int)substr($number, 0, 8);
        $checkDigits = (int)substr($number, 8, 2);

        return $checkDigits === (97 - ($base % 97));
    }

    /**
     * Validiert niederländische USt-ID.
     *
     * @param string $vatId Die normalisierte USt-ID.
     * @return bool True wenn gültig.
     */
    private static function validateNLVatId(string $vatId): bool {
        $number = substr($vatId, 2); // Entferne "NL"

        if (strlen($number) !== 12) {
            return false;
        }

        // Format: 9 Ziffern + B + 2 Ziffern
        if ($number[9] !== 'B') {
            return false;
        }

        // Modulo 11 für die ersten 8 Ziffern
        $weights = [9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;

        for ($i = 0; $i < 8; $i++) {
            $sum += (int)$number[$i] * $weights[$i];
        }

        $checkDigit = $sum % 11;
        if ($checkDigit > 9) {
            return false;
        }

        return (int)$number[8] === $checkDigit;
    }

    /**
     * Validiert italienische USt-ID (Luhn-Algorithmus).
     *
     * @param string $vatId Die normalisierte USt-ID.
     * @return bool True wenn gültig.
     */
    private static function validateITVatId(string $vatId): bool {
        $number = substr($vatId, 2); // Entferne "IT"

        if (strlen($number) !== 11) {
            return false;
        }

        // Erste 7 Ziffern: Unternehmensidentifikation
        // Ziffern 8-10: Provinzcode oder 888 für Ausländer
        // Letzte Ziffer: Prüfziffer

        $sumOdd = 0;
        $sumEven = 0;

        for ($i = 0; $i < 10; $i++) {
            $digit = (int)$number[$i];
            if ($i % 2 === 0) {
                $sumOdd += $digit;
            } else {
                $doubled = $digit * 2;
                $sumEven += ($doubled > 9) ? $doubled - 9 : $doubled;
            }
        }

        $checkDigit = (10 - (($sumOdd + $sumEven) % 10)) % 10;

        return (int)$number[10] === $checkDigit;
    }

    /**
     * Validiert französische USt-ID (Modulo 97).
     *
     * @param string $vatId Die normalisierte USt-ID.
     * @return bool True wenn gültig.
     */
    private static function validateFRVatId(string $vatId): bool {
        $number = substr($vatId, 2); // Entferne "FR"

        if (strlen($number) !== 11) {
            return false;
        }

        $key = substr($number, 0, 2);
        $siren = substr($number, 2);

        // Wenn der Key nur Zahlen enthält
        if (ctype_digit($key)) {
            $computed = ((int)$siren * 100 + 12) % 97;
            return (int)$key === $computed;
        }

        // Erweiterte Prüfung für alphanumerische Keys
        return true; // Format-Check ist bereits erfolgt
    }

    /**
     * Validiert spanische USt-ID (NIF/CIF).
     *
     * @param string $vatId Die normalisierte USt-ID.
     * @return bool True wenn gültig.
     */
    private static function validateESVatId(string $vatId): bool {
        $number = substr($vatId, 2); // Entferne "ES"

        if (strlen($number) !== 9) {
            return false;
        }

        $firstChar = $number[0];
        $lastChar = $number[8];

        // Juristische Personen (CIF)
        if (preg_match('/[ABCDEFGHJNPQRSUVW]/', $firstChar)) {
            $digits = substr($number, 1, 7);
            $sumOdd = 0;
            $sumEven = 0;

            for ($i = 0; $i < 7; $i++) {
                $digit = (int)$digits[$i];
                if ($i % 2 === 0) {
                    $doubled = $digit * 2;
                    $sumOdd += (int)($doubled / 10) + ($doubled % 10);
                } else {
                    $sumEven += $digit;
                }
            }

            $control = (10 - (($sumOdd + $sumEven) % 10)) % 10;
            $controlLetter = chr(64 + $control + 1);

            if (preg_match('/[PQRSW]/', $firstChar)) {
                return $lastChar === $controlLetter;
            } elseif (preg_match('/[ABEH]/', $firstChar)) {
                return $lastChar === (string)$control;
            } else {
                return $lastChar === (string)$control || $lastChar === $controlLetter;
            }
        }

        // Natürliche Personen (NIF) - vereinfachte Prüfung
        return true;
    }

    /**
     * Validiert polnische USt-ID (NIP).
     *
     * @param string $vatId Die normalisierte USt-ID.
     * @return bool True wenn gültig.
     */
    private static function validatePLVatId(string $vatId): bool {
        $number = substr($vatId, 2); // Entferne "PL"

        if (strlen($number) !== 10) {
            return false;
        }

        $weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$number[$i] * $weights[$i];
        }

        $checkDigit = $sum % 11;
        if ($checkDigit === 10) {
            return false; // Ungültige Prüfziffer
        }

        return (int)$number[9] === $checkDigit;
    }

    /**
     * Validiert portugiesische USt-ID (NIF).
     *
     * @param string $vatId Die normalisierte USt-ID.
     * @return bool True wenn gültig.
     */
    private static function validatePTVatId(string $vatId): bool {
        $number = substr($vatId, 2); // Entferne "PT"

        if (strlen($number) !== 9) {
            return false;
        }

        $weights = [9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;

        for ($i = 0; $i < 8; $i++) {
            $sum += (int)$number[$i] * $weights[$i];
        }

        $checkDigit = 11 - ($sum % 11);
        if ($checkDigit >= 10) {
            $checkDigit = 0;
        }

        return (int)$number[8] === $checkDigit;
    }

    /**
     * Validiert finnische USt-ID (Y-tunnus).
     *
     * @param string $vatId Die normalisierte USt-ID.
     * @return bool True wenn gültig.
     */
    private static function validateFIVatId(string $vatId): bool {
        $number = substr($vatId, 2); // Entferne "FI"

        if (strlen($number) !== 8) {
            return false;
        }

        $weights = [7, 9, 10, 5, 8, 4, 2];
        $sum = 0;

        for ($i = 0; $i < 7; $i++) {
            $sum += (int)$number[$i] * $weights[$i];
        }

        $checkDigit = 11 - ($sum % 11);
        if ($checkDigit === 11) {
            $checkDigit = 0;
        } elseif ($checkDigit === 10) {
            return false;
        }

        return (int)$number[7] === $checkDigit;
    }

    /**
     * Validiert dänische USt-ID (CVR-nummer).
     *
     * @param string $vatId Die normalisierte USt-ID.
     * @return bool True wenn gültig.
     */
    private static function validateDKVatId(string $vatId): bool {
        $number = substr($vatId, 2); // Entferne "DK"

        if (strlen($number) !== 8) {
            return false;
        }

        $weights = [2, 7, 6, 5, 4, 3, 2, 1];
        $sum = 0;

        for ($i = 0; $i < 8; $i++) {
            $sum += (int)$number[$i] * $weights[$i];
        }

        return $sum % 11 === 0;
    }

    /**
     * Validiert luxemburgische USt-ID.
     *
     * @param string $vatId Die normalisierte USt-ID.
     * @return bool True wenn gültig.
     */
    private static function validateLUVatId(string $vatId): bool {
        $number = substr($vatId, 2); // Entferne "LU"

        if (strlen($number) !== 8) {
            return false;
        }

        $base = (int)substr($number, 0, 6);
        $checkDigits = (int)substr($number, 6, 2);

        return $checkDigits === ($base % 89);
    }

    /**
     * Validiert ungarische USt-ID.
     *
     * @param string $vatId Die normalisierte USt-ID.
     * @return bool True wenn gültig.
     */
    private static function validateHUVatId(string $vatId): bool {
        $number = substr($vatId, 2); // Entferne "HU"

        if (strlen($number) !== 8) {
            return false;
        }

        $weights = [9, 7, 3, 1, 9, 7, 3];
        $sum = 0;

        for ($i = 0; $i < 7; $i++) {
            $sum += (int)$number[$i] * $weights[$i];
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return (int)$number[7] === $checkDigit;
    }

    /**
     * Validiert slowenische USt-ID.
     *
     * @param string $vatId Die normalisierte USt-ID.
     * @return bool True wenn gültig.
     */
    private static function validateSIVatId(string $vatId): bool {
        $number = substr($vatId, 2); // Entferne "SI"

        if (strlen($number) !== 8) {
            return false;
        }

        $weights = [8, 7, 6, 5, 4, 3, 2];
        $sum = 0;

        for ($i = 0; $i < 7; $i++) {
            $sum += (int)$number[$i] * $weights[$i];
        }

        $checkDigit = 11 - ($sum % 11);
        if ($checkDigit === 10) {
            $checkDigit = 0;
        } elseif ($checkDigit === 11) {
            return false;
        }

        return (int)$number[7] === $checkDigit;
    }
}
