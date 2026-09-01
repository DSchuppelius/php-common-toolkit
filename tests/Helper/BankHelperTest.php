<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Helper\Data\BankHelper;
use Tests\Contracts\BaseTestCase;

class BankHelperTest extends BaseTestCase {
    /**
     * @return array{BLZ: string, KTO: string}
     */
    private function splitIbanOrFail(string $iban): array {
        $parts = BankHelper::splitIBAN($iban);
        if ($parts === false) {
            self::fail("splitIBAN($iban) sollte ein Array liefern");
        }
        return $parts;
    }

    /**
     * @return array<string, string>
     */
    private function splitComponentsOrFail(string $iban): array {
        $components = BankHelper::splitIBANComponents($iban);
        if ($components === false) {
            self::fail("splitIBANComponents($iban) sollte ein Array liefern");
        }
        return $components;
    }

    public function test_is_blz(): void {
        $this->assertTrue(BankHelper::isBLZ("10000000"));
        $this->assertFalse(BankHelper::isBLZ("123"));
    }

    public function test_is_konto(): void {
        $this->assertTrue(BankHelper::isKTO("1234567890"));
        $this->assertFalse(BankHelper::isKTO("abc"));
    }

    public function test_is_iban(): void {
        $this->assertTrue(BankHelper::isIBAN("DE44500105175407324931"));
        $this->assertFalse(BankHelper::isIBAN("INVALID_IBAN"));
    }

    public function test_check_iban_valid(): void {
        $this->assertTrue(BankHelper::checkIBAN("DE89370400440532013000"));
    }

    public function test_check_iban_invalid(): void {
        $this->assertFalse(BankHelper::checkIBAN("DE00500105175407324931"));
    }

    public function test_generate_iban(): void {
        $iban = BankHelper::generateIBAN("DE", "100500001234567890");
        $this->assertEquals("DE46100500001234567890", $iban);
        $ibanGermany = BankHelper::generateGermanIBAN("10050000", "1234567890");
        $this->assertEquals("DE46100500001234567890", $ibanGermany);
        $this->assertMatchesRegularExpression('/^DE\d{2}100500001234567890$/', $ibanGermany);
        $this->assertTrue(BankHelper::checkIBAN($ibanGermany));
    }

    public function test_split_iban(): void {
        $parts = $this->splitIbanOrFail("DE44500105175407324931");
        $this->assertEquals("50010517", $parts['BLZ']);
        $this->assertEquals("5407324931", $parts['KTO']);
    }

    public function test_bic_from_iban_loads_file(): void {
        $iban = "DE44500105175407324931";
        $bic = BankHelper::bicFromIban($iban);
        $this->assertIsString($bic);
    }

    public function test_check_bic_returns_bankname(): void {
        $bic = BankHelper::checkBIC("COBADEFF");
        if ($bic === false) {
            self::fail("checkBIC sollte einen Banknamen liefern");
        }
        $this->assertStringContainsString("COMMERZBANK", $bic);
    }

    public function test_is_bic(): void {
        $this->assertTrue(BankHelper::isBIC("COBADEFFXXX"));
        $this->assertFalse(BankHelper::isBIC("INVALID"));
    }

    public function test_is_iban_anon(): void {
        $this->assertTrue(BankHelper::isIBANAnon("DEXX30020900532XXXX486"));
        $this->assertFalse(BankHelper::isIBANAnon("DE44500105175407324931"));
    }

    // ========== Internationale IBAN-Tests ==========

