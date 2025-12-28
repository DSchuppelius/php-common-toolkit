<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CompanyIdHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use ERRORToolkit\Traits\ErrorLog;

/**
 * Helper-Klasse für die Validierung von Unternehmenskennungen.
 *
 * Unterstützt:
 * - Handelsregisternummer (HRA/HRB/GnR/PR/VR)
 * - LEI (Legal Entity Identifier) - 20 Zeichen
 * - D-U-N-S Nummer - 9 Ziffern
 * - GLN/EAN (Global Location Number) - 13 Ziffern
 * - Wirtschafts-Identifikationsnummer (W-IdNr) - 11 Zeichen
 *
 * @package CommonToolkit\Helper\Data
 */
class CompanyIdHelper {
    use ErrorLog;

    /**
     * Gültige Handelsregister-Präfixe.
     */
    private const HR_PREFIXES = ['HRA', 'HRB', 'GNR', 'PR', 'VR'];

    /**
     * Prüft, ob eine Handelsregisternummer ein gültiges Format hat.
     *
     * Format: [Präfix] [Nummer] [Suffix]
     * Präfix: HRA, HRB, GnR, PR, VR
     * Nummer: 1-6 Ziffern
     * Suffix: Optional (z.B. Buchstabe für Zweigniederlassung)
     *
     * @param string|null $hrNumber Die zu prüfende Handelsregisternummer.
     * @return bool True, wenn das Format gültig ist.
     */
    public static function isHRNumber(?string $hrNumber): bool {
        if ($hrNumber === null || $hrNumber === '') {
            return false;
        }

        $hrNumber = self::normalizeHRNumber($hrNumber);

        // Pattern: HRA/HRB/GNR/PR/VR + Nummer (1-6 Ziffern) + optionaler Suffix
        $pattern = '/^(HRA|HRB|GNR|PR|VR)\s*[0-9]{1,6}[A-Z]?$/i';

        return preg_match($pattern, $hrNumber) === 1;
    }

    /**
     * Extrahiert Präfix und Nummer aus einer Handelsregisternummer.
     *
     * @param string $hrNumber Die Handelsregisternummer.
     * @return array{prefix: string|null, number: string|null, suffix: string|null}
     */
    public static function parseHRNumber(string $hrNumber): array {
        $hrNumber = self::normalizeHRNumber($hrNumber);

        if (!self::isHRNumber($hrNumber)) {
            return ['prefix' => null, 'number' => null, 'suffix' => null];
        }

        preg_match('/^(HRA|HRB|GNR|PR|VR)\s*([0-9]{1,6})([A-Z])?$/i', $hrNumber, $matches);

        return [
            'prefix' => strtoupper($matches[1] ?? ''),
            'number' => $matches[2] ?? null,
            'suffix' => isset($matches[3]) ? strtoupper($matches[3]) : null,
        ];
    }

    /**
     * Normalisiert eine Handelsregisternummer.
     *
     * @param string $hrNumber Die zu normalisierende Nummer.
     * @return string Die normalisierte Nummer.
     */
    public static function normalizeHRNumber(string $hrNumber): string {
        // Entferne überflüssige Leerzeichen
        $hrNumber = preg_replace('/\s+/', ' ', trim($hrNumber));
        // Entferne Punkte
        $hrNumber = str_replace('.', '', $hrNumber);

        return strtoupper($hrNumber);
    }

    /**
     * Formatiert eine Handelsregisternummer.
     *
     * @param string $hrNumber Die zu formatierende Nummer.
     * @return string Die formatierte Nummer (z.B. "HRB 12345").
     */
    public static function formatHRNumber(string $hrNumber): string {
        $parts = self::parseHRNumber($hrNumber);

        if ($parts['prefix'] === null || $parts['number'] === null) {
            return $hrNumber;
        }

        $formatted = $parts['prefix'] . ' ' . $parts['number'];

        if ($parts['suffix'] !== null) {
            $formatted .= ' ' . $parts['suffix'];
        }

        return $formatted;
    }

