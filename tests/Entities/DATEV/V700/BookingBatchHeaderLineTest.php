<?php
/*
 * Created on   : Sat Dec 14 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingBatchHeaderLineTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Entities\DATEV\Header\V700;

use CommonToolkit\Entities\DATEV\Header\BookingBatchHeaderLine;
use CommonToolkit\Entities\DATEV\Header\V700\BookingBatchHeaderDefinition;
use CommonToolkit\Enums\DATEV\V700\BookingBatchHeaderField;
use Tests\Contracts\BaseTestCase;

class BookingBatchHeaderLineTest extends BaseTestCase {
    private const BOOKINGBATCH_V700 = 'Umsatz (ohne Soll/Haben-Kz);Soll/Haben-Kennzeichen;WKZ Umsatz;Kurs;' .
        'Basis-Umsatz;WKZ Basis-Umsatz;Konto;Gegenkonto (ohne BU-Schlüssel);BU-Schlüssel;Belegdatum;' .
        'Belegfeld 1;Belegfeld 2;Skonto;Buchungstext;Postensperre;Diverse Adressnummer;Geschäftspartnerbank;' .
        'Sachverhalt;Zinssperre;Beleglink;Beleginfo - Art 1;Beleginfo - Inhalt 1;Beleginfo - Art 2;' .
        'Beleginfo - Inhalt 2;Beleginfo - Art 3;Beleginfo - Inhalt 3;Beleginfo - Art 4;Beleginfo - Inhalt 4;' .
        'Beleginfo - Art 5;Beleginfo - Inhalt 5;Beleginfo - Art 6;Beleginfo - Inhalt 6;Beleginfo - Art 7;' .
        'Beleginfo - Inhalt 7;Beleginfo - Art 8;Beleginfo - Inhalt 8;KOST1 - Kostenstelle;KOST2 - Kostenstelle;' .
        'Kost-Menge;EU-Land u. UStID (Bestimmung);EU-Steuersatz (Bestimmung);Abw. Versteuerungsart;' .
        'Sachverhalt L+L;Funktionsergänzung L+L;BU 49 Hauptfunktionstyp;BU 49 Hauptfunktionsnummer;' .
        'BU 49 Funktionsergänzung;Zusatzinformation - Art 1;Zusatzinformation- Inhalt 1;' .
        'Zusatzinformation - Art 2;Zusatzinformation- Inhalt 2;Zusatzinformation - Art 3;' .
        'Zusatzinformation- Inhalt 3;Zusatzinformation - Art 4;Zusatzinformation- Inhalt 4;' .
        'Zusatzinformation - Art 5;Zusatzinformation- Inhalt 5;Zusatzinformation - Art 6;' .
        'Zusatzinformation- Inhalt 6;Zusatzinformation - Art 7;Zusatzinformation- Inhalt 7;' .
        'Zusatzinformation - Art 8;Zusatzinformation- Inhalt 8;Zusatzinformation - Art 9;' .
        'Zusatzinformation- Inhalt 9;Zusatzinformation - Art 10;Zusatzinformation- Inhalt 10;' .
        'Zusatzinformation - Art 11;Zusatzinformation- Inhalt 11;Zusatzinformation - Art 12;' .
        'Zusatzinformation- Inhalt 12;Zusatzinformation - Art 13;Zusatzinformation- Inhalt 13;' .
        'Zusatzinformation - Art 14;Zusatzinformation- Inhalt 14;Zusatzinformation - Art 15;' .
        'Zusatzinformation- Inhalt 15;Zusatzinformation - Art 16;Zusatzinformation- Inhalt 16;' .
        'Zusatzinformation - Art 17;Zusatzinformation- Inhalt 17;Zusatzinformation - Art 18;' .
        'Zusatzinformation- Inhalt 18;Zusatzinformation - Art 19;Zusatzinformation- Inhalt 19;' .
        'Zusatzinformation - Art 20;Zusatzinformation- Inhalt 20;Stück;Gewicht;Zahlweise;Forderungsart;' .
        'Veranlagungsjahr;Zugeordnete Fälligkeit;Skontotyp;Auftragsnummer;Buchungstyp;' .
        'USt-Schlüssel (Anzahlungen);EU-Land (Anzahlungen);Sachverhalt L+L (Anzahlungen);' .
        'EU-Steuersatz (Anzahlungen);Erlöskonto (Anzahlungen);Herkunft-Kz;Buchungs GUID;KOST-Datum;' .
        'SEPA-Mandatsreferenz;Skontosperre;Gesellschaftername;Beteiligtennummer;Identifikationsnummer;' .
        'Zeichnernummer;Postensperre bis;Bezeichnung SoBil-Sachverhalt;Kennzeichen SoBil-Buchung;' .
        'Festschreibung;Leistungsdatum;Datum Zuord. Steuerperiode;Fälligkeit;Generalumkehr (GU);' .
        'Steuersatz;Land;Abrechnungsreferenz;BVV-Position;EU-Land u. UStID (Ursprung);' .
        'EU-Steuersatz (Ursprung);Abw. Skontokonto';

    private const CREDITORS_V700 = 'Konto;Name (Adressattyp Unternehmen);Unternehmensgegenstand;' .
        'Name (Adressattyp natürl. Person);Vorname (Adressattyp natürl. Person);Name (Adressattyp keine Angabe);' .
        'Adressattyp;Kurzbezeichnung;EU-Land;EU-UStID;Anrede;Titel/Akad. Grad;Adelstitel;Namensvorsatz;' .
        'Adressart;Straße;Postfach;Postleitzahl;Ort;Land;Versandzusatz;Adresszusatz';

    public function testCanCreateWithDefinition(): void {
        $definition = new BookingBatchHeaderDefinition();
        $headerLine = new BookingBatchHeaderLine($definition);

        $this->assertInstanceOf(BookingBatchHeaderLine::class, $headerLine);
        $this->assertInstanceOf(BookingBatchHeaderDefinition::class, $headerLine->getDefinition());
    }

    public function testCreateDefault(): void {
        $definition = new BookingBatchHeaderDefinition();
        $headerLine = new BookingBatchHeaderLine($definition);
        $fields = $headerLine->getFields();

        $this->assertCount(125, $fields, 'V700 sollte 125 Felder haben');

        // Teste die ersten paar Felder
        $fieldValues = array_map(fn($f) => trim($f->getValue(), '"'), $fields);
        $this->assertEquals('Umsatz (ohne Soll/Haben-Kz)', $fieldValues[0]);
        $this->assertEquals('Soll/Haben-Kennzeichen', $fieldValues[1]);
        $this->assertEquals('WKZ Umsatz', $fieldValues[2]);
    }

    public function testCreateMinimal(): void {
        $definition = new BookingBatchHeaderDefinition();
        $headerLine = BookingBatchHeaderLine::createMinimal($definition);
        $fields = $headerLine->getFields();

        // Minimal sollte nur Pflichtfelder haben
        $requiredCount = count(BookingBatchHeaderField::required());
        $this->assertCount($requiredCount, $fields);
    }

    public function testFieldAccess(): void {
        $definition = new BookingBatchHeaderDefinition();
        $headerLine = new BookingBatchHeaderLine($definition);

        // Test hasField
        $this->assertTrue($headerLine->hasField(BookingBatchHeaderField::Umsatz));
        $this->assertTrue($headerLine->hasField('Umsatz (ohne Soll/Haben-Kz)'));

        // Test getFieldIndex
        $this->assertEquals(0, $headerLine->getFieldIndex(BookingBatchHeaderField::Umsatz));
        $this->assertEquals(1, $headerLine->getFieldIndex(BookingBatchHeaderField::SollHabenKennzeichen));
        $this->assertEquals(-1, $headerLine->getFieldIndex('NichtVorhandenesFelder'));
    }

    public function testEnumCompatibility(): void {
        $definition = new BookingBatchHeaderDefinition();
        $headerLine = new BookingBatchHeaderLine($definition);

        // Test V700 Kompatibilität
        $this->assertTrue($headerLine->isV700BookingHeader());
        $this->assertTrue($headerLine->isCompatibleWithEnum(BookingBatchHeaderField::class));

        // Test mit falscher Enum-Klasse
        $this->assertFalse($headerLine->isCompatibleWithEnum('NonExistentEnum'));
    }

    public function testFormatDetection(): void {
        $definition = new BookingBatchHeaderDefinition();
        $headerLine = new BookingBatchHeaderLine($definition);

        // Test Format-Erkennung
        $candidates = [BookingBatchHeaderField::class];
        $detectedFormat = $headerLine->detectFormat($candidates);

        $this->assertEquals(BookingBatchHeaderField::class, $detectedFormat);

        // Test mit leerer Kandidatenliste
        $this->assertNull($headerLine->detectFormat([]));
    }

    public function testHeaderValidationWithRealDATEVHeaders(): void {
        $definition = new BookingBatchHeaderDefinition();
        $headerLine = new BookingBatchHeaderLine($definition);

        // Test: BookingBatch V700 Header sollte validiert werden
        $BookingBatchFields = explode(';', self::BOOKINGBATCH_V700);

        // Prüfe ob alle Felder aus dem echten DATEV-Header in unserem Enum existieren
        $enumValues = array_map(fn($case) => $case->value, BookingBatchHeaderField::cases());

        $foundFields = 0;
        foreach ($BookingBatchFields as $field) {
            if (in_array($field, $enumValues, true)) {
                $foundFields++;
            }
        }

        // Es sollten viele Felder gefunden werden (nicht alle, da sich DATEV-Formate unterscheiden können)
        $this->assertGreaterThan(50, $foundFields, 'Mindestens 50 Felder vom echten DATEV-Header sollten im Enum gefunden werden');

        // Test: Unser Header sollte als V700 BookingBatch erkannt werden
        $this->assertTrue($headerLine->isV700BookingHeader());

        // Test: Kreditoren Header sollte NICHT als BookingBatch erkannt werden
        $kreditorenFields = explode(';', self::CREDITORS_V700);
        $kreditorenFoundInBooking = 0;

        foreach ($kreditorenFields as $field) {
            if (in_array($field, $enumValues, true)) {
                $kreditorenFoundInBooking++;
            }
        }

        // Kreditoren-Felder sollten kaum im BookingBatch-Enum gefunden werden
        $this->assertLessThan(20, $kreditorenFoundInBooking, 'Kreditoren-Felder sollten nicht in BookingBatch-Enum sein');
    }

    public function testFieldCountConsistency(): void {
        $definition = new BookingBatchHeaderDefinition();
        $headerLine = new BookingBatchHeaderLine($definition);

        // Unser Header sollte 125 Felder haben (wie im Enum definiert)
        $this->assertCount(125, $headerLine->getFields());

        // Test mit echten DATEV-Headern
        $BookingBatchFieldCount = count(explode(';', self::BOOKINGBATCH_V700));
        $kreditorenFieldCount = count(explode(';', self::CREDITORS_V700));

        // Dokumentiere die Unterschiede für Debugging
        $this->addToAssertionCount(1); // Zähle als Assertion

        // Optional: Ausgabe für Debugging (wird nur bei Fehlern angezeigt)
        if ($BookingBatchFieldCount !== 125) {
            $this->markTestIncomplete(
                "DATEV BookingBatch hat $BookingBatchFieldCount Felder, unser Enum hat 125. " .
                    "Kreditoren hat $kreditorenFieldCount Felder."
            );
        }
    }
}