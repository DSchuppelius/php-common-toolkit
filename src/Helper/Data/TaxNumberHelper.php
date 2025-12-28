<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TaxNumberHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use ERRORToolkit\Traits\ErrorLog;

/**
 * Helper-Klasse für die Validierung deutscher Steuernummern und Steuer-Identifikationsnummern.
 *
 * Unterstützt:
 * - Deutsche Steuer-Identifikationsnummer (IdNr) - 11 Ziffern
 * - Deutsche Steuernummer (StNr) - 10-13 Ziffern je nach Bundesland
 * - Bundeseinheitliche Steuernummer (ELSTER-Format) - 13 Ziffern
 *
 * @package CommonToolkit\Helper\Data
 */
class TaxNumberHelper {
    use ErrorLog;

    /**
     * Bundesland-Codes für die bundeseinheitliche Steuernummer.
     * Format: Bundesland-Code => [Finanzamts-Präfixe]
     *
     * @var array<string, array{code: string, name: string, format: string}>
     */
    private const FEDERAL_STATES = [
        'BW' => ['code' => '28', 'name' => 'Baden-Württemberg',      'format' => 'FF/BBB/UUUUP'],
        'BY' => ['code' => '9',  'name' => 'Bayern',                 'format' => 'FFF/BBB/UUUUP'],
        'BE' => ['code' => '11', 'name' => 'Berlin',                 'format' => 'FF/BBB/UUUUP'],
        'BB' => ['code' => '3',  'name' => 'Brandenburg',            'format' => 'FFF/BBB/UUUUP'],
        'HB' => ['code' => '24', 'name' => 'Bremen',                 'format' => 'FF BBB UUUUP'],
        'HH' => ['code' => '22', 'name' => 'Hamburg',                'format' => 'FF/BBB/UUUUP'],
        'HE' => ['code' => '26', 'name' => 'Hessen',                 'format' => 'FFF BBB UUUUP'],
        'MV' => ['code' => '4',  'name' => 'Mecklenburg-Vorpommern', 'format' => 'FFF/BBB/UUUUP'],
        'NI' => ['code' => '23', 'name' => 'Niedersachsen',          'format' => 'FF/BBB/UUUUP'],
        'NW' => ['code' => '5',  'name' => 'Nordrhein-Westfalen',    'format' => 'FFF/BBBB/UUUP'],
        'RP' => ['code' => '27', 'name' => 'Rheinland-Pfalz',        'format' => 'FF/BBB/UUUUP'],
        'SL' => ['code' => '1',  'name' => 'Saarland',               'format' => 'FFF/BBB/UUUUP'],
        'SN' => ['code' => '3',  'name' => 'Sachsen',                'format' => 'FFF/BBB/UUUUP'],
        'ST' => ['code' => '3',  'name' => 'Sachsen-Anhalt',         'format' => 'FFF/BBB/UUUUP'],
        'SH' => ['code' => '21', 'name' => 'Schleswig-Holstein',     'format' => 'FF BBB UUUUP'],
        'TH' => ['code' => '4',  'name' => 'Thüringen',              'format' => 'FFF/BBB/UUUUP'],
    ];

    /**
     * Prüft, ob eine Steuer-Identifikationsnummer (IdNr) ein gültiges Format hat.
     *
     * Die IdNr ist eine 11-stellige Nummer, die seit 2008 jedem in Deutschland
     * gemeldeten Bürger zugeteilt wird.
     *
     * @param string|null $idNr Die zu prüfende IdNr.
     * @return bool True, wenn das Format gültig ist.
     */
    public static function isIdNr(?string $idNr): bool {
        if ($idNr === null || $idNr === '') {
            return false;
        }

        $idNr = self::normalize($idNr);

        // IdNr muss genau 11 Ziffern haben
        if (!preg_match('/^[0-9]{11}$/', $idNr)) {
            return false;
        }

        // Erste Ziffer darf nicht 0 sein
        if ($idNr[0] === '0') {
            return false;
        }

        // Genau eine Ziffer muss doppelt vorkommen, eine darf fehlen
        $digitCounts = array_count_values(str_split(substr($idNr, 0, 10)));
        $doubles = 0;
        $missing = 0;

        for ($i = 0; $i <= 9; $i++) {
            $count = $digitCounts[(string)$i] ?? 0;
            if ($count === 2) {
                $doubles++;
            } elseif ($count === 3) {
                // Drei gleiche Ziffern nur erlaubt wenn nicht aufeinanderfolgend
                $doubles += 2;
            } elseif ($count === 0) {
                $missing++;
            }
        }

        // Mindestens eine Ziffer muss doppelt vorkommen
        if ($doubles < 1) {
            return false;
        }

        return true;
    }

