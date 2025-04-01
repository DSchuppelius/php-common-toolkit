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

use ConfigToolkit\ConfigLoader;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;

class BankHelper {
    use ErrorLog;

    public static function isBLZ(?string $value): bool {
        return $value !== null && preg_match("/^[0-9]{8}\$/", $value) === 1;
    }

    public static function isKTO(?string $value): bool {
        return $value !== null && preg_match("/^[0-9]{10}\$/", $value) === 1;
    }

    public static function isIBAN(?string $value): bool {
        if ($value === null || preg_match("/X{5,}/", $value)) return false;
        $value = str_replace(' ', '', $value);
        return preg_match("/^[A-Z]{2}[A-Z0-9]{14,33}\$/", $value) === 1;
    }

    public static function isIBANAnon(?string $value): bool {
        return $value !== null && preg_match("/^[A-Z]{2}XX[0-9]{11}XXXX[0-9]{3}\$/", $value) === 1;
    }

    public static function isBIC(?string $value): bool {
        return $value !== null && preg_match("/^[A-Z]{6}[2-9A-Z][0-9A-NP-Z]([A-Z0-9]{3}|x{3})?\$/", $value) === 1;
    }

    public static function checkIBAN(string $iban): bool {
        $iban = strtoupper(str_replace(' ', '', $iban));
        if (!self::isIBAN($iban)) {
            return false;
        }

        $countries = self::countryLengths();
        $chars = self::ibanCharMap();

        $countryCode = strtolower(substr($iban, 0, 2));
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

    public static function generateIBAN(string $countryCode, string $accountNumber): string {
        $countryCode = strtolower($countryCode);
        $countries = self::countryLengths();
        $chars = self::ibanCharMap();

        if (!isset($countries[$countryCode])) {
            throw new InvalidArgumentException("Invalid country code: $countryCode");
        }

        $tmp = $accountNumber . $countryCode . '00';
        $converted = '';
        foreach (str_split($tmp) as $char) {
            $converted .= is_numeric($char) ? $char : $chars[$char] ?? '';
        }

        $checksum = 98 - (int) bcmod($converted, '97');
        return strtoupper($countryCode . str_pad((string)$checksum, 2, '0', STR_PAD_LEFT) . $accountNumber);
    }

    public static function bicFromIBAN(string $iban): string {
        $blz = substr($iban, 4, 8);
        $data = self::loadBundesbankData();
        foreach ($data as $entry) {
            if (substr($entry, 0, 8) === $blz) {
                return trim(substr($entry, 139, 11));
            }
        }
        return '';
    }

    public static function checkBIC(string $bic, array $bicList): string {
        $bic = strtoupper(substr(trim($bic), 0, 8));
        foreach ($bicList as $entry) {
            $fields = explode(";", $entry);
            if (strtoupper(substr($fields[0], 0, 8)) === $bic) {
                return $bic . "XXX " . $fields[1];
            }
        }
        return '';
    }

    public static function splitIBAN(?string $iban): false|array {
        if ($iban === null || strlen($iban) < 22) {
            return false;
        }
        return [
            'BLZ' => substr($iban, 4, 8),
            'KTO' => substr($iban, 12, 10)
        ];
    }

    private static function loadBundesbankData(): array {
        self::setLogger();
        $configLoader = ConfigLoader::getInstance(self::$logger);
        $configLoader->loadConfigFile(__DIR__ . '/../../../config/helper.json');

        $path = $configLoader->get('Bundesbank', 'file', 'data/blz-aktuell-txt-data.txt');
        $url = $configLoader->get('Bundesbank', 'resourceurl', '');
        $expiry = $configLoader->get('Bundesbank', 'expiry_days', 365);

        if (!file_exists($path) || filemtime($path) < strtotime("-$expiry days")) {
            if (!empty($url)) {
                @file_put_contents($path, file_get_contents($url));
            }
        }

        return file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    private static function countryLengths(): array {
        return [
            'de' => 22,
            'at' => 20,
            'ch' => 21,
            'nl' => 18,
            'be' => 16,
            'fr' => 27,
            'it' => 27,
            'es' => 24,
            'gb' => 22,
            'pl' => 28,
            'se' => 24,
            'fi' => 18,
            'pt' => 25,
            'dk' => 18,
            'ie' => 22,
            'no' => 15,
            'cz' => 24,
            'sk' => 24,
            'hu' => 28,
            'lt' => 20,
            'lu' => 20,
            'si' => 19,
            'lv' => 21,
            'ee' => 20,
            'ro' => 24,
            'bg' => 22,
            'hr' => 21,
            'cy' => 28,
            'mt' => 31,
            'li' => 21,
            'is' => 26,
            'tr' => 26
        ];
    }

    private static function ibanCharMap(): array {
        return array_combine(range('a', 'z'), range(10, 35));
    }
}
