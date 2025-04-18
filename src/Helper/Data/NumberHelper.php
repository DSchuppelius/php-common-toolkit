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

use CommonToolkit\Enums\MetricPrefix;
use CommonToolkit\Enums\TemperatureUnit;
use RuntimeException;

class NumberHelper {
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
            throw new RuntimeException("Ungültiges Format: '$input'");
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
            default => throw new \RuntimeException("Ungültige Temperaturumrechnung: {$from->value} zu {$to->value}")
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
            throw new RuntimeException("Uneinheitliche Basiseinheit: $fromBase zu $toBase");
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
     * Normalisiert bzw. Konvertiert eine Dezimalzahl, indem sie Punkte und Leerzeichen entfernt und Kommas in Punkte umwandelt.
     * @param string $value Der zu normalisierende Wert.
     * @return float Der normalisierte Wert.
     */
    public static function normalizeDecimal(string $value): float {
        $value = str_replace(['.', ' '], '', $value);
        $value = str_replace(',', '.', $value);
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
}
