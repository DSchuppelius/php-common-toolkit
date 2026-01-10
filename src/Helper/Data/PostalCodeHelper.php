<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PostalCodeHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Enums\CountryCode;
use ERRORToolkit\Traits\ErrorLog;

/**
 * Helper-Klasse für die Validierung von Postleitzahlen.
 *
 * Unterstützt Validierung für verschiedene Länder mit länderspezifischen Formaten.
 *
 * @package CommonToolkit\Helper\Data
 */
class PostalCodeHelper {
    use ErrorLog;

    /**
     * Länderspezifische Postleitzahlformate.
     * Format: Ländercode => [Länge(n), Regex-Pattern, Beschreibung]
     *
     * @var array<string, array{length: int|array<int>, pattern: string, format: string}>
     */
    private const POSTAL_CODE_FORMATS = [
        // DACH-Länder
        'DE' => ['length' => 5,            'pattern' => '/^[0-9]{5}$/',                                  'format' => '5 Ziffern'],
        'AT' => ['length' => 4,            'pattern' => '/^[0-9]{4}$/',                                  'format' => '4 Ziffern'],
        'CH' => ['length' => 4,            'pattern' => '/^[0-9]{4}$/',                                  'format' => '4 Ziffern'],

        // Westeuropa
        'FR' => ['length' => 5,            'pattern' => '/^[0-9]{5}$/',                                  'format' => '5 Ziffern'],
        'BE' => ['length' => 4,            'pattern' => '/^[0-9]{4}$/',                                  'format' => '4 Ziffern'],
        'NL' => ['length' => [6, 7],       'pattern' => '/^[0-9]{4}\s?[A-Z]{2}$/',                       'format' => '1234 AB'],
        'LU' => ['length' => 4,            'pattern' => '/^[0-9]{4}$/',                                  'format' => '4 Ziffern'],

        // Nordeuropa
        'DK' => ['length' => 4,            'pattern' => '/^[0-9]{4}$/',                                  'format' => '4 Ziffern'],
        'SE' => ['length' => [5, 6],       'pattern' => '/^[0-9]{3}\s?[0-9]{2}$/',                       'format' => '123 45'],
        'NO' => ['length' => 4,            'pattern' => '/^[0-9]{4}$/',                                  'format' => '4 Ziffern'],
        'FI' => ['length' => 5,            'pattern' => '/^[0-9]{5}$/',                                  'format' => '5 Ziffern'],

        // Südeuropa
        'IT' => ['length' => 5,            'pattern' => '/^[0-9]{5}$/',                                  'format' => '5 Ziffern'],
        'ES' => ['length' => 5,            'pattern' => '/^[0-9]{5}$/',                                  'format' => '5 Ziffern'],
        'PT' => ['length' => [4, 7, 8],    'pattern' => '/^[0-9]{4}(-[0-9]{3})?$/',                      'format' => '1234 oder 1234-567'],

        // Osteuropa
        'PL' => ['length' => [5, 6],       'pattern' => '/^[0-9]{2}-?[0-9]{3}$/',                        'format' => '12-345'],
        'CZ' => ['length' => [5, 6],       'pattern' => '/^[0-9]{3}\s?[0-9]{2}$/',                       'format' => '123 45'],
        'HU' => ['length' => 4,            'pattern' => '/^[0-9]{4}$/',                                  'format' => '4 Ziffern'],

        // Großbritannien & Irland
        'GB' => ['length' => [5, 6, 7, 8], 'pattern' => '/^[A-Z]{1,2}[0-9][0-9A-Z]?\s?[0-9][A-Z]{2}$/i', 'format' => 'AA9A 9AA'],
        'IE' => ['length' => [7, 8],       'pattern' => '/^[A-Z][0-9]{2}\s?[A-Z0-9]{4}$/i',              'format' => 'A65 F4E2'],

        // Andere
        'US' => ['length' => [5, 10],      'pattern' => '/^[0-9]{5}(-[0-9]{4})?$/',                      'format' => '12345 oder 12345-6789'],
        'CA' => ['length' => [6, 7],       'pattern' => '/^[A-Z][0-9][A-Z]\s?[0-9][A-Z][0-9]$/i',        'format' => 'A1A 1A1'],
    ];

