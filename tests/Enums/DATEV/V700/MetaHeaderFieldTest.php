<?php
/*
 * Created on   : Thu Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MetaHeaderFieldTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Enums\DATEV\V700;

use CommonToolkit\Enums\DATEV\HeaderFields\V700\MetaHeaderField;
use Tests\Contracts\BaseTestCase;

class MetaHeaderFieldTest extends BaseTestCase {
    public function testHasCorrectNumberOfFields(): void {
        $ordered = MetaHeaderField::ordered();
        $this->assertCount(31, $ordered, 'DATEV MetaHeader V700 sollte genau 31 Felder haben');
    }

    public function testOrderedFieldsAreComplete(): void {
        $ordered = MetaHeaderField::ordered();

        // Erste 10 Felder
        $this->assertEquals(MetaHeaderField::Kennzeichen, $ordered[0]);
        $this->assertEquals(MetaHeaderField::Versionsnummer, $ordered[1]);
        $this->assertEquals(MetaHeaderField::Formatkategorie, $ordered[2]);
        $this->assertEquals(MetaHeaderField::Formatname, $ordered[3]);
        $this->assertEquals(MetaHeaderField::Formatversion, $ordered[4]);
        $this->assertEquals(MetaHeaderField::ErzeugtAm, $ordered[5]);
        $this->assertEquals(MetaHeaderField::Importiert, $ordered[6]);
        $this->assertEquals(MetaHeaderField::Herkunft, $ordered[7]);
        $this->assertEquals(MetaHeaderField::ExportiertVon, $ordered[8]);
        $this->assertEquals(MetaHeaderField::ImportiertVon, $ordered[9]);

        // Letzte Felder
        $this->assertEquals(MetaHeaderField::Anwendungsinformation, $ordered[30]);
    }

    public function testPositionMethod(): void {
        $this->assertEquals(1, MetaHeaderField::Kennzeichen->position());
        $this->assertEquals(4, MetaHeaderField::Formatname->position());
        $this->assertEquals(11, MetaHeaderField::Beraternummer->position());
        $this->assertEquals(31, MetaHeaderField::Anwendungsinformation->position());
    }

    public function testLabelMethod(): void {
        $this->assertEquals('Kennzeichen', MetaHeaderField::Kennzeichen->label());
        $this->assertEquals('Versionsnummer', MetaHeaderField::Versionsnummer->label());
        $this->assertEquals('Formatkategorie', MetaHeaderField::Formatkategorie->label());
        $this->assertEquals('Formatname', MetaHeaderField::Formatname->label());
        $this->assertEquals('Erzeugt am', MetaHeaderField::ErzeugtAm->label());
        $this->assertEquals('WJ-Beginn', MetaHeaderField::WJBeginn->label());
        $this->assertEquals('Sachkontenlänge', MetaHeaderField::Sachkontenlaenge->label());
    }

    /**
     * Testet die isQuoted() Methode gemäß DATEV-Spezifikation.
     *
     * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/header
     */
    public function testIsQuotedFieldsAccordingToDatevSpec(): void {
        // Gequotete Felder laut DATEV-Spezifikation (Pattern enthält ["])
        $quotedFields = [
            MetaHeaderField::Kennzeichen,           // 1: ^["](EXTF|DTVF)["]$
            MetaHeaderField::Formatname,            // 4: ^["](Buchungsstapel|...)["]$
            MetaHeaderField::Herkunft,              // 8: ^["]\w{0,2}["]$
            MetaHeaderField::ExportiertVon,         // 9: ^["]\w{0,25}["]$
            MetaHeaderField::ImportiertVon,         // 10: ^["]\w{0,25}["]$
            MetaHeaderField::Bezeichnung,           // 17: ^["][\w.-/ ]{0,30}["]$
            MetaHeaderField::Diktatkuerzel,         // 18: ^["]([A-Z]{2}){0,2}["]$
            MetaHeaderField::Waehrungskennzeichen,  // 22: ^["]([A-Z]{3})["]$
            MetaHeaderField::Derivatskennzeichen,   // 24: ^["]["]$
            MetaHeaderField::Sachkontenrahmen,      // 27: ^["](\d{2}){0,2}["]$
            MetaHeaderField::Reserviert30,          // 30: ^["]["]$
            MetaHeaderField::Anwendungsinformation, // 31: ^["].{0,16}["]$
        ];

        foreach ($quotedFields as $field) {
            $this->assertTrue(
                $field->isQuoted(),
                "Feld {$field->name} (Position {$field->position()}) sollte gequotet sein"
            );
        }
    }

    public function testIsNotQuotedFieldsAccordingToDatevSpec(): void {
        // Nicht gequotete Felder laut DATEV-Spezifikation
        $unquotedFields = [
            MetaHeaderField::Versionsnummer,        // 2
            MetaHeaderField::Formatkategorie,       // 3
            MetaHeaderField::Formatversion,         // 5
            MetaHeaderField::ErzeugtAm,             // 6
            MetaHeaderField::Importiert,            // 7
            MetaHeaderField::Beraternummer,         // 11
            MetaHeaderField::Mandantennummer,       // 12
            MetaHeaderField::WJBeginn,              // 13
            MetaHeaderField::Sachkontenlaenge,      // 14
            MetaHeaderField::DatumVon,              // 15
            MetaHeaderField::DatumBis,              // 16
            MetaHeaderField::Buchungstyp,           // 19
            MetaHeaderField::Rechnungslegungszweck, // 20
            MetaHeaderField::Festschreibung,        // 21
            MetaHeaderField::Reserviert23,          // 23
            MetaHeaderField::Reserviert25,          // 25
            MetaHeaderField::Reserviert26,          // 26
            MetaHeaderField::BranchenloesungID,     // 28
            MetaHeaderField::Reserviert29,          // 29
        ];

        foreach ($unquotedFields as $field) {
            $this->assertFalse(
                $field->isQuoted(),
                "Feld {$field->name} (Position {$field->position()}) sollte NICHT gequotet sein"
            );
        }
    }

    public function testQuotedFieldsCount(): void {
        $quotedCount = 0;
        foreach (MetaHeaderField::ordered() as $field) {
            if ($field->isQuoted()) {
                $quotedCount++;
            }
        }

        $this->assertEquals(12, $quotedCount, 'Es sollten genau 12 gequotete Felder im MetaHeader sein');
    }

    public function testPatternValidation(): void {
        // Kennzeichen
        $this->assertMatchesRegularExpression(
            '/' . MetaHeaderField::Kennzeichen->pattern() . '/',
            'EXTF'
        );
        $this->assertMatchesRegularExpression(
            '/' . MetaHeaderField::Kennzeichen->pattern() . '/',
            'DTVF'
        );

        // Versionsnummer
        $this->assertMatchesRegularExpression(
            '/' . MetaHeaderField::Versionsnummer->pattern() . '/',
            '700'
        );

        // Formatkategorie
        $this->assertMatchesRegularExpression(
            '/' . MetaHeaderField::Formatkategorie->pattern() . '/',
            '21'
        );
        $this->assertMatchesRegularExpression(
            '/' . MetaHeaderField::Formatkategorie->pattern() . '/',
            '16'
        );

        // ErzeugtAm (Timestamp-Format: YYYYMMDDHHmmssSSS)
        $this->assertMatchesRegularExpression(
            '/' . MetaHeaderField::ErzeugtAm->pattern() . '/',
            '20241226143000000'
        );

        // Beraternummer
        $this->assertMatchesRegularExpression(
            '/' . MetaHeaderField::Beraternummer->pattern() . '/',
            '29098'
        );

        // Sachkontenlänge
        $this->assertMatchesRegularExpression(
            '/' . MetaHeaderField::Sachkontenlaenge->pattern() . '/',
            '4'
        );
        $this->assertMatchesRegularExpression(
            '/' . MetaHeaderField::Sachkontenlaenge->pattern() . '/',
            '8'
        );
    }

    public function testAllFieldsHaveLabel(): void {
        foreach (MetaHeaderField::ordered() as $field) {
            $this->assertNotEmpty(
                $field->label(),
                "Feld {$field->name} sollte ein Label haben"
            );
        }
    }

    public function testAllFieldsHavePattern(): void {
        foreach (MetaHeaderField::ordered() as $field) {
            $this->assertNotNull(
                $field->pattern(),
                "Feld {$field->name} sollte ein Validierungsmuster haben"
            );
        }
    }
}
