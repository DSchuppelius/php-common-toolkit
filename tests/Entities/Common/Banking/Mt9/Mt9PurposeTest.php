<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt940PurposeTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\CommonToolkit\Entities\Common\Banking\Mt9;

use CommonToolkit\Entities\Common\Banking\Mt9\Purpose;
use Tests\Contracts\BaseTestCase;

class Mt9PurposeTest extends BaseTestCase {

    public function testParseStructuredPurpose(): void {
        $lines = [
            '166SEPA-Lastschrift',
            '?00FOLGELASTSCHRIFT',
            '?10123456',
            '?20EREF+ORDER12345',
            '?21MREF+MANDATE67890',
            '?22CRED+DE98ZZZ09999999999',
            '?23SVWZ+Mitgliedsbeitrag',
            '?24Januar 2025',
            '?30DEUTDEDB',
            '?31DE89370400440532013000',
            '?32Max Mustermann',
            '?33GmbH',
            '?34997',
        ];

        $purpose = Purpose::fromRawLines($lines);

        $this->assertEquals('166', $purpose->getGvcCode());
        $this->assertEquals('FOLGELASTSCHRIFT', $purpose->getBookingText());
        $this->assertEquals('123456', $purpose->getPrimanotenNr());
        $this->assertCount(5, $purpose->getPurposeLines());
        $this->assertEquals('DEUTDEDB', $purpose->getPayerBlz());
        $this->assertEquals('DE89370400440532013000', $purpose->getPayerAccount());
        $this->assertEquals('Max Mustermann', $purpose->getPayerName1());
        $this->assertEquals('GmbH', $purpose->getPayerName2());
        $this->assertEquals('Max Mustermann GmbH', $purpose->getPayerName());
        $this->assertEquals('997', $purpose->getTextKeyExt());
    }

    public function testParseUnstructuredPurpose(): void {
        $lines = [
            'SEPA-Überweisung',
            'Rechnung 12345',
        ];

        $purpose = Purpose::fromRawLines($lines);

        $this->assertNull($purpose->getGvcCode());
        $this->assertNull($purpose->getBookingText());
        $this->assertStringContainsString('SEPA-Überweisung', $purpose->getRawText());
        $this->assertStringContainsString('Rechnung 12345', $purpose->getRawText());
    }

    public function testFromString(): void {
        $purpose = Purpose::fromString('Testzahlung für Projekt ABC');

        $this->assertEquals('Testzahlung für Projekt ABC', $purpose->getRawText());
        $this->assertEquals('Testzahlung für Projekt ABC', $purpose->getFullText());
    }

    public function testGetPurposeText(): void {
        $purpose = new Purpose(
            gvcCode: '020',
            purposeLines: ['Verwendungszweck Zeile 1', 'Zeile 2', 'Zeile 3']
        );

        $this->assertEquals('Verwendungszweck Zeile 1 Zeile 2 Zeile 3', $purpose->getPurposeText());
    }

    public function testGetFullText(): void {
        $purpose = new Purpose(
            bookingText: 'ÜBERWEISUNG',
            purposeLines: ['Rechnung 12345'],
            payerName1: 'Max Mustermann',
            payerName2: 'GmbH'
        );

        $fullText = $purpose->getFullText();
        $this->assertStringContainsString('ÜBERWEISUNG', $fullText);
        $this->assertStringContainsString('Rechnung 12345', $fullText);
        $this->assertStringContainsString('Max Mustermann GmbH', $fullText);
    }

    public function testToMt940Lines(): void {
        $purpose = new Purpose(
            gvcCode: '020',
            bookingText: 'ÜBERWEISUNG',
            primanotenNr: '12345',
            purposeLines: ['Verwendungszweck Zeile 1', 'Zeile 2'],
            payerBlz: 'DEUTDEDB',
            payerAccount: 'DE89370400440532013000',
            payerName1: 'Max Mustermann',
            payerName2: 'GmbH'
        );

        $lines = $purpose->toMt940Lines();

        $this->assertStringStartsWith(':86:020', $lines[0]);
        $this->assertContains('?1012345', $lines);
        $this->assertContains('?20Verwendungszweck Zeile 1', $lines);
        $this->assertContains('?21Zeile 2', $lines);
        $this->assertContains('?30DEUTDEDB', $lines);
        $this->assertContains('?31DE89370400440532013000', $lines);
        $this->assertContains('?32Max Mustermann', $lines);
        $this->assertContains('?33GmbH', $lines);
    }

    public function testToMt940LinesFromRawText(): void {
        // Langer Text, der in 27-Zeichen-Segmente aufgeteilt werden muss
        $longText = 'SEPA-Überweisung Max Mustermann GmbH Rechnung 12345 vom 01.01.2025';
        $purpose = Purpose::fromString($longText);

        $lines = $purpose->toMt940Lines();

        $this->assertStringStartsWith(':86:', $lines[0]);
        // Es sollte Fortsetzungszeilen geben
        $this->assertGreaterThan(1, count($lines));
    }

    public function testToString(): void {
        $purpose = new Purpose(
            purposeLines: ['Test Verwendungszweck']
        );

        $this->assertEquals('Test Verwendungszweck', (string) $purpose);
    }

    public function testExtendedPurposeFields(): void {
        // Test für ?60-?63 Felder
        $lines = [
            '020ÜBERWEISUNG',
            '?20Zeile 20',
            '?21Zeile 21',
            '?22Zeile 22',
            '?23Zeile 23',
            '?24Zeile 24',
            '?25Zeile 25',
            '?26Zeile 26',
            '?27Zeile 27',
            '?28Zeile 28',
            '?29Zeile 29',
            '?60Zeile 60',
            '?61Zeile 61',
            '?62Zeile 62',
            '?63Zeile 63',
        ];

        $purpose = Purpose::fromRawLines($lines);

        // Sollte alle 14 Verwendungszweck-Zeilen enthalten
        $this->assertCount(14, $purpose->getPurposeLines());
    }
}
