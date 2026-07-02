<?php
/*
 * Created on   : Thu Jul 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankHelperHashIbanTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Enums\HashAlgorithm;
use CommonToolkit\Helper\Data\BankHelper;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für BankHelper::hashIBAN() — Blind-Index-Digest der normalisierten
 * IBAN. Das Ausgabeformat (SHA-256-Hex der normalisierten IBAN) ist
 * Format-Anker für persistierte Blind-Indizes und darf sich nicht ändern.
 */
class BankHelperHashIbanTest extends BaseTestCase {
    public function test_format_anchor_sha256_hex_of_normalized_iban(): void {
        $this->assertSame(
            hash('sha256', 'DE89370400440532013000'),
            BankHelper::hashIBAN('DE89370400440532013000')
        );
    }

    public function test_spaced_and_compact_notation_yield_same_hash(): void {
        $this->assertSame(
            BankHelper::hashIBAN('de89 3704 0044 0532 0130 00'),
            BankHelper::hashIBAN('DE89370400440532013000')
        );
    }

    public function test_null_empty_and_whitespace_yield_null(): void {
        $this->assertNull(BankHelper::hashIBAN(null));
        $this->assertNull(BankHelper::hashIBAN(''));
        $this->assertNull(BankHelper::hashIBAN('   '));
    }

    public function test_output_is_64_char_hex(): void {
        $hash = BankHelper::hashIBAN('DE89370400440532013000');
        $this->assertNotNull($hash);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
    }

    public function test_algorithm_switch_changes_output(): void {
        $iban = 'DE89370400440532013000';
        $this->assertNotSame(
            BankHelper::hashIBAN($iban),
            BankHelper::hashIBAN($iban, HashAlgorithm::SHA512)
        );
        $this->assertSame(hash('sha512', $iban), BankHelper::hashIBAN($iban, HashAlgorithm::SHA512));
    }
}