    public function test_check_iban_international(): void {
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

    public function test_check_iban_international_invalid(): void {
        // Falsche Prüfziffer
        $this->assertFalse(BankHelper::checkIBAN("AT001904300234573201"));
        // Falsche Länge
        $this->assertFalse(BankHelper::checkIBAN("CH93007620116238529"));
    }

    public function test_split_iban_components_german(): void {
        $components = $this->splitComponentsOrFail("DE44500105175407324931");
        $this->assertEquals("DE", $components['countryCode']);
        $this->assertEquals("44", $components['checkDigits']);
        $this->assertEquals("500105175407324931", $components['bban']);
        $this->assertEquals("50010517", $components['bankCode']);
        $this->assertEquals("5407324931", $components['accountNumber']);
    }

    public function test_split_iban_components_austrian(): void {
        $components = $this->splitComponentsOrFail("AT611904300234573201");
        $this->assertEquals("AT", $components['countryCode']);
        $this->assertEquals("61", $components['checkDigits']);
        $this->assertEquals("19043", $components['bankCode']);
        $this->assertEquals("00234573201", $components['accountNumber']);
    }

    public function test_split_iban_components_swiss(): void {
        $components = $this->splitComponentsOrFail("CH9300762011623852957");
        $this->assertEquals("CH", $components['countryCode']);
        $this->assertEquals("00762", $components['bankCode']);
        $this->assertEquals("011623852957", $components['accountNumber']);
    }

    public function test_split_iban_components_french(): void {
        $components = $this->splitComponentsOrFail("FR1420041010050500013M02606");
        $this->assertEquals("FR", $components['countryCode']);
        $this->assertEquals("20041", $components['bankCode']);
        $this->assertEquals("01005", $components['branchCode']);
        $this->assertEquals("0500013M026", $components['accountNumber']);
        $this->assertEquals("06", $components['nationalCheckDigits']);
    }

    public function test_split_iban_components_spanish(): void {
        $components = $this->splitComponentsOrFail("ES9121000418450200051332");
        $this->assertEquals("ES", $components['countryCode']);
        $this->assertEquals("2100", $components['bankCode']);
        $this->assertEquals("0418", $components['branchCode']);
        $this->assertEquals("45", $components['nationalCheckDigits']);
        $this->assertEquals("0200051332", $components['accountNumber']);
    }

    public function test_split_iban_components_british(): void {
        $components = $this->splitComponentsOrFail("GB29NWBK60161331926819");
        $this->assertEquals("GB", $components['countryCode']);
        $this->assertEquals("NWBK", $components['bankCode']);
        $this->assertEquals("601613", $components['branchCode']);
        $this->assertEquals("31926819", $components['accountNumber']);
    }

    public function test_split_iban_components_invalid(): void {
        $this->assertFalse(BankHelper::splitIBANComponents(null));
        $this->assertFalse(BankHelper::splitIBANComponents("INVALID"));
        $this->assertFalse(BankHelper::splitIBANComponents("XX123456789012345678"));
    }

    public function test_get_country_code_from_iban(): void {
        $this->assertEquals(CountryCode::Germany, BankHelper::getCountryCodeFromIBAN("DE44500105175407324931"));
        $this->assertEquals(CountryCode::Austria, BankHelper::getCountryCodeFromIBAN("AT611904300234573201"));
        $this->assertEquals(CountryCode::Switzerland, BankHelper::getCountryCodeFromIBAN("CH9300762011623852957"));
        $this->assertEquals(CountryCode::France, BankHelper::getCountryCodeFromIBAN("FR1420041010050500013M02606"));
        $this->assertEquals(CountryCode::UnitedKingdomOfGreatBritainAndNorthernIreland, BankHelper::getCountryCodeFromIBAN("GB29NWBK60161331926819"));
        $this->assertNull(BankHelper::getCountryCodeFromIBAN(null));
        $this->assertNull(BankHelper::getCountryCodeFromIBAN("XX"));
    }

    public function test_is_iban_from_country(): void {
        $this->assertTrue(BankHelper::isIBANFromCountry("DE44500105175407324931", "DE"));
        $this->assertTrue(BankHelper::isIBANFromCountry("AT611904300234573201", "AT"));
        $this->assertFalse(BankHelper::isIBANFromCountry("DE44500105175407324931", "AT"));
        $this->assertFalse(BankHelper::isIBANFromCountry(null, "DE"));
    }

    public function test_is_sepa_iban(): void {
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

    public function test_get_bank_code_from_iban(): void {
        $this->assertEquals("50010517", BankHelper::getBankCodeFromIBAN("DE44500105175407324931"));
        $this->assertEquals("19043", BankHelper::getBankCodeFromIBAN("AT611904300234573201"));
        $this->assertEquals("00762", BankHelper::getBankCodeFromIBAN("CH9300762011623852957"));
        $this->assertEquals("NWBK", BankHelper::getBankCodeFromIBAN("GB29NWBK60161331926819"));
        $this->assertNull(BankHelper::getBankCodeFromIBAN(null));
    }

    public function test_get_account_number_from_iban(): void {
        $this->assertEquals("5407324931", BankHelper::getAccountNumberFromIBAN("DE44500105175407324931"));
        $this->assertEquals("00234573201", BankHelper::getAccountNumberFromIBAN("AT611904300234573201"));
        $this->assertEquals("011623852957", BankHelper::getAccountNumberFromIBAN("CH9300762011623852957"));
        $this->assertEquals("31926819", BankHelper::getAccountNumberFromIBAN("GB29NWBK60161331926819"));
        $this->assertNull(BankHelper::getAccountNumberFromIBAN(null));
    }

    public function test_split_iban_only_works_for_german(): void {
        // splitIBAN ist deprecated und funktioniert nur für deutsche IBANs
        $this->assertIsArray(BankHelper::splitIBAN("DE44500105175407324931"));
        $this->assertFalse(BankHelper::splitIBAN("AT611904300234573201"));
        $this->assertFalse(BankHelper::splitIBAN("CH9300762011623852957"));
    }

    public function test_split_iban_components_additional_countries(): void {
        // Andorra
        $components = $this->splitComponentsOrFail("AD1200012030200359100100");
        $this->assertEquals("AD", $components['countryCode']);
        $this->assertEquals("0001", $components['bankCode']);
        $this->assertEquals("2030", $components['branchCode']);
        $this->assertEquals("200359100100", $components['accountNumber']);

        // VAE
        $components = $this->splitComponentsOrFail("AE070331234567890123456");
        $this->assertEquals("AE", $components['countryCode']);
        $this->assertEquals("033", $components['bankCode']);
        $this->assertEquals("1234567890123456", $components['accountNumber']);

        // Albanien
        $components = $this->splitComponentsOrFail("AL47212110090000000235698741");
        $this->assertEquals("AL", $components['countryCode']);
        $this->assertEquals("21211009", $components['bankCode']);
        $this->assertEquals("0000000235698741", $components['accountNumber']);

        // Bosnien
        $components = $this->splitComponentsOrFail("BA391290079401028494");
        $this->assertEquals("BA", $components['countryCode']);
        $this->assertEquals("129", $components['bankCode']);
        $this->assertEquals("007", $components['branchCode']);
        $this->assertEquals("9401028494", $components['accountNumber']);

        // Serbien
        $components = $this->splitComponentsOrFail("RS35260005601001611379");
        $this->assertEquals("RS", $components['countryCode']);
        $this->assertEquals("260", $components['bankCode']);
        $this->assertEquals("005601001611379", $components['accountNumber']);

        // Montenegro
        $components = $this->splitComponentsOrFail("ME25505000012345678951");
        $this->assertEquals("ME", $components['countryCode']);
        $this->assertEquals("505", $components['bankCode']);
        $this->assertEquals("000012345678951", $components['accountNumber']);

        // Saudi-Arabien
        $components = $this->splitComponentsOrFail("SA0380000000608010167519");
        $this->assertEquals("SA", $components['countryCode']);
        $this->assertEquals("80", $components['bankCode']);
        $this->assertEquals("000000608010167519", $components['accountNumber']);

        // Israel
        $components = $this->splitComponentsOrFail("IL620108000000099999999");
        $this->assertEquals("IL", $components['countryCode']);
        $this->assertEquals("010", $components['bankCode']);
        $this->assertEquals("800", $components['branchCode']);
        $this->assertEquals("0000099999999", $components['accountNumber']);
    }

    /**
     * extractIBAN() bricht beim ersten gueltigen Treffer ab und muss dabei
     * exakt das erste Element von extractIBANs() liefern.
     */
    public function test_extract_iban_matches_first_of_extract_ibans(): void {
        $texts = [
            '',
            'kein iban enthalten',
            'DE25100800000012345605',
            'x DE25100800000012345605 y DE89370400440532013000',
            'DE00000000000000000000 dann DE89370400440532013000',
            'DE89 3704 0044 0532 0130 00',
            'ABCD1234567890123 DE89370400440532013000',
            str_repeat('DE99000000000000000001 ', 20) . 'DE89370400440532013000',
            'FR7630006000011234567890189 DE89370400440532013000',
        ];
        foreach ($texts as $text) {
            foreach ([[false, false], [true, false], [false, true], [true, true]] as [$strict, $spaceTolerant]) {
                $this->assertSame(
                    BankHelper::extractIBANs($text, $strict, $spaceTolerant)[0] ?? null,
                    BankHelper::extractIBAN($text, $strict, $spaceTolerant),
                    "Text '$text' (strict=" . var_export($strict, true) . ', spaceTolerant=' . var_export($spaceTolerant, true) . ')'
                );
            }
        }
        $this->assertSame('DE89370400440532013000', BankHelper::extractIBAN('ABCD1234567890123 DE89370400440532013000'));
        $this->assertNull(BankHelper::extractIBAN(null));
    }

    public function test_bic_from_iban_for_countries_with_numeric_bank_code(): void {
        // AT, CH und BE führen einen numerischen Bankcode in der IBAN; der BIC steckt
        // — anders als in den Niederlanden — nicht darin, sondern nur in der Liste der
        // jeweiligen Stelle (OeNB, SIX Interbank Clearing, NBB).
        self::assertSame('TRWIBEB1', BankHelper::bicFromIBAN('BE16967023680187'), 'BE: Bankcode = Stellen 5-7');
        self::assertSame('KBBECH22XXX', BankHelper::bicFromIBAN('CH1630790016245148291'), 'CH: IID = Stellen 5-9');
        self::assertSame('RLNWATWWXXX', BankHelper::bicFromIBAN('AT483200000012345864'), 'AT: BLZ = Stellen 5-9');
    }

    public function test_bic_from_iban_strips_leading_zeros_of_bank_code(): void {
        // Die IBAN füllt den Bankcode auf feste Breite auf ("CH32 3000 0…" → IID 30000),
        // die Quellen führen ihn ohne Auffüllung.
        self::assertSame('POFICHBEXXX', BankHelper::bicFromIBAN('CH3230000001876930777'));
    }

    public function test_bic_from_iban_returns_null_for_country_without_table(): void {
        // Für Luxemburg gibt es keine offen abrufbare Zuordnung — dann bleibt es bei null.
        self::assertNull(BankHelper::bicFromIBAN('LU280019400644750000'));
    }
}
