<?php
/*
 * Created on   : Tue Apr 01 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Helper\FileSystem\Folder;
use ConfigToolkit\ConfigLoader;
use ERRORToolkit\Traits\ErrorLog;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class BankHelper {
    use ErrorLog;

    /** @var array<string, string>|null Cache für BLZ-Index (BLZ => Zeile) */
    private static ?array $blzIndex = null;

    /** @var array<string, string>|null Cache für BIC-Index (BIC8 => Zeile) */
    private static ?array $bicIndex = null;

    /**
     * Überprüft die Bankleitzahl (BLZ) auf Gültigkeit.
     *
     * @param string|null $value Die Bankleitzahl.
     * @return bool True, wenn die BLZ gültig ist, andernfalls false.
     */
    public static function isBLZ(?string $value): bool {
        return $value !== null && preg_match("/^[0-9]{8}\$/", $value) === 1;
    }

    /**
     * Überprüft die Kontonummer auf Gültigkeit.
     *
     * @param string|null $value Die Kontonummer.
     * @return bool True, wenn die Kontonummer gültig ist, andernfalls false.
     */
    public static function isKTO(?string $value): bool {
        return $value !== null && preg_match("/^[0-9]{10}\$/", $value) === 1;
    }

    /**
     * Überprüft die IBAN auf Gültigkeit.
     *
     * @param string|null $value Die IBAN.
     * @return bool True, wenn die IBAN gültig ist, andernfalls false.
     */
    public static function isIBAN(?string $value): bool {
        if ($value === null || preg_match("/X{5,}/", $value)) return false;
        $value = str_replace(' ', '', $value);
        return preg_match("/^[A-Z]{2}[A-Z0-9]{14,33}\$/", $value) === 1;
    }

    /**
     * Validiert eine IBAN mit optionaler Prüfsummenvalidierung.
     *
     * Diese Methode kombiniert Format- und Prüfsummenvalidierung:
     * - $strict = false: Nur Format-Check (schnell)
     * - $strict = true: Format + Prüfsumme + Länderlänge (vollständig)
     *
     * @param string|null $value Die zu validierende IBAN.
     * @param bool $strict Bei true wird auch die Prüfsumme validiert.
     * @return bool True, wenn die IBAN gültig ist.
     */
    public static function validateIBAN(?string $value, bool $strict = false): bool {
        if (!self::isIBAN($value)) {
            return false;
        }
        return $strict ? self::checkIBAN($value) : true;
    }

    /**
     * Prüft, ob der String wie eine IBAN formatiert ist (beginnt mit 2 Buchstaben + 2 Ziffern).
     * 
     * Diese Methode ist weniger strikt als isIBAN() und prüft nur das Anfangsformat,
     * nicht die vollständige IBAN-Struktur oder Prüfsumme. Geeignet für XML-Generierung,
     * wo auch Platzhalter-IBANs als <IBAN>-Element formatiert werden sollen.
     *
     * @param string|null $value Der zu prüfende String.
     * @return bool True, wenn der String wie eine IBAN aussieht.
     */
    public static function hasIBANFormat(?string $value): bool {
        if ($value === null || $value === '') {
            return false;
        }
        return preg_match('/^[A-Z]{2}\d{2}/', $value) === 1;
    }

    /**
     * Prüft, ob der String wie eine IBAN formatiert ist und ob diese gültig ist.
     * 
     * Loggt eine Warnung, wenn das Format einer IBAN entspricht, aber die IBAN nicht gültig ist.
     * Gibt true zurück, wenn der String als IBAN formatiert werden soll (auch wenn ungültig).
     *
     * @param string|null $value Der zu prüfende String.
     * @return bool True, wenn der Identifier als IBAN formatiert werden soll (auch wenn ungültig).
     */
    public static function shouldFormatAsIBAN(?string $value): bool {
        if (!self::hasIBANFormat($value)) {
            return false;
        }

        if (self::isIBANAnon($value)) {
            self::logWarning("'{$value}' ist eine anonymisierte IBAN.");
        } elseif (!self::isIBAN($value)) {
            self::logWarning("'{$value}' hat IBAN-Format, ist aber keine gültige IBAN.");
        }

        return true;
    }

    /**
     * Überprüft, ob die IBAN anonymisiert ist.
     *
     * @param string|null $value Die IBAN.
     * @return bool True, wenn die IBAN anonymisiert ist, andernfalls false.
     */
    public static function isIBANAnon(?string $value): bool {
        return $value !== null && preg_match("/^[A-Z]{2}XX[0-9]{11}XXXX[0-9]{3}\$/", $value) === 1;
    }

    /**
     * Überprüft die BIC auf Gültigkeit.
     *
     * @param string|null $value Die BIC.
     * @return bool True, wenn die BIC gültig ist, andernfalls false.
     */
    public static function isBIC(?string $value): bool {
        return $value !== null && preg_match("/^[A-Z]{6}[2-9A-Z][0-9A-NP-Z]([A-Z0-9]{3}|x{3})?\$/", $value) === 1;
    }

    /**
     * Überprüft die IBAN auf Gültigkeit.
     *
     * @param string $iban Die IBAN.
     * @return bool True, wenn die IBAN gültig ist, andernfalls false.
     */
    public static function checkIBAN(string $iban): bool {
        self::requireBcMath();

        $iban = strtoupper(str_replace(' ', '', $iban));
        if (!self::isIBAN($iban)) {
            return false;
        }

        $countries = self::countryLengths();
        $chars = self::ibanCharMap();

        $countryCode = substr($iban, 0, 2);
        if (!isset($countries[$countryCode]) || strlen($iban) !== $countries[$countryCode]) {
            return false;
        }

        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        $converted = '';
        foreach (str_split($rearranged) as $char) {
            if (ctype_digit($char)) {
                $converted .= $char;
            } elseif (isset($chars[strtolower($char)])) {
                $converted .= $chars[strtolower($char)];
            } else {
                return false;
            }
        }

        $result = bcmod($converted, '97');
        if ($result === false) {
            return false;
        }

        return $result === '1';
    }

    /**
     * Generiert eine IBAN für Deutschland basierend auf BLZ und KTO.
     *
     * @param string $blz Die Bankleitzahl (BLZ).
     * @param string $kto Die Kontonummer (KTO).
     * @return string Die generierte IBAN.
     * @throws InvalidArgumentException Bei ungültiger BLZ oder Kontonummer.
     */
    public static function generateGermanIBAN(string $blz, string $kto): string {
        // Entferne nicht-numerische Zeichen und normalisiere
        $blzClean = preg_replace('/[^0-9]/', '', $blz);
        $ktoClean = preg_replace('/[^0-9]/', '', $kto);

        // Padding auf Standardlängen
        $blzPadded = str_pad($blzClean, 8, '0', STR_PAD_LEFT);
        $ktoPadded = str_pad($ktoClean, 10, '0', STR_PAD_LEFT);

        // Validierung mit Helper-Funktionen
        if (!self::isBLZ($blzPadded)) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige BLZ: '$blz' (nach Normalisierung: '$blzPadded')");
        } elseif (!self::isKTO($ktoPadded)) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige Kontonummer: '$kto' (nach Normalisierung: '$ktoPadded')");
        }

        $account = $blzPadded . $ktoPadded;
        return self::generateIBAN('DE', $account);
    }

    /**
     * Generiert eine IBAN für ein bestimmtes Land und eine Kontonummer.
     *
     * @param CountryCode|string $countryCode Der Ländercode (z.B. 'DE' für Deutschland).
     * @param string $accountNumber Die Kontonummer.
     * @return string Die generierte IBAN.
     */
    public static function generateIBAN(CountryCode|string $countryCode, string $accountNumber): string {
        self::requireBcMath();

        if ($countryCode instanceof CountryCode) {
            $countryCode = $countryCode->value;
        }

        $countryCode = strtoupper($countryCode);
        $countries = self::countryLengths();
        $chars = self::ibanCharMap();

        if (!isset($countries[$countryCode])) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültiger Ländercode: '$countryCode'");
        }

        $expectedLength = $countries[$countryCode] - 4; // ohne Prüfziffer und Länderkennung
        if (strlen($accountNumber) !== $expectedLength) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Die Kontonummer hat nicht die richtige Länge ($expectedLength) für $countryCode. Eingabe: '$accountNumber'");
        }

        $rearranged = $accountNumber . $countryCode . '00';

        $converted = '';
        foreach (str_split($rearranged) as $char) {
            $converted .= is_numeric($char) ? $char : $chars[strtolower($char)] ?? '';
        }

        $checksum = 98 - (int) bcmod($converted, '97');
        return $countryCode . str_pad((string)$checksum, 2, '0', STR_PAD_LEFT) . $accountNumber;
    }

    /**
     * Gibt die BIC aus einer IBAN zurück.
     *
     * Nutzt einen BLZ-Index für O(1) Lookups statt linearer Suche.
     *
     * @param string $iban Die IBAN.
     * @return string Die BIC oder ein leerer String, wenn keine BIC gefunden wurde.
     */
    public static function bicFromIBAN(string $iban): string {
        $blz = substr($iban, 4, 8);
        $index = self::getBlzIndex();

        if (isset($index[$blz])) {
            return trim(substr($index[$blz], 139, 11));
        }

        return '';
    }

    /**
     * Gibt den BLZ-Index zurück (lazy-loaded und gecached).
     *
     * @return array<string, string> BLZ => Bundesbank-Zeile
     */
    private static function getBlzIndex(): array {
        if (self::$blzIndex === null) {
            self::$blzIndex = [];
            foreach (self::loadBundesbankBLZData() as $entry) {
                $blz = substr($entry, 0, 8);
                // Nur erste Zeile pro BLZ speichern (Hauptstelle)
                if (!isset(self::$blzIndex[$blz])) {
                    self::$blzIndex[$blz] = $entry;
                }
            }
        }
        return self::$blzIndex;
    }

    /**
     * Überprüft die BIC und gibt die BIC inkl. Banknamen zurück.
     *
     * Nutzt einen BIC-Index für O(1) Lookups statt linearer Suche.
     *
     * @param string $bic Die BIC.
     * @return string|false Der Bankname oder false bei ungültiger BIC.
     */
    public static function checkBIC(string $bic): string|false {
        $bic8 = strtoupper(substr(trim($bic), 0, 8));
        $index = self::getBicIndex();

        if (isset($index[$bic8])) {
            return $bic8 . "XXX " . $index[$bic8];
        }

        return false;
    }

    /**
     * Gibt den BIC-Index zurück (lazy-loaded und gecached).
     *
     * @return array<string, string> BIC8 => Bankname
     */
    private static function getBicIndex(): array {
        if (self::$bicIndex === null) {
            self::$bicIndex = [];
            foreach (self::loadBundesbankBICData() as $entry) {
                $fields = explode(";", $entry);
                if (count($fields) >= 2) {
                    $bic8 = strtoupper(substr($fields[0], 0, 8));
                    if (!isset(self::$bicIndex[$bic8])) {
                        self::$bicIndex[$bic8] = $fields[1];
                    }
                }
            }
        }
        return self::$bicIndex;
    }

    /**
     * Gibt die BLZ und KTO aus einer deutschen IBAN zurück.
     *
     * @param string|null $iban Die deutsche IBAN.
     * @return false|array Ein Array mit 'BLZ' und 'KTO' oder false bei ungültiger IBAN.
     * @deprecated Verwende splitIBANComponents() für internationale Unterstützung.
     */
    public static function splitIBAN(?string $iban): array|false {
        if ($iban === null || strlen($iban) < 22) {
            return false;
        }

        $countryCode = strtoupper(substr($iban, 0, 2));
        if ($countryCode !== 'DE') {
            return false;
        }

        return [
            'BLZ' => substr($iban, 4, 8),
            'KTO' => substr($iban, 12, 10)
        ];
    }

    /**
     * Extrahiert die Komponenten einer IBAN für verschiedene Länder.
     *
     * Gibt ein Array mit länderspezifischen Komponenten zurück:
     * - 'countryCode': Der 2-stellige ISO-Ländercode
     * - 'checkDigits': Die 2-stellige Prüfziffer
     * - 'bban': Der Basic Bank Account Number (länderspezifischer Teil)
     * - Zusätzliche länderspezifische Felder (z.B. 'bankCode', 'branchCode', 'accountNumber')
     *
     * @param string|null $iban Die IBAN.
     * @return false|array<string, string> Ein Array mit IBAN-Komponenten oder false bei ungültiger IBAN.
     */
    public static function splitIBANComponents(?string $iban): array|false {
        if ($iban === null) {
            return false;
        }

        $iban = strtoupper(str_replace(' ', '', $iban));
        if (!self::isIBAN($iban)) {
            return false;
        }

        $countryCode = substr($iban, 0, 2);
        $checkDigits = substr($iban, 2, 2);
        $bban = substr($iban, 4);

        $countries = self::countryLengths();
        if (!isset($countries[$countryCode]) || strlen($iban) !== $countries[$countryCode]) {
            return false;
        }

        $result = [
            'countryCode' => $countryCode,
            'checkDigits' => $checkDigits,
            'bban' => $bban,
        ];

        // Länderspezifische BBAN-Zerlegung
        $result = array_merge($result, self::parseBBAN($countryCode, $bban));

        return $result;
    }

    /**
     * Zerlegt den BBAN (Basic Bank Account Number) nach länderspezifischen Regeln.
     *
     * @param string $countryCode Der 2-stellige ISO-Ländercode.
     * @param string $bban Der BBAN-Teil der IBAN.
     * @return array<string, string> Länderspezifische Komponenten.
     */
    private static function parseBBAN(string $countryCode, string $bban): array {
        return match ($countryCode) {
            // Deutschland: 8 BLZ + 10 Konto
            'DE' => [
                'bankCode' => substr($bban, 0, 8),
                'accountNumber' => substr($bban, 8, 10),
            ],
            // Österreich: 5 BLZ + 11 Konto
            'AT' => [
                'bankCode' => substr($bban, 0, 5),
                'accountNumber' => substr($bban, 5, 11),
            ],
            // Schweiz: 5 BC-Nummer + 12 Konto
            'CH', 'LI' => [
                'bankCode' => substr($bban, 0, 5),
                'accountNumber' => substr($bban, 5),
            ],
            // Frankreich, Monaco: 5 Bank + 5 Filiale + 11 Konto + 2 Prüf
            'FR', 'MC' => [
                'bankCode' => substr($bban, 0, 5),
                'branchCode' => substr($bban, 5, 5),
                'accountNumber' => substr($bban, 10, 11),
                'nationalCheckDigits' => substr($bban, 21, 2),
            ],
            // Italien, San Marino: 1 Prüf + 5 ABI + 5 CAB + 12 Konto
            'IT', 'SM' => [
                'checkChar' => substr($bban, 0, 1),
                'bankCode' => substr($bban, 1, 5),
                'branchCode' => substr($bban, 6, 5),
                'accountNumber' => substr($bban, 11, 12),
            ],
            // Spanien: 4 Bank + 4 Filiale + 2 Prüf + 10 Konto
            'ES' => [
                'bankCode' => substr($bban, 0, 4),
                'branchCode' => substr($bban, 4, 4),
                'nationalCheckDigits' => substr($bban, 8, 2),
                'accountNumber' => substr($bban, 10, 10),
            ],
            // Niederlande: 4 Bank + 10 Konto
            'NL' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 10),
            ],
            // Belgien: 3 Bank + 7 Konto + 2 Prüf
            'BE' => [
                'bankCode' => substr($bban, 0, 3),
                'accountNumber' => substr($bban, 3, 7),
                'nationalCheckDigits' => substr($bban, 10, 2),
            ],
            // Luxemburg: 3 Bank + 13 Konto
            'LU' => [
                'bankCode' => substr($bban, 0, 3),
                'accountNumber' => substr($bban, 3, 13),
            ],
            // Großbritannien: 4 Bank + 6 Filiale + 8 Konto
            'GB' => [
                'bankCode' => substr($bban, 0, 4),
                'branchCode' => substr($bban, 4, 6),
                'accountNumber' => substr($bban, 10, 8),
            ],
            // Irland: 4 Bank + 6 Filiale + 8 Konto
            'IE' => [
                'bankCode' => substr($bban, 0, 4),
                'branchCode' => substr($bban, 4, 6),
                'accountNumber' => substr($bban, 10, 8),
            ],
            // Polen: 8 Bank + 16 Konto
            'PL' => [
                'bankCode' => substr($bban, 0, 8),
                'accountNumber' => substr($bban, 8, 16),
            ],
            // Tschechien: 4 Bank + 6 Konto-Präfix + 10 Konto
            'CZ' => [
                'bankCode' => substr($bban, 0, 4),
                'accountPrefix' => substr($bban, 4, 6),
                'accountNumber' => substr($bban, 10, 10),
            ],
            // Slowakei: 4 Bank + 6 Konto-Präfix + 10 Konto
            'SK' => [
                'bankCode' => substr($bban, 0, 4),
                'accountPrefix' => substr($bban, 4, 6),
                'accountNumber' => substr($bban, 10, 10),
            ],
            // Ungarn: 3 Bank + 4 Filiale + 1 Prüf + 16 Konto
            'HU' => [
                'bankCode' => substr($bban, 0, 3),
                'branchCode' => substr($bban, 3, 4),
                'nationalCheckDigit' => substr($bban, 7, 1),
                'accountNumber' => substr($bban, 8, 16),
            ],
            // Portugal: 4 Bank + 4 Filiale + 11 Konto + 2 Prüf
            'PT' => [
                'bankCode' => substr($bban, 0, 4),
                'branchCode' => substr($bban, 4, 4),
                'accountNumber' => substr($bban, 8, 11),
                'nationalCheckDigits' => substr($bban, 19, 2),
            ],
            // Griechenland: 3 Bank + 4 Filiale + 16 Konto
            'GR' => [
                'bankCode' => substr($bban, 0, 3),
                'branchCode' => substr($bban, 3, 4),
                'accountNumber' => substr($bban, 7, 16),
            ],
            // Dänemark, Färöer, Grönland: 4 Bank + 10 Konto
            'DK', 'FO', 'GL' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 10),
            ],
            // Schweden: 3 Bank + 17 Konto
            'SE' => [
                'bankCode' => substr($bban, 0, 3),
                'accountNumber' => substr($bban, 3, 17),
            ],
            // Norwegen: 4 Bank + 7 Konto
            'NO' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 7),
            ],
            // Finnland: 3 Bank + 11 Konto
            'FI' => [
                'bankCode' => substr($bban, 0, 3),
                'accountNumber' => substr($bban, 3, 11),
            ],
            // Estland: 2 Bank + 14 Konto
            'EE' => [
                'bankCode' => substr($bban, 0, 2),
                'accountNumber' => substr($bban, 2, 14),
            ],
            // Lettland: 4 Bank + 13 Konto
            'LV' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 13),
            ],
            // Litauen: 5 Bank + 11 Konto
            'LT' => [
                'bankCode' => substr($bban, 0, 5),
                'accountNumber' => substr($bban, 5, 11),
            ],
            // Kroatien: 7 Bank + 10 Konto
            'HR' => [
                'bankCode' => substr($bban, 0, 7),
                'accountNumber' => substr($bban, 7, 10),
            ],
            // Slowenien: 5 Bank + 10 Konto
            'SI' => [
                'bankCode' => substr($bban, 0, 5),
                'accountNumber' => substr($bban, 5, 10),
            ],
            // Rumänien: 4 Bank + 16 Konto
            'RO' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 16),
            ],
            // Bulgarien: 4 Bank + 4 Filiale + 2 Typ + 8 Konto
            'BG' => [
                'bankCode' => substr($bban, 0, 4),
                'branchCode' => substr($bban, 4, 4),
                'accountType' => substr($bban, 8, 2),
                'accountNumber' => substr($bban, 10, 8),
            ],
            // Zypern: 3 Bank + 5 Filiale + 16 Konto
            'CY' => [
                'bankCode' => substr($bban, 0, 3),
                'branchCode' => substr($bban, 3, 5),
                'accountNumber' => substr($bban, 8, 16),
            ],
            // Malta: 4 Bank + 5 Filiale + 18 Konto
            'MT' => [
                'bankCode' => substr($bban, 0, 4),
                'branchCode' => substr($bban, 4, 5),
                'accountNumber' => substr($bban, 9, 18),
            ],
            // Türkei: 5 Bank + 17 Konto
            'TR' => [
                'bankCode' => substr($bban, 0, 5),
                'accountNumber' => substr($bban, 5, 17),
            ],
            // Albanien: 8 Bank + 16 Konto
            'AL' => [
                'bankCode' => substr($bban, 0, 8),
                'accountNumber' => substr($bban, 8, 16),
            ],
            // Andorra: 4 Bank + 4 Filiale + 12 Konto
            'AD' => [
                'bankCode' => substr($bban, 0, 4),
                'branchCode' => substr($bban, 4, 4),
                'accountNumber' => substr($bban, 8, 12),
            ],
            // Aserbaidschan: 4 Bank + 20 Konto
            'AZ' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 20),
            ],
            // Bahrain: 4 Bank + 14 Konto
            'BH' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 14),
            ],
            // Bosnien und Herzegowina: 3 Bank + 3 Filiale + 10 Konto
            'BA' => [
                'bankCode' => substr($bban, 0, 3),
                'branchCode' => substr($bban, 3, 3),
                'accountNumber' => substr($bban, 6, 10),
            ],
            // Brasilien: 8 Bank + 5 Filiale + 10 Konto + 1 Typ + 1 Prüf
            'BR' => [
                'bankCode' => substr($bban, 0, 8),
                'branchCode' => substr($bban, 8, 5),
                'accountNumber' => substr($bban, 13, 10),
                'accountType' => substr($bban, 23, 1),
                'ownerAccountNumber' => substr($bban, 24, 1),
            ],
            // Costa Rica: 4 Bank + 14 Konto
            'CR' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 14),
            ],
            // Dominikanische Republik: 4 Bank + 20 Konto
            'DO' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 20),
            ],
            // Georgien: 2 Bank + 16 Konto
            'GE' => [
                'bankCode' => substr($bban, 0, 2),
                'accountNumber' => substr($bban, 2, 16),
            ],
            // Gibraltar: 4 Bank + 15 Konto
            'GI' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 15),
            ],
            // Guatemala: 4 Bank + 20 Konto
            'GT' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 20),
            ],
            // Island: 4 Bank + 2 Filiale + 6 Konto + 10 Kennnummer
            'IS' => [
                'bankCode' => substr($bban, 0, 4),
                'branchCode' => substr($bban, 4, 2),
                'accountNumber' => substr($bban, 6, 6),
                'identificationNumber' => substr($bban, 12, 10),
            ],
            // Israel: 3 Bank + 3 Filiale + 13 Konto
            'IL' => [
                'bankCode' => substr($bban, 0, 3),
                'branchCode' => substr($bban, 3, 3),
                'accountNumber' => substr($bban, 6, 13),
            ],
            // Jordanien: 4 Bank + 4 Filiale + 18 Konto
            'JO' => [
                'bankCode' => substr($bban, 0, 4),
                'branchCode' => substr($bban, 4, 4),
                'accountNumber' => substr($bban, 8, 18),
            ],
            // Kasachstan: 3 Bank + 13 Konto
            'KZ' => [
                'bankCode' => substr($bban, 0, 3),
                'accountNumber' => substr($bban, 3, 13),
            ],
            // Kosovo: 4 Bank + 12 Konto
            'XK' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 12),
            ],
            // Kuwait: 4 Bank + 22 Konto
            'KW' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 22),
            ],
            // Libanon: 4 Bank + 20 Konto
            'LB' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 20),
            ],
            // Mauretanien: 5 Bank + 5 Filiale + 11 Konto + 2 Prüf
            'MR' => [
                'bankCode' => substr($bban, 0, 5),
                'branchCode' => substr($bban, 5, 5),
                'accountNumber' => substr($bban, 10, 11),
                'nationalCheckDigits' => substr($bban, 21, 2),
            ],
            // Mauritius: 4 Bank + 2 Filiale + 18 Konto + 3 Währung
            'MU' => [
                'bankCode' => substr($bban, 0, 4),
                'branchCode' => substr($bban, 4, 2),
                'accountNumber' => substr($bban, 6, 18),
                'currencyCode' => substr($bban, 24, 3),
            ],
            // Moldawien: 2 Bank + 18 Konto
            'MD' => [
                'bankCode' => substr($bban, 0, 2),
                'accountNumber' => substr($bban, 2, 18),
            ],
            // Montenegro: 3 Bank + 15 Konto
            'ME' => [
                'bankCode' => substr($bban, 0, 3),
                'accountNumber' => substr($bban, 3, 15),
            ],
            // Pakistan: 4 Bank + 16 Konto
            'PK' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 16),
            ],
            // Palästina: 4 Bank + 21 Konto
            'PS' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 21),
            ],
            // Katar: 4 Bank + 21 Konto
            'QA' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 21),
            ],
            // Saudi-Arabien: 2 Bank + 18 Konto
            'SA' => [
                'bankCode' => substr($bban, 0, 2),
                'accountNumber' => substr($bban, 2, 18),
            ],
            // Serbien: 3 Bank + 15 Konto
            'RS' => [
                'bankCode' => substr($bban, 0, 3),
                'accountNumber' => substr($bban, 3, 15),
            ],
            // Tunesien: 2 Bank + 3 Filiale + 15 Konto
            'TN' => [
                'bankCode' => substr($bban, 0, 2),
                'branchCode' => substr($bban, 2, 3),
                'accountNumber' => substr($bban, 5, 15),
            ],
            // Vereinigte Arabische Emirate: 3 Bank + 16 Konto
            'AE' => [
                'bankCode' => substr($bban, 0, 3),
                'accountNumber' => substr($bban, 3, 16),
            ],
            // Britische Jungferninseln: 4 Bank + 16 Konto
            'VG' => [
                'bankCode' => substr($bban, 0, 4),
                'accountNumber' => substr($bban, 4, 16),
            ],
            // Fallback: Nur BBAN ohne weitere Zerlegung
            default => [],
        };
    }

    /**
     * Extrahiert den Ländercode aus einer IBAN.
     *
     * @param string|null $iban Die IBAN.
     * @return string|null Der 2-stellige ISO-Ländercode oder null bei ungültiger IBAN.
     */
    public static function getCountryCodeFromIBAN(?string $iban): ?string {
        if ($iban === null || strlen($iban) < 2) {
            return null;
        }

        $countryCode = strtoupper(substr($iban, 0, 2));
        $countries = self::countryLengths();

        return isset($countries[$countryCode]) ? $countryCode : null;
    }

    /**
     * Prüft, ob die IBAN zu einem bestimmten Land gehört.
     *
     * @param string|null $iban Die IBAN.
     * @param CountryCode|string $countryCode Der erwartete Ländercode.
     * @return bool True, wenn die IBAN zum angegebenen Land gehört.
     */
    public static function isIBANFromCountry(?string $iban, CountryCode|string $countryCode): bool {
        if ($countryCode instanceof CountryCode) {
            $countryCode = $countryCode->value;
        }

        $ibanCountry = self::getCountryCodeFromIBAN($iban);
        return $ibanCountry !== null && strtoupper($countryCode) === $ibanCountry;
    }

    /**
     * Prüft, ob die IBAN aus einem SEPA-Land stammt.
     *
     * @param string|null $iban Die IBAN.
     * @return bool True, wenn die IBAN aus einem SEPA-Land stammt.
     */
    public static function isSepaIBAN(?string $iban): bool {
        $countryCode = self::getCountryCodeFromIBAN($iban);
        if ($countryCode === null) {
            return false;
        }

        return in_array($countryCode, self::sepaCountries(), true);
    }

    /**
     * Gibt die Liste der SEPA-Länder zurück.
     *
     * @return string[] ISO-2 Ländercodes aller SEPA-Teilnehmerländer.
     */
    private static function sepaCountries(): array {
        return [
            // EU-Mitgliedstaaten
            'AT',
            'BE',
            'BG',
            'HR',
            'CY',
            'CZ',
            'DK',
            'EE',
            'FI',
            'FR',
            'DE',
            'GR',
            'HU',
            'IE',
            'IT',
            'LV',
            'LT',
            'LU',
            'MT',
            'NL',
            'PL',
            'PT',
            'RO',
            'SK',
            'SI',
            'ES',
            'SE',
            // EWR-Länder
            'IS',
            'LI',
            'NO',
            // Weitere SEPA-Teilnehmer
            'CH',
            'MC',
            'SM',
            'AD',
            'VA',
            'GB',
            'GI',
            // Territorien
            'GG',
            'IM',
            'JE', // Kanalinseln & Isle of Man
            'PM',
            'BL',
            'MF',
            'GP',
            'MQ',
            'GF',
            'RE',
            'YT', // Französische Überseegebiete
        ];
    }

    /**
     * Extrahiert den Bank-Code aus einer IBAN (länderspezifisch).
     *
     * @param string|null $iban Die IBAN.
     * @return string|null Der Bank-Code oder null, wenn nicht verfügbar.
     */
    public static function getBankCodeFromIBAN(?string $iban): ?string {
        $components = self::splitIBANComponents($iban);
        return $components['bankCode'] ?? null;
    }

    /**
     * Extrahiert die Kontonummer aus einer IBAN (länderspezifisch).
     *
     * @param string|null $iban Die IBAN.
     * @return string|null Die Kontonummer oder null, wenn nicht verfügbar.
     */
    public static function getAccountNumberFromIBAN(?string $iban): ?string {
        $components = self::splitIBANComponents($iban);
        return $components['accountNumber'] ?? null;
    }

    /**
     * Lädt die aktuelle BLZ-Liste von der Deutschen Bundesbank.
     *
     * @return array
     */
    private static function loadBundesbankBLZData(): array {
        $configLoader = ConfigLoader::getInstance(self::$logger);
        $configLoader->loadConfigFile(__DIR__ . '/../../../config/helper.json');

        $path = $configLoader->get('Bundesbank', 'file', 'data/blz-aktuell-txt-data.txt');
        $url = $configLoader->get('Bundesbank', 'resourceurl', '');
        $expiry = $configLoader->get('Bundesbank', 'expiry_days', 365);

        return self::loadDataFile($path, $url, $expiry);
    }

    /**
     * Lädt die aktuelle BIC-Liste von der Deutschen Bundesbank.
     *
     * @return array
     */
    private static function loadBundesbankBICData(): array {
        $configLoader = ConfigLoader::getInstance(self::$logger);
        $configLoader->loadConfigFile(__DIR__ . '/../../../config/helper.json');

        $path = $configLoader->get('Zahlungsdienstleister', 'file', 'data/verzeichnis-der-erreichbaren-zahlungsdienstleister-data.csv');
        $url = $configLoader->get('Zahlungsdienstleister', 'resourceurl', '');
        $expiry = $configLoader->get('Zahlungsdienstleister', 'expiry_days', 365);

        return self::loadDataFile($path, $url, $expiry);
    }

    /**
     * Lädt eine Datei von einer URL oder vom lokalen Dateisystem.
     *
     * @param string $path Der Pfad zur Datei.
     * @param string|null $url Die URL, von der die Datei geladen werden soll.
     * @param int $expiry Die Anzahl der Tage, nach denen die Datei als abgelaufen betrachtet wird.
     * @return array Der Inhalt der Datei als Array von Zeilen.
     */
    private static function loadDataFile(string $path, ?string $url = null, int $expiry = 365): array {
        if (!File::isAbsolutePath($path)) {
            $path = __DIR__ . '/../../../' . $path;
            if (!Folder::exists(dirname($path))) {
                Folder::create(dirname($path));
            }
        }

        if (!file_exists($path) || filemtime($path) < strtotime("-$expiry days")) {
            if (!empty($url)) {
                try {
                    File::write($path, File::read($url));
                } catch (Exception) {
                    // URL nicht erreichbar - bestehende Datei behalten falls vorhanden
                }
            }
        }

        return file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * Gibt die Länge der IBAN für verschiedene Länder zurück.
     *
     * @return array
     */
    private static function countryLengths(): array {
        return [
            'AL' => 28, // Albanien
            'AD' => 24, // Andorra
            'AT' => 20, // Österreich
            'AZ' => 28, // Aserbaidschan
            'BH' => 22, // Bahrain
            'BE' => 16, // Belgien
            'BA' => 20, // Bosnien und Herzegowina
            'BR' => 29, // Brasilien
            'BG' => 22, // Bulgarien
            'CR' => 22, // Costa Rica
            'HR' => 21, // Kroatien
            'CY' => 28, // Zypern
            'CZ' => 24, // Tschechien
            'DK' => 18, // Dänemark
            'DO' => 28, // Dominikanische Republik
            'EE' => 20, // Estland
            'FO' => 18, // Färöer
            'FI' => 18, // Finnland
            'FR' => 27, // Frankreich
            'GE' => 22, // Georgien
            'DE' => 22, // Deutschland
            'GI' => 23, // Gibraltar
            'GR' => 27, // Griechenland
            'GL' => 18, // Grönland
            'GT' => 28, // Guatemala
            'HU' => 28, // Ungarn
            'IS' => 26, // Island
            'IE' => 22, // Irland
            'IL' => 23, // Israel
            'IT' => 27, // Italien
            'JO' => 30, // Jordanien
            'KZ' => 20, // Kasachstan
            'XK' => 20, // Kosovo
            'KW' => 30, // Kuwait
            'LV' => 21, // Lettland
            'LB' => 28, // Libanon
            'LI' => 21, // Liechtenstein
            'LT' => 20, // Litauen
            'LU' => 20, // Luxemburg
            'MT' => 31, // Malta
            'MR' => 27, // Mauretanien
            'MU' => 30, // Mauritius
            'MD' => 24, // Moldawien
            'MC' => 27, // Monaco
            'ME' => 22, // Montenegro
            'NL' => 18, // Niederlande
            'NO' => 15, // Norwegen
            'PK' => 24, // Pakistan
            'PS' => 29, // Palästina
            'PL' => 28, // Polen
            'PT' => 25, // Portugal
            'QA' => 29, // Katar
            'RO' => 24, // Rumänien
            'SM' => 27, // San Marino
            'SA' => 24, // Saudi-Arabien
            'RS' => 22, // Serbien
            'SK' => 24, // Slowakei
            'SI' => 19, // Slowenien
            'ES' => 24, // Spanien
            'SE' => 24, // Schweden
            'CH' => 21, // Schweiz
            'TN' => 24, // Tunesien
            'TR' => 26, // Türkei
            'AE' => 23, // Vereinigte Arabische Emirate
            'GB' => 22, // Großbritannien
            'VG' => 24, // Britische Jungferninseln
            // TODO: Add more countries
        ];
    }

    /**
     * Gibt eine Zuordnung von Buchstaben zu Zahlen zurück, die für die IBAN-Prüfziffernberechnung verwendet wird.
     *
     * @return array
     */
    private static function ibanCharMap(): array {
        return array_combine(range('a', 'z'), range(10, 35));
    }

    private static function requireBcMath(): void {
        if (!function_exists('bcmod')) {
            self::logErrorAndThrow(RuntimeException::class, "Die PHP-Erweiterung 'bcmath' ist erforderlich, aber nicht aktiviert.");
        }
    }

    /**
     * Leert alle gecachten Daten.
     *
     * Nützlich für Tests oder wenn Bundesbank-Daten aktualisiert wurden.
     */
    public static function clearCache(): void {
        self::$blzIndex = null;
        self::$bicIndex = null;
    }
}