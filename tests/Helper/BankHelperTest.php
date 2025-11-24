<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

use CommonToolkit\Helper\Data\BankHelper;
use Tests\Contracts\BaseTestCase;

class BankHelperTest extends BaseTestCase {

    public function testIsBLZ() {
        $this->assertTrue(BankHelper::isBLZ("10000000"));
        $this->assertFalse(BankHelper::isBLZ("123"));
    }

    public function testIsKonto() {
        $this->assertTrue(BankHelper::isKTO("1234567890"));
        $this->assertFalse(BankHelper::isKTO("abc"));
    }

    public function testIsIBAN() {
        $this->assertTrue(BankHelper::isIBAN("DE44500105175407324931"));
        $this->assertFalse(BankHelper::isIBAN("INVALID_IBAN"));
    }

    public function testCheckIBANValid() {
        $this->assertTrue(BankHelper::checkIBAN("DE89370400440532013000"));
    }

    public function testCheckIBANInvalid() {
        $this->assertFalse(BankHelper::checkIBAN("DE00500105175407324931"));
    }

    public function testGenerateIBAN() {
        $iban = BankHelper::generateIBAN("DE", "100500001234567890");
        $this->assertEquals("DE46100500001234567890", $iban);
        $ibanGermany = BankHelper::generateGermanIBAN("10050000", "1234567890");
        $this->assertEquals("DE46100500001234567890", $ibanGermany);
        $this->assertMatchesRegularExpression('/^DE\d{2}100500001234567890$/', $ibanGermany);
        $this->assertTrue(BankHelper::checkIBAN($ibanGermany));
    }

    public function testSplitIban() {
        $parts = BankHelper::splitIBAN("DE44500105175407324931");
        $this->assertEquals("50010517", $parts['BLZ']);
        $this->assertEquals("5407324931", $parts['KTO']);
    }

    public function testBicFromIbanLoadsFile() {
        $iban = "DE44500105175407324931";
        $bic = BankHelper::bicFromIban($iban);
        $this->assertIsString($bic);
    }

    public function testCheckBicReturnsBankname() {
        $bic = BankHelper::checkBIC("COBADEFF");
        $this->assertStringContainsString("COMMERZBANK", $bic);
    }

    public function testIsBIC() {
        $this->assertTrue(BankHelper::isBIC("COBADEFFXXX"));
        $this->assertFalse(BankHelper::isBIC("INVALID"));
    }

    public function testIsIBANAnon() {
        $this->assertTrue(BankHelper::isIBANAnon("DEXX30020900532XXXX486"));
        $this->assertFalse(BankHelper::isIBANAnon("DE44500105175407324931"));
    }
}
