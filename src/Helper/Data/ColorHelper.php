<?php
/*
 * Created on   : Sat Jul 11 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ColorHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

/**
 * Zustandslose Farb-Mathematik nach WCAG 2.x (relative Luminanz und
 * Kontrastverhältnis) plus strikte HEX-Normalisierung.
 *
 * Bewusst eng gehalten: ausschließlich 6-stellige HEX-Werte. Das schützt
 * zugleich vor CSS-Injection, weil normalizeHex() nur `#` + 6 Hex-Zeichen
 * durchlässt — jeder andere Input ergibt null.
 */
class ColorHelper {
    /**
     * Normalisiert einen 6-stelligen HEX-Wert auf `#rrggbb` (kleingeschrieben)
     * oder gibt null zurück, wenn der Wert kein gültiges 6-stelliges HEX ist.
     */
    public static function normalizeHex(?string $value): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || !preg_match('/^#?[0-9a-fA-F]{6}$/', $value)) {
            return null;
        }

        return str_starts_with($value, '#') ? strtolower($value) : '#' . strtolower($value);
    }

    /**
     * Relative Luminanz (sRGB, 0.0 … 1.0) nach WCAG 2.x. Ungültige Werte
     * (kein 6-stelliges HEX) ergeben 0.0.
     *
     * @param string $hex 6-stelliges HEX, mit oder ohne führendes `#`
     */
    public static function relativeLuminance(string $hex): float {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return 0.0;
        }

        $channels = [];
        foreach ([0, 2, 4] as $offset) {
            $c = hexdec(substr($hex, $offset, 2)) / 255;
            $channels[] = $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * WCAG-Kontrastverhältnis zweier HEX-Farben (1.0 … 21.0), symmetrisch.
     */
    public static function contrastRatio(string $a, string $b): float {
        $la = self::relativeLuminance($a);
        $lb = self::relativeLuminance($b);
        $light = max($la, $lb);
        $dark = min($la, $lb);

        return ($light + 0.05) / ($dark + 0.05);
    }

    /**
     * Wählt eine gut lesbare Vordergrundfarbe für einen gegebenen
     * Hintergrund: helle Hintergründe (Luminanz über dem Schwellwert)
     * bekommen die dunkle, dunkle Hintergründe die helle Farbe.
     *
     * @param string $hex 6-stelliges HEX, mit oder ohne führendes `#`
     * @param string $dark Rückgabe für helle Hintergründe
     * @param string $light Rückgabe für dunkle Hintergründe
     * @param float $luminanceThreshold Grenzwert der relativen Luminanz
     */
    public static function readableForeground(string $hex, string $dark = '#1f2937', string $light = '#ffffff', float $luminanceThreshold = 0.55): string {
        return self::relativeLuminance($hex) > $luminanceThreshold ? $dark : $light;
    }
}
