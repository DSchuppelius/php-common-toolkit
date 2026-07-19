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

use CommonToolkit\Enums\{CountryCode, CurrencyCode, MetricPrefix, RoundingMode, TemperatureUnit};
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
            'B' => 1,
            'KB' => 1024,
            'MB' => 1024 ** 2,
            'GB' => 1024 ** 3,
            'TB' => 1024 ** 4,
            'PB' => 1024 ** 5,
            default => 1,
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
        if ($from === $to) {
            return $value;
        }

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
        usort($sortedPrefixes, fn ($a, $b) => strlen($b) <=> strlen($a)); // längste zuerst

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
     * @return float Der gerundete Wert.
     */
    public static function roundToNearest(float $value, int $nearest): float {
        return round($value / $nearest) * $nearest;
    }

    /**
     * Fixiert eine Zahl auf einen bestimmten Bereich und gibt den den entsprechenden Wert zurück.
     * Bei Über- oder Unterlauf wird der Wert auf den entsprechenden Grenzwert gesetzt.
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
    public static function toGermanFormat(string|float|int $amount, int $decimals = 2, bool $withThousandsSeparator = false, ?CountryCode $country = null): string {
        if (is_string($amount)) {
            $amount = trim($amount);
            if ($amount === '') {
                return number_format(0, $decimals, ',', $withThousandsSeparator ? '.' : '');
            }
            $floatVal = self::normalizeDecimal($amount, $country);
        } else {
            $floatVal = (float) $amount;
        }

        return number_format($floatVal, $decimals, ',', $withThousandsSeparator ? '.' : '');
    }

    /**
     * Wie {@see toGermanFormat()}, aber gibt für leere oder nicht-numerische
     * Strings `null` zurück, statt sie als "0,00" zu interpretieren.
     *
     * Gedacht für rohe Spreadsheet-/CSV-Betragszellen: Header oder Freitext sollen
     * nicht fälschlich zu "0,00" werden. Aufrufer entscheiden per `?? $roh`, ob der
     * unbekannte Wert unverändert durchgereicht oder verworfen wird.
     *
     * @param string|float|int $value Betrag (reine Zahl) oder beliebiger Zellwert.
     * @param int $decimals Anzahl Dezimalstellen (Standard: 2).
     * @param bool $withThousandsSeparator Tausendertrenner anzeigen (Standard: false).
     * @return string|null Deutsches Format oder null, wenn kein numerischer Wert.
     */
    public static function toGermanFormatOrNull(string|float|int $value, int $decimals = 2, bool $withThousandsSeparator = false): ?string {
        if (is_string($value)) {
            // Auch FORMATIERTE Beträge ("22,00", "1,234.56", "1.234,56", "-318,00")
            // akzeptieren – nicht nur is_numeric()-Strings. Verworfen werden nur
            // Leerwerte und Nicht-Zahlen (Header/Freitext); normalizeDecimal()
            // übernimmt danach die DE/US-Erkennung. Hinweis: erwartet eine Betrags-
            // zelle; mehrteilige Werte (z.B. Datumsangaben) sind nicht im Skopus.
            $cleaned = str_replace(' ', '', trim($value));
            if ($cleaned === '' || !preg_match('/^[+-]?(?=.*\d)[\d.,]+$/', $cleaned)) {
                return null;
            }
        }

        return self::toGermanFormat($value, $decimals, $withThousandsSeparator);
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
     * @param CountryCode|null $country Optionales Land für eindeutige Tausendertrenner-Erkennung.
     * @return string Betrag im US-Format (z.B. "1234.56" oder "1,234.56").
     */
    public static function toUSFormat(string|float|int $amount, int $decimals = 2, bool $withThousandsSeparator = false, ?CountryCode $country = null): string {
        if (is_string($amount)) {
            $amount = trim($amount);
            if ($amount === '') {
                return number_format(0, $decimals, '.', $withThousandsSeparator ? ',' : '');
            }
            $floatVal = self::normalizeDecimal($amount, $country);
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
     * Mit CountryCode::Germany wird das deutsche Tausendertrennzeichen-Pattern
     * eindeutig erkannt: 2.000 → 2000, 1.234.567 → 1234567, 2.000,50 → 2000.50.
     *
     * @param string $value Der zu normalisierende Wert.
     * @param CountryCode|null $country Optionales Land für länder-spezifische Erkennung.
     * @return float Der normalisierte Wert.
     */
    public static function normalizeDecimal(string $value, ?CountryCode $country = null): float {
        // Delegiert an die string-basierte Normalisierung (Single Source of Truth)
        // und castet erst am Ende auf float.
        return (float) self::normalizeDecimalString($value, $country);
    }

    /**
     * Normalisiert einen Zahl-String auf kanonisches Punkt-Dezimalformat, OHNE
     * float-Konvertierung — präzisionswahrend für bcmath (Geld/Mengen).
     *
     * Gleiche Format-Erkennung wie {@see normalizeDecimal()} (deutsche/US-
     * Tausender- und Dezimaltrennzeichen), liefert aber den kanonischen String:
     * Punkt als Dezimaltrenner, keine Tausendertrenner. Leere Eingabe → "0".
     *
     * Beispiele: "1.234,56" → "1234.56", "1,234.56" → "1234.56",
     * "1234,56" → "1234.56", "1234.56" → "1234.56", "2.000" (DE) → "2000".
     *
     * @param string $value Der zu normalisierende Wert.
     * @param CountryCode|null $country Optionales Land für länder-spezifische Erkennung.
     * @return string Kanonischer Punkt-Dezimal-String.
     */
    public static function normalizeDecimalString(string $value, ?CountryCode $country = null): string {
        $value = trim($value);
        if ($value === '') {
            return '0';
        }

        // Anhaftende Währungssymbole (€ £ $) früh entfernen – VOR der Vorzeichen-
        // erkennung, damit ein trailing-Symbol das nachgestellte Minus nicht verdeckt
        // (z.B. "1.234,56- €"). Sonst bräche ein Symbol zudem den bcmath-Pfad.
        $value = trim(str_replace(['€', '£', '$'], '', $value));
        if ($value === '') {
            return '0';
        }

        // --- Vorzeichen-/Kennungs-Erkennung VOR dem Entfernen der Trennzeichen ---
        $negative = false;

        // Accounting-Klammer-Minus: "(1.234,56)" ⇒ negativ.
        if (preg_match('/^\((.+)\)$/', $value, $m)) {
            $negative = true;
            $value = trim($m[1]);
        }

        // Deutsches Soll-/Haben-Suffix aus Bankauszügen: "123,45 S" = Soll (negativ),
        // "123,45 H" = Haben (positiv). Greift nur, wenn direkt davor eine Ziffer steht.
        if (preg_match('/^(.*\d)\s*([SsHh])$/u', $value, $m)) {
            if (strcasecmp($m[2], 'S') === 0) {
                $negative = true;
            }
            $value = $m[1];
        }

        // Explizites Minus (führend oder nachgestellt, z.B. "1234,56-").
        if (str_starts_with($value, '-') || str_ends_with($value, '-')) {
            $negative = true;
        }

        // Tausender-/Rauschtrenner entfernen, die kein Punkt oder Komma sind:
        // ASCII-Space, geschütztes (U+00A0) und schmales (U+202F) Leerzeichen sowie
        // Schweizer Apostroph (gerade ' und typografisch ’). Vorzeichen separat behandelt.
        // (Währungssymbole werden bereits oben vor der Vorzeichenerkennung entfernt.)
        $value = str_replace([' ', "\u{00A0}", "\u{202F}", "'", "\u{2019}", '+', '-'], '', $value);
        if ($value === '') {
            return '0';
        }

        $normalized = self::normalizeUnsignedDecimalString($value, $country);

        if ($negative && preg_match('/[1-9]/', $normalized)) {
            $normalized = '-' . $normalized;
        }

        return $normalized;
    }

    /**
     * Kern-Normalisierung von Punkt/Komma zum kanonischen Punkt-Dezimal-String.
     *
     * Erwartet einen bereits von Vorzeichen, Whitespace und Apostrophen befreiten
     * Wert (siehe {@see normalizeDecimalString()}). Getrennt gehalten, damit die
     * Tausender-/Dezimal-Heuristik unabhängig von der Vorzeichenbehandlung bleibt.
     */
    private static function normalizeUnsignedDecimalString(string $value, ?CountryCode $country = null): string {
        // Deutsche/europäische Tausendertrennzeichen eindeutig erkennen:
        // Pattern: 1-3 Ziffern, dann Gruppen von exakt 3 Ziffern nach Punkt, optional Dezimalkomma
        // Beispiele: 2.000 → 2000, 1.234.567 → 1234567, 2.000,50 → 2000.50
        // Nicht betroffen: 902.36 (nur 2 Ziffern nach Punkt), 2.5 (nur 1 Ziffer)
        if ($country === CountryCode::Germany && preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $value)) {
            $value = str_replace('.', '', $value);
            return str_replace(',', '.', $value);
        }

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
        // Nur Punkt: bereits kanonischer Dezimaltrenner

        return $value;
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
     * Formatiert eine Zahl mit explizitem Vorzeichen.
     *
     * Positive Zahlen erhalten ein '+', negative ein '-', null kann konfiguriert werden.
     *
     * @param float|int $number Die zu formatierende Zahl.
     * @param int $decimals Anzahl der Dezimalstellen (Standard: 2).
     * @param string $decimalSeparator Dezimaltrennzeichen (Standard: ',').
     * @param string $thousandsSeparator Tausendertrennzeichen (Standard: '.').
     * @param string $zeroSign Vorzeichen für Null (Standard: '' für kein Vorzeichen, kann '+' oder '±' sein).
     * @return string Die formatierte Zahl mit Vorzeichen.
     */
    public static function formatWithSign(float|int $number, int $decimals = 2, string $decimalSeparator = ',', string $thousandsSeparator = '.', string $zeroSign = ''): string {
        $formatted = number_format(abs($number), $decimals, $decimalSeparator, $thousandsSeparator);

        if ($number > 0) {
            return '+' . $formatted;
        } elseif ($number < 0) {
            return '-' . $formatted;
        }

        // Null
        return $zeroSign . $formatted;
    }

    /**
     * Formatiert einen Betrag als Währung.
     *
     * @param float|int $amount Der Betrag.
     * @param CurrencyCode $currency Die Währung (Standard: EUR).
     * @param int $decimals Anzahl Dezimalstellen (Standard: 2).
     * @param string $decimalSeparator Dezimaltrennzeichen (Standard: ',').
     * @param string $thousandsSeparator Tausendertrennzeichen (Standard: '.').
     * @param bool $symbolBefore Symbol vor dem Betrag (Standard: false für deutsch).
     * @return string Der formatierte Währungsbetrag.
     */
    public static function formatCurrency(float|int $amount, CurrencyCode $currency = CurrencyCode::Euro, int $decimals = 2, string $decimalSeparator = ',', string $thousandsSeparator = '.', bool $symbolBefore = false): string {
        $formatted = number_format(abs($amount), $decimals, $decimalSeparator, $thousandsSeparator);
        $currencySymbol = $currency->getSymbol();

        // Fallback auf ISO-Code wenn kein Symbol definiert
        if ($currencySymbol === '') {
            $currencySymbol = $currency->value;
        }

        $sign = $amount < 0 ? '-' : '';

        if ($symbolBefore) {
            return $sign . $currencySymbol . ' ' . $formatted;
        }

        return $sign . $formatted . ' ' . $currencySymbol;
    }

    /**
     * Formatiert eine Zahl mit explizitem Vorzeichen und Währungssymbol.
     *
     * @param float|int $amount Der Betrag.
     * @param CurrencyCode $currency Die Währung (Standard: EUR).
     * @param int $decimals Anzahl der Dezimalstellen (Standard: 2).
     * @param string $decimalSeparator Dezimaltrennzeichen (Standard: ',').
     * @param string $thousandsSeparator Tausendertrennzeichen (Standard: '.').
     * @param bool $symbolBefore Symbol vor dem Betrag (Standard: false für deutsch).
     * @param string $zeroSign Vorzeichen für Null (Standard: '').
     * @return string Der formatierte Währungsbetrag mit Vorzeichen.
     */
    public static function formatCurrencyWithSign(float|int $amount, CurrencyCode $currency = CurrencyCode::Euro, int $decimals = 2, string $decimalSeparator = ',', string $thousandsSeparator = '.', bool $symbolBefore = false, string $zeroSign = ''): string {
        $formattedWithSign = self::formatWithSign($amount, $decimals, $decimalSeparator, $thousandsSeparator, $zeroSign);
        $currencySymbol = $currency->getSymbol();

        // Fallback auf ISO-Code wenn kein Symbol definiert
        if ($currencySymbol === '') {
            $currencySymbol = $currency->value;
        }

        if ($symbolBefore) {
            // Vorzeichen extrahieren und Symbol einfügen
            $sign = '';
            if (str_starts_with($formattedWithSign, '+') || str_starts_with($formattedWithSign, '-')) {
                $sign = $formattedWithSign[0];
                $formattedWithSign = substr($formattedWithSign, 1);
            } elseif ($zeroSign !== '' && str_starts_with($formattedWithSign, $zeroSign)) {
                $sign = $zeroSign;
                $formattedWithSign = substr($formattedWithSign, strlen($zeroSign));
            }
            return $sign . $currencySymbol . ' ' . $formattedWithSign;
        }

        return $formattedWithSign . ' ' . $currencySymbol;
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
            'neunzehn',
        ];
        $tens = ['', '', 'zwanzig', 'dreißig', 'vierzig', 'fünfzig', 'sechzig', 'siebzig', 'achtzig', 'neunzig'];

        $convertBelow1000 = function (int $n) use ($ones, $tens): string {
            if ($n === 0) {
                return '';
            }

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
     * Hinweis: bcmath schneidet am `$scale` ab, es rundet nicht. Mit `$mode`
     * (Standard: {@see RoundingMode::Truncate} = unverändertes bcmath-Verhalten)
     * wird das exakte Zwischenergebnis stattdessen echt auf `$scale` gerundet.
     *
     * @param numeric-string $a Erste Zahl (als String).
     * @param numeric-string $b Zweite Zahl (als String).
     * @param int          $scale Anzahl Dezimalstellen (Standard: 0).
     * @param RoundingMode $mode Rundungsverfahren (Standard: Truncate).
     * @return numeric-string Das Ergebnis.
     */
    public static function addPrecise(string $a, string $b, int $scale = 0, RoundingMode $mode = RoundingMode::Truncate): string {
        if ($mode === RoundingMode::Truncate) {
            return bcadd($a, $b, $scale);
        }
        $exact = bcadd($a, $b, max(self::decimalScale(trim($a)), self::decimalScale(trim($b))));
        return self::roundPrecise($exact, $scale, $mode);
    }

    /**
     * Subtrahiert zwei große Zahlen ohne Präzisionsverlust.
     *
     * Zum Rundungsverhalten siehe {@see addPrecise()}.
     *
     * @param numeric-string $a Erste Zahl (Minuend).
     * @param numeric-string $b Zweite Zahl (Subtrahend).
     * @param int          $scale Anzahl Dezimalstellen (Standard: 0).
     * @param RoundingMode $mode Rundungsverfahren (Standard: Truncate).
     * @return numeric-string Das Ergebnis.
     */
    public static function subtractPrecise(string $a, string $b, int $scale = 0, RoundingMode $mode = RoundingMode::Truncate): string {
        if ($mode === RoundingMode::Truncate) {
            return bcsub($a, $b, $scale);
        }
        $exact = bcsub($a, $b, max(self::decimalScale(trim($a)), self::decimalScale(trim($b))));
        return self::roundPrecise($exact, $scale, $mode);
    }

    /**
     * Multipliziert zwei große Zahlen ohne Präzisionsverlust.
     *
     * Ohne `$mode` schneidet bcmath am `$scale` ab (bei Produkten der Normalfall,
     * da Nachkommastellen sich addieren). Mit `$mode` wird das exakte Produkt
     * (Skala = Summe der Operanden-Nachkommastellen) echt auf `$scale` gerundet.
     *
     * @param numeric-string $a Erste Zahl.
     * @param numeric-string $b Zweite Zahl.
     * @param int          $scale Anzahl Dezimalstellen (Standard: 0).
     * @param RoundingMode $mode Rundungsverfahren (Standard: Truncate).
     * @return numeric-string Das Ergebnis.
     */
    public static function multiplyPrecise(string $a, string $b, int $scale = 0, RoundingMode $mode = RoundingMode::Truncate): string {
        if ($mode === RoundingMode::Truncate) {
            return bcmul($a, $b, $scale);
        }
        $exact = bcmul($a, $b, self::decimalScale(trim($a)) + self::decimalScale(trim($b)));
        return self::roundPrecise($exact, $scale, $mode);
    }

    /**
     * Dividiert zwei große Zahlen ohne Präzisionsverlust.
     *
     * bcdiv schneidet am `$scale` ab – bei periodischen Quotienten (z. B. 2/3)
     * praktisch immer. Mit `$mode` wird über einen Puffer gerechnet und das
     * Ergebnis echt auf `$scale` gerundet (kaufmännisch: `RoundingMode::HalfUp`).
     *
     * @param numeric-string $a Dividend.
     * @param numeric-string $b Divisor.
     * @param int          $scale Anzahl Dezimalstellen (Standard: 0).
     * @param RoundingMode $mode Rundungsverfahren (Standard: Truncate).
     * @return numeric-string Das Ergebnis.
     * @throws RuntimeException Bei Division durch Null.
     */
    public static function dividePrecise(string $a, string $b, int $scale = 0, RoundingMode $mode = RoundingMode::Truncate): string {
        // Divisor vollpräzise gegen Null prüfen (nicht am $scale trunkiert –
        // sonst gälte z. B. "0.4" bei $scale=0 fälschlich als Null).
        if (self::isZeroPrecise($b)) {
            self::logErrorAndThrow(RuntimeException::class, "Division durch Null nicht erlaubt");
        }
        if ($mode === RoundingMode::Truncate) {
            return bcdiv($a, $b, $scale);
        }
        return self::roundPrecise(bcdiv($a, $b, $scale + 10), $scale, $mode);
    }

    /**
     * Sichere Division mit Fallback: teilt $a durch $b nur, wenn $b GRÖSSER
     * als 0 ist; andernfalls wird $default zurückgegeben (kein Fehler).
     *
     * Für „sicherer Durchschnitt/Rate"-Muster: Wert/Menge, Anteil/Summe, Kosten/
     * Stückzahl — wo ein nicht-positiver Divisor (leer/0/negativ) fachlich einen
     * definierten Ersatzwert liefern soll statt zu werfen. Kapselt das häufige
     * `bccomp($b,'0',$scale) > 0 ? bcdiv($a,$b,$scale) : $default`.
     *
     * @param numeric-string $a       Dividend.
     * @param numeric-string $b       Divisor.
     * @param int            $scale   Anzahl Dezimalstellen (Standard: 0).
     * @param numeric-string $default Ersatzwert, wenn $b nicht > 0 (Standard: "0").
     * @return numeric-string         Quotient oder $default.
     */
    public static function divideOrDefault(string $a, string $b, int $scale = 0, string $default = '0'): string {
        return bccomp($b, '0', $scale) > 0 ? bcdiv($a, $b, $scale) : $default;
    }

    /**
     * Berechnet die Summe eines Arrays großer Zahlen ohne Präzisionsverlust.
     *
     * Mit `$mode` wird exakt akkumuliert und erst das Endergebnis gerundet –
     * keine wiederholte Zwischentrunkierung. Standard bleibt Truncate.
     *
     * @param array<array-key,string|int|float> $numbers Array von Zahlen als Strings.
     * @param int          $scale Anzahl Dezimalstellen (Standard: 0).
     * @param RoundingMode $mode Rundungsverfahren (Standard: Truncate).
     * @return numeric-string Die Summe.
     */
    public static function sumPrecise(array $numbers, int $scale = 0, RoundingMode $mode = RoundingMode::Truncate): string {
        if ($mode === RoundingMode::Truncate) {
            $result = '0';
            foreach ($numbers as $number) {
                $result = bcadd($result, (string) $number, $scale);
            }
            return $result;
        }

        $work = $scale;
        foreach ($numbers as $number) {
            $work = max($work, self::decimalScale(trim((string) $number)));
        }
        $result = '0';
        foreach ($numbers as $number) {
            $result = bcadd($result, (string) $number, $work);
        }
        return self::roundPrecise($result, $scale, $mode);
    }

    /**
     * Berechnet die Differenz eines Arrays großer Zahlen (erstes Element minus alle weiteren).
     *
     * Zum Rundungsverhalten siehe {@see sumPrecise()}.
     *
     * @param array<array-key,string|int|float> $numbers Array von Zahlen als Strings.
     * @param int          $scale Anzahl Dezimalstellen (Standard: 0).
     * @param RoundingMode $mode Rundungsverfahren (Standard: Truncate).
     * @return numeric-string Die Differenz.
     */
    public static function subtractAllPrecise(array $numbers, int $scale = 0, RoundingMode $mode = RoundingMode::Truncate): string {
        if (empty($numbers)) {
            return '0';
        }

        $work = $scale;
        if ($mode !== RoundingMode::Truncate) {
            foreach ($numbers as $number) {
                $work = max($work, self::decimalScale(trim((string) $number)));
            }
        }

        $result = (string) array_shift($numbers);
        foreach ($numbers as $number) {
            $result = bcsub($result, (string) $number, $work);
        }
        return $mode === RoundingMode::Truncate ? $result : self::roundPrecise($result, $scale, $mode);
    }

    /**
     * Vergleicht zwei große Zahlen.
     *
     * @param numeric-string $a Erste Zahl.
     * @param numeric-string $b Zweite Zahl.
     * @param int $scale Anzahl Dezimalstellen (Standard: 0).
     * @return int -1 wenn a < b, 0 wenn gleich, 1 wenn a > b.
     */
    public static function comparePrecise(string $a, string $b, int $scale = 0): int {
        return bccomp($a, $b, $scale);
    }

    /**
     * Berechnet den Modulo großer Zahlen ohne Präzisionsverlust.
     *
     * @param numeric-string $a Dividend.
     * @param numeric-string $b Divisor.
     * @param int $scale Anzahl Dezimalstellen (Standard: 0).
     * @return numeric-string Der Rest.
     * @throws RuntimeException Bei Modulo durch Null.
     */
    public static function modPrecise(string $a, string $b, int $scale = 0): string {
        if (bccomp($b, '0', $scale) === 0) {
            self::logErrorAndThrow(RuntimeException::class, "Modulo durch Null nicht erlaubt");
        }
        return bcmod($a, $b, $scale);
    }

    /**
     * Potenziert eine große Zahl ohne Präzisionsverlust.
     *
     * Mit `$mode` wird mit Reserve-Skala gerechnet und das Endergebnis echt
     * gerundet, statt Richtung Null zu kürzen. Standard bleibt Truncate.
     *
     * @param numeric-string $base Basis.
     * @param numeric-string $exponent Exponent (ganzzahliger Anteil; bcpow-Semantik).
     * @param int $scale Anzahl Dezimalstellen (Standard: 0).
     * @param RoundingMode $mode Rundungsverfahren (Standard: Truncate).
     * @return numeric-string Das Ergebnis.
     */
    public static function powPrecise(string $base, string $exponent, int $scale = 0, RoundingMode $mode = RoundingMode::Truncate): string {
        if ($mode === RoundingMode::Truncate) {
            return bcpow($base, $exponent, $scale);
        }
        return self::roundPrecise(bcpow($base, $exponent, $scale + 10), $scale, $mode);
    }

    /**
     * Berechnet die Quadratwurzel einer großen Zahl.
     *
     * Mit `$mode` wird mit Reserve-Skala gerechnet und das Endergebnis echt
     * gerundet, statt Richtung Null zu kürzen. Standard bleibt Truncate.
     *
     * @param numeric-string $number Die Zahl.
     * @param int            $scale Anzahl Dezimalstellen (Standard: 0).
     * @param RoundingMode $mode Rundungsverfahren (Standard: Truncate).
     * @return numeric-string Die Quadratwurzel.
     */
    public static function sqrtPrecise(string $number, int $scale = 0, RoundingMode $mode = RoundingMode::Truncate): string {
        if ($mode === RoundingMode::Truncate) {
            return bcsqrt($number, $scale);
        }
        return self::roundPrecise(bcsqrt($number, $scale + 10), $scale, $mode);
    }

    // ==================== Präzise Rundung / Vorzeichen / Verteilung ====================

    /**
     * Ermittelt die Anzahl der Nachkommastellen eines kanonischen Zahl-Strings.
     * Interner Helfer für skalen-erhaltende Precise-Operationen.
     */
    private static function decimalScale(string $value): int {
        $dot = strpos($value, '.');
        return $dot === false ? 0 : strlen($value) - $dot - 1;
    }

    /**
     * Rundet einen numerischen String präzisionswahrend auf `$scale` Nachkommastellen.
     *
     * Schließt die zentrale bcmath-Lücke: bcadd/bcdiv & Co. runden nicht, sie
     * schneiden Richtung Null ab. Diese Methode rundet echt gemäß {@see RoundingMode}
     * (Standard: kaufmännisch HalfUp) und arbeitet vorzeichensicher (kein "-0").
     *
     * @param numeric-string $value Numerischer String (kanonisch, Punkt als Dezimaltrenner).
     * @param int            $scale Ziel-Nachkommastellen (>= 0).
     * @param RoundingMode   $mode  Rundungsverfahren (Standard: HalfUp).
     * @return numeric-string Der gerundete Wert mit genau `$scale` Nachkommastellen.
     * @throws InvalidArgumentException Bei negativer Skala.
     */
    public static function roundPrecise(string $value, int $scale = 0, RoundingMode $mode = RoundingMode::HalfUp): string {
        if ($scale < 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Scale darf nicht negativ sein: $scale");
        }

        $value = trim($value);
        $negative = str_starts_with($value, '-');
        $abs = $negative ? substr($value, 1) : ltrim($value, '+');

        $work = max(self::decimalScale($abs), $scale + 1);
        $truncated = bcadd($abs, '0', $scale);          // Betrag Richtung Null gekürzt
        $rest = bcsub($abs, $truncated, $work);         // exakter Rest jenseits der Zielskala
        $half = '0.' . str_repeat('0', $scale) . '5';   // 0,5 · 10^-scale

        $roundUp = match ($mode) {
            RoundingMode::Truncate => false,
            RoundingMode::Ceil => !$negative && bccomp($rest, '0', $work) > 0,
            RoundingMode::Floor => $negative && bccomp($rest, '0', $work) > 0,
            RoundingMode::HalfUp => bccomp($rest, $half, $work) >= 0,
            RoundingMode::HalfDown => bccomp($rest, $half, $work) > 0,
            RoundingMode::HalfEven => self::halfEvenRoundsUp($rest, $half, $truncated, $work),
        };

        if ($roundUp) {
            $ulp = $scale === 0 ? '1' : '0.' . str_repeat('0', $scale - 1) . '1';
            $truncated = bcadd($truncated, $ulp, $scale);
        }

        $result = $negative ? bcmul($truncated, '-1', $scale) : $truncated;
        // "-0[,00]" auf positives Null normalisieren
        return bccomp($result, '0', $scale) === 0 ? bcadd('0', '0', $scale) : $result;
    }

    /**
     * HalfEven-Entscheidung: bei exakter Hälfte zur geraden Endziffer runden.
     */
    private static function halfEvenRoundsUp(string $rest, string $half, string $truncated, int $work): bool {
        $cmp = bccomp($rest, $half, $work);
        if ($cmp !== 0) {
            return $cmp > 0;
        }
        $digits = str_replace(['.', '-'], '', $truncated);
        return ((int) substr($digits, -1)) % 2 === 1;
    }

    /**
     * Absolutwert eines numerischen Strings – exakt, ohne Skalen- oder Präzisionsverlust.
     *
     * Ersetzt das verbreitete `bccomp($v,'0',$s) < 0 ? bcmul($v,'-1',$s) : $v`-Muster.
     *
     * @param numeric-string $value Numerischer String.
     * @return numeric-string Betrag von `$value`.
     */
    public static function absPrecise(string $value): string {
        $value = trim($value);
        return str_starts_with($value, '-') ? substr($value, 1) : ltrim($value, '+');
    }

    /**
     * Negiert einen numerischen String skalen-erhaltend (kein "-0").
     *
     * @param numeric-string $value Numerischer String.
     * @return numeric-string Das Negative von `$value`.
     */
    public static function negatePrecise(string $value): string {
        $value = trim($value);
        return bcmul($value, '-1', self::decimalScale($value));
    }

    /**
     * Vorzeichen eines numerischen Strings.
     *
     * @param numeric-string $value Numerischer String.
     * @return int -1, 0 oder 1.
     */
    public static function signPrecise(string $value): int {
        return bccomp(trim($value), '0', self::decimalScale(trim($value)));
    }

    /**
     * Prüft, ob ein numerischer String (bei gegebener Skala) null ist.
     *
     * @param numeric-string $value Numerischer String.
     * @param int|null       $scale Vergleichsskala; null = volle Präzision des Werts.
     */
    public static function isZeroPrecise(string $value, ?int $scale = null): bool {
        $value = trim($value);
        return bccomp($value, '0', $scale ?? self::decimalScale($value)) === 0;
    }

    /**
     * Prüft, ob ein numerischer String (bei gegebener Skala) positiv ist (> 0).
     *
     * @param numeric-string $value Numerischer String.
     * @param int|null       $scale Vergleichsskala; null = volle Präzision des Werts.
     */
    public static function isPositivePrecise(string $value, ?int $scale = null): bool {
        $value = trim($value);
        return bccomp($value, '0', $scale ?? self::decimalScale($value)) > 0;
    }

    /**
     * Prüft, ob ein numerischer String (bei gegebener Skala) negativ ist (< 0).
     *
     * @param numeric-string $value Numerischer String.
     * @param int|null       $scale Vergleichsskala; null = volle Präzision des Werts.
     */
    public static function isNegativePrecise(string $value, ?int $scale = null): bool {
        $value = trim($value);
        return bccomp($value, '0', $scale ?? self::decimalScale($value)) < 0;
    }

    /**
     * Gibt den kleineren zweier numerischer Strings zurück (Original unverändert).
     *
     * @param numeric-string $a     Numerischer String.
     * @param numeric-string $b     Numerischer String.
     * @param int|null       $scale Vergleichsskala; null = maximale Präzision beider Werte.
     * @return numeric-string
     */
    public static function minPrecise(string $a, string $b, ?int $scale = null): string {
        $scale ??= max(self::decimalScale(trim($a)), self::decimalScale(trim($b)));
        return bccomp($a, $b, $scale) <= 0 ? $a : $b;
    }

    /**
     * Gibt den größeren zweier numerischer Strings zurück (Original unverändert).
     *
     * @param numeric-string $a     Numerischer String.
     * @param numeric-string $b     Numerischer String.
     * @param int|null       $scale Vergleichsskala; null = maximale Präzision beider Werte.
     * @return numeric-string
     */
    public static function maxPrecise(string $a, string $b, ?int $scale = null): string {
        $scale ??= max(self::decimalScale(trim($a)), self::decimalScale(trim($b)));
        return bccomp($a, $b, $scale) >= 0 ? $a : $b;
    }

    /**
     * Begrenzt einen numerischen String präzise auf [$min, $max].
     *
     * @param numeric-string $value Numerischer String.
     * @param numeric-string $min   Untere Grenze.
     * @param numeric-string $max   Obere Grenze.
     * @param int|null       $scale Vergleichsskala; null = maximale Präzision aller Werte.
     * @return numeric-string
     */
    public static function clampPrecise(string $value, string $min, string $max, ?int $scale = null): string {
        $scale ??= max(self::decimalScale(trim($value)), self::decimalScale(trim($min)), self::decimalScale(trim($max)));
        if (bccomp($value, $min, $scale) < 0) {
            return $min;
        }
        if (bccomp($value, $max, $scale) > 0) {
            return $max;
        }
        return $value;
    }

    /**
     * Rundet einen numerischen String präzise auf das nächste Vielfache von `$step`.
     * Präzisionswahrende Entsprechung zu {@see roundToNearest()}.
     *
     * @param numeric-string $value Numerischer String.
     * @param numeric-string $step  Schrittweite (> 0), z. B. "0.05" für 5-Cent-Rundung.
     * @param int            $scale Ziel-Nachkommastellen des Ergebnisses.
     * @param RoundingMode   $mode  Rundungsverfahren für den Zwischenschritt.
     * @return numeric-string
     * @throws InvalidArgumentException Wenn `$step` nicht > 0 ist.
     */
    public static function roundToStepPrecise(string $value, string $step, int $scale = 2, RoundingMode $mode = RoundingMode::HalfUp): string {
        if (bccomp($step, '0', self::decimalScale(trim($step))) <= 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Schrittweite muss > 0 sein: $step");
        }
        $work = $scale + 8;
        $steps = self::roundPrecise(bcdiv($value, $step, $work), 0, $mode);
        return bcmul($steps, $step, $scale);
    }

    /**
     * Berechnet den prozentualen Anteil von `$part` an `$total` (Teil/Gesamt·100).
     * Präzise Entsprechung zu {@see percentage()}; Gesamt = 0 → "0".
     *
     * @param numeric-string $part  Teilwert.
     * @param numeric-string $total Gesamtwert.
     * @param int            $scale Nachkommastellen des Ergebnisses (Standard: 2).
     * @return numeric-string
     */
    public static function percentagePrecise(string $part, string $total, int $scale = 2, RoundingMode $mode = RoundingMode::HalfUp): string {
        if (self::isZeroPrecise($total)) {
            return bcadd('0', '0', $scale);
        }
        $work = $scale + 6;
        return self::roundPrecise(bcdiv(bcmul($part, '100', $work), $total, $work), $scale, $mode);
    }

    /**
     * Berechnet `$percent` Prozent von `$value` (Wert·Prozent/100).
     * Typischer Anwendungsfall: Steuer-/Rabattbeträge (z. B. 19 % USt).
     *
     * @param numeric-string $value   Basiswert.
     * @param numeric-string $percent Prozentsatz (z. B. "19" für 19 %).
     * @param int            $scale   Nachkommastellen des Ergebnisses (Standard: 2).
     * @return numeric-string
     */
    public static function percentOfPrecise(string $value, string $percent, int $scale = 2, RoundingMode $mode = RoundingMode::HalfUp): string {
        $work = $scale + 6;
        return self::roundPrecise(bcdiv(bcmul($value, $percent, $work), '100', $work), $scale, $mode);
    }

    /**
     * Verteilt einen Gesamtbetrag cent-sicher gemäß Gewichten auf mehrere Positionen.
     *
     * Nutzt das Largest-Remainder-Verfahren: erst wird abgerundet, der durch die
     * Rundung verbleibende Restbetrag (in ULPs = 10^-scale) wird anschließend auf die
     * Positionen mit den größten Nachkomma-Resten verteilt. Dadurch gilt garantiert
     * `sum(result) === round(total, scale)` – kein Cent geht verloren oder entsteht.
     *
     * Array-Schlüssel der Gewichte bleiben erhalten. Sind alle Gewichte 0 (oder leer),
     * wird gleichmäßig verteilt. Negative Gesamtbeträge werden betragsweise verteilt
     * und das Vorzeichen zurückgegeben.
     *
     * @param numeric-string                    $total   Zu verteilender Gesamtbetrag.
     * @param array<array-key,string|int|float> $weights Gewichte/Verhältnisse je Position.
     * @param int                               $scale   Nachkommastellen (Standard: 2).
     * @return array<array-key,numeric-string> Verteilte Beträge (gleiche Schlüssel wie `$weights`).
     */
    public static function allocate(string $total, array $weights, int $scale = 2): array {
        $keys = array_keys($weights);
        $n = count($keys);
        if ($n === 0) {
            return [];
        }

        $negative = self::isNegativePrecise($total);
        $absTotal = self::absPrecise(trim($total));
        $work = $scale + 8;

        $weightStrings = array_map(static fn ($w): string => (string) $w, array_values($weights));
        $weightSum = self::sumPrecise($weightStrings, $work);
        if (bccomp($weightSum, '0', $work) <= 0) {
            $weightStrings = array_fill(0, $n, '1');
            $weightSum = (string) $n;
        }

        $floors = [];
        $remainders = [];
        $allocated = bcadd('0', '0', $scale);
        foreach ($weightStrings as $i => $w) {
            $exact = bcdiv(bcmul($absTotal, $w, $work), $weightSum, $work);
            $floor = self::roundPrecise($exact, $scale, RoundingMode::Floor);
            $floors[$i] = $floor;
            $remainders[$i] = bcsub($exact, $floor, $work);
            $allocated = bcadd($allocated, $floor, $scale);
        }

        $ulp = $scale === 0 ? '1' : '0.' . str_repeat('0', $scale - 1) . '1';
        $remainder = bcsub($absTotal, $allocated, $scale);
        $steps = (int) bcdiv($remainder, $ulp, 0);

        // Positionen nach Rest absteigend; bei Gleichstand niedrigerer Index zuerst (deterministisch)
        $order = range(0, $n - 1);
        usort($order, static function (int $x, int $y) use ($remainders, $work): int {
            $cmp = bccomp($remainders[$y], $remainders[$x], $work);
            return $cmp !== 0 ? $cmp : $x <=> $y;
        });

        for ($k = 0; $k < $steps; $k++) {
            $idx = $order[$k % $n];
            $floors[$idx] = bcadd($floors[$idx], $ulp, $scale);
        }

        $result = [];
        foreach ($keys as $pos => $key) {
            $value = $floors[$pos];
            $result[$key] = $negative && !self::isZeroPrecise($value, $scale) ? self::negatePrecise($value) : $value;
        }
        return $result;
    }

    /**
     * Verteilt einen Gesamtbetrag cent-sicher gleichmäßig auf `$parts` Positionen.
     * Kurzform von {@see allocate()} mit gleichen Gewichten.
     *
     * @param numeric-string $total Zu verteilender Gesamtbetrag.
     * @param int            $parts Anzahl Positionen (> 0).
     * @param int            $scale Nachkommastellen (Standard: 2).
     * @return array<array-key,numeric-string> Liste der verteilten Beträge.
     * @throws InvalidArgumentException Wenn `$parts` <= 0.
     */
    public static function allocateEvenly(string $total, int $parts, int $scale = 2): array {
        if ($parts <= 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Anzahl Positionen muss > 0 sein: $parts");
        }
        return self::allocate($total, array_fill(0, $parts, '1'), $scale);
    }

    /**
     * Berechnet den arithmetischen Durchschnitt einer Liste numerischer Strings – präzise.
     *
     * @param array<array-key,string|int|float> $numbers Zahlenliste. Leer → "0".
     * @param int                               $scale Nachkommastellen (Standard: 2).
     * @param RoundingMode                      $mode  Rundungsverfahren (Standard: HalfUp).
     * @return numeric-string
     */
    public static function averagePrecise(array $numbers, int $scale = 2, RoundingMode $mode = RoundingMode::HalfUp): string {
        if (empty($numbers)) {
            return bcadd('0', '0', $scale);
        }
        $work = $scale + 6;
        $sum = self::sumPrecise(array_map(static fn ($n): string => (string) $n, $numbers), $work);
        return self::roundPrecise(bcdiv($sum, (string) count($numbers), $work), $scale, $mode);
    }

    /**
     * Berechnet den Median einer Liste numerischer Strings – präzise.
     *
     * @param array<array-key,string|int|float> $numbers Zahlenliste. Leer → "0".
     * @param int                               $scale Nachkommastellen (Standard: 2).
     * @param RoundingMode                      $mode  Rundungsverfahren für den Mittelwert bei gerader Anzahl.
     * @return numeric-string
     */
    public static function medianPrecise(array $numbers, int $scale = 2, RoundingMode $mode = RoundingMode::HalfUp): string {
        if (empty($numbers)) {
            return bcadd('0', '0', $scale);
        }
        $nums = array_map(static fn ($n): string => (string) $n, array_values($numbers));
        $work = $scale + 6;
        usort($nums, static fn (string $a, string $b): int => bccomp($a, $b, $work));

        $count = count($nums);
        $mid = intdiv($count, 2);
        if ($count % 2 === 0) {
            return self::roundPrecise(bcdiv(bcadd($nums[$mid - 1], $nums[$mid], $work), '2', $work), $scale, $mode);
        }
        return self::roundPrecise($nums[$mid], $scale, $mode);
    }

    /**
     * Gibt den kleinsten Wert einer Liste numerischer Strings zurück (Original unverändert).
     *
     * @param array<array-key,string|int|float> $numbers Zahlenliste.
     * @param int|null                          $scale Vergleichsskala; null = maximale Präzision aller Werte.
     * @return numeric-string|null Kleinster Wert oder null bei leerer Liste.
     */
    public static function minOfPrecise(array $numbers, ?int $scale = null): ?string {
        if (empty($numbers)) {
            return null;
        }
        $nums = array_map(static fn ($n): string => (string) $n, array_values($numbers));
        $scale ??= max(array_map(static fn (string $n): int => self::decimalScale(trim($n)), $nums));
        $min = $nums[0];
        foreach ($nums as $n) {
            if (bccomp($n, $min, $scale) < 0) {
                $min = $n;
            }
        }
        return $min;
    }

    /**
     * Gibt den größten Wert einer Liste numerischer Strings zurück (Original unverändert).
     *
     * @param array<array-key,string|int|float> $numbers Zahlenliste.
     * @param int|null                          $scale Vergleichsskala; null = maximale Präzision aller Werte.
     * @return numeric-string|null Größter Wert oder null bei leerer Liste.
     */
    public static function maxOfPrecise(array $numbers, ?int $scale = null): ?string {
        if (empty($numbers)) {
            return null;
        }
        $nums = array_map(static fn ($n): string => (string) $n, array_values($numbers));
        $scale ??= max(array_map(static fn (string $n): int => self::decimalScale(trim($n)), $nums));
        $max = $nums[0];
        foreach ($nums as $n) {
            if (bccomp($n, $max, $scale) > 0) {
                $max = $n;
            }
        }
        return $max;
    }
}
