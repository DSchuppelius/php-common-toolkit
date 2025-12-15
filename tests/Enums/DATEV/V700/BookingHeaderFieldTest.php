<?php
/*
 * Created on   : Sat Dec 14 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingHeaderFieldTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Enums\DATEV\V700;

use CommonToolkit\Enums\DATEV\V700\BookingHeaderField;
use Tests\Contracts\BaseTestCase;

class BookingHeaderFieldTest extends BaseTestCase {

    public function testHasCorrectNumberOfFields(): void {
        $ordered = BookingHeaderField::ordered();
        $this->assertCount(125, $ordered, 'DATEV V700 sollte genau 125 Felder haben');
    }

    public function testOrderedFieldsAreComplete(): void {
        $ordered = BookingHeaderField::ordered();

        // Erste 10 Felder (Grunddaten)
        $this->assertEquals(BookingHeaderField::Umsatz, $ordered[0]);
        $this->assertEquals(BookingHeaderField::SollHabenKennzeichen, $ordered[1]);
        $this->assertEquals(BookingHeaderField::WKZUmsatz, $ordered[2]);
        $this->assertEquals(BookingHeaderField::Kurs, $ordered[3]);
        $this->assertEquals(BookingHeaderField::BasisUmsatz, $ordered[4]);
        $this->assertEquals(BookingHeaderField::WKZBasisUmsatz, $ordered[5]);
        $this->assertEquals(BookingHeaderField::Konto, $ordered[6]);
        $this->assertEquals(BookingHeaderField::Gegenkonto, $ordered[7]);
        $this->assertEquals(BookingHeaderField::BUSchluessel, $ordered[8]);
        $this->assertEquals(BookingHeaderField::Belegdatum, $ordered[9]);
    }

    public function testRequiredFields(): void {
        $required = BookingHeaderField::required();

        $this->assertContains(BookingHeaderField::Umsatz, $required);
        $this->assertContains(BookingHeaderField::SollHabenKennzeichen, $required);
        $this->assertContains(BookingHeaderField::Konto, $required);
        $this->assertContains(BookingHeaderField::Gegenkonto, $required);
        $this->assertContains(BookingHeaderField::Belegdatum, $required);
        $this->assertContains(BookingHeaderField::Belegfeld1, $required);
        $this->assertContains(BookingHeaderField::Buchungstext, $required);

        $this->assertCount(7, $required, 'Es sollte genau 7 Pflichtfelder geben');
    }

    public function testIsRequiredMethod(): void {
        $this->assertTrue(BookingHeaderField::Umsatz->isRequired());
        $this->assertTrue(BookingHeaderField::SollHabenKennzeichen->isRequired());
        $this->assertTrue(BookingHeaderField::Konto->isRequired());
        $this->assertTrue(BookingHeaderField::Gegenkonto->isRequired());

        $this->assertFalse(BookingHeaderField::Skonto->isRequired());
        $this->assertFalse(BookingHeaderField::KOST1->isRequired());
        $this->assertFalse(BookingHeaderField::Gewicht->isRequired());
    }

    public function testEuFields(): void {
        $euFields = BookingHeaderField::euFields();

        $this->assertContains(BookingHeaderField::EULandUStID, $euFields);
        $this->assertContains(BookingHeaderField::EUSteuer, $euFields);
        $this->assertContains(BookingHeaderField::EUMitgliedstaatAnzahlung, $euFields);
        $this->assertContains(BookingHeaderField::EUSteuersatzAnzahlung, $euFields);
        $this->assertContains(BookingHeaderField::EUMitgliedstaatUstID, $euFields);
        $this->assertContains(BookingHeaderField::EUSteuersatzUrsprung, $euFields);
    }

    public function testIsEuFieldMethod(): void {
        $this->assertTrue(BookingHeaderField::EULandUStID->isEuField());
        $this->assertTrue(BookingHeaderField::EUSteuer->isEuField());
        $this->assertTrue(BookingHeaderField::EUMitgliedstaatAnzahlung->isEuField());

        $this->assertFalse(BookingHeaderField::Umsatz->isEuField());
        $this->assertFalse(BookingHeaderField::Buchungstext->isEuField());
    }

    public function testSepaFields(): void {
        $sepaFields = BookingHeaderField::sepaFields();

        $this->assertContains(BookingHeaderField::SEPAMandatsreferenz, $sepaFields);
        $this->assertContains(BookingHeaderField::Zahlweise, $sepaFields);
        $this->assertContains(BookingHeaderField::Faelligkeit, $sepaFields);
        $this->assertContains(BookingHeaderField::Geschaeftspartnerbank, $sepaFields);
        $this->assertContains(BookingHeaderField::Skontosperre, $sepaFields);
    }

    public function testIsSepaFieldMethod(): void {
        $this->assertTrue(BookingHeaderField::SEPAMandatsreferenz->isSepaField());
        $this->assertTrue(BookingHeaderField::Zahlweise->isSepaField());
        $this->assertTrue(BookingHeaderField::Faelligkeit->isSepaField());

        $this->assertFalse(BookingHeaderField::Umsatz->isSepaField());
        $this->assertFalse(BookingHeaderField::BUSchluessel->isSepaField());
    }

    public function testCostFields(): void {
        $costFields = BookingHeaderField::costFields();

        $this->assertContains(BookingHeaderField::KOST1, $costFields);
        $this->assertContains(BookingHeaderField::KOST2, $costFields);
        $this->assertContains(BookingHeaderField::KostMenge, $costFields);
        $this->assertContains(BookingHeaderField::Stueck, $costFields);
        $this->assertContains(BookingHeaderField::Gewicht, $costFields);
        $this->assertContains(BookingHeaderField::KOSTDatum, $costFields);
    }

    public function testTaxFields(): void {
        $taxFields = BookingHeaderField::taxFields();

        $this->assertContains(BookingHeaderField::UStSchluessel, $taxFields);
        $this->assertContains(BookingHeaderField::Steuersatz, $taxFields);
        $this->assertContains(BookingHeaderField::EUSteuer, $taxFields);
        $this->assertContains(BookingHeaderField::EUSteuersatzAnzahlung, $taxFields);
        $this->assertContains(BookingHeaderField::EUSteuersatzUrsprung, $taxFields);
        $this->assertContains(BookingHeaderField::Abwkonto, $taxFields);
    }

    public function testAdditionalInfoFields(): void {
        $additionalFields = BookingHeaderField::additionalInfoFields();

        // Sollte 40 Felder haben (20 Art + 20 Inhalt)
        $this->assertCount(40, $additionalFields);

        // Teste erste und letzte Paare
        $this->assertContains(BookingHeaderField::ZusatzInfo1, $additionalFields);
        $this->assertContains(BookingHeaderField::ZusatzInfoInhalt1, $additionalFields);
        $this->assertContains(BookingHeaderField::ZusatzInfo20, $additionalFields);
        $this->assertContains(BookingHeaderField::ZusatzInfoInhalt20, $additionalFields);
    }

    public function testIsAdditionalInfoFieldMethod(): void {
        $this->assertTrue(BookingHeaderField::ZusatzInfo1->isAdditionalInfoField());
        $this->assertTrue(BookingHeaderField::ZusatzInfoInhalt1->isAdditionalInfoField());
        $this->assertTrue(BookingHeaderField::ZusatzInfo10->isAdditionalInfoField());
        $this->assertTrue(BookingHeaderField::ZusatzInfo20->isAdditionalInfoField());

        $this->assertFalse(BookingHeaderField::Umsatz->isAdditionalInfoField());
        $this->assertFalse(BookingHeaderField::BelegInfoArt1->isAdditionalInfoField());
    }

    public function testDocumentInfoFields(): void {
        $documentFields = BookingHeaderField::documentInfoFields();

        // Sollte 16 Felder haben (8 Art + 8 Inhalt)
        $this->assertCount(16, $documentFields);

        $this->assertContains(BookingHeaderField::BelegInfoArt1, $documentFields);
        $this->assertContains(BookingHeaderField::BelegInfoInhalt1, $documentFields);
        $this->assertContains(BookingHeaderField::BelegInfoArt8, $documentFields);
        $this->assertContains(BookingHeaderField::BelegInfoInhalt8, $documentFields);
    }

    public function testIsDocumentInfoFieldMethod(): void {
        $this->assertTrue(BookingHeaderField::BelegInfoArt1->isDocumentInfoField());
        $this->assertTrue(BookingHeaderField::BelegInfoInhalt1->isDocumentInfoField());
        $this->assertTrue(BookingHeaderField::BelegInfoArt8->isDocumentInfoField());

        $this->assertFalse(BookingHeaderField::Umsatz->isDocumentInfoField());
        $this->assertFalse(BookingHeaderField::ZusatzInfo1->isDocumentInfoField());
    }

    public function testGetFieldType(): void {
        // Numerische Felder
        $this->assertEquals('numeric', BookingHeaderField::Umsatz->getFieldType());
        $this->assertEquals('numeric', BookingHeaderField::BasisUmsatz->getFieldType());
        $this->assertEquals('numeric', BookingHeaderField::Skonto->getFieldType());
        $this->assertEquals('numeric', BookingHeaderField::KostMenge->getFieldType());
        $this->assertEquals('numeric', BookingHeaderField::Stueck->getFieldType());
        $this->assertEquals('numeric', BookingHeaderField::Gewicht->getFieldType());

        // Datumsfelder
        $this->assertEquals('date', BookingHeaderField::Belegdatum->getFieldType());
        $this->assertEquals('date', BookingHeaderField::Leistungsdatum->getFieldType());
        $this->assertEquals('date', BookingHeaderField::Faelligkeit->getFieldType());
        $this->assertEquals('date', BookingHeaderField::KOSTDatum->getFieldType());
        $this->assertEquals('date', BookingHeaderField::DatumZuordnungSteuerperiode->getFieldType());

        // Dezimalfelder
        $this->assertEquals('decimal', BookingHeaderField::Kurs->getFieldType());
        $this->assertEquals('decimal', BookingHeaderField::Steuersatz->getFieldType());
        $this->assertEquals('decimal', BookingHeaderField::EUSteuer->getFieldType());
        $this->assertEquals('decimal', BookingHeaderField::EUSteuersatzAnzahlung->getFieldType());
        $this->assertEquals('decimal', BookingHeaderField::EUSteuersatzUrsprung->getFieldType());

        // Enum-Felder
        $this->assertEquals('enum', BookingHeaderField::SollHabenKennzeichen->getFieldType());
        $this->assertEquals('enum', BookingHeaderField::Postensperre->getFieldType());
        $this->assertEquals('enum', BookingHeaderField::Zinssperre->getFieldType());

        // String-Felder (default)
        $this->assertEquals('string', BookingHeaderField::Buchungstext->getFieldType());
        $this->assertEquals('string', BookingHeaderField::Belegfeld1->getFieldType());
        $this->assertEquals('string', BookingHeaderField::Konto->getFieldType());
    }

    public function testGetMaxLength(): void {
        // Konten haben max 9 Zeichen
        $this->assertEquals(9, BookingHeaderField::Konto->getMaxLength());
        $this->assertEquals(9, BookingHeaderField::Gegenkonto->getMaxLength());

        // BU-Schlüssel hat max 2 Zeichen
        $this->assertEquals(2, BookingHeaderField::BUSchluessel->getMaxLength());

        // Soll/Haben-Kennzeichen hat max 1 Zeichen
        $this->assertEquals(1, BookingHeaderField::SollHabenKennzeichen->getMaxLength());

        // Währungen haben max 3 Zeichen
        $this->assertEquals(3, BookingHeaderField::WKZUmsatz->getMaxLength());
        $this->assertEquals(3, BookingHeaderField::WKZBasisUmsatz->getMaxLength());

        // Belegfelder haben max 36 Zeichen
        $this->assertEquals(36, BookingHeaderField::Belegfeld1->getMaxLength());
        $this->assertEquals(36, BookingHeaderField::Belegfeld2->getMaxLength());

        // Buchungstext hat max 60 Zeichen
        $this->assertEquals(60, BookingHeaderField::Buchungstext->getMaxLength());

        // Kostenstellen haben max 8 Zeichen
        $this->assertEquals(8, BookingHeaderField::KOST1->getMaxLength());
        $this->assertEquals(8, BookingHeaderField::KOST2->getMaxLength());

        // Felder ohne bekannte Begrenzung
        $this->assertNull(BookingHeaderField::Umsatz->getMaxLength());
        $this->assertNull(BookingHeaderField::ZusatzInfo1->getMaxLength());
    }

    public function testFieldValues(): void {
        // Teste einige wichtige Feldwerte
        $this->assertEquals('Umsatz (ohne Soll/Haben-Kz)', BookingHeaderField::Umsatz->value);
        $this->assertEquals('Soll/Haben-Kennzeichen', BookingHeaderField::SollHabenKennzeichen->value);
        $this->assertEquals('Konto', BookingHeaderField::Konto->value);
        $this->assertEquals('Gegenkonto (ohne BU-Schlüssel)', BookingHeaderField::Gegenkonto->value);
        $this->assertEquals('BU-Schlüssel', BookingHeaderField::BUSchluessel->value);
        $this->assertEquals('Buchungstext', BookingHeaderField::Buchungstext->value);
    }

    public function testZusatzinfoFieldsAreInCorrectOrder(): void {
        $ordered = BookingHeaderField::ordered();

        // ZI-Felder sollten in Spalte 48-87 stehen (Array-Index 47-86)
        $this->assertEquals(BookingHeaderField::ZusatzInfo1, $ordered[47]);
        $this->assertEquals(BookingHeaderField::ZusatzInfoInhalt1, $ordered[48]);
        $this->assertEquals(BookingHeaderField::ZusatzInfo2, $ordered[49]);
        $this->assertEquals(BookingHeaderField::ZusatzInfoInhalt2, $ordered[50]);

        // Letztes ZI-Feld-Paar
        $this->assertEquals(BookingHeaderField::ZusatzInfo20, $ordered[85]);
        $this->assertEquals(BookingHeaderField::ZusatzInfoInhalt20, $ordered[86]);
    }
}
