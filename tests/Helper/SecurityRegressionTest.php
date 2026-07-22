<?php
/*
 * Created on   : Wed Jul 22 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SecurityRegressionTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Enums\Common\CSV\QuotingStyle;
use CommonToolkit\Helper\Data\{BankHelper, CreditorIdHelper, SecurityHelper, VatNumberHelper};
use CommonToolkit\Helper\Data\CSV\StringHelper;
use Tests\Contracts\BaseTestCase;

class SecurityRegressionTest extends BaseTestCase {
    public function test_creditor_id_regex_rejects_overlong_input_quickly(): void {
        // Pathological input that previously drove O(n^2) backtracking. The
        // length guard must reject it well under a second.
        $evil = 'DE12' . str_repeat('A', 20000) . '!';

        $start = hrtime(true);
        $result = CreditorIdHelper::isCreditorId($evil);
        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        $this->assertFalse($result);
        $this->assertLessThan(50, $elapsedMs, 'isCreditorId should not backtrack on long input');
    }

    public function test_creditor_id_still_accepts_valid_ids(): void {
        $this->assertTrue(CreditorIdHelper::isCreditorId('DE98ZZZ09999999999'));
        $this->assertTrue(CreditorIdHelper::isCreditorId('DE02ABC01234567890'));
    }

    public function test_csv_formula_injection_is_neutralized_when_opted_in(): void {
        $this->assertSame("'=cmd|'/c calc'!A1", StringHelper::neutralizeFormulaInjection("=cmd|'/c calc'!A1"));
        $this->assertSame("'+SUM(A1)", StringHelper::neutralizeFormulaInjection('+SUM(A1)'));
        $this->assertSame('safe value', StringHelper::neutralizeFormulaInjection('safe value'));

        // Opt-in via encodeField: a formula-leading field gets the apostrophe.
        $encoded = StringHelper::encodeField('=HYPERLINK("http://evil")', ',', '"', QuotingStyle::MINIMAL, '\\', true);
        $this->assertStringStartsWith("'=", trim($encoded, '"'));
    }

    public function test_fr_vat_strict_rejects_unverifiable_alphanumeric_key(): void {
        // Format-only check still accepts the shape …
        $this->assertTrue(VatNumberHelper::isVatId('FRXX123456789'));
        // … but strict validation must not report an unverifiable number valid.
        $this->assertFalse(VatNumberHelper::validateVatId('FRXX123456789', true));
    }

    public function test_is_iban_rejects_masked_format(): void {
        $masked = 'DEXX30020900532XXXX486';
        $this->assertTrue(BankHelper::isIBANAnon($masked));
        $this->assertFalse(BankHelper::isIBAN($masked));
        // A real IBAN is unaffected.
        $this->assertTrue(BankHelper::isIBAN('DE44500105175407324931'));
    }

    public function test_csrf_token_does_not_disclose_session_id_and_is_not_forgeable(): void {
        $sessionId = 'sess_abc123secret';
        $token = SecurityHelper::generateCsrfToken($sessionId, 'transfer');

        $decoded = base64_decode($token, true);
        $this->assertIsString($decoded);
        $this->assertStringNotContainsString($sessionId, $decoded);

        $this->assertTrue(SecurityHelper::validateCsrfToken($token, $sessionId, 'transfer'));
        $this->assertFalse(SecurityHelper::validateCsrfToken($token, 'other_session', 'transfer'));
        $this->assertFalse(SecurityHelper::validateCsrfToken($token, $sessionId, 'login'));
    }
}