    /**
     * Deutsche Bundesländer nach PLZ-Bereichen.
     *
     * @var array<string, array<int, int>>
     */
    private const GERMAN_PLZ_REGIONS = [
        // Reihenfolge ist wichtig bei Überlappungen!
        'HH' => [[20000, 21149], [22000, 22769]], // Hamburg (vor SH wegen Überlappung)
        'SH' => [[21150, 25999]], // Schleswig-Holstein (ohne Hamburg-Bereich)
        'NI' => [[21200, 21449], [26000, 27809], [28200, 29999], [30000, 31999], [37000, 37199], [38000, 38729], [49000, 49849]], // Niedersachsen
        'HB' => [[27500, 28199]], // Bremen
        'NW' => [[32000, 33829], [40000, 48999], [50000, 53999], [57000, 59999]], // Nordrhein-Westfalen
        'HE' => [[34000, 36399], [55200, 55299], [60000, 65999], [68500, 68549]], // Hessen
        'RP' => [[54000, 56999], [55000, 55199], [55300, 55599], [66500, 67999]], // Rheinland-Pfalz
        'BW' => [[68000, 68499], [68550, 69259], [70000, 79999], [88000, 89999]], // Baden-Württemberg
        'BY' => [[63000, 63939], [80000, 87999], [90000, 96999]], // Bayern
        'SL' => [[66000, 66459]], // Saarland
        'BE' => [[10000, 14199]], // Berlin
        'BB' => [[1900, 1999], [3000, 3299], [4890, 4899], [14400, 16949], [17200, 17299], [19300, 19399]], // Brandenburg
        'MV' => [[17000, 17199], [17300, 19299]], // Mecklenburg-Vorpommern
        'SN' => [[1000, 1899], [2600, 2999], [4000, 4889], [7900, 9669]], // Sachsen
        'ST' => [[6000, 6999], [38800, 39999]], // Sachsen-Anhalt
        'TH' => [[4600, 4639], [7300, 7899], [98000, 99999]], // Thüringen
    ];

    /**
     * Prüft, ob eine Postleitzahl für ein bestimmtes Land gültig ist.
     *
     * @param string|null $postalCode Die zu prüfende Postleitzahl.
     * @param string $country Der Ländercode (ISO 3166-1 alpha-2).
     * @return bool True, wenn die Postleitzahl gültig ist.
     */
    public static function isValid(?string $postalCode, string $country = 'DE'): bool {
        if ($postalCode === null || $postalCode === '') {
            return false;
        }

        $country = strtoupper($country);

        if (!isset(self::POSTAL_CODE_FORMATS[$country])) {
            self::logWarning("Keine Postleitzahl-Validierung für Land: {$country}");
            // Fallback: Mindestens 3 alphanumerische Zeichen
            return preg_match('/^[A-Z0-9]{3,10}$/i', $postalCode) === 1;
        }

        $format = self::POSTAL_CODE_FORMATS[$country];

        return preg_match($format['pattern'], $postalCode) === 1;
    }

    /**
     * Prüft, ob eine deutsche Postleitzahl gültig ist.
     *
     * @param string|null $postalCode Die zu prüfende Postleitzahl.
     * @return bool True, wenn die Postleitzahl gültig ist.
     */
    public static function isGermanPostalCode(?string $postalCode): bool {
        return self::isValid($postalCode, 'DE');
    }

    /**
     * Normalisiert eine Postleitzahl (entfernt Leerzeichen, konvertiert zu Großbuchstaben).
     *
     * @param string $postalCode Die zu normalisierende Postleitzahl.
     * @param string $country Der Ländercode.
     * @return string Die normalisierte Postleitzahl.
     */
    public static function normalize(string $postalCode, string $country = 'DE'): string {
        $postalCode = trim($postalCode);
        $country = strtoupper($country);

        // Länderspezifische Normalisierung
        return match ($country) {
            'NL', 'GB', 'IE', 'CA' => strtoupper($postalCode), // Buchstaben in Großbuchstaben
            'PL' => preg_replace('/[^0-9]/', '', $postalCode) !== null
                ? substr_replace(preg_replace('/[^0-9]/', '', $postalCode), '-', 2, 0)
                : $postalCode, // 12-345 Format
            default => $postalCode,
        };
    }

