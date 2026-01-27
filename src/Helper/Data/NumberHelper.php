<?php
/*
 * Created on   : Thu Apr 17 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : NumberHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Enums\MetricPrefix;
use CommonToolkit\Enums\TemperatureUnit;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use RuntimeException;

class NumberHelper {
    use ErrorLog;
    /**
     * Konvertiert eine Zahl in einen menschenlesbaren Byte-Wert (z. B. "2.5 GB").
     * @param int|float $bytes Die Anzahl der Bytes.
     * @param int $precision Die Anzahl der Dezimalstellen.
     * @return string Der formatierte Byte-Wert.
     */
    public static function formatBytes(int|float $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);

        $normalized = $bytes / (1024 ** $pow);
        return round($normalized, $precision) . ' ' . $units[$pow];
    }

    /**
     * Konvertiert einen menschenlesbaren Byte-Wert (z. B. "2.5 GB") in eine Ganzzahl.
     * @param string $input Der menschenlesbare Byte-Wert.
     * @return int Die Anzahl der Bytes.
     * @throws RuntimeException Wenn das Format ungültig ist.
     */
    public static function parseByteString(string $input): int {
        if (!preg_match('/^([\d\.,]+)\s*(B|KB|MB|GB|TB|PB)$/i', trim($input), $matches)) {
            self::logErrorAndThrow(RuntimeException::class, "Ungültiges Format: '$input'");
        }

        $value = (float) str_replace(',', '.', $matches[1]);
        $unit = strtoupper($matches[2]);
        $factor = match ($unit) {
            'B'  => 1,
            'KB' => 1024,
            'MB' => 1024 ** 2,
            'GB' => 1024 ** 3,
            'TB' => 1024 ** 4,
            'PB' => 1024 ** 5,
        };

        return (int) round($value * $factor);
    }

    /**
     * Konvertiert eine Temperatur von einer Einheit in eine andere.
     * @param float $value Der Temperaturwert.
     * @param TemperatureUnit $from Die Einheit, von der konvertiert wird.
     * @param TemperatureUnit $to Die Einheit, in die konvertiert wird.
     * @return float Der konvertierte Temperaturwert.
     */
    public static function convertTemperature(float $value, TemperatureUnit $from, TemperatureUnit $to): float {
        if ($from === $to) return $value;

        return match ("{$from->value}-{$to->value}") {
            'C-F' => $value * 9 / 5 + 32,
            'F-C' => ($value - 32) * 5 / 9,
            'C-K' => $value + 273.15,
            'K-C' => $value - 273.15,
            'F-K' => ($value - 32) * 5 / 9 + 273.15,
            'K-F' => ($value - 273.15) * 9 / 5 + 32,
            default => self::logErrorAndThrow(RuntimeException::class, "Ungültige Temperaturumrechnung: {$from->value} zu {$to->value}")
        };
    }

    /**
     * Konvertiert eine Zahl von einer metrischen Einheit in eine andere.
     * @param float $value Der Wert, der konvertiert werden soll.
     * @param string $fromUnit Die Einheit, von der konvertiert wird. z.B. "km", "ml", "g"
     * @param string $toUnit Die Einheit, in die konvertiert wird. z.B. "m", "l", "kg"
     * @param int $baseFactor Der Basisfaktor (Standard ist 10).
     * @return float Der konvertierte Wert.
     */
    public static function convertMetric(float $value, string $fromUnit, string $toUnit, int $baseFactor = 10): float {
        $prefixes = MetricPrefix::prefixMap();
        $sortedPrefixes = array_keys($prefixes);
        usort($sortedPrefixes, fn($a, $b) => strlen($b) <=> strlen($a)); // längste zuerst

        $getPrefix = function (string $unit) use ($sortedPrefixes): array {
            foreach ($sortedPrefixes as $prefix) {
                $suffix = substr($unit, strlen($prefix));
                if ($prefix !== '' && str_starts_with($unit, $prefix) && $suffix !== '') {
                    return [$prefix, $suffix];
                }
            }
            return ['', $unit]; // Kein Präfix erkannt → gesamte Einheit ist Basiseinheit
        };

        [$fromPrefix, $fromBase] = $getPrefix($fromUnit);
        [$toPrefix, $toBase] = $getPrefix($toUnit);

        if ($fromBase !== $toBase) {
            self::logErrorAndThrow(RuntimeException::class, "Uneinheitliche Basiseinheit: $fromBase zu $toBase");
        }

        $fromExp = $prefixes[$fromPrefix] ?? 0;
        $toExp = $prefixes[$toPrefix] ?? 0;
        $expDiff = $fromExp - $toExp;

        return $value * ($baseFactor ** $expDiff);
    }

    /**
     * Rundet eine Zahl auf die nächste ganze Zahl.
     * @param float $value Der Wert, der gerundet werden soll.
     * @return int Der gerundete Wert.
     */
    public static function roundToNearest(float $value, int $nearest): float {
        return round($value / $nearest) * $nearest;
    }

    /**
     * Fixiert eine Zahl auf einen bestimmten Bereich und gibt den den entsprechenden Wert zurück.
     * Bei Über- oder Unterlauf wird der Wert auf den entsprechenden Grenzwert gesetzt.
     *
     * @param float $value
     * @param float $min
     * @param float $max
     * @return float
     */
    public static function clamp(float $value, float $min, float $max): float {
        return min(max($value, $min), $max);
    }

    /**
     * Konvertiert einen Betrag zu deutschem Format mit optionalen Tausendertrennern.
     * 
     * Akzeptiert US-Format (1,234.56), deutsches Format (1.234,56) und gemischte Formate.
     * Normalisiert den Eingabestring und gibt das deutsche Format zurück.
     * Ohne Tausendertrenner (Standard) ideal für Datenfelder, CSV, APIs.
     * 
     * @param string|float|int $amount Betrag in beliebigem Format.
     * @param int $decimals Anzahl Dezimalstellen (Standard: 2).
     * @param bool $withThousandsSeparator Tausendertrenner anzeigen (Standard: false).
     * @return string Betrag im deutschen Format (z.B. "1234,56" oder "1.234,56").
     * 
     * @see formatCurrency() Für Anzeige-Formatierung mit Währungssymbol und Tausendertrennern.
     */
    public static function toGermanFormat(string|float|int $amount, int $decimals = 2, bool $withThousandsSeparator = false): string {
        if (is_string($amount)) {
            $amount = trim($amount);
            if ($amount === '') {
                return number_format(0, $decimals, ',', $withThousandsSeparator ? '.' : '');
            }
            $floatVal = self::normalizeDecimal($amount);
        } else {
            $floatVal = (float) $amount;
        }

        return number_format($floatVal, $decimals, ',', $withThousandsSeparator ? '.' : '');
    }

    /**
     * Konvertiert einen Betrag zu US-Format mit optionalen Tausendertrennern.
     * 
     * Akzeptiert deutsches Format (1.234,56), US-Format (1,234.56) und gemischte Formate.
     * Ohne Tausendertrenner (Standard) ideal für Datenfelder, CSV, APIs.
     * 
     * @param string|float|int $amount Betrag in beliebigem Format.
     * @param int $decimals Anzahl Dezimalstellen (Standard: 2).
     * @param bool $withThousandsSeparator Tausendertrenner anzeigen (Standard: false).
     * @return string Betrag im US-Format (z.B. "1234.56" oder "1,234.56").
     */
    public static function toUSFormat(string|float|int $amount, int $decimals = 2, bool $withThousandsSeparator = false): string {
        if (is_string($amount)) {
            $amount = trim($amount);
            if ($amount === '') {
                return number_format(0, $decimals, '.', $withThousandsSeparator ? ',' : '');
            }
            $floatVal = self::normalizeDecimal($amount);
        } else {
            $floatVal = (float) $amount;
        }

        return number_format($floatVal, $decimals, '.', $withThousandsSeparator ? ',' : '');
    }

    /**
     * Normalisiert eine Dezimalzahl mit automatischer Format-Erkennung.
     * 
     * Unterstützt:
     * - Deutsches Format: 1.234,56 → 1234.56
     * - US-Format: 1,234.56 → 1234.56
     * - Einfache Formate: 1,5 oder 1.5
     * 
     * Bei Mehrdeutigkeit (nur ein Trenner mit genau 3 Nachkommastellen) wird
     * Dezimal bevorzugt. Für eindeutige Tausender-Erkennung beide Trenner verwenden
     * (z.B. "1.234,00" oder "1,234.00").
     * 
     * @param string $value Der zu normalisierende Wert.
     * @return float Der normalisierte Wert.
     */
    public static function normalizeDecimal(string $value): float {
        $value = trim(str_replace(' ', '', $value));
        if ($value === '') return 0.0;

        // Position von Punkt und Komma finden
        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        // Beide vorhanden: das letzte ist Dezimaltrenner
        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                // Deutsches Format: 1.234,56
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                // US Format: 1,234.56
                $value = str_replace(',', '', $value);
            }
        } elseif ($lastComma !== false) {
            // Nur Komma vorhanden → immer als Dezimaltrenner behandeln
            // (wie im deutschen Format üblich)
            $value = str_replace(',', '.', $value);
        }
        // Nur Punkt: PHP versteht es bereits als Dezimal

        return (float) $value;
    }

    /**
     * Berechnet den Prozentsatz eines Teils im Verhältnis zu einem Gesamtwert.
     * @param float $part Der Teilwert.
     * @param float $total Der Gesamtwert.
     * @return float Der Prozentsatz des Teils im Verhältnis zum Gesamtwert.
     */
    public static function percentage(float $part, float $total): float {
        return $total !== 0.0 ? ($part / $total) * 100 : 0.0;
    }

    /**
     * Erkennt das Format einer Zahl und gibt ein generisches Template basierend auf der Input-Länge zurück.
     *
     * @param string $value Der zu analysierende Zahlenwert
     * @return string|null Format-Template angepasst an die Input-Struktur oder null wenn nicht erkannt
     */
    public static function detectNumberFormat(string $value): ?string {
        $trimmed = trim($value);
        if ($trimmed === '' || !is_numeric(str_replace(['.', ',', ' '], '', $trimmed))) {
            return null;
        }

        // Negative Zahlen: Vorzeichen entfernen für Template-Generierung
        $workingValue = ltrim($trimmed, '-');

        // Einfache Ganzzahlen (ohne Trennzeichen)
        if (preg_match('/^\d+$/', $workingValue)) {
            return str_repeat('0', strlen($workingValue));
        }

        // Deutsche/Europäische Formate: Punkt als Tausender, Komma als Dezimal

        // Format: 1.234.567,89 (deutsches Format mit Tausendertrennern)
        if (preg_match('/^(\d{1,3})(?:\.(\d{3}))*,(\d+)$/', $workingValue, $matches)) {
            $beforeComma = $matches[1];
            $afterComma = end($matches); // Letzte Gruppe = Nachkommastellen

            // Template für Vorkommastellen generieren
            $template = str_repeat('0', strlen($beforeComma));
            // Punkt-getrennte 3er-Gruppen hinzufügen
            $groupCount = substr_count($workingValue, '.');
            for ($i = 0; $i < $groupCount; $i++) {
                $template .= '.000';
            }
            // Nachkommastellen hinzufügen
            $template .= ',' . str_repeat('0', strlen($afterComma));

            return $template;
        }

        // Format: 1.234 (deutsche Ganzzahl mit Tausendertrennern)
        if (preg_match('/^(\d{1,3})(?:\.(\d{3}))+$/', $workingValue, $matches)) {
            $beforePoint = $matches[1];
            $template = str_repeat('0', strlen($beforePoint));

            // Punkt-getrennte 3er-Gruppen hinzufügen
            $groupCount = substr_count($workingValue, '.');
            for ($i = 0; $i < $groupCount; $i++) {
                $template .= '.000';
            }

            return $template;
        }

        // US/Anglo Formate: Komma als Tausender, Punkt als Dezimal

        // Format: 1,234,567.89 (US Format mit Tausendertrennern)
        if (preg_match('/^(\d{1,3})(?:,(\d{3}))*\.(\d+)$/', $workingValue, $matches)) {
            $beforeComma = $matches[1];
            $afterDot = end($matches); // Letzte Gruppe = Nachkommastellen

            // Template für Vorkommastellen generieren
            $template = str_repeat('0', strlen($beforeComma));
            // Komma-getrennte 3er-Gruppen hinzufügen
            $groupCount = substr_count($workingValue, ',');
            for ($i = 0; $i < $groupCount; $i++) {
                $template .= ',000';
            }
            // Nachkommastellen hinzufügen
            $template .= '.' . str_repeat('0', strlen($afterDot));

            return $template;
        }

        // Format: 1,234 (US Ganzzahl mit Tausendertrennern)
        if (preg_match('/^(\d{1,3})(?:,(\d{3}))+$/', $workingValue, $matches)) {
            $beforeComma = $matches[1];
            $template = str_repeat('0', strlen($beforeComma));

            // Komma-getrennte 3er-Gruppen hinzufügen
            $groupCount = substr_count($workingValue, ',');
            for ($i = 0; $i < $groupCount; $i++) {
                $template .= ',000';
            }

            return $template;
        }

        // Einfache Dezimalformate

        // Format: 100,18 (einfaches deutsches Format)
        if (preg_match('/^(\d+),(\d+)$/', $workingValue, $matches)) {
            $beforeComma = $matches[1];
            $afterComma = $matches[2];
            return str_repeat('0', strlen($beforeComma)) . ',' . str_repeat('0', strlen($afterComma));
        }

        // Format: 100.18 (einfaches US Format)
        if (preg_match('/^(\d+)\.(\d+)$/', $workingValue, $matches)) {
            $beforeDot = $matches[1];
            $afterDot = $matches[2];
            return str_repeat('0', strlen($beforeDot)) . '.' . str_repeat('0', strlen($afterDot));
        }

        return null;
    }

    /**
     * Formatiert eine Zahl gemäß einem dynamischen Format-Template.
     * 
     * @param float|int $number Die zu formatierende Zahl
     * @param string $formatTemplate Template angepasst an die Input-Struktur
     * @return string Die formatierte Zahl
     */
    public static function formatNumberByTemplate(float|int $number, string $formatTemplate): string {
        // Einfache Ganzzahlen: nur Nullen (z.B. "000", "00000")
        if (preg_match('/^0+$/', $formatTemplate)) {
            $intValue = (int) $number;
            $result = (string) abs($intValue);
            // Auf Template-Länge auffüllen (linksbündig mit Nullen)
            $result = str_pad($result, strlen($formatTemplate), '0', STR_PAD_LEFT);
            return $number < 0 ? '-' . $result : $result;
        }

        // Ganzzahlen mit Tausendertrennzeichen erkennen zuerst!
        // US: 0,000 oder 00,000 (Komma genau 3 Zeichen vor Ende, max 2 Nullen davor)
        if (preg_match('/^0{1,2},000$/', $formatTemplate)) {
            return number_format($number, 0, '', ',');
        }

        // Deutsche: 0.000 oder 00.000 (Punkt genau 3 Zeichen vor Ende, max 2 Nullen davor)  
        if (preg_match('/^0{1,2}\.000$/', $formatTemplate)) {
            return number_format($number, 0, '', '.');
        }

        // Deutsche Dezimalformate mit Komma
        if (str_contains($formatTemplate, ',') && preg_match('/,0+$/', $formatTemplate)) {
            $parts = explode(',', $formatTemplate);
            $afterComma = array_pop($parts);
            $beforeComma = implode(',', $parts);
            $decimalPlaces = strlen($afterComma);

            // Tausendertrennzeichen bestimmen
            $thousandsSep = str_contains($beforeComma, '.') ? '.' : '';
            return number_format($number, $decimalPlaces, ',', $thousandsSep);
        }

        // US Dezimalformate mit Punkt
        if (str_contains($formatTemplate, '.') && preg_match('/\.0+$/', $formatTemplate)) {
            $parts = explode('.', $formatTemplate);
            $afterDot = array_pop($parts);
            $beforeDot = implode('.', $parts);
            $decimalPlaces = strlen($afterDot);

            // Tausendertrennzeichen bestimmen
            $thousandsSep = str_contains($beforeDot, ',') ? ',' : '';
            return number_format($number, $decimalPlaces, '.', $thousandsSep);
        }

        // Fallback
        return (string) $number;
    }

    /**
     * Formatiert einen Betrag als Währung.
     *
     * @param float|int $amount Der Betrag.
     * @param string $currencySymbol Das Währungssymbol (Standard: '€').
     * @param int $decimals Anzahl Dezimalstellen (Standard: 2).
     * @param string $decimalSeparator Dezimaltrennzeichen (Standard: ',').
     * @param string $thousandsSeparator Tausendertrennzeichen (Standard: '.').
     * @param bool $symbolBefore Symbol vor dem Betrag (Standard: false für deutsch).
     * @return string Der formatierte Währungsbetrag.
     */
    public static function formatCurrency(
        float|int $amount,
        string $currencySymbol = '€',
        int $decimals = 2,
        string $decimalSeparator = ',',
        string $thousandsSeparator = '.',
        bool $symbolBefore = false
    ): string {
        $formatted = number_format(abs($amount), $decimals, $decimalSeparator, $thousandsSeparator);
        $sign = $amount < 0 ? '-' : '';

        if ($symbolBefore) {
            return $sign . $currencySymbol . ' ' . $formatted;
        }

        return $sign . $formatted . ' ' . $currencySymbol;
    }

    /**
     * Konvertiert eine Zahl in ihre Ordinalform.
     *
     * @param int $number Die Zahl.
     * @param string $locale Die Sprache ('de' oder 'en', Standard: 'de').
     * @return string Die Ordinalform (z.B. '1.' oder '1st').
     */
    public static function ordinalize(int $number, string $locale = 'de'): string {
        if ($locale === 'de') {
            return $number . '.';
        }

        // Englische Ordinalzahlen
        $suffix = 'th';
        $lastDigit = $number % 10;
        $lastTwoDigits = $number % 100;

        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 13) {
            $suffix = 'th';
        } elseif ($lastDigit === 1) {
            $suffix = 'st';
        } elseif ($lastDigit === 2) {
            $suffix = 'nd';
        } elseif ($lastDigit === 3) {
            $suffix = 'rd';
        }

        return $number . $suffix;
    }

    /**
     * Konvertiert eine Zahl in Worte (deutsch).
     * Unterstützt Zahlen von 0 bis 999.999.999.999.
     *
     * @param int $number Die Zahl.
     * @param bool $capitalize Ersten Buchstaben groß (Standard: false).
     * @return string Die Zahl in Worten.
     */
    public static function toWords(int $number, bool $capitalize = false): string {
        if ($number === 0) {
            return $capitalize ? 'Null' : 'null';
        }

        $isNegative = $number < 0;
        $number = abs($number);

        $ones = [
            '',
            'eins',
            'zwei',
            'drei',
            'vier',
            'fünf',
            'sechs',
            'sieben',
            'acht',
            'neun',
            'zehn',
            'elf',
            'zwölf',
            'dreizehn',
            'vierzehn',
            'fünfzehn',
            'sechzehn',
            'siebzehn',
            'achtzehn',
            'neunzehn'
        ];
        $tens = ['', '', 'zwanzig', 'dreißig', 'vierzig', 'fünfzig', 'sechzig', 'siebzig', 'achtzig', 'neunzig'];

        $convertBelow1000 = function (int $n) use ($ones, $tens): string {
            if ($n === 0) return '';

            $result = '';

            if ($n >= 100) {
                $hundreds = (int) ($n / 100);
                $result .= ($hundreds === 1 ? 'ein' : $ones[$hundreds]) . 'hundert';
                $n %= 100;
            }

            if ($n >= 20) {
                $ten = (int) ($n / 10);
                $one = $n % 10;
                if ($one > 0) {
                    $oneWord = $one === 1 ? 'ein' : $ones[$one];
                    $result .= $oneWord . 'und' . $tens[$ten];
                } else {
                    $result .= $tens[$ten];
                }
            } elseif ($n > 0) {
                $result .= $ones[$n];
            }

            return $result;
        };

        $parts = [];

        // Milliarden
        if ($number >= 1_000_000_000) {
            $billions = (int) ($number / 1_000_000_000);
            $parts[] = ($billions === 1 ? 'eine Milliarde' : $convertBelow1000($billions) . ' Milliarden');
            $number %= 1_000_000_000;
        }

        // Millionen
        if ($number >= 1_000_000) {
            $millions = (int) ($number / 1_000_000);
            $parts[] = ($millions === 1 ? 'eine Million' : $convertBelow1000($millions) . ' Millionen');
            $number %= 1_000_000;
        }

        // Tausend
        if ($number >= 1000) {
            $thousands = (int) ($number / 1000);
            $parts[] = ($thousands === 1 ? 'ein' : $convertBelow1000($thousands)) . 'tausend';
            $number %= 1000;
        }

        // Rest unter 1000
        if ($number > 0) {
            $parts[] = $convertBelow1000($number);
        }

        $result = implode('', $parts);
        $result = ($isNegative ? 'minus ' : '') . $result;

        return $capitalize ? ucfirst($result) : $result;
    }

    /**
     * Prüft ob eine Zahl gerade ist.
     *
     * @param int $number Die Zahl.
     * @return bool True wenn gerade.
     */
    public static function isEven(int $number): bool {
        return $number % 2 === 0;
    }

    /**
     * Prüft ob eine Zahl ungerade ist.
     *
     * @param int $number Die Zahl.
     * @return bool True wenn ungerade.
     */
    public static function isOdd(int $number): bool {
        return $number % 2 !== 0;
    }

    /**
     * Prüft ob eine Zahl positiv ist (> 0).
     *
     * @param float|int $number Die Zahl.
     * @return bool True wenn positiv.
     */
    public static function isPositive(float|int $number): bool {
        return $number > 0;
    }

    /**
     * Prüft ob eine Zahl negativ ist (< 0).
     *
     * @param float|int $number Die Zahl.
     * @return bool True wenn negativ.
     */
    public static function isNegative(float|int $number): bool {
        return $number < 0;
    }

    /**
     * Prüft ob eine Zahl null ist.
     *
     * @param float|int $number Die Zahl.
     * @return bool True wenn null.
     */
    public static function isZero(float|int $number): bool {
        return $number == 0;
    }

    /**
     * Berechnet den Durchschnitt einer Zahlenliste.
     *
     * @param array $numbers Array von Zahlen.
     * @return float Der Durchschnitt oder 0 wenn leer.
     */
    public static function average(array $numbers): float {
        if (empty($numbers)) {
            return 0.0;
        }
        return array_sum($numbers) / count($numbers);
    }

    /**
     * Berechnet den Median einer Zahlenliste.
     *
     * @param array $numbers Array von Zahlen.
     * @return float Der Median oder 0 wenn leer.
     */
    public static function median(array $numbers): float {
        if (empty($numbers)) {
            return 0.0;
        }

        sort($numbers);
        $count = count($numbers);
        $middle = (int) floor($count / 2);

        if ($count % 2 === 0) {
            return ($numbers[$middle - 1] + $numbers[$middle]) / 2;
        }

        return $numbers[$middle];
    }

    /**
     * Gibt das Vorzeichen einer Zahl zurück.
     *
     * @param float|int $number Die Zahl.
     * @return int -1, 0 oder 1.
     */
    public static function sign(float|int $number): int {
        return $number <=> 0;
    }

    /**
     * Formatiert eine Zahl mit SI-Präfix (k, M, G, etc.).
     *
     * @param float|int $number Die Zahl.
     * @param int $precision Dezimalstellen (Standard: 2).
     * @param bool $binary Binäre Präfixe verwenden (Ki, Mi, Gi) (Standard: false).
     * @return string Die formatierte Zahl.
     */
    public static function formatWithSiPrefix(float|int $number, int $precision = 2, bool $binary = false): string {
        if ($number == 0) {
            return '0';
        }

        $base = $binary ? 1024 : 1000;
        $prefixes = $binary
            ? ['', 'Ki', 'Mi', 'Gi', 'Ti', 'Pi', 'Ei']
            : ['', 'k', 'M', 'G', 'T', 'P', 'E'];

        $isNegative = $number < 0;
        $number = abs($number);

        $exp = (int) floor(log($number, $base));
        $exp = min($exp, count($prefixes) - 1);
        $exp = max($exp, 0);

        $value = $number / pow($base, $exp);
        $formatted = round($value, $precision);

        return ($isNegative ? '-' : '') . $formatted . $prefixes[$exp];
    }

    /**
     * Berechnet die Fakultät einer Zahl.
     *
     * @param int $number Die Zahl (0-170).
     * @return float Die Fakultät.
     * @throws InvalidArgumentException Wenn die Zahl negativ oder zu groß ist.
     */
    public static function factorial(int $number): float {
        if ($number < 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Fakultät für negative Zahlen nicht definiert.");
        }
        if ($number > 170) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Fakultät zu groß für Float-Darstellung.");
        }

        $result = 1.0;
        for ($i = 2; $i <= $number; $i++) {
            $result *= $i;
        }
        return $result;
    }

    /**
     * Prüft ob eine Zahl eine Primzahl ist.
     *
     * @param int $number Die Zahl.
     * @return bool True wenn Primzahl.
     */
    public static function isPrime(int $number): bool {
        if ($number < 2) {
            return false;
        }
        if ($number === 2) {
            return true;
        }
        if ($number % 2 === 0) {
            return false;
        }

        $sqrt = (int) sqrt($number);
        for ($i = 3; $i <= $sqrt; $i += 2) {
            if ($number % $i === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Berechnet den größten gemeinsamen Teiler (GGT).
     *
     * @param int $a Erste Zahl.
     * @param int $b Zweite Zahl.
     * @return int Der größte gemeinsame Teiler.
     */
    public static function gcd(int $a, int $b): int {
        $a = abs($a);
        $b = abs($b);

        while ($b !== 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }

        return $a;
    }

    /**
     * Berechnet das kleinste gemeinsame Vielfache (KGV).
     *
     * @param int $a Erste Zahl.
     * @param int $b Zweite Zahl.
     * @return int Das kleinste gemeinsame Vielfache.
     */
    public static function lcm(int $a, int $b): int {
        if ($a === 0 || $b === 0) {
            return 0;
        }
        return abs($a * $b) / self::gcd($a, $b);
    }

    // ==================== Präzise Berechnungen (BC Math) ====================

    /**
     * Addiert zwei große Zahlen ohne Präzisionsverlust.
     *
     * @param string $a Erste Zahl (als String).
     * @param string $b Zweite Zahl (als String).
     * @param int $scale Anzahl Dezimalstellen (Standard: 0).
     * @return string Das Ergebnis.
     */
    public static function addPrecise(string $a, string $b, int $scale = 0): string {
        return bcadd($a, $b, $scale);
    }

    /**
     * Subtrahiert zwei große Zahlen ohne Präzisionsverlust.
     *
     * @param string $a Erste Zahl (Minuend).
     * @param string $b Zweite Zahl (Subtrahend).
     * @param int $scale Anzahl Dezimalstellen (Standard: 0).
     * @return string Das Ergebnis.
     */
    public static function subtractPrecise(string $a, string $b, int $scale = 0): string {
        return bcsub($a, $b, $scale);
    }

    /**
     * Multipliziert zwei große Zahlen ohne Präzisionsverlust.
     *
     * @param string $a Erste Zahl.
     * @param string $b Zweite Zahl.
     * @param int $scale Anzahl Dezimalstellen (Standard: 0).
     * @return string Das Ergebnis.
     */
    public static function multiplyPrecise(string $a, string $b, int $scale = 0): string {
        return bcmul($a, $b, $scale);
    }

    /**
     * Dividiert zwei große Zahlen ohne Präzisionsverlust.
     *
     * @param string $a Dividend.
     * @param string $b Divisor.
     * @param int $scale Anzahl Dezimalstellen (Standard: 0).
     * @return string Das Ergebnis.
     * @throws RuntimeException Bei Division durch Null.
     */
    public static function dividePrecise(string $a, string $b, int $scale = 0): string {
        if (bccomp($b, '0', $scale) === 0) {
            self::logErrorAndThrow(RuntimeException::class, "Division durch Null nicht erlaubt");
        }
        return bcdiv($a, $b, $scale);
    }

    /**
     * Berechnet die Summe eines Arrays großer Zahlen ohne Präzisionsverlust.
     *
     * @param array<string> $numbers Array von Zahlen als Strings.
     * @param int $scale Anzahl Dezimalstellen (Standard: 0).
     * @return string Die Summe.
     */
    public static function sumPrecise(array $numbers, int $scale = 0): string {
        $result = '0';
        foreach ($numbers as $number) {
            $result = bcadd($result, (string) $number, $scale);
        }
        return $result;
    }

    /**
     * Berechnet die Differenz eines Arrays großer Zahlen (erstes Element minus alle weiteren).
     *
     * @param array<string> $numbers Array von Zahlen als Strings.
     * @param int $scale Anzahl Dezimalstellen (Standard: 0).
     * @return string Die Differenz.
     */
    public static function subtractAllPrecise(array $numbers, int $scale = 0): string {
        if (empty($numbers)) {
            return '0';
        }

        $result = (string) array_shift($numbers);
        foreach ($numbers as $number) {
            $result = bcsub($result, (string) $number, $scale);
        }
        return $result;
    }

    /**
     * Vergleicht zwei große Zahlen.
     *
     * @param string $a Erste Zahl.
     * @param string $b Zweite Zahl.
     * @param int $scale Anzahl Dezimalstellen (Standard: 0).
     * @return int -1 wenn a < b, 0 wenn gleich, 1 wenn a > b.
     */
    public static function comparePrecise(string $a, string $b, int $scale = 0): int {
        return bccomp($a, $b, $scale);
    }

    /**
     * Berechnet den Modulo großer Zahlen ohne Präzisionsverlust.
     *
     * @param string $a Dividend.
     * @param string $b Divisor.
     * @param int $scale Anzahl Dezimalstellen (Standard: 0).
     * @return string Der Rest.
     */
    public static function modPrecise(string $a, string $b, int $scale = 0): string {
        return bcmod($a, $b, $scale);
    }

    /**
     * Potenziert eine große Zahl ohne Präzisionsverlust.
     *
     * @param string $base Basis.
     * @param string $exponent Exponent.
     * @param int $scale Anzahl Dezimalstellen (Standard: 0).
     * @return string Das Ergebnis.
     */
    public static function powPrecise(string $base, string $exponent, int $scale = 0): string {
        return bcpow($base, $exponent, $scale);
    }

    /**
     * Berechnet die Quadratwurzel einer großen Zahl.
     *
     * @param string $number Die Zahl.
     * @param int $scale Anzahl Dezimalstellen (Standard: 0).
     * @return string Die Quadratwurzel.
     */
    public static function sqrtPrecise(string $number, int $scale = 0): string {
        return bcsqrt($number, $scale);
    }
}