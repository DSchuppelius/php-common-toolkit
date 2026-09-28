<?php
/*
 * Created on   : Mon Sep 28 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CreditorReferenceHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

/**
 * Strukturierte Gläubigerreferenz nach ISO 11649 („RF-Referenz“).
 *
 * Aufbau: `RF` + zwei Prüfziffern (ISO 7064 MOD 97-10) + Referenz aus
 * 1–21 Buchstaben/Ziffern; höchstens 25 Zeichen. Papierform in Vierergruppen
 * (`RF18 5390 0754 7034`), elektronisch ohne Leerzeichen.
 */
final class CreditorReferenceHelper {
    private const MAX_REFERENCE_LENGTH = 21;

    /**
     * Bildet die RF-Referenz zu einer Kennung (z. B. einer Rechnungsnummer).
     * Leerzeichen fallen weg, Kleinbuchstaben werden groß; andere Zeichen
     * oder mehr als 21 Stellen ergeben null.
     */
    public static function create(string $reference): ?string {
        $reference = self::compact($reference);
        if (preg_match('/^[A-Z0-9]{1,' . self::MAX_REFERENCE_LENGTH . '}$/', $reference) !== 1) {
            return null;
        }
        $check = 98 - self::mod97($reference . 'RF00');

        return 'RF' . str_pad((string) $check, 2, '0', STR_PAD_LEFT) . $reference;
    }

    /** Prüft Aufbau und Prüfziffern; Leerzeichen und Kleinschreibung sind erlaubt. */
    public static function isValid(?string $value): bool {
        $compact = self::compact((string) $value);
        if (preg_match('/^RF[0-9]{2}[A-Z0-9]{1,' . self::MAX_REFERENCE_LENGTH . '}$/', $compact) !== 1) {
            return false;
        }

        return self::mod97(substr($compact, 4) . substr($compact, 0, 4)) === 1;
    }

    /** Die Kennung hinter `RFnn` einer gültigen Referenz, sonst null. */
    public static function reference(?string $value): ?string {
        return self::isValid($value) ? substr(self::compact((string) $value), 4) : null;
    }

    /** Papierform in Vierergruppen; ungültige Werte bleiben unverändert. */
    public static function format(string $value): string {
        if (!self::isValid($value)) {
            return $value;
        }

        return implode(' ', str_split(self::compact($value), 4));
    }

    /**
     * Gültige RF-Referenzen in einem Freitext (z. B. Verwendungszweck), auch in
     * Vierergruppen geschrieben. Je Fundstelle gilt die längste gültige Lesart.
     *
     * @return list<string> kompakt, ohne Dubletten
     */
    public static function extract(?string $text): array {
        if ($text === null || $text === '') {
            return [];
        }
        if (preg_match_all('/(?<![A-Z0-9])RF ?[0-9]{2}((?: ?[A-Z0-9]){1,' . self::MAX_REFERENCE_LENGTH . '})/i', $text, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        $found = [];
        foreach ($matches as $match) {
            $candidate = self::compact($match[0]);
            for ($length = strlen($candidate); $length >= 5; $length--) {
                $prefix = substr($candidate, 0, $length);
                if (self::isValid($prefix)) {
                    $found[$prefix] = true;
                    break;
                }
            }
        }

        return array_keys($found);
    }

    private static function compact(string $value): string {
        return strtoupper((string) preg_replace('/\s+/', '', $value));
    }

    /** Rest modulo 97 nach ISO 7064 (Buchstaben A=10 … Z=35), ziffernweise ohne bcmath. */
    private static function mod97(string $value): int {
        $remainder = 0;
        foreach (str_split($value) as $char) {
            $digits = ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
            foreach (str_split($digits) as $digit) {
                $remainder = ($remainder * 10 + (int) $digit) % 97;
            }
        }

        return $remainder;
    }
}
