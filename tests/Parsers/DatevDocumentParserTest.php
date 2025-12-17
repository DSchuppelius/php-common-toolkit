<?php
/*
 * Created on   : Mon Dec 15 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DatevDocumentParserTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Parsers;

use CommonToolkit\Entities\DATEV\Document;
use CommonToolkit\Entities\DATEV\Header\BookingBatchHeaderLine;
use CommonToolkit\Parsers\DatevDocumentParser;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

class DatevDocumentParserTest extends BaseTestCase {
    public function testAnalyzeFormatBookingBatchV700(): void {
        $csvContent = '"EXTF";700;21;"Buchungsstapel";13;20240130140440439;;"RE";"";"";29098;55003;20240101;4;20240101;20240831;"Buchungsstapel";"WD";1;0;0;"EUR";;"";;;"03";;;"";""' . "\n" .
            'Umsatz (ohne Soll/Haben-Kz);Soll/Haben-Kennzeichen;WKZ Umsatz;Kurs;Basis-Umsatz;WKZ Basis-Umsatz;Konto;Gegenkonto (ohne BU-Schlüssel);BU-Schlüssel;Belegdatum;Belegfeld 1;Belegfeld 2;Skonto;Buchungstext;Postensperre;Diverse Adressnummer;Geschäftspartnerbank;Sachverhalt;Zinssperre;Beleglink;Beleginfo - Art 1;Beleginfo - Inhalt 1;Beleginfo - Art 2;Beleginfo - Inhalt 2;Beleginfo - Art 3;Beleginfo - Inhalt 3;Beleginfo - Art 4;Beleginfo - Inhalt 4;Beleginfo - Art 5;Beleginfo - Inhalt 5;Beleginfo - Art 6;Beleginfo - Inhalt 6;Beleginfo - Art 7;Beleginfo - Inhalt 7;Beleginfo - Art 8;Beleginfo - Inhalt 8;KOST1 - Kostenstelle;KOST2 - Kostenstelle;Kost-Menge;EU-Land u. UStID (Bestimmung);EU-Steuersatz (Bestimmung);Abw. Versteuerungsart;Sachverhalt L+L;Funktionsergänzung L+L;BU 49 Hauptfunktionstyp;BU 49 Hauptfunktionsnummer;BU 49 Funktionsergänzung;Zusatzinformation - Art 1;Zusatzinformation- Inhalt 1;Zusatzinformation - Art 2;Zusatzinformation- Inhalt 2;Zusatzinformation - Art 3;Zusatzinformation- Inhalt 3;Zusatzinformation - Art 4;Zusatzinformation- Inhalt 4;Zusatzinformation - Art 5;Zusatzinformation- Inhalt 5;Zusatzinformation - Art 6;Zusatzinformation- Inhalt 6;Zusatzinformation - Art 7;Zusatzinformation- Inhalt 7;Zusatzinformation - Art 8;Zusatzinformation- Inhalt 8;Zusatzinformation - Art 9;Zusatzinformation- Inhalt 9;Zusatzinformation - Art 10;Zusatzinformation- Inhalt 10;Zusatzinformation - Art 11;Zusatzinformation- Inhalt 11;Zusatzinformation - Art 12;Zusatzinformation- Inhalt 12;Zusatzinformation - Art 13;Zusatzinformation- Inhalt 13;Zusatzinformation - Art 14;Zusatzinformation- Inhalt 14;Zusatzinformation - Art 15;Zusatzinformation- Inhalt 15;Zusatzinformation - Art 16;Zusatzinformation- Inhalt 16;Zusatzinformation - Art 17;Zusatzinformation- Inhalt 17;Zusatzinformation - Art 18;Zusatzinformation- Inhalt 18;Zusatzinformation - Art 19;Zusatzinformation- Inhalt 19;Zusatzinformation - Art 20;Zusatzinformation- Inhalt 20;Stück;Gewicht;Zahlweise;Forderungsart;Veranlagungsjahr;Zugeordnete Fälligkeit;Skontotyp;Auftragsnummer;Buchungstyp;USt-Schlüssel (Anzahlungen);EU-Land (Anzahlungen);Sachverhalt L+L (Anzahlungen);EU-Steuersatz (Anzahlungen);Erlöskonto (Anzahlungen);Herkunft-Kz;Buchungs GUID;KOST-Datum;SEPA-Mandatsreferenz;Skontosperre;Gesellschaftername;Beteiligtennummer;Identifikationsnummer;Zeichnernummer;Postensperre bis;Bezeichnung SoBil-Sachverhalt;Kennzeichen SoBil-Buchung;Festschreibung;Leistungsdatum;Datum Zuord. Steuerperiode;Fälligkeit;Generalumkehr (GU);Steuersatz;Land;Abrechnungsreferenz;BVV-Position;EU-Land u. UStID (Ursprung);EU-Steuersatz (Ursprung);Abw. Skontokonto' . "\n" .
            '100,18;"S";"";;;"";48400;8401;"";3101;"";"";;"Test Anzahlung";;"";1;;"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"50";"";;"";;"";;"";;;;"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";;;;"";2012;;1;"Projekt 4711";"AG";3;"";;;8070;"WK";"";;"";;"";;"";"";;"";;0;;;;"";;"";"";;"";;';

        $analysis = DatevDocumentParser::analyzeFormat($csvContent);

        $this->assertEquals('Buchungsstapel', $analysis['format_type']);
        $this->assertEquals(700, $analysis['version']);
        $this->assertTrue($analysis['supported']);
        $this->assertEquals(3, $analysis['line_count']);
        $this->assertArrayHasKey('format_info', $analysis);
    }

    public function testAnalyzeFormatDebitorsCreditors(): void {
        $csvContent = '"EXTF";700;16;"Debitoren/Kreditoren";5;20240130140659583;;"RE";"";"";29098;55003;20240101;4;;;"";"";;;;"";;"";;;"03";;;"";""' . "\n" .
            'Konto;Name (Adressattyp Unternehmen);Unternehmensgegenstand;Name (Adressattyp nat�rl. Person);Vorname (Adressattyp nat�rl. Person);Name (Adressattyp keine Angabe);Adressattyp;Kurzbezeichnung;EU-Land;EU-UStID;Anrede;Titel/Akad. Grad;Adelstitel;Namensvorsatz;Adressart;Stra�e;Postfach;Postleitzahl;Ort;Land;Versandzusatz;Adresszusatz;Abweichende Anrede;Abw. Zustellbezeichnung 1;Abw. Zustellbezeichnung 2;Kennz. Korrespondenzadresse;Adresse G�ltig von;Adresse G�ltig bis;Telefon;Bemerkung (Telefon);Telefon GL;Bemerkung (Telefon GL);E-Mail;Bemerkung (E-Mail);Internet;Bemerkung (Internet);Fax;Bemerkung (Fax);Sonstige;Bemerkung (Sonstige);Bankleitzahl 1;Bankbezeichnung 1;Bank-Kontonummer 1;L�nderkennzeichen 1;IBAN-Nr. 1;Leerfeld;SWIFT-Code 1;Abw. Kontoinhaber 1;Kennz. Hauptbankverb. 1;Bankverb 1 G�ltig von;Bankverb 1 G�ltig bis;Bankleitzahl 2;Bankbezeichnung 2;Bank-Kontonummer 2;L�nderkennzeichen 2;IBAN-Nr. 2;Leerfeld;SWIFT-Code 2;Abw. Kontoinhaber 2;Kennz. Hauptbankverb. 2;Bankverb 2 G�ltig von;Bankverb 2 G�ltig bis;Bankleitzahl 3;Bankbezeichnung 3;Bank-Kontonummer 3;L�nderkennzeichen 3;IBAN-Nr. 3;Leerfeld;SWIFT-Code 3;Abw. Kontoinhaber 3;Kennz. Hauptbankverb. 3;Bankverb 3 G�ltig von;Bankverb 3 G�ltig bis;Bankleitzahl 4;Bankbezeichnung 4;Bank-Kontonummer 4;L�nderkennzeichen 4;IBAN-Nr. 4;Leerfeld;SWIFT-Code 4;Abw. Kontoinhaber 4;Kennz. Hauptbankverb. 4;Bankverb 4 G�ltig von;Bankverb 4 G�ltig bis;Bankleitzahl 5;Bankbezeichnung 5;Bank-Kontonummer 5;L�nderkennzeichen 5;IBAN-Nr. 5;Leerfeld;SWIFT-Code 5;Abw. Kontoinhaber 5;Kennz. Hauptbankverb. 5;Bankverb 5 G�ltig von;Bankverb 5 G�ltig bis;Leerfeld;Briefanrede;Gru�formel;Kunden-/Lief.-Nr.;Steuernummer;Sprache;Ansprechpartner;Vertreter;Sachbearbeiter;Diverse-Konto;Ausgabeziel;W�hrungssteuerung;Kreditlimit (Debitor);Zahlungsbedingung;F�lligkeit in Tagen (Debitor);Skonto in Prozent (Debitor);Kreditoren-Ziel 1 Tg.;Kreditoren-Skonto 1 %;Kreditoren-Ziel 2 Tg.;Kreditoren-Skonto 2 %;Kreditoren-Ziel 3 Brutto Tg.;Kreditoren-Ziel 4 Tg.;Kreditoren-Skonto 4 %;Kreditoren-Ziel 5 Tg.;Kreditoren-Skonto 5 %;Mahnung;Kontoauszug;Mahntext 1;Mahntext 2;Mahntext 3;Kontoauszugstext;Mahnlimit Betrag;Mahnlimit %;Zinsberechnung;Mahnzinssatz 1;Mahnzinssatz 2;Mahnzinssatz 3;Lastschrift;Leerfeld;Mandantenbank;Zahlungstr�ger;Indiv. Feld 1;Indiv. Feld 2;Indiv. Feld 3;Indiv. Feld 4;Indiv. Feld 5;Indiv. Feld 6;Indiv. Feld 7;Indiv. Feld 8;Indiv. Feld 9;Indiv. Feld 10;Indiv. Feld 11;Indiv. Feld 12;Indiv. Feld 13;Indiv. Feld 14;Indiv. Feld 15;Abweichende Anrede (Rechnungsadresse);Adressart (Rechnungsadresse);Stra�e (Rechnungsadresse);Postfach (Rechnungsadresse);Postleitzahl (Rechnungsadresse);Ort (Rechnungsadresse);Land (Rechnungsadresse);Versandzusatz (Rechnungsadresse);Adresszusatz (Rechnungsadresse);Abw. Zustellbezeichnung 1 (Rechnungsadresse);Abw. Zustellbezeichnung 2 (Rechnungsadresse);Adresse G�ltig von (Rechnungsadresse);Adresse G�ltig bis (Rechnungsadresse);Bankleitzahl 6;Bankbezeichnung 6;Bank-Kontonummer 6;L�nderkennzeichen 6;IBAN-Nr. 6;Leerfeld;SWIFT-Code 6;Abw. Kontoinhaber 6;Kennz. Hauptbankverb. 6;Bankverb 6 G�ltig von;Bankverb 6 G�ltig bis;Bankleitzahl 7;Bankbezeichnung 7;Bank-Kontonummer 7;L�nderkennzeichen 7;IBAN-Nr. 7;Leerfeld;SWIFT-Code 7;Abw. Kontoinhaber 7;Kennz. Hauptbankverb. 7;Bankverb 7 G�ltig von;Bankverb 7 G�ltig bis;Bankleitzahl 8;Bankbezeichnung 8;Bank-Kontonummer 8;L�nderkennzeichen 8;IBAN-Nr. 8;Leerfeld;SWIFT-Code 8;Abw. Kontoinhaber 8;Kennz. Hauptbankverb. 8;Bankverb 8 G�ltig von;Bankverb 8 G�ltig bis;Bankleitzahl 9;Bankbezeichnung 9;Bank-Kontonummer 9;L�nderkennzeichen 9;IBAN-Nr. 9;Leerfeld;SWIFT-Code 9;Abw. Kontoinhaber 9;Kennz. Hauptbankverb. 9;Bankverb 9 G�ltig von;Bankverb 9 G�ltig bis;Bankleitzahl 10;Bankbezeichnung 10;Bank-Kontonummer 10;L�nderkennzeichen 10;IBAN-Nr. 10;Leerfeld;SWIFT-Code 10;Abw. Kontoinhaber 10;Kennz. Hauptbankverb. 10;Bankverb 10 G�ltig von;Bankverb 10 G�ltig bis;Nummer Fremdsystem;Insolvent;SEPA-Mandatsreferenz 1;SEPA-Mandatsreferenz 2;SEPA-Mandatsreferenz 3;SEPA-Mandatsreferenz 4;SEPA-Mandatsreferenz 5;SEPA-Mandatsreferenz 6;SEPA-Mandatsreferenz 7;SEPA-Mandatsreferenz 8;SEPA-Mandatsreferenz 9;SEPA-Mandatsreferenz 10;Verkn�pftes OPOS-Konto;Mahnsperre bis;Lastschriftsperre bis;Zahlungssperre bis;Geb�hrenberechnung;Mahngeb�hr 1;Mahngeb�hr 2;Mahngeb�hr 3;Pauschalenberechnung;Verzugspauschale 1;Verzugspauschale 2;Verzugspauschale 3;Alternativer Suchname;Status;Anschrift manuell ge�ndert (Korrespondenzadresse);Anschrift individuell (Korrespondenzadresse);Anschrift manuell ge�ndert (Rechnungsadresse);Anschrift individuell (Rechnungsadresse);Fristberechnung bei Debitor;Mahnfrist 1;Mahnfrist 2;Mahnfrist 3;Letzte Frist' . "\n" .
            '10000;"M�bel Testgruber";"Schreinerei";"";"";"";"2";"M�bel Testgrube";"DE";"133546770";"Firma";"";"";"";"STR";"Nelkenteststra�e 125";"";"90482";"N�rnberg";"";"";"";"Firma";"";"";1;01012012;;"";"";"";"";"";"";"";"";"";"";"";"";"50090500";"Sparda-Bank Hessen";"2345678";"DE";"DE49100102220002222222";"";"GENODEF1S12";"Herr Testm�ller";"1";01012012;01012022;"50090500";"Sparda-Bank Hessen";"2345678";"DE";"DE49100102220002222222";"";"GENODEF1S12";"Herr Testm�ller";"0";01012012;01012020;"50090500";"Sparda-Bank Hessen";"2345678";"DE";"DE49100102220002222222";"";"GENODEF1S12";"Herr Testm�ller";"0";01012012;01012020;"50090500";"Sparda-Bank Hessen";"2345678";"DE";"DE49100102220002222222";"";"GENODEF1S12";"Herr Testm�ller";"0";01012012;01012020;"50090500";"Sparda-Bank Hessen";"2345678";"DE";"DE49100102220002222222";"";"GENODEF1S12";"Herr Testm�ller";"0";01012012;01012020;"";"Sehr geehrte Frau";"Hallo";"KDN 12345";"DE776655";"1";"Frau Huber";"Herr Schmid";"Frau Tester";;;"";0;0;0;0,00;0;0,00;0;0,00;0;0;0,00;0;0,00;7;;;;;;23,30;20,25;1;10,50;11,11;12,12;"7";"1";1;"9";"ind. Feld";"";"";"";"";"";"";"";"";"";"";"individuelle Beschriftung";"";"";"";"";"";"";"";"";"";"";"";"";"";"";;;"";"";"";"";"";"";"";"";"";;;"";"";"";"";"";"";"";"";"";;;"";"";"";"";"";"";"";"";"";;;"";"";"";"";"";"";"";"";"";;;"";"";"";"";"";"";"";"";"";;;"";0;"1234-AB-56787";"";"";"";"";"";"";"";"";"778259637";;03122018;02122018;01122018;1;5,1;5,2;5,3;1;0,9;0,2;0,5;"";1;0;"";1;"";;;;;';

        $analysis = DatevDocumentParser::analyzeFormat($csvContent);

        $this->assertArrayHasKey('format_type', $analysis, 'Format sollte erkannt werden');
        $this->assertEquals('Debitoren/Kreditoren', $analysis['format_type']);
        $this->assertEquals(700, $analysis['version']);
        $this->assertTrue($analysis['supported']);
    }

    public function testAnalyzeFormatInvalid(): void {
        $csvContent = 'Invalid CSV Content';

        $analysis = DatevDocumentParser::analyzeFormat($csvContent);

        $this->assertArrayHasKey('error', $analysis);
        $this->assertStringContainsString('Ungültiger DATEV MetaHeader', $analysis['error']);
    }

    public function testParseBookingBatchV700FromRealFile(): void {
        $sampleFile = __DIR__ . '/../../.samples/DATEV/EXTF_Buchungsstapel.csv';
        $this->assertFileExists($sampleFile, 'Sample file für Buchungsstapel muss existieren');

        $csvContent = file_get_contents($sampleFile);
        $this->assertNotEmpty($csvContent, 'Sample file darf nicht leer sein');

        $document = DatevDocumentParser::fromString($csvContent);

        $this->assertInstanceOf(Document::class, $document);
        $this->assertTrue($document->hasHeader());
        $this->assertInstanceOf(BookingBatchHeaderLine::class, $document->getHeader());

        // Prüfe dass FieldHeader die erwartete Anzahl von Feldern hat (125 bei V700)
        $header = $document->getHeader();
        $fields = $header->getFields();
        $this->assertCount(125, $fields, 'DATEV V700 Buchungsstapel muss 125 Felder haben');

        // Prüfe spezifische Header-Felder
        $this->assertTrue($header->hasField('Umsatz (ohne Soll/Haben-Kz)'));
        $this->assertTrue($header->hasField('Soll/Haben-Kennzeichen'));
        $this->assertTrue($header->hasField('WKZ Umsatz'));
        $this->assertTrue($header->hasField('Konto'));
        $this->assertTrue($header->hasField('Gegenkonto (ohne BU-Schlüssel)'));

        // Prüfe dass Datenzeilen existieren
        $this->assertGreaterThan(0, $document->countRows(), 'Es sollten Datensätze vorhanden sein');
    }

    public function testParseBookingBatchV700WithEmptyLines(): void {
        $csvContent = '"EXTF";700;21;"Buchungsstapel";13;20240130140440439;;"RE";"";"";29098;55003;20240101;4;20240101;20240831;"Buchungsstapel";"WD";1;0;0;"EUR";;"";;;"03";;;"";""' . "\n" .
            'Umsatz (ohne Soll/Haben-Kz);Soll/Haben-Kennzeichen;WKZ Umsatz;Kurs;Basis-Umsatz;WKZ Basis-Umsatz;Konto;Gegenkonto (ohne BU-Schlüssel);BU-Schlüssel;Belegdatum;Belegfeld 1;Belegfeld 2;Skonto;Buchungstext;Postensperre;Diverse Adressnummer;Geschäftspartnerbank;Sachverhalt;Zinssperre;Beleglink;Beleginfo - Art 1;Beleginfo - Inhalt 1;Beleginfo - Art 2;Beleginfo - Inhalt 2;Beleginfo - Art 3;Beleginfo - Inhalt 3;Beleginfo - Art 4;Beleginfo - Inhalt 4;Beleginfo - Art 5;Beleginfo - Inhalt 5;Beleginfo - Art 6;Beleginfo - Inhalt 6;Beleginfo - Art 7;Beleginfo - Inhalt 7;Beleginfo - Art 8;Beleginfo - Inhalt 8;KOST1 - Kostenstelle;KOST2 - Kostenstelle;Kost-Menge;EU-Land u. UStID (Bestimmung);EU-Steuersatz (Bestimmung);Abw. Versteuerungsart;Sachverhalt L+L;Funktionsergänzung L+L;BU 49 Hauptfunktionstyp;BU 49 Hauptfunktionsnummer;BU 49 Funktionsergänzung;Zusatzinformation - Art 1;Zusatzinformation- Inhalt 1;Zusatzinformation - Art 2;Zusatzinformation- Inhalt 2;Zusatzinformation - Art 3;Zusatzinformation- Inhalt 3;Zusatzinformation - Art 4;Zusatzinformation- Inhalt 4;Zusatzinformation - Art 5;Zusatzinformation- Inhalt 5;Zusatzinformation - Art 6;Zusatzinformation- Inhalt 6;Zusatzinformation - Art 7;Zusatzinformation- Inhalt 7;Zusatzinformation - Art 8;Zusatzinformation- Inhalt 8;Zusatzinformation - Art 9;Zusatzinformation- Inhalt 9;Zusatzinformation - Art 10;Zusatzinformation- Inhalt 10;Zusatzinformation - Art 11;Zusatzinformation- Inhalt 11;Zusatzinformation - Art 12;Zusatzinformation- Inhalt 12;Zusatzinformation - Art 13;Zusatzinformation- Inhalt 13;Zusatzinformation - Art 14;Zusatzinformation- Inhalt 14;Zusatzinformation - Art 15;Zusatzinformation- Inhalt 15;Zusatzinformation - Art 16;Zusatzinformation- Inhalt 16;Zusatzinformation - Art 17;Zusatzinformation- Inhalt 17;Zusatzinformation - Art 18;Zusatzinformation- Inhalt 18;Zusatzinformation - Art 19;Zusatzinformation- Inhalt 19;Zusatzinformation - Art 20;Zusatzinformation- Inhalt 20;Stück;Gewicht;Zahlweise;Forderungsart;Veranlagungsjahr;Zugeordnete Fälligkeit;Skontotyp;Auftragsnummer;Buchungstyp;USt-Schlüssel (Anzahlungen);EU-Land (Anzahlungen);Sachverhalt L+L (Anzahlungen);EU-Steuersatz (Anzahlungen);Erlöskonto (Anzahlungen);Herkunft-Kz;Buchungs GUID;KOST-Datum;SEPA-Mandatsreferenz;Skontosperre;Gesellschaftername;Beteiligtennummer;Identifikationsnummer;Zeichnernummer;Postensperre bis;Bezeichnung SoBil-Sachverhalt;Kennzeichen SoBil-Buchung;Festschreibung;Leistungsdatum;Datum Zuord. Steuerperiode;Fälligkeit;Generalumkehr (GU);Steuersatz;Land;Abrechnungsreferenz;BVV-Position;EU-Land u. UStID (Ursprung);EU-Steuersatz (Ursprung);Abw. Skontokonto' . "\n" .
            '64083;"S";"";;;"";4400;85;"";3101;"";"";;"Normalabschreibung Gebäude";;"";;;;"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"50";"";;"";;"";;;;;;"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";;;;"";;;;"";"";;"";;;;"WK";"";;"";;"";;"";"";;"";;0;;;;"";;"";"";;"";;' . "\n" .
            '' . "\n" .  // Leere Zeile
            '15301,67;"H";"";;;"";8400;10300;"";2002;"201802027";"";;"";;"";;;;"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"201";"";;"";;"";;;;;;"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";;;;"";;;;"";"";;"";;;;"WK";"";;"";;"";;"";"";;"";;0;;;;"";;"";"";;"";;' . "\n" .
            '';          // Leere Zeile am Ende

        $document = DatevDocumentParser::fromString($csvContent);

        $this->assertEquals(2, $document->countRows(), 'Leere Zeilen sollten übersprungen werden');
        $this->assertEquals('64083', $document->getFieldsByName('Umsatz (ohne Soll/Haben-Kz)')[0]->toString());
        $this->assertEquals('15301,67', $document->getFieldsByName('Umsatz (ohne Soll/Haben-Kz)')[1]->getValue());
        $this->assertEquals('S', $document->getFieldsByName('Soll/Haben-Kennzeichen')[0]->getValue());
        $this->assertEquals('"H"', $document->getFieldsByName('Soll/Haben-Kennzeichen')[1]->toString());

        $this->assertTrue($document->getFieldsByName('Soll/Haben-Kennzeichen')[1]->isQuoted());
        $this->assertFalse($document->getFieldsByName('Umsatz (ohne Soll/Haben-Kz)')[1]->isQuoted());
    }

    public function testParseDebKredStammFromRealFile(): void {
        $sampleFile = __DIR__ . '/../../.samples/DATEV/EXTF_DebKred_Stamm.csv';
        $this->assertFileExists($sampleFile, 'Sample file für Debitoren/Kreditoren muss existieren');

        $csvContent = file_get_contents($sampleFile);
        $this->assertNotEmpty($csvContent, 'Sample file darf nicht leer sein');

        // Debitoren/Kreditoren ist jetzt implementiert und sollte funktionieren
        $document = DatevDocumentParser::fromString($csvContent);
        $this->assertNotNull($document, 'DATEV-Dokument sollte erfolgreich geparst werden');
    }

    public function testAnalyzeFormatFromRealDebKredFile(): void {
        $sampleFile = __DIR__ . '/../../.samples/DATEV/EXTF_DebKred_Stamm.csv';
        $this->assertFileExists($sampleFile, 'Sample file für Debitoren/Kreditoren muss existieren');

        $csvContent = file_get_contents($sampleFile);
        $analysis = DatevDocumentParser::analyzeFormat($csvContent);

        $this->assertArrayHasKey('format_type', $analysis, 'Format sollte erkannt werden');
        $this->assertEquals('Debitoren/Kreditoren', $analysis['format_type']);
        $this->assertEquals(700, $analysis['version']);
        $this->assertTrue($analysis['supported']);
    }

    public function testParseSachkontobeschriftungenFromRealFile(): void {
        $sampleFile = __DIR__ . '/../../.samples/DATEV/EXTF_Sachkontobeschriftungen.csv';
        $this->assertFileExists($sampleFile, 'Sample file für Sachkontobeschriftungen muss existieren');

        $csvContent = file_get_contents($sampleFile);
        $this->assertNotEmpty($csvContent, 'Sample file darf nicht leer sein');

        // Kontenbeschriftungen ist jetzt implementiert und sollte funktionieren
        $document = DatevDocumentParser::fromString($csvContent);
        $this->assertNotNull($document, 'DATEV-Dokument sollte erfolgreich geparst werden');
    }

    public function testParseUnknownFormat(): void {
        $this->markTestSkipped('Dieser Test wird übersprungen, da er eine Exception testet für die eine Implementierung fehlt.');

        $csvContent = '"EXTF";700;"99";"UnknownFormat";7;20191001000000;7;"";"";"";"SV";"";"";"";0;""' . "\n" .
            '"Field1";"Field2"' . "\n" .
            '"Value1";"Value2"';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Format 'Unbekannt' v700 ist noch nicht implementiert");

        DatevDocumentParser::fromString($csvContent);
    }

    public function testParseInsufficientLines(): void {
        $csvContent = '"EXTF";700;"21";"Buchungsstapel";7;20191001000000;7;"";"";"";"SV";"";"";"";0;""';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DATEV-CSV muss mindestens 2 Zeilen haben');

        DatevDocumentParser::fromString($csvContent);
    }

    public function testParseInvalidMetaHeader(): void {
        $csvContent = 'Invalid MetaHeader' . "\n" .
            '"Field1";"Field2"' . "\n" .
            '"Value1";"Value2"';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Ungültiger DATEV MetaHeader');

        DatevDocumentParser::fromString($csvContent);
    }

    public function testAnalyzeFormatFromAllSampleFiles(): void {
        $sampleDir = __DIR__ . '/../../.samples/DATEV/';
        $sampleFiles = [
            'EXTF_Buchungsstapel.csv' => ['format' => 'Buchungsstapel', 'supported' => true],
            'EXTF_DebKred_Stamm.csv' => ['format' => 'Debitoren/Kreditoren', 'supported' => true],
            'EXTF_Sachkontobeschriftungen.csv' => ['format' => 'Kontenbeschriftungen', 'supported' => true],
            'EXTF_Zahlungsbedingungen.csv' => ['format' => 'Zahlungsbedingungen', 'supported' => true],
            'EXTF_Naturalstapel.csv' => ['format' => 'Naturalstapel', 'supported' => true],
            'EXTF_Wiederkehrende-Buchungen.csv' => ['format' => 'Wiederkehrende Buchungen', 'supported' => true],
            'EXTF_Div-Adressen.csv' => ['format' => 'Diverse Adressen', 'supported' => true]
        ];

        foreach ($sampleFiles as $fileName => $expected) {
            $sampleFile = $sampleDir . $fileName;

            if (!file_exists($sampleFile)) {
                $this->markTestSkipped("Sample file {$fileName} nicht gefunden");
                continue;
            }

            $csvContent = file_get_contents($sampleFile);
            $this->assertNotEmpty($csvContent, "Sample file {$fileName} darf nicht leer sein");

            $analysis = DatevDocumentParser::analyzeFormat($csvContent);

            $this->assertArrayHasKey('format_type', $analysis, "Format von {$fileName} sollte erkannt werden");
            $this->assertEquals($expected['format'], $analysis['format_type'], "Format-Typ von {$fileName} stimmt nicht");
            $this->assertEquals(700, $analysis['version'], "Version von {$fileName} sollte 700 sein");
            $this->assertEquals($expected['supported'], $analysis['supported'], "Support-Status von {$fileName} stimmt nicht");
        }
    }

    public function testGetSupportedFormats(): void {
        // Test der verfügbaren Formate mit echter Buchungsstapel-Datei
        $sampleFile = __DIR__ . '/../../.samples/DATEV/EXTF_Buchungsstapel.csv';

        if (file_exists($sampleFile)) {
            $csvContent = file_get_contents($sampleFile);
            $analysis = DatevDocumentParser::analyzeFormat($csvContent);
            $this->assertTrue($analysis['supported'], 'Buchungsstapel sollte unterstützt werden');
        }

        // Test mit nicht unterstütztem Format
        // $sampleFile = __DIR__ . '/../../.samples/DATEV/EXTF_DebKred_Stamm.csv';

        // if (file_exists($sampleFile)) {
        //     $csvContent = file_get_contents($sampleFile);
        //     $analysis = DatevDocumentParser::analyzeFormat($csvContent);
        //     $this->assertFalse($analysis['supported'], 'Debitoren/Kreditoren sollte noch nicht unterstützt werden');
        // }
    }
}
