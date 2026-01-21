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

    // ========== Internationale IBAN-Tests ==========

    public function testCheckIBANInternational() {
        // Österreich
        $this->assertTrue(BankHelper::checkIBAN("AT611904300234573201"));
        // Schweiz
        $this->assertTrue(BankHelper::checkIBAN("CH9300762011623852957"));
        // Frankreich
        $this->assertTrue(BankHelper::checkIBAN("FR1420041010050500013M02606"));
        // Italien
        $this->assertTrue(BankHelper::checkIBAN("IT60X0542811101000000123456"));
        // Spanien
        $this->assertTrue(BankHelper::checkIBAN("ES9121000418450200051332"));
        // Niederlande
        $this->assertTrue(BankHelper::checkIBAN("NL91ABNA0417164300"));
        // Großbritannien
        $this->assertTrue(BankHelper::checkIBAN("GB29NWBK60161331926819"));
        // Polen
        $this->assertTrue(BankHelper::checkIBAN("PL61109010140000071219812874"));
        // Belgien
        $this->assertTrue(BankHelper::checkIBAN("BE68539007547034"));
        // Weitere Länder
        $this->assertTrue(BankHelper::checkIBAN("AD1200012030200359100100")); // Andorra
        $this->assertTrue(BankHelper::checkIBAN("AE070331234567890123456"));  // VAE
        $this->assertTrue(BankHelper::checkIBAN("AL47212110090000000235698741")); // Albanien
        $this->assertTrue(BankHelper::checkIBAN("BA391290079401028494"));     // Bosnien
        $this->assertTrue(BankHelper::checkIBAN("RS35260005601001611379"));   // Serbien
        $this->assertTrue(BankHelper::checkIBAN("ME25505000012345678951"));   // Montenegro
        $this->assertTrue(BankHelper::checkIBAN("SA0380000000608010167519")); // Saudi-Arabien
        $this->assertTrue(BankHelper::checkIBAN("IL620108000000099999999"));  // Israel
    }

    public function testCheckIBANInternationalInvalid() {
        // Falsche Prüfziffer
        $this->assertFalse(BankHelper::checkIBAN("AT001904300234573201"));
        // Falsche Länge
        $this->assertFalse(BankHelper::checkIBAN("CH93007620116238529"));
    }

    public function testSplitIBANComponentsGerman() {
        $components = BankHelper::splitIBANComponents("DE44500105175407324931");
        $this->assertIsArray($components);
        $this->assertEquals("DE", $components['countryCode']);
        $this->assertEquals("44", $components['checkDigits']);
        $this->assertEquals("500105175407324931", $components['bban']);
        $this->assertEquals("50010517", $components['bankCode']);
        $this->assertEquals("5407324931", $components['accountNumber']);
    }

    public function testSplitIBANComponentsAustrian() {
        $components = BankHelper::splitIBANComponents("AT611904300234573201");
        $this->assertIsArray($components);
        $this->assertEquals("AT", $components['countryCode']);
        $this->assertEquals("61", $components['checkDigits']);
        $this->assertEquals("19043", $components['bankCode']);
        $this->assertEquals("00234573201", $components['accountNumber']);
    }

    public function testSplitIBANComponentsSwiss() {
        $components = BankHelper::splitIBANComponents("CH9300762011623852957");
        $this->assertIsArray($components);
        $this->assertEquals("CH", $components['countryCode']);
        $this->assertEquals("00762", $components['bankCode']);
        $this->assertEquals("011623852957", $components['accountNumber']);
    }

    public function testSplitIBANComponentsFrench() {
        $components = BankHelper::splitIBANComponents("FR1420041010050500013M02606");
        $this->assertIsArray($components);
        $this->assertEquals("FR", $components['countryCode']);
        $this->assertEquals("20041", $components['bankCode']);
        $this->assertEquals("01005", $components['branchCode']);
        $this->assertEquals("0500013M026", $components['accountNumber']);
        $this->assertEquals("06", $components['nationalCheckDigits']);
    }

    public function testSplitIBANComponentsSpanish() {
        $components = BankHelper::splitIBANComponents("ES9121000418450200051332");
        $this->assertIsArray($components);
        $this->assertEquals("ES", $components['countryCode']);
        $this->assertEquals("2100", $components['bankCode']);
        $this->assertEquals("0418", $components['branchCode']);
        $this->assertEquals("45", $components['nationalCheckDigits']);
        $this->assertEquals("0200051332", $components['accountNumber']);
    }

    public function testSplitIBANComponentsBritish() {
        $components = BankHelper::splitIBANComponents("GB29NWBK60161331926819");
        $this->assertIsArray($components);
        $this->assertEquals("GB", $components['countryCode']);
        $this->assertEquals("NWBK", $components['bankCode']);
        $this->assertEquals("601613", $components['branchCode']);
        $this->assertEquals("31926819", $components['accountNumber']);
    }

    public function testSplitIBANComponentsInvalid() {
        $this->assertFalse(BankHelper::splitIBANComponents(null));
        $this->assertFalse(BankHelper::splitIBANComponents("INVALID"));
        $this->assertFalse(BankHelper::splitIBANComponents("XX123456789012345678"));
    }

    public function testGetCountryCodeFromIBAN() {
        $this->assertEquals("DE", BankHelper::getCountryCodeFromIBAN("DE44500105175407324931"));
        $this->assertEquals("AT", BankHelper::getCountryCodeFromIBAN("AT611904300234573201"));
        $this->assertEquals("CH", BankHelper::getCountryCodeFromIBAN("CH9300762011623852957"));
        $this->assertEquals("FR", BankHelper::getCountryCodeFromIBAN("FR1420041010050500013M02606"));
        $this->assertEquals("GB", BankHelper::getCountryCodeFromIBAN("GB29NWBK60161331926819"));
        $this->assertNull(BankHelper::getCountryCodeFromIBAN(null));
        $this->assertNull(BankHelper::getCountryCodeFromIBAN("XX"));
    }

    public function testIsIBANFromCountry() {
        $this->assertTrue(BankHelper::isIBANFromCountry("DE44500105175407324931", "DE"));
        $this->assertTrue(BankHelper::isIBANFromCountry("AT611904300234573201", "AT"));
        $this->assertFalse(BankHelper::isIBANFromCountry("DE44500105175407324931", "AT"));
        $this->assertFalse(BankHelper::isIBANFromCountry(null, "DE"));
    }

    public function testIsSepaIBAN() {
        // SEPA-Länder
        $this->assertTrue(BankHelper::isSepaIBAN("DE44500105175407324931")); // Deutschland
        $this->assertTrue(BankHelper::isSepaIBAN("AT611904300234573201"));   // Österreich
        $this->assertTrue(BankHelper::isSepaIBAN("CH9300762011623852957"));  // Schweiz
        $this->assertTrue(BankHelper::isSepaIBAN("GB29NWBK60161331926819")); // UK
        $this->assertTrue(BankHelper::isSepaIBAN("FR1420041010050500013M02606")); // Frankreich

        // Ungültige IBAN
        $this->assertFalse(BankHelper::isSepaIBAN(null));
        $this->assertFalse(BankHelper::isSepaIBAN("INVALID"));
    }

    public function testGetBankCodeFromIBAN() {
        $this->assertEquals("50010517", BankHelper::getBankCodeFromIBAN("DE44500105175407324931"));
        $this->assertEquals("19043", BankHelper::getBankCodeFromIBAN("AT611904300234573201"));
        $this->assertEquals("00762", BankHelper::getBankCodeFromIBAN("CH9300762011623852957"));
        $this->assertEquals("NWBK", BankHelper::getBankCodeFromIBAN("GB29NWBK60161331926819"));
        $this->assertNull(BankHelper::getBankCodeFromIBAN(null));
    }

    public function testGetAccountNumberFromIBAN() {
        $this->assertEquals("5407324931", BankHelper::getAccountNumberFromIBAN("DE44500105175407324931"));
        $this->assertEquals("00234573201", BankHelper::getAccountNumberFromIBAN("AT611904300234573201"));
        $this->assertEquals("011623852957", BankHelper::getAccountNumberFromIBAN("CH9300762011623852957"));
        $this->assertEquals("31926819", BankHelper::getAccountNumberFromIBAN("GB29NWBK60161331926819"));
        $this->assertNull(BankHelper::getAccountNumberFromIBAN(null));
    }

    public function testSplitIBANOnlyWorksForGerman() {
        // splitIBAN ist deprecated und funktioniert nur für deutsche IBANs
        $this->assertIsArray(BankHelper::splitIBAN("DE44500105175407324931"));
        $this->assertFalse(BankHelper::splitIBAN("AT611904300234573201"));
        $this->assertFalse(BankHelper::splitIBAN("CH9300762011623852957"));
    }

    public function testSplitIBANComponentsAdditionalCountries() {
        // Andorra
        $components = BankHelper::splitIBANComponents("AD1200012030200359100100");
        $this->assertEquals("AD", $components['countryCode']);
        $this->assertEquals("0001", $components['bankCode']);
        $this->assertEquals("2030", $components['branchCode']);
        $this->assertEquals("200359100100", $components['accountNumber']);

        // VAE
        $components = BankHelper::splitIBANComponents("AE070331234567890123456");
        $this->assertEquals("AE", $components['countryCode']);
        $this->assertEquals("033", $components['bankCode']);
        $this->assertEquals("1234567890123456", $components['accountNumber']);

        // Albanien
        $components = BankHelper::splitIBANComponents("AL47212110090000000235698741");
        $this->assertEquals("AL", $components['countryCode']);
        $this->assertEquals("21211009", $components['bankCode']);
        $this->assertEquals("0000000235698741", $components['accountNumber']);

        // Bosnien
        $components = BankHelper::splitIBANComponents("BA391290079401028494");
        $this->assertEquals("BA", $components['countryCode']);
        $this->assertEquals("129", $components['bankCode']);
        $this->assertEquals("007", $components['branchCode']);
        $this->assertEquals("9401028494", $components['accountNumber']);

        // Serbien
        $components = BankHelper::splitIBANComponents("RS35260005601001611379");
        $this->assertEquals("RS", $components['countryCode']);
        $this->assertEquals("260", $components['bankCode']);
        $this->assertEquals("005601001611379", $components['accountNumber']);

        // Montenegro
        $components = BankHelper::splitIBANComponents("ME25505000012345678951");
        $this->assertEquals("ME", $components['countryCode']);
        $this->assertEquals("505", $components['bankCode']);
        $this->assertEquals("000012345678951", $components['accountNumber']);

        // Saudi-Arabien
        $components = BankHelper::splitIBANComponents("SA0380000000608010167519");
        $this->assertEquals("SA", $components['countryCode']);
        $this->assertEquals("80", $components['bankCode']);
        $this->assertEquals("000000608010167519", $components['accountNumber']);

        // Israel
        $components = BankHelper::splitIBANComponents("IL620108000000099999999");
        $this->assertEquals("IL", $components['countryCode']);
        $this->assertEquals("010", $components['bankCode']);
        $this->assertEquals("800", $components['branchCode']);
        $this->assertEquals("0000099999999", $components['accountNumber']);
    }
}
