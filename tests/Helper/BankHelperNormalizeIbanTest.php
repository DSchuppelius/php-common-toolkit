<?php
/*
 * Created on   : Thu Jul 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankHelperNormalizeIbanTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\BankHelper;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für BankHelper::normalizeIBAN().
 *
 * Das Verhalten ist bewusst NUR Normalisierung (Whitespace strippen + Uppercase,
 * null/leer → null) – keine Validierung, keine Formatierung. Nachgelagerte
 * Blind-Indizes/Hashes hängen am exakten Rückgabeformat.
 */
class BankHelperNormalizeIbanTest extends BaseTestCase {
    public function test_null_returns_null(): void {
        $this->assertNull(BankHelper::normalizeIBAN(null));
    }

    public function test_empty_string_returns_null(): void {
        $this->assertNull(BankHelper::normalizeIBAN(''));
    }

    public function test_whitespace_only_returns_null(): void {
        $this->assertNull(BankHelper::normalizeIBAN('   '));
        $this->assertNull(BankHelper::normalizeIBAN("\t\n\r "));
    }

    public function test_strips_grouped_spaces(): void {
        $this->assertSame(
            'DE89370400440532013000',
            BankHelper::normalizeIBAN('DE89 3704 0044 0532 0130 00')
        );
    }

    public function test_uppercases_lowercase_input(): void {
        $this->assertSame(
            'DE89370400440532013000',
            BankHelper::normalizeIBAN('de89370400440532013000')
        );
    }

    public function test_mixed_whitespace_and_case(): void {
        $this->assertSame(
            'DE89370400440532013000',
            BankHelper::normalizeIBAN("  de89\t3704 0044\n0532 0130 00  ")
        );
    }

    public function test_no_validation_invalid_input_passes_through(): void {
        // Keine Validierung: auch Nicht-IBANs werden nur normalisiert.
        $this->assertSame('FOO', BankHelper::normalizeIBAN(' foo '));
        $this->assertSame('123', BankHelper::normalizeIBAN('1 2 3'));
    }

    public function test_already_normalized_is_stable(): void {
        $iban = 'AT611904300234573201';
        $this->assertSame($iban, BankHelper::normalizeIBAN($iban));
        $this->assertSame($iban, BankHelper::normalizeIBAN(BankHelper::normalizeIBAN($iban)));
    }
}
