<?php
/*
 * Created on   : Fri Sep 18 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankHelperNormalizeBicTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\BankHelper;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für BankHelper::normalizeBIC() – Gegenstück zu normalizeIBAN():
 * nur Whitespace strippen + Uppercase, kein Auffüllen, keine Validierung.
 */
class BankHelperNormalizeBicTest extends BaseTestCase {
    public function test_null_empty_and_whitespace_return_null(): void {
        $this->assertNull(BankHelper::normalizeBIC(null));
        $this->assertNull(BankHelper::normalizeBIC(''));
        $this->assertNull(BankHelper::normalizeBIC("  \t\n\r "));
        $this->assertNull(BankHelper::normalizeBIC("\u{00A0}\u{2009}\u{3000}"));
    }

    public function test_strips_whitespace_and_uppercases(): void {
        $this->assertSame('COBADEFFXXX', BankHelper::normalizeBIC(' coba de ff xxx '));
        $this->assertSame('DEUTDEDB', BankHelper::normalizeBIC("deut\tde\ndb"));
    }

    public function test_strips_unicode_whitespace(): void {
        $this->assertSame('COBADEFFXXX', BankHelper::normalizeBIC("COBA\u{00A0}DEFF\u{202F}XXX"));
    }

    public function test_bic8_is_not_padded(): void {
        $this->assertSame('COBADEFF', BankHelper::normalizeBIC('cobadeff'));
    }

    public function test_no_validation(): void {
        $this->assertSame('FOO-1', BankHelper::normalizeBIC(' foo-1 '));
    }

    public function test_invalid_utf8_falls_back_to_ascii_whitespace(): void {
        $this->assertSame("AB\xFFC", BankHelper::normalizeBIC("a b\xFF c"));
    }

    public function test_idempotent(): void {
        $bic = BankHelper::normalizeBIC(' Coba DE ff ');
        $this->assertSame($bic, BankHelper::normalizeBIC($bic));
    }
}
