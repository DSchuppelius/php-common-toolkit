<?php
/*
 * Created on   : Tue Apr 01 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CurrencyHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use ERRORToolkit\Traits\ErrorLog;
use NumberFormatter;
use Locale;
use RuntimeException;

class CurrencyHelper {
    use ErrorLog;

    private static function ensureNumberFormatterAvailable(): void {
        if (!class_exists(NumberFormatter::class)) {
            self::logError("Die PHP Intl-Extension (intl) ist nicht aktiv. NumberFormatter nicht verfügbar.");
            throw new RuntimeException("Die PHP Intl-Extension (intl) ist nicht aktiviert. Bitte aktiviere sie in deiner php.ini.");
        }
    }

    /**
     * Formatiert einen Betrag mit Währung nach aktuellem Gebietsschema
     */
    public static function format(float $amount, string $currency = 'EUR', ?string $locale = null): string {
        self::ensureNumberFormatterAvailable();

        $locale ??= Locale::getDefault();
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $formatted = $formatter->formatCurrency($amount, $currency);

        if ($formatted === false) {
            self::logError("Fehler bei der Währungsformatierung: " . $formatter->getErrorMessage());
            throw new RuntimeException("Fehler bei der Währungsformatierung: " . $formatter->getErrorMessage());
        }

        return $formatted;
    }

    /**
     * Gibt einen Betrag als Float zurück, z. B. aus einem Formularfeld
     */
    public static function parse(string $input, string $currency = 'EUR', ?string $locale = null): float {
        self::ensureNumberFormatterAvailable();

        $locale ??= Locale::getDefault();
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $parsed = $formatter->parseCurrency($input, $parsedCurrency);

        if ($parsed === false || $parsedCurrency !== $currency) {
            self::logError("Fehler beim Währungsparsing: " . $formatter->getErrorMessage());
            throw new RuntimeException("Ungültige Währungseingabe: '$input'");
        }

        return $parsed;
    }

    /**
     * Rundet den Betrag kaufmännisch auf zwei Stellen
     */
    public static function round(float $amount, int $precision = 2): float {
        return round($amount, $precision);
    }

    /**
     * Vergleicht zwei Währungsbeträge mit Toleranz
     */
    public static function equals(float $a, float $b, float $tolerance = 0.01): bool {
        return round(abs($a - $b), 10) <= $tolerance;
    }

    /**
     * Prüft, ob ein String ein valider Betrag im US- oder DE-Format ist
     */
    public static function isValid(string $input, string &$format = ''): bool {
        if ($input === null || trim($input) === '') return false;
        $input = trim($input);

        if (preg_match("/\A(-)?([0-9]+)((,)[0-9]{3})*((\.)[0-9])?([0-9]*)\z/", $input)) {
            $format = 'US';
            return true;
        }

        if (preg_match("/\A(-)?([0-9]+)((\.)[0-9]{3})*((,)[0-9])?([0-9]*)\z/", $input)) {
            $format = 'DE';
            return true;
        }

        if (preg_match("/\A(-|\+)?([0-9\.]+),\d{2}\z/", $input)) {
            $format = 'DE';
            return true;
        }

        return false;
    }

    /**
     * Wandelt einen Betrag vom US-Format ins DE-Format um
     */
    public static function usToDe(?string $amount): string {
        if ($amount === null || $amount === '') return '';

        $amount = trim(str_replace([" ", "+", "€"], '', $amount));
        $amount = trim($amount, "'");

        if (preg_match("/^[-0-9\.]*,[0-9]{0,2}\$/", $amount)) {
            return str_replace(".", '', $amount);
        }

        if (preg_match("/^[-0-9]+\.[0-9]{3}\$/", $amount)) {
            return str_replace(".", '', $amount);
        }

        $amount = str_replace(',', '', $amount);
        $amount = str_replace('.', ',', $amount);

        if (!str_contains($amount, ',')) {
            $amount .= ',00';
        }

        return $amount;
    }

    /**
     * Wandelt einen Betrag vom DE-Format ins US-Format um
     */
    public static function deToUs(?string $amount): string {
        if ($amount === null || $amount === '') return '';

        $amount = trim(str_replace([" ", "+"], '', $amount));
        $amount = trim($amount, "'");
        $amount = preg_replace("/[A-Z ]/", '', $amount);

        if (preg_match("/^[\-0-9,]*\.[0-9]{0,2}\$/", $amount)) {
            return str_replace(',', '', $amount);
        }

        if (preg_match("/^[\-0-9]+,[0-9]{3}\$/", $amount)) {
            return str_replace(',', '', $amount);
        }

        $amount = str_replace('.', '', $amount);
        return str_replace(',', '.', $amount);
    }
}
