<?php
/*
 * Created on   : Sun Jul 19 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankHelperSanitizeIbanTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\BankHelper;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für BankHelper::sanitizeIBAN().
 *
 * Aufbauend auf normalizeIBAN() (Whitespace strippen + Uppercase) entfernt
 * sanitizeIBAN zusätzlich einen fälschlich angehängten, exakt bekannten
 * ISO-4217-Währungscode, sofern die IBAN dadurch auf die erwartete Länderlänge
 * zurückfällt. Bewusst konservativ und ohne Prüfsummenvalidierung.
 */
class BankHelperSanitizeIbanTest extends BaseTestCase {
    public function test_null_returns_null(): void {
        $this->assertNull(BankHelper::sanitizeIBAN(null));
    }

    public function test_empty_and_whitespace_returns_null(): void {
        $this->assertNull(BankHelper::sanitizeIBAN(''));
        $this->assertNull(BankHelper::sanitizeIBAN("  \t\n "));
    }

    public function test_clean_iban_is_stable(): void {
        $iban = 'DE89370400440532013000';
        $this->assertSame($iban, BankHelper::sanitizeIBAN($iban));
    }

    public function test_normalizes_like_normalize_iban(): void {
        $this->assertSame(
            'DE89370400440532013000',
            BankHelper::sanitizeIBAN('de89 3704 0044 0532 0130 00')
        );
    }

    public function test_strips_trailing_currency_with_space(): void {
        // Der ursprüngliche Vivid-Fehler: Währungskürzel aus der Nachbarspalte.
        $this->assertSame(
            'DE89370400440532013000',
            BankHelper::sanitizeIBAN('DE89 3704 0044 0532 0130 00 EUR')
        );
    }

    public function test_strips_trailing_currency_without_space(): void {
        // MT940 :25:-Feld trägt die Währung direkt an der IBAN.
        $this->assertSame(
            'DE27202208000027756428',
            BankHelper::sanitizeIBAN('DE27202208000027756428EUR')
        );
    }

    public function test_strips_other_known_currency_code(): void {
        $this->assertSame(
            'DE89370400440532013000',
            BankHelper::sanitizeIBAN('DE89370400440532013000USD')
        );
    }

    public function test_keeps_unknown_suffix(): void {
        // Kein exakter Währungscode → konservativ nicht abschneiden.
        $this->assertSame(
            'DE89370400440532013000EURX',
            BankHelper::sanitizeIBAN('DE89370400440532013000EURX')
        );
    }

    public function test_keeps_too_short_iban_with_currency(): void {
        // Länge unterschreitet die DE-Länderlänge auch nach dem Suffix → nichts
        // entfernen (bleibt ungültig und wird nachgelagert als solche erkannt).
        $this->assertSame(
            'DE1234567890EUR',
            BankHelper::sanitizeIBAN('DE1234567890 EUR')
        );
    }
}