    /**
     * Validiert eine Steuer-Identifikationsnummer (IdNr) mit Prüfziffer.
     *
     * @param string|null $idNr Die zu validierende IdNr.
     * @return bool True, wenn die IdNr gültig ist (inkl. Prüfziffer).
     */
    public static function validateIdNr(?string $idNr): bool {
        if (!self::isIdNr($idNr)) {
            return false;
        }

        $idNr = self::normalize($idNr);

        return self::checkIdNrChecksum($idNr);
    }

    /**
     * Prüft, ob eine Steuernummer (StNr) ein gültiges Format hat.
     *
     * @param string|null $stNr Die zu prüfende Steuernummer.
     * @return bool True, wenn das Format gültig ist.
     */
    public static function isStNr(?string $stNr): bool {
        if ($stNr === null || $stNr === '') {
            return false;
        }

        $stNr = self::normalize($stNr);

        // Steuernummer hat 10-13 Ziffern (je nach Bundesland und Format)
        if (!preg_match('/^[0-9]{10,13}$/', $stNr)) {
            return false;
        }

        return true;
    }

    /**
     * Prüft, ob eine bundeseinheitliche Steuernummer (ELSTER-Format) gültig ist.
     *
     * Format: 13 Ziffern - FFBBB0UUUUP
     * FF = Bundesfinanzamtsnummer (2 Ziffern)
     * BBB = Bezirksnummer (3-4 Ziffern)
     * UUUU = Unterscheidungsnummer (4 Ziffern)
     * P = Prüfziffer
     *
     * @param string|null $stNr Die zu prüfende Steuernummer.
     * @return bool True, wenn das Format gültig ist.
     */
    public static function isUnifiedStNr(?string $stNr): bool {
        if ($stNr === null || $stNr === '') {
            return false;
        }

        $stNr = self::normalize($stNr);

        // Bundeseinheitliche StNr hat genau 13 Ziffern
        if (!preg_match('/^[0-9]{13}$/', $stNr)) {
            return false;
        }

        // Prüfe gültigen Bundesland-Code (erste 2-3 Ziffern)
        $stateCode = substr($stNr, 0, 2);

        // Bekannte Bundesland-Codes
        $validStateCodes = ['10', '11', '21', '22', '23', '24', '26', '27', '28', '29'];
        $validStateCodesAlt = ['30', '31', '32', '40', '41', '50', '51', '52', '53', '54', '55'];

        if (!in_array($stateCode, $validStateCodes) && !in_array($stateCode, $validStateCodesAlt)) {
            // Prüfe 1-stellige Codes (Saarland, Brandenburg, etc.)
            $singleDigitCodes = ['1', '2', '3', '4', '5', '9'];
            if (!in_array($stNr[0], $singleDigitCodes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Normalisiert eine Steuernummer (entfernt Leerzeichen, Schrägstriche, etc.).
     *
     * @param string $number Die zu normalisierende Nummer.
     * @return string Die normalisierte Nummer.
     */
    public static function normalize(string $number): string {
        return preg_replace('/[^0-9]/', '', $number);
    }

    /**
     * Formatiert eine IdNr mit Leerzeichen für bessere Lesbarkeit.
     *
     * @param string $idNr Die zu formatierende IdNr.
     * @return string Die formatierte IdNr (XX XXX XXX XXX).
     */
    public static function formatIdNr(string $idNr): string {
        $idNr = self::normalize($idNr);

        if (strlen($idNr) !== 11) {
            return $idNr;
        }

        return substr($idNr, 0, 2) . ' ' .
            substr($idNr, 2, 3) . ' ' .
            substr($idNr, 5, 3) . ' ' .
            substr($idNr, 8, 3);
    }

    /**
     * Formatiert eine Steuernummer im landesspezifischen Format.
     *
     * @param string $stNr Die Steuernummer.
     * @param string|null $state Das Bundesland-Kürzel (z.B. 'NW', 'BY').
     * @return string Die formatierte Steuernummer.
     */
    public static function formatStNr(string $stNr, ?string $state = null): string {
        $stNr = self::normalize($stNr);

        if ($state !== null && isset(self::FEDERAL_STATES[$state])) {
            $format = self::FEDERAL_STATES[$state]['format'];

            // Vereinfachte Formatierung basierend auf Bundesland
            return match ($state) {
                'NW' => strlen($stNr) >= 13
                    ? substr($stNr, 0, 3) . '/' . substr($stNr, 3, 4) . '/' . substr($stNr, 7)
                    : $stNr,
                'BY' => strlen($stNr) >= 13
                    ? substr($stNr, 0, 3) . '/' . substr($stNr, 3, 3) . '/' . substr($stNr, 6)
                    : $stNr,
                default => strlen($stNr) >= 13
                    ? substr($stNr, 0, 2) . '/' . substr($stNr, 2, 3) . '/' . substr($stNr, 5)
                    : $stNr,
            };
        }

        // Standard-Formatierung für bundeseinheitliche StNr
        if (strlen($stNr) === 13) {
            return substr($stNr, 0, 4) . '/' . substr($stNr, 4, 4) . '/' . substr($stNr, 8);
        }

        return $stNr;
    }

    /**
     * Konvertiert eine landesspezifische Steuernummer in das bundeseinheitliche Format.
     *
     * @param string $stNr Die landesspezifische Steuernummer.
     * @param string $state Das Bundesland-Kürzel.
     * @return string|null Die bundeseinheitliche Steuernummer oder null bei Fehler.
     */
    public static function toUnifiedFormat(string $stNr, string $state): ?string {
        $stNr = self::normalize($stNr);

        if (!isset(self::FEDERAL_STATES[$state])) {
            self::logError("Unbekanntes Bundesland: {$state}");
            return null;
        }

        $stateCode = self::FEDERAL_STATES[$state]['code'];

        // Länge der Eingabe prüfen und entsprechend konvertieren
        // Dies ist eine vereinfachte Implementierung - die genauen Regeln
        // variieren je nach Bundesland erheblich

        if (strlen($stNr) < 10 || strlen($stNr) > 13) {
            self::logError("Ungültige Steuernummernlänge: " . strlen($stNr));
            return null;
        }

        // Pad auf 13 Stellen mit führenden Nullen wenn nötig
        if (strlen($stNr) < 13) {
            // Füge Bundesland-Code hinzu
            $stNr = str_pad($stateCode, 2, '0', STR_PAD_LEFT) . str_pad($stNr, 11, '0', STR_PAD_LEFT);
        }

        return substr($stNr, 0, 13);
    }

    /**
     * Gibt alle unterstützten Bundesländer zurück.
     *
     * @return array<string, string> Bundesland-Kürzel => Name
     */
    public static function getFederalStates(): array {
        $result = [];
        foreach (self::FEDERAL_STATES as $code => $data) {
            $result[$code] = $data['name'];
        }
        return $result;
    }

    /**
     * Ermittelt das Bundesland anhand einer bundeseinheitlichen Steuernummer.
     *
     * @param string $stNr Die bundeseinheitliche Steuernummer.
     * @return string|null Das Bundesland-Kürzel oder null.
     */
    public static function getFederalStateFromStNr(string $stNr): ?string {
        $stNr = self::normalize($stNr);

        if (strlen($stNr) < 2) {
            return null;
        }

        $prefix = substr($stNr, 0, 2);

        // Mapping der Präfixe zu Bundesländern
        $prefixMap = [
            '10' => 'SL',
            '11' => 'BE',
            '21' => 'SH',
            '22' => 'HH',
            '23' => 'NI',
            '24' => 'HB',
            '26' => 'HE',
            '27' => 'RP',
            '28' => 'BW',
            '29' => 'BW',
        ];

        if (isset($prefixMap[$prefix])) {
            return $prefixMap[$prefix];
        }

        // Einstellige Präfixe
        $singlePrefix = $stNr[0];
        $singlePrefixMap = [
            '1' => 'SL',
            '3' => 'BB', // Auch SN, ST - hier vereinfacht
            '4' => 'MV', // Auch TH
            '5' => 'NW',
            '9' => 'BY',
        ];

        return $singlePrefixMap[$singlePrefix] ?? null;
    }

    // ========================================
    // Private Methoden
    // ========================================

    /**
     * Berechnet und prüft die Prüfziffer einer IdNr.
     *
     * Die Prüfziffer wird nach einem modifizierten ISO 7064 MOD 11,10 Verfahren berechnet.
     *
     * @param string $idNr Die normalisierte IdNr (11 Ziffern).
     * @return bool True, wenn die Prüfziffer korrekt ist.
     */
    private static function checkIdNrChecksum(string $idNr): bool {
        $product = 10;

        for ($i = 0; $i < 10; $i++) {
            $sum = ((int)$idNr[$i] + $product) % 10;
            if ($sum === 0) {
                $sum = 10;
            }
            $product = (2 * $sum) % 11;
        }

        $checkDigit = 11 - $product;
        if ($checkDigit === 10) {
            $checkDigit = 0;
        }

        return (int)$idNr[10] === $checkDigit;
    }
}
