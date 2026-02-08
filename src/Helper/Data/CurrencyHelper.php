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

use CommonToolkit\Enums\CurrencyCode;
use ERRORToolkit\Traits\ErrorLog;
use NumberFormatter;
use Locale;
use RuntimeException;

class CurrencyHelper {
    use ErrorLog;

    /**
     * Überprüft, ob die PHP Intl-Extension verfügbar ist
     *
     * @throws RuntimeException
     */
    private static function ensureNumberFormatterAvailable(): void {
        if (!class_exists(NumberFormatter::class)) {
            self::logErrorAndThrow(RuntimeException::class, "Die PHP Intl-Extension (intl) ist nicht aktiv. NumberFormatter nicht verfügbar.");
        }
    }

    /**
     * Formatiert einen Betrag mit Währung nach aktuellem Gebietsschema
     *
     * @param float $amount
     * @param CurrencyCode|string $currency
     * @param string|null $locale
     * @return string
     */
    public static function format(float $amount, CurrencyCode|string $currency = CurrencyCode::Euro, ?string $locale = null): string {
        self::ensureNumberFormatterAvailable();

        $locale ??= Locale::getDefault();
        $currencyCode = $currency instanceof CurrencyCode ? $currency->value : $currency;
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $formatted = $formatter->formatCurrency($amount, $currencyCode);

        if ($formatted === false) {
            self::logErrorAndThrow(RuntimeException::class, "Fehler bei der Währungsformatierung: " . $formatter->getErrorMessage());
        }

        return $formatted;
    }

    /**
     * Parst einen Betrag mit Währung nach aktuellem Gebietsschema
     *
     * @param string $input
     * @param CurrencyCode|string $currency
     * @param string|null $locale
     * @return float
     */
    public static function parse(string $input, CurrencyCode|string $currency = CurrencyCode::Euro, ?string $locale = null): float {
        self::ensureNumberFormatterAvailable();

        $locale ??= Locale::getDefault();
        $currencyCode = $currency instanceof CurrencyCode ? $currency->value : $currency;
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $parsed = $formatter->parseCurrency($input, $parsedCurrency);

        if ($parsed === false || $parsedCurrency !== $currencyCode) {
            self::logErrorAndThrow(RuntimeException::class, "Fehler beim Währungsparsing für Eingabe '$input': " . $formatter->getErrorMessage());
        }

        return $parsed;
    }

    /**
     * Rundet einen Betrag auf die angegebene Anzahl von Dezimalstellen
     *
     * @param float $amount
     * @param int $precision
     * @return float
     */
    public static function round(float $amount, int $precision = 2): float {
        return round($amount, $precision);
    }

    /**
     * Vergleicht zwei Beträge mit einer Toleranz
     *
     * @param float $a
     * @param float $b
     * @param float $tolerance
     * @return bool
     */
    public static function equals(float $a, float $b, float $tolerance = 0.01): bool {
        return round(abs($a - $b), 10) <= $tolerance;
    }

    /**
     * Erkennt das Währungsformat eines Betrags.
     *
     * @param string $input Der zu prüfende Betrag
     * @return string|null 'US', 'DE' oder null wenn kein gültiges Format erkannt wurde
     */
    public static function detectCurrencyFormat(string $input): ?string {
        $format = null;
        self::isCurrency($input, $format);
        return $format;
    }

    /**
     * Überprüft, ob der Betrag im US- oder DE-Format vorliegt
     *
     * @param string $input
     * @param string|null $format
     * @return bool
     */
    public static function isCurrency(string $input, ?string &$format = null): bool {
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

        $format = null;
        return false;
    }

    /**
     * Wandelt einen Betrag vom US-Format ins DE-Format um
     *
     * @param string|null $amount
     * @return string
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
     *
     * @param string|null $amount
     * @return string
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

    /**
     * Entfernt Währungssymbole und -codes aus einem Betrag.
     *
     * Unterstützt:
     * - Symbole: € $ £ ¥ ₣ ₹ ₽ ₩ ₴ ₪ ₦ ₡ ₿ CHF
     * - Codes: EUR, USD, GBP, JPY, CHF, etc. (3-Buchstaben ISO-Codes)
     * - Unicode-Minuszeichen (–) wird zu normalem Minus (-) konvertiert
     * - Schweizer Tausendertrennzeichen (') wird entfernt
     *
     * @param string|null $amount Der Betrag mit Währungssymbolen
     * @param bool $trim Ob Whitespace getrimmt werden soll (default: true)
     * @return string Der bereinigte Betrag
     */
    public static function stripSymbols(?string $amount, bool $trim = true): string {
        if ($amount === null || $amount === '') {
            return '';
        }

        // Währungssymbole entfernen (mit UTF-8 Support)
        $amount = preg_replace('/[€$£¥₣₹₽₩₴₪₦₡₿]/u', '', $amount) ?? $amount;

        // CHF als Text entfernen (vor dem allgemeinen 3-Buchstaben-Pattern)
        $amount = preg_replace('/\bCHF\b/iu', '', $amount) ?? $amount;

        // 3-Buchstaben ISO-Währungscodes entfernen (EUR, USD, GBP, etc.)
        $amount = preg_replace('/\b[A-Z]{3}\b/u', '', $amount) ?? $amount;

        // Unicode-Minuszeichen (– U+2013, − U+2212) zu normalem Minus (-) konvertieren
        $amount = str_replace(['–', '−'], '-', $amount);

        // Schweizer Tausendertrennzeichen (Apostroph) entfernen
        $amount = str_replace("'", '', $amount);

        return $trim ? trim($amount) : $amount;
    }

    /**
     * Normalisiert einen Betrag: entfernt Währungssymbole und konvertiert ins deutsche Format.
     *
     * Kombiniert stripSymbols() mit Format-Erkennung und Konvertierung.
     *
     * @param string|null $amount Der Eingabebetrag
     * @return string Der normalisierte Betrag im deutschen Format (z.B. "1.234,56" oder "-99,00")
     */
    public static function normalizeAmount(?string $amount): string {
        if ($amount === null || $amount === '') {
            return '';
        }

        $cleaned = self::stripSymbols($amount);

        // Format erkennen und ggf. konvertieren
        $format = self::detectCurrencyFormat($cleaned);

        return match ($format) {
            'US' => self::usToDe($cleaned),
            'DE' => $cleaned,
            default => $cleaned,
        };
    }
}