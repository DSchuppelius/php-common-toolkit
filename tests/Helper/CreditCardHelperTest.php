<?php
/*
 * Created on   : Wed Jul 22 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CreditCardHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\CreditCardHelper;
use Tests\Contracts\BaseTestCase;

class CreditCardHelperTest extends BaseTestCase {
    /** Canonical Luhn-valid numbers must validate. */
    public function test_luhn_accepts_canonical_valid_numbers(): void {
        foreach (['4111111111111111', '378282246310005', '79927398713', '5555555555554444', '6011111111111117'] as $valid) {
            $this->assertTrue(CreditCardHelper::validateLuhn($valid), "{$valid} should be Luhn-valid");
        }
    }

    /** A single-digit typo must fail Luhn. */
    public function test_luhn_rejects_invalid_numbers(): void {
        foreach (['4111111111111112', '1234567890123456', '0000000000000001'] as $invalid) {
            $this->assertFalse(CreditCardHelper::validateLuhn($invalid), "{$invalid} should be Luhn-invalid");
        }
    }

    public function test_is_valid_card_number_matches_luhn(): void {
        $this->assertTrue(CreditCardHelper::isValidCardNumber('4111 1111 1111 1111'));
        $this->assertFalse(CreditCardHelper::isValidCardNumber('4111 1111 1111 1112'));
    }

    /** The class must accept the numbers it generates itself. */
    public function test_generated_test_numbers_are_valid(): void {
        foreach (['Visa', 'Mastercard', 'American Express', 'Diners Club', 'Discover', 'JCB'] as $type) {
            $number = CreditCardHelper::generateTestCardNumber($type);
            $this->assertNotNull($number, "{$type} should have a test number");
            $this->assertTrue(CreditCardHelper::validateLuhn($number), "generated {$type} number should be Luhn-valid");
        }
    }
}