    /**
     * Prüft, ob ein LEI (Legal Entity Identifier) ein gültiges Format hat.
     *
     * Der LEI ist ein 20-stelliger alphanumerischer Code nach ISO 17442.
     * Format: 4 Zeichen LOU + 14 Zeichen Entity + 2 Prüfziffern
     *
     * @param string|null $lei Der zu prüfende LEI.
     * @return bool True, wenn das Format gültig ist.
     */
    public static function isLEI(?string $lei): bool {
        if ($lei === null || $lei === '') {
            return false;
        }

        $lei = self::normalizeLEI($lei);

        // LEI muss genau 20 alphanumerische Zeichen haben
        if (!preg_match('/^[A-Z0-9]{20}$/', $lei)) {
            return false;
        }

        // Zeichen 5-6 müssen 00 sein (reserviert)
        // Hinweis: Diese Regel ist nicht immer strikt, wird hier aber geprüft
        // if (substr($lei, 4, 2) !== '00') {
        //     return false;
        // }

        return true;
    }

    /**
     * Validiert einen LEI mit Prüfsumme (ISO 7064 MOD 97-10).
     *
     * @param string|null $lei Der zu validierende LEI.
     * @return bool True, wenn der LEI gültig ist.
     */
    public static function validateLEI(?string $lei): bool {
        if (!self::isLEI($lei)) {
            return false;
        }

        $lei = self::normalizeLEI($lei);

        return self::checkMod97($lei);
    }