    /**
     * Formatiert eine Postleitzahl im länderspezifischen Format.
     *
     * @param string $postalCode Die zu formatierende Postleitzahl.
     * @param string $country Der Ländercode.
     * @return string Die formatierte Postleitzahl.
     */
    public static function format(string $postalCode, string $country = 'DE'): string {
        $country = strtoupper($country);

        // Entferne alle Leerzeichen für die Neuformatierung
        $clean = preg_replace('/\s+/', '', $postalCode);

        return match ($country) {
            'NL' => strlen($clean) === 6
                ? strtoupper(substr($clean, 0, 4) . ' ' . substr($clean, 4))
                : strtoupper($postalCode),
            'SE' => strlen($clean) === 5
                ? substr($clean, 0, 3) . ' ' . substr($clean, 3)
                : $postalCode,
            'CZ' => strlen($clean) === 5
                ? substr($clean, 0, 3) . ' ' . substr($clean, 3)
                : $postalCode,
            'PL' => strlen($clean) === 5
                ? substr($clean, 0, 2) . '-' . substr($clean, 2)
                : $postalCode,
            'GB' => self::formatUKPostalCode($postalCode),
            default => $postalCode,
        };
    }

    /**
     * Ermittelt das Bundesland anhand einer deutschen Postleitzahl.
     *
     * @param string $postalCode Die deutsche Postleitzahl.
     * @return string|null Das Bundesland-Kürzel oder null.
     */
    public static function getGermanState(string $postalCode): ?string {
        if (!self::isGermanPostalCode($postalCode)) {
            return null;
        }

        $plz = (int)$postalCode;

        foreach (self::GERMAN_PLZ_REGIONS as $state => $ranges) {
            foreach ($ranges as $range) {
                if ($plz >= $range[0] && $plz <= $range[1]) {
                    return $state;
                }
            }
        }

        return null;
    }

    /**
     * Gibt alle unterstützten Länder zurück.
     *
     * @return array<string> Liste der unterstützten Ländercodes.
     */
    public static function getSupportedCountries(): array {
        return array_keys(self::POSTAL_CODE_FORMATS);
    }

    /**
     * Gibt das erwartete Format für ein Land zurück.
     *
     * @param string $country Der Ländercode.
     * @return string|null Die Formatbeschreibung oder null.
     */
    public static function getExpectedFormat(string $country): ?string {
        $country = strtoupper($country);

        if (!isset(self::POSTAL_CODE_FORMATS[$country])) {
            return null;
        }

        return self::POSTAL_CODE_FORMATS[$country]['format'];
    }

    /**
     * Prüft, ob eine Postleitzahl zu einem bestimmten Land passt.
     *
     * @param string $postalCode Die Postleitzahl.
     * @param CountryCode $country Das Land.
     * @return bool True, wenn die PLZ zum Land passt.
     */
    public static function matchesCountry(string $postalCode, CountryCode $country): bool {
        return self::isValid($postalCode, $country->value);
    }

    // ========================================
    // Private Methoden
    // ========================================

    /**
     * Formatiert eine UK-Postleitzahl.
     *
     * @param string $postalCode Die Postleitzahl.
     * @return string Die formatierte Postleitzahl.
     */
    private static function formatUKPostalCode(string $postalCode): string {
        $postalCode = strtoupper(preg_replace('/\s+/', '', $postalCode));
        $length = strlen($postalCode);

        if ($length < 5 || $length > 7) {
            return $postalCode;
        }

        // Die letzten 3 Zeichen sind immer der Inward Code
        $outward = substr($postalCode, 0, -3);
        $inward = substr($postalCode, -3);

        return $outward . ' ' . $inward;
    }
}