<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CheckDigitHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;

/**
 * Prüfziffern nach ISO/IEC 7064 MOD 97-10, wie sie IBAN (ISO 13616),
 * RF-Referenz (ISO 11649), SEPA-Gläubiger-ID, LEI (ISO 17442) und Leitweg-ID
 * verwenden.
 *
 * Buchstaben zählen als zwei Ziffern A = 10 … Z = 35, Kleinbuchstaben wie
 * Großbuchstaben. Die Prüfziffern stehen am Ende der Folge; Formate, die sie
 * vorn tragen (IBAN, RF, Gläubiger-ID), stellen die Folge vorher um.
 * Normalisieren (Leerraum, Trennzeichen) und die Struktur des Formats prüft
 * der Aufrufer.
 *
 * @example
 * ```php
 * CheckDigitHelper::mod97CheckDigits('040110001234512345');    // "06" (Leitweg-ID)
 * CheckDigitHelper::mod97CheckDigits('370400440532013000DE');  // "89" (IBAN DE89…)
 * CheckDigitHelper::isValidMod97('370400440532013000DE89');    // true
 * CheckDigitHelper::mod97Remainder('370400440532013000DE89');  // 1
 * ```
 */
final class CheckDigitHelper {
    use ErrorLog;

    /**
     * Rest modulo 97 der Folge.
     *
     * @throws InvalidArgumentException bei leerer Eingabe oder anderen Zeichen als 0–9, A–Z, a–z
     */
    public static function mod97Remainder(string $value): int {
        self::assertAlphanumeric($value);

        return self::remainder($value);
    }

    /**
     * Die zwei Prüfziffern (02–98), mit denen die Folge Rest 1 ergibt:
     * 98 minus Rest der Folge mit angehängtem „00“, mit führender Null.
     *
     * @throws InvalidArgumentException bei leerer Eingabe oder anderen Zeichen als 0–9, A–Z, a–z
     */
    public static function mod97CheckDigits(string $value): string {
        self::assertAlphanumeric($value);

        return sprintf('%02d', 98 - self::remainder($value . '00'));
    }

    /**
     * Die Folge samt ihrer Prüfziffern am Ende ergibt Rest 1. Leere Eingabe und
     * andere Zeichen als 0–9, A–Z, a–z ergeben false.
     */
    public static function isValidMod97(string $value): bool {
        return self::isAlphanumeric($value) && self::remainder($value) === 1;
    }

    private static function isAlphanumeric(string $value): bool {
        return preg_match('/^[0-9A-Za-z]+$/', $value) === 1;
    }

    private static function assertAlphanumeric(string $value): void {
        if (!self::isAlphanumeric($value)) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'ISO 7064 MOD 97-10 erwartet mindestens ein Zeichen und nur 0–9 und A–Z.');
        }
    }

    /** Ziffernweise ohne bcmath; ein Buchstabe rückt zwei Stellen weiter. */
    private static function remainder(string $value): int {
        $remainder = 0;
        foreach (str_split($value) as $char) {
            if ($char <= '9') {
                $remainder = ($remainder * 10 + (int) $char) % 97;
                continue;
            }
            // ord() | 0x20 bildet A–Z auf a–z (97–122) ab, ohne Locale; a = 10.
            $remainder = ($remainder * 100 + (ord($char) | 0x20) - 87) % 97;
        }

        return $remainder;
    }
}
