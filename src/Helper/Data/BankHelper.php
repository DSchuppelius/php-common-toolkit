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
use InvalidArgumentException;
use RuntimeException;

class BankHelper {
    use ErrorLog;

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
     */
    public static function generateGermanIBAN(string $blz, string $kto): string {
        $account = $blz . str_pad($kto, 10, '0', STR_PAD_LEFT);
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
            throw new InvalidArgumentException("Invalid country code: $countryCode");
        }

        $expectedLength = $countries[$countryCode] - 4; // ohne Prüfziffer und Länderkennung
        if (strlen($accountNumber) !== $expectedLength) {
            self::logDebug("Die Kontonummer hat nicht die richtige Länge ($expectedLength) für $countryCode. Eingabe: '$accountNumber'");
            throw new InvalidArgumentException("Ungültige Kontonummer-Länge für $countryCode");
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
     * @param string $iban Die IBAN.
     * @return string Die BIC oder ein leerer String, wenn keine BIC gefunden wurde.
     */
    public static function bicFromIBAN(string $iban): string {
        $blz = substr($iban, 4, 8);
        $data = self::loadBundesbankBLZData();
        foreach ($data as $entry) {
            if (substr($entry, 0, 8) === $blz) {
                return trim(substr($entry, 139, 11));
            }
        }
        return '';
    }

    /**
     * Überprüft die BIC und gibt die BIC inkl. Banknamen zurück.
     *
     * @param string $bic Die BIC.
     * @return string|false Der Bankname oder false bei ungültiger BIC.
     */
    public static function checkBIC(string $bic): string|false {
        $bic = strtoupper(substr(trim($bic), 0, 8));
        $data = self::loadBundesbankBICData();
        foreach ($data as $entry) {
            $fields = explode(";", $entry);
            if (strtoupper(substr($fields[0], 0, 8)) === $bic) {
                return $bic . "XXX " . $fields[1];
            }
        }
        return false;
    }

    /**
     * Gibt die BLZ und KTO aus einer IBAN zurück.
     *
     * @param string|null $iban Die IBAN.
     * @return false|array Ein Array mit 'BLZ' und 'KTO' oder false bei ungültiger IBAN.
     */
    public static function splitIBAN(?string $iban): array|false {
        if ($iban === null || strlen($iban) < 22) {
            return false;
        }
        return [
            'BLZ' => substr($iban, 4, 8),
            'KTO' => substr($iban, 12, 10)
        ];
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
                @file_put_contents($path, file_get_contents($url));
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
            self::logError("bcmath nicht verfügbar.");
            throw new RuntimeException("Die PHP-Erweiterung 'bcmath' ist erforderlich, aber nicht aktiviert.");
        }
    }
}