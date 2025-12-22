<?php
/*
 * Created on   : Sat Dec 14 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingBatchHeaderFieldTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Enums\DATEV\V700;

use CommonToolkit\Enums\DATEV\V700\BookingBatchHeaderField;
use Tests\Contracts\BaseTestCase;

class BookingBatchHeaderFieldTest extends BaseTestCase {
    public function testHasCorrectNumberOfFields(): void {
        $ordered = BookingBatchHeaderField::ordered();
        $this->assertCount(125, $ordered, 'DATEV Buchungsstapel V700 sollte genau 125 Felder haben');
    }

    public function testOrderedFieldsAreComplete(): void {
        $ordered = BookingBatchHeaderField::ordered();

        // Erste 10 Felder (Grunddaten)
        $this->assertEquals(BookingBatchHeaderField::Umsatz, $ordered[0]);
        $this->assertEquals(BookingBatchHeaderField::SollHabenKennzeichen, $ordered[1]);
        $this->assertEquals(BookingBatchHeaderField::WKZUmsatz, $ordered[2]);
        $this->assertEquals(BookingBatchHeaderField::Kurs, $ordered[3]);
        $this->assertEquals(BookingBatchHeaderField::BasisUmsatz, $ordered[4]);
        $this->assertEquals(BookingBatchHeaderField::WKZBasisUmsatz, $ordered[5]);
        $this->assertEquals(BookingBatchHeaderField::Konto, $ordered[6]);
        $this->assertEquals(BookingBatchHeaderField::Gegenkonto, $ordered[7]);
        $this->assertEquals(BookingBatchHeaderField::BUSchluessel, $ordered[8]);
        $this->assertEquals(BookingBatchHeaderField::Belegdatum, $ordered[9]);
    }

    public function testRequiredFields(): void {
        $required = BookingBatchHeaderField::required();

        $this->assertContains(BookingBatchHeaderField::Umsatz, $required);
        $this->assertContains(BookingBatchHeaderField::SollHabenKennzeichen, $required);
        $this->assertContains(BookingBatchHeaderField::Konto, $required);
        $this->assertContains(BookingBatchHeaderField::Gegenkonto, $required);
        $this->assertContains(BookingBatchHeaderField::Belegdatum, $required);
        $this->assertContains(BookingBatchHeaderField::Belegfeld1, $required);
        $this->assertContains(BookingBatchHeaderField::Buchungstext, $required);

        $this->assertCount(7, $required, 'Es sollte genau 7 Pflichtfelder geben');
    }

    public function testIsRequiredMethod(): void {
        $this->assertTrue(BookingBatchHeaderField::Umsatz->isRequired());
        $this->assertTrue(BookingBatchHeaderField::SollHabenKennzeichen->isRequired());
        $this->assertTrue(BookingBatchHeaderField::Konto->isRequired());
        $this->assertTrue(BookingBatchHeaderField::Gegenkonto->isRequired());

        $this->assertFalse(BookingBatchHeaderField::Skonto->isRequired());
        $this->assertFalse(BookingBatchHeaderField::KOST1->isRequired());
        $this->assertFalse(BookingBatchHeaderField::Gewicht->isRequired());
    }

    public function testEuFields(): void {
        $euFields = BookingBatchHeaderField::euFields();

        $this->assertContains(BookingBatchHeaderField::EULandUStID, $euFields);
        $this->assertContains(BookingBatchHeaderField::EUSteuer, $euFields);
        $this->assertContains(BookingBatchHeaderField::EUMitgliedstaatAnzahlung, $euFields);
        $this->assertContains(BookingBatchHeaderField::EUSteuersatzAnzahlung, $euFields);
        $this->assertContains(BookingBatchHeaderField::EUMitgliedstaatUstID, $euFields);
        $this->assertContains(BookingBatchHeaderField::EUSteuersatzUrsprung, $euFields);
    }

    public function testIsEuFieldMethod(): void {
        $this->assertTrue(BookingBatchHeaderField::EULandUStID->isEuField());
        $this->assertTrue(BookingBatchHeaderField::EUSteuer->isEuField());
        $this->assertTrue(BookingBatchHeaderField::EUMitgliedstaatAnzahlung->isEuField());

        $this->assertFalse(BookingBatchHeaderField::Umsatz->isEuField());
        $this->assertFalse(BookingBatchHeaderField::Buchungstext->isEuField());
    }

    public function testSepaFields(): void {
        $sepaFields = BookingBatchHeaderField::sepaFields();

        $this->assertContains(BookingBatchHeaderField::SEPAMandatsreferenz, $sepaFields);
        $this->assertContains(BookingBatchHeaderField::Zahlweise, $sepaFields);
        $this->assertContains(BookingBatchHeaderField::Faelligkeit, $sepaFields);
        $this->assertContains(BookingBatchHeaderField::Geschaeftspartnerbank, $sepaFields);
        $this->assertContains(BookingBatchHeaderField::Skontosperre, $sepaFields);
    }

    public function testIsSepaFieldMethod(): void {
        $this->assertTrue(BookingBatchHeaderField::SEPAMandatsreferenz->isSepaField());
        $this->assertTrue(BookingBatchHeaderField::Zahlweise->isSepaField());
        $this->assertTrue(BookingBatchHeaderField::Faelligkeit->isSepaField());

        $this->assertFalse(BookingBatchHeaderField::Umsatz->isSepaField());
        $this->assertFalse(BookingBatchHeaderField::BUSchluessel->isSepaField());
    }

    public function testCostFields(): void {
        $costFields = BookingBatchHeaderField::costFields();

        $this->assertContains(BookingBatchHeaderField::KOST1, $costFields);
        $this->assertContains(BookingBatchHeaderField::KOST2, $costFields);
        $this->assertContains(BookingBatchHeaderField::KostMenge, $costFields);
        $this->assertContains(BookingBatchHeaderField::Stueck, $costFields);
        $this->assertContains(BookingBatchHeaderField::Gewicht, $costFields);
        $this->assertContains(BookingBatchHeaderField::KOSTDatum, $costFields);
    }

    public function testTaxFields(): void {
        $taxFields = BookingBatchHeaderField::taxFields();

        $this->assertContains(BookingBatchHeaderField::UStSchluessel, $taxFields);
        $this->assertContains(BookingBatchHeaderField::Steuersatz, $taxFields);
        $this->assertContains(BookingBatchHeaderField::EUSteuer, $taxFields);
        $this->assertContains(BookingBatchHeaderField::EUSteuersatzAnzahlung, $taxFields);
        $this->assertContains(BookingBatchHeaderField::EUSteuersatzUrsprung, $taxFields);
        $this->assertContains(BookingBatchHeaderField::Abwkonto, $taxFields);
    }

    public function testAdditionalInfoFields(): void {
        $additionalFields = BookingBatchHeaderField::additionalInfoFields();

        // Sollte 40 Felder haben (20 Art + 20 Inhalt)
        $this->assertCount(40, $additionalFields);

        // Teste erste und letzte Paare
        $this->assertContains(BookingBatchHeaderField::ZusatzInfo1, $additionalFields);
        $this->assertContains(BookingBatchHeaderField::ZusatzInfoInhalt1, $additionalFields);
        $this->assertContains(BookingBatchHeaderField::ZusatzInfo20, $additionalFields);
        $this->assertContains(BookingBatchHeaderField::ZusatzInfoInhalt20, $additionalFields);
    }

    public function testIsAdditionalInfoFieldMethod(): void {
        $this->assertTrue(BookingBatchHeaderField::ZusatzInfo1->isAdditionalInfoField());
        $this->assertTrue(BookingBatchHeaderField::ZusatzInfoInhalt1->isAdditionalInfoField());
        $this->assertTrue(BookingBatchHeaderField::ZusatzInfo10->isAdditionalInfoField());
        $this->assertTrue(BookingBatchHeaderField::ZusatzInfo20->isAdditionalInfoField());

        $this->assertFalse(BookingBatchHeaderField::Umsatz->isAdditionalInfoField());
        $this->assertFalse(BookingBatchHeaderField::BelegInfoArt1->isAdditionalInfoField());
    }

    public function testDocumentInfoFields(): void {
        $documentFields = BookingBatchHeaderField::documentInfoFields();

        // Sollte 16 Felder haben (8 Art + 8 Inhalt)
        $this->assertCount(16, $documentFields);

        $this->assertContains(BookingBatchHeaderField::BelegInfoArt1, $documentFields);
        $this->assertContains(BookingBatchHeaderField::BelegInfoInhalt1, $documentFields);
        $this->assertContains(BookingBatchHeaderField::BelegInfoArt8, $documentFields);
        $this->assertContains(BookingBatchHeaderField::BelegInfoInhalt8, $documentFields);
    }

    public function testIsDocumentInfoFieldMethod(): void {
        $this->assertTrue(BookingBatchHeaderField::BelegInfoArt1->isDocumentInfoField());
        $this->assertTrue(BookingBatchHeaderField::BelegInfoInhalt1->isDocumentInfoField());
        $this->assertTrue(BookingBatchHeaderField::BelegInfoArt8->isDocumentInfoField());

        $this->assertFalse(BookingBatchHeaderField::Umsatz->isDocumentInfoField());
        $this->assertFalse(BookingBatchHeaderField::ZusatzInfo1->isDocumentInfoField());
    }

    public function testGetFieldType(): void {
        // Numerische Felder
        $this->assertEquals('numeric', BookingBatchHeaderField::Umsatz->getFieldType());
        $this->assertEquals('numeric', BookingBatchHeaderField::BasisUmsatz->getFieldType());
        $this->assertEquals('numeric', BookingBatchHeaderField::Skonto->getFieldType());
        $this->assertEquals('numeric', BookingBatchHeaderField::KostMenge->getFieldType());
        $this->assertEquals('numeric', BookingBatchHeaderField::Stueck->getFieldType());
        $this->assertEquals('numeric', BookingBatchHeaderField::Gewicht->getFieldType());

        // Datumsfelder
        $this->assertEquals('date', BookingBatchHeaderField::Belegdatum->getFieldType());
        $this->assertEquals('date', BookingBatchHeaderField::Leistungsdatum->getFieldType());
        $this->assertEquals('date', BookingBatchHeaderField::Faelligkeit->getFieldType());
        $this->assertEquals('date', BookingBatchHeaderField::KOSTDatum->getFieldType());
        $this->assertEquals('date', BookingBatchHeaderField::DatumZuordnungSteuerperiode->getFieldType());

        // Dezimalfelder
        $this->assertEquals('decimal', BookingBatchHeaderField::Kurs->getFieldType());
        $this->assertEquals('decimal', BookingBatchHeaderField::Steuersatz->getFieldType());
        $this->assertEquals('decimal', BookingBatchHeaderField::EUSteuer->getFieldType());
        $this->assertEquals('decimal', BookingBatchHeaderField::EUSteuersatzAnzahlung->getFieldType());
        $this->assertEquals('decimal', BookingBatchHeaderField::EUSteuersatzUrsprung->getFieldType());

        // Enum-Felder
        $this->assertEquals('enum', BookingBatchHeaderField::SollHabenKennzeichen->getFieldType());
        $this->assertEquals('enum', BookingBatchHeaderField::Postensperre->getFieldType());
        $this->assertEquals('enum', BookingBatchHeaderField::Zinssperre->getFieldType());

        // String-Felder (default)
        $this->assertEquals('string', BookingBatchHeaderField::Buchungstext->getFieldType());
        $this->assertEquals('string', BookingBatchHeaderField::Belegfeld1->getFieldType());
        $this->assertEquals('string', BookingBatchHeaderField::Konto->getFieldType());
    }

    public function testGetMaxLength(): void {
        // Konten haben max 9 Zeichen
        $this->assertEquals(9, BookingBatchHeaderField::Konto->getMaxLength());
        $this->assertEquals(9, BookingBatchHeaderField::Gegenkonto->getMaxLength());

        // BU-Schlüssel hat max 2 Zeichen
        $this->assertEquals(2, BookingBatchHeaderField::BUSchluessel->getMaxLength());

        // Soll/Haben-Kennzeichen hat max 1 Zeichen
        $this->assertEquals(1, BookingBatchHeaderField::SollHabenKennzeichen->getMaxLength());

        // Währungen haben max 3 Zeichen
        $this->assertEquals(3, BookingBatchHeaderField::WKZUmsatz->getMaxLength());
        $this->assertEquals(3, BookingBatchHeaderField::WKZBasisUmsatz->getMaxLength());

        // Belegfelder haben max 36 Zeichen
        $this->assertEquals(36, BookingBatchHeaderField::Belegfeld1->getMaxLength());
        $this->assertEquals(36, BookingBatchHeaderField::Belegfeld2->getMaxLength());

        // Buchungstext hat max 60 Zeichen
        $this->assertEquals(60, BookingBatchHeaderField::Buchungstext->getMaxLength());

        // Kostenstellen haben max 8 Zeichen
        $this->assertEquals(8, BookingBatchHeaderField::KOST1->getMaxLength());
        $this->assertEquals(8, BookingBatchHeaderField::KOST2->getMaxLength());

        // Felder ohne bekannte Begrenzung
        $this->assertNull(BookingBatchHeaderField::Umsatz->getMaxLength());
        $this->assertNull(BookingBatchHeaderField::ZusatzInfo1->getMaxLength());
    }

    public function testFieldValues(): void {
        // Teste einige wichtige Feldwerte
        $this->assertEquals('Umsatz (ohne Soll/Haben-Kz)', BookingBatchHeaderField::Umsatz->value);
        $this->assertEquals('Soll/Haben-Kennzeichen', BookingBatchHeaderField::SollHabenKennzeichen->value);
        $this->assertEquals('Konto', BookingBatchHeaderField::Konto->value);
        $this->assertEquals('Gegenkonto (ohne BU-Schlüssel)', BookingBatchHeaderField::Gegenkonto->value);
        $this->assertEquals('BU-Schlüssel', BookingBatchHeaderField::BUSchluessel->value);
        $this->assertEquals('Buchungstext', BookingBatchHeaderField::Buchungstext->value);
    }

    public function testZusatzinfoFieldsAreInCorrectOrder(): void {
        $ordered = BookingBatchHeaderField::ordered();

        // ZI-Felder sollten in Spalte 48-87 stehen (Array-Index 47-86)
        $this->assertEquals(BookingBatchHeaderField::ZusatzInfo1, $ordered[47]);
        $this->assertEquals(BookingBatchHeaderField::ZusatzInfoInhalt1, $ordered[48]);
        $this->assertEquals(BookingBatchHeaderField::ZusatzInfo2, $ordered[49]);
        $this->assertEquals(BookingBatchHeaderField::ZusatzInfoInhalt2, $ordered[50]);

        // Letztes ZI-Feld-Paar
        $this->assertEquals(BookingBatchHeaderField::ZusatzInfo20, $ordered[85]);
        $this->assertEquals(BookingBatchHeaderField::ZusatzInfoInhalt20, $ordered[86]);
    }
}