    /**
     * Normalisiert einen LEI.
     *
     * @param string $lei Der zu normalisierende LEI.
     * @return string Der normalisierte LEI.
     */
    public static function normalizeLEI(string $lei): string {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $lei));
    }

    /**
     * Formatiert einen LEI mit Leerzeichen für bessere Lesbarkeit.
     *
     * @param string $lei Der zu formatierende LEI.
     * @return string Der formatierte LEI (z.B. "5299 00RD KKCV ES7W JZ75").
     */
    public static function formatLEI(string $lei): string {
        $lei = self::normalizeLEI($lei);

        if (strlen($lei) !== 20) {
            return $lei;
        }

        return substr($lei, 0, 4) . ' ' .
            substr($lei, 4, 4) . ' ' .
            substr($lei, 8, 4) . ' ' .
            substr($lei, 12, 4) . ' ' .
            substr($lei, 16, 4);
    }

    /**
     * Prüft, ob eine D-U-N-S Nummer ein gültiges Format hat.
     *
     * D-U-N-S (Data Universal Numbering System) ist eine 9-stellige Nummer.
     *
     * @param string|null $duns Die zu prüfende D-U-N-S Nummer.
     * @return bool True, wenn das Format gültig ist.
     */
    public static function isDUNS(?string $duns): bool {
        if ($duns === null || $duns === '') {
            return false;
        }

        $duns = self::normalizeDUNS($duns);

        // D-U-N-S muss genau 9 Ziffern haben
        return preg_match('/^[0-9]{9}$/', $duns) === 1;
    }

    /**
     * Normalisiert eine D-U-N-S Nummer.
     *
     * @param string $duns Die zu normalisierende Nummer.
     * @return string Die normalisierte Nummer.
     */
    public static function normalizeDUNS(string $duns): string {
        return preg_replace('/[^0-9]/', '', $duns);
    }

    /**
     * Formatiert eine D-U-N-S Nummer.
     *
     * @param string $duns Die zu formatierende Nummer.
     * @return string Die formatierte Nummer (z.B. "12-345-6789").
     */
    public static function formatDUNS(string $duns): string {
        $duns = self::normalizeDUNS($duns);

        if (strlen($duns) !== 9) {
            return $duns;
        }

        return substr($duns, 0, 2) . '-' .
            substr($duns, 2, 3) . '-' .
            substr($duns, 5, 4);
    }

    /**
     * Prüft, ob eine GLN/EAN (Global Location Number) ein gültiges Format hat.
     *
     * Die GLN ist eine 13-stellige Nummer zur Identifikation von Standorten/Unternehmen.
     *
     * @param string|null $gln Die zu prüfende GLN.
     * @return bool True, wenn das Format gültig ist.
     */
    public static function isGLN(?string $gln): bool {
        if ($gln === null || $gln === '') {
            return false;
        }

        $gln = self::normalizeGLN($gln);

        // GLN muss genau 13 Ziffern haben
        return preg_match('/^[0-9]{13}$/', $gln) === 1;
    }

    /**
     * Validiert eine GLN mit Prüfziffer.
     *
     * @param string|null $gln Die zu validierende GLN.
     * @return bool True, wenn die GLN gültig ist.
     */
    public static function validateGLN(?string $gln): bool {
        if (!self::isGLN($gln)) {
            return false;
        }

        $gln = self::normalizeGLN($gln);

        return self::checkEANChecksum($gln);
    }

    /**
     * Normalisiert eine GLN.
     *
     * @param string $gln Die zu normalisierende GLN.
     * @return string Die normalisierte GLN.
     */
    public static function normalizeGLN(string $gln): string {
        return preg_replace('/[^0-9]/', '', $gln);
    }

    /**
     * Formatiert eine GLN.
     *
     * @param string $gln Die zu formatierende GLN.
     * @return string Die formatierte GLN.
     */
    public static function formatGLN(string $gln): string {
        $gln = self::normalizeGLN($gln);

        if (strlen($gln) !== 13) {
            return $gln;
        }

        // Standard EAN-13 Formatierung: X-XXXXXX-XXXXX-X
        return substr($gln, 0, 1) . '-' .
            substr($gln, 1, 6) . '-' .
            substr($gln, 7, 5) . '-' .
            substr($gln, 12, 1);
    }

    /**
     * Prüft, ob eine Wirtschafts-Identifikationsnummer (W-IdNr) ein gültiges Format hat.
     *
     * Die W-IdNr ist eine 11-stellige Kennung für Unternehmen in Deutschland.
     * Format: DE + 9 Ziffern (ähnlich USt-ID, aber ohne Prüfziffer)
     *
     * @param string|null $wIdNr Die zu prüfende W-IdNr.
     * @return bool True, wenn das Format gültig ist.
     */
    public static function isWIdNr(?string $wIdNr): bool {
        if ($wIdNr === null || $wIdNr === '') {
            return false;
        }

        $wIdNr = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $wIdNr));

        // W-IdNr: DE + 9 Ziffern
        return preg_match('/^DE[0-9]{9}$/', $wIdNr) === 1;
    }

    /**
     * Prüft, ob eine EAN/GTIN (European Article Number) ein gültiges Format hat.
     *
     * @param string|null $ean Die zu prüfende EAN.
     * @param int $length Die erwartete Länge (8, 12, 13 oder 14).
     * @return bool True, wenn das Format gültig ist.
     */
    public static function isEAN(?string $ean, int $length = 13): bool {
        if ($ean === null || $ean === '') {
            return false;
        }

        $ean = preg_replace('/[^0-9]/', '', $ean);

        if (!in_array($length, [8, 12, 13, 14], true)) {
            return false;
        }

        return strlen($ean) === $length && preg_match('/^[0-9]+$/', $ean) === 1;
    }

    /**
     * Validiert eine EAN/GTIN mit Prüfziffer.
     *
     * @param string|null $ean Die zu validierende EAN.
     * @return bool True, wenn die EAN gültig ist.
     */
    public static function validateEAN(?string $ean): bool {
        if ($ean === null || $ean === '') {
            return false;
        }

        $ean = preg_replace('/[^0-9]/', '', $ean);

        if (!in_array(strlen($ean), [8, 12, 13, 14], true)) {
            return false;
        }

        return self::checkEANChecksum($ean);
    }

    // ========================================
    // Private Methoden
    // ========================================

    /**
     * Prüft die MOD 97-10 Prüfsumme (für LEI).
     *
     * @param string $code Der zu prüfende Code.
     * @return bool True, wenn die Prüfsumme korrekt ist.
     */
    private static function checkMod97(string $code): bool {
        // Konvertiere Buchstaben in Zahlen (A=10, B=11, ...)
        $converted = '';
        foreach (str_split($code) as $char) {
            if (ctype_digit($char)) {
                $converted .= $char;
            } else {
                $converted .= (ord($char) - ord('A') + 10);
            }
        }

        // MOD 97 Berechnung
        $remainder = 0;
        foreach (str_split($converted) as $digit) {
            $remainder = ($remainder * 10 + (int)$digit) % 97;
        }

        return $remainder === 1;
    }

    /**
     * Prüft die EAN/GTIN Prüfziffer.
     *
     * @param string $ean Die zu prüfende EAN (8, 12, 13 oder 14 Ziffern).
     * @return bool True, wenn die Prüfziffer korrekt ist.
     */
    private static function checkEANChecksum(string $ean): bool {
        $length = strlen($ean);

        if (!in_array($length, [8, 12, 13, 14], true)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < $length - 1; $i++) {
            $digit = (int)$ean[$i];
            // GS1 Standard: Position 0,2,4... = Gewicht 1; Position 1,3,5... = Gewicht 3
            $weight = ($i % 2 === 0) ? 1 : 3;
            $sum += $digit * $weight;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return (int)$ean[$length - 1] === $checkDigit;
    }
}
