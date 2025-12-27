<?php
/*
 * Created on   : Sun Jul 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CamtParserTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Parsers;

use CommonToolkit\Entities\Common\Banking\Camt\Type52\Document as Camt052Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document as Camt053Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type54\Document as Camt054Document;
use CommonToolkit\Enums\Common\Banking\CamtType;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Parsers\CamtParser;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für den generischen CamtParser.
 */
class CamtParserTest extends BaseTestCase {
    private string $samplesPath;

    protected function setUp(): void {
        parent::setUp();
        $this->samplesPath = dirname(__DIR__, 2) . '/.samples/Banking/CAMT/';
    }

    // ============================================================
    // CAMT.052 Tests
    // ============================================================

    public function testParseCamt052Bareinzahlung(): void {
        $file = $this->samplesPath . '01_EBICS_camt.052_Bareinzahlung_auf_Dot.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $document = CamtParser::parseFile($file);

        $this->assertInstanceOf(Camt052Document::class, $document);
        $this->assertEquals(CamtType::CAMT052, $document->getCamtType());
        $this->assertEquals('DE00IBANdesDotationskontos', $document->getAccountIdentifier());
        $this->assertEquals('EUR', $document->getCurrency()->value);

        // Opening Balance
        $openingBalance = $document->getOpeningBalance();
        $this->assertNotNull($openingBalance);
        $this->assertEquals(0.0, $openingBalance->getAmount());
        $this->assertTrue($openingBalance->isCredit());

        // Closing Balance
        $closingBalance = $document->getClosingBalance();
        $this->assertNotNull($closingBalance);
        $this->assertEquals(100000.0, $closingBalance->getAmount());
        $this->assertTrue($closingBalance->isCredit());

        // Entries
        $this->assertGreaterThan(0, $document->countEntries());

        /** @var \CommonToolkit\Entities\Common\Banking\Camt\Type52\Transaction $firstEntry */
        $firstEntry = $document->getEntries()[0];
        $this->assertEquals(100000.0, $firstEntry->getAmount());
        $this->assertTrue($firstEntry->isCredit());
        $this->assertEquals('PMNT', $firstEntry->getDomainCode());
        $this->assertEquals('CNTR', $firstEntry->getFamilyCode());
        $this->assertEquals('CDPT', $firstEntry->getSubFamilyCode());
    }

    public function testParseCamt052Barscheckauszahlung(): void {
        $file = $this->samplesPath . '02_EBICS_camt.052_Barscheckauszahlung_vom_Dot.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $document = CamtParser::parseFile($file);

        $this->assertInstanceOf(Camt052Document::class, $document);
        $this->assertGreaterThan(0, $document->countEntries());

        // Barscheckauszahlung sollte DBIT sein
        $entries = $document->getEntries();
        $hasDebit = false;
        foreach ($entries as $entry) {
            if ($entry->isDebit()) {
                $hasDebit = true;
                break;
            }
        }
        $this->assertTrue($hasDebit, 'Barscheckauszahlung sollte mindestens eine Soll-Buchung haben');
    }

    public function testParseCamt052DetectsType(): void {
        $file = $this->samplesPath . '01_EBICS_camt.052_Bareinzahlung_auf_Dot.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $content = file_get_contents($file);
        $type = CamtParser::detectType($content);

        $this->assertEquals(CamtType::CAMT052, $type);
        $this->assertEquals('BkToCstmrAcctRpt', $type->getRootElement());
        $this->assertEquals('Rpt', $type->getStatementElement());
    }

    // ============================================================
    // CAMT.053 Tests
    // ============================================================

    public function testParseCamt053Kontoauszug(): void {
        $file = $this->samplesPath . '11_EBICS_camt.053_Kontoauszug_mit_allen_Umsätzen.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $document = CamtParser::parseFile($file);

        $this->assertInstanceOf(Camt053Document::class, $document);
        $this->assertEquals(CamtType::CAMT053, $document->getCamtType());
        $this->assertEquals('DE00IBANdesDotationskontos', $document->getAccountIban());

        // Mehrere Transaktionen erwartet
        $this->assertGreaterThan(5, $document->countEntries());

        // Prüfe Credits und Debits
        $this->assertGreaterThan(0, $document->getTotalCredits());
        $this->assertGreaterThan(0, $document->getTotalDebits());
    }

    public function testParseCamt053RtgsDca(): void {
        $file = $this->samplesPath . '4. camt.053-Beispieldatei RTGS DCA.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $document = CamtParser::parseFile($file);

        $this->assertInstanceOf(Camt053Document::class, $document);

        // Opening Balance prüfen
        $openingBalance = $document->getOpeningBalance();
        $this->assertNotNull($openingBalance);
        $this->assertEquals(5368506.70, $openingBalance->getAmount());

        // Closing Balance prüfen
        $closingBalance = $document->getClosingBalance();
        $this->assertNotNull($closingBalance);
        $this->assertEquals(5368206.70, $closingBalance->getAmount());
    }

    public function testParseCamt053SubAccount(): void {
        $file = $this->samplesPath . '5. camt.053-Beispieldatei Sub-Account.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $document = CamtParser::parseFile($file);

        $this->assertInstanceOf(Camt053Document::class, $document);
        $this->assertEquals(11, $document->countEntries());
    }

    // ============================================================
    // CAMT.054 Tests
    // ============================================================

    public function testParseCamt054LiquidityTransferOrder(): void {
        $file = $this->samplesPath . '1. camt.054- Beispieldatei liquidity transfer order.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $document = CamtParser::parseFile($file);

        $this->assertInstanceOf(Camt054Document::class, $document);
        $this->assertEquals(CamtType::CAMT054, $document->getCamtType());

        $this->assertGreaterThan(0, $document->countEntries());

        /** @var \CommonToolkit\Entities\Common\Banking\Camt\Type54\Transaction $firstEntry */
        $firstEntry = $document->getEntries()[0];
        $this->assertEquals(258808.98, $firstEntry->getAmount());
        $this->assertTrue($firstEntry->isDebit());
        $this->assertEquals('LIQT', $firstEntry->getBankTransactionCode());

        // Referenzen prüfen
        $this->assertNotNull($firstEntry->getInstructionId());
        $this->assertNotNull($firstEntry->getEndToEndId());

        // Agent BICs prüfen
        $this->assertEquals('ZYBUDEFFSEK', $firstEntry->getInstructingAgentBic());
        $this->assertEquals('MARKDEFFSCL', $firstEntry->getDebtorAgentBic());
    }

    public function testParseCamt054Booking(): void {
        $file = $this->samplesPath . '2. camt.054- Beispieldatei booking.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $document = CamtParser::parseFile($file);

        $this->assertInstanceOf(Camt054Document::class, $document);
        $this->assertGreaterThan(0, $document->countEntries());
    }

    public function testParseCamt054DetectsType(): void {
        $file = $this->samplesPath . '1. camt.054- Beispieldatei liquidity transfer order.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $content = file_get_contents($file);
        $type = CamtParser::detectType($content);

        $this->assertEquals(CamtType::CAMT054, $type);
        $this->assertEquals('BkToCstmrDbtCdtNtfctn', $type->getRootElement());
        $this->assertEquals('Ntfctn', $type->getStatementElement());
    }

    // ============================================================
    // CamtType Enum Tests
    // ============================================================

    public function testCamtTypeDescriptions(): void {
        $this->assertEquals('Untertägige Kontobewegungsinformation', CamtType::CAMT052->getDescription());
        $this->assertEquals('Täglicher Kontoauszug', CamtType::CAMT053->getDescription());
        $this->assertEquals('Soll/Haben-Avis', CamtType::CAMT054->getDescription());
    }

    public function testCamtTypeMessageNames(): void {
        $this->assertEquals('BankToCustomerAccountReport', CamtType::CAMT052->getMessageName());
        $this->assertEquals('BankToCustomerStatement', CamtType::CAMT053->getMessageName());
        $this->assertEquals('BankToCustomerDebitCreditNotification', CamtType::CAMT054->getMessageName());
    }

    public function testCamtTypeNamespaces(): void {
        $namespaces052 = CamtType::CAMT052->getNamespaces();
        $this->assertArrayHasKey('02', $namespaces052);
        $this->assertArrayHasKey('08', $namespaces052);
        $this->assertEquals('urn:iso:std:iso:20022:tech:xsd:camt.052.001.08', $namespaces052['08']);

        $namespaces053 = CamtType::CAMT053->getNamespaces();
        $this->assertArrayHasKey('02', $namespaces053);
        $this->assertArrayHasKey('08', $namespaces053);
        $this->assertEquals('urn:iso:std:iso:20022:tech:xsd:camt.053.001.02', $namespaces053['02']);
    }

    // ============================================================
    // XML Generation Tests
    // ============================================================

    public function testCamt052ToXml(): void {
        $file = $this->samplesPath . '01_EBICS_camt.052_Bareinzahlung_auf_Dot.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $document = CamtParser::parseFile($file);
        $this->assertInstanceOf(Camt052Document::class, $document);

        $xml = $document->toXml();

        $this->assertStringContainsString('<?xml version="1.0"', $xml);
        $this->assertStringContainsString('BkToCstmrAcctRpt', $xml);
        $this->assertStringContainsString('Rpt', $xml);
        $this->assertStringContainsString('camt.052', $xml);
    }

    public function testCamt054ToXml(): void {
        $file = $this->samplesPath . '1. camt.054- Beispieldatei liquidity transfer order.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $document = CamtParser::parseFile($file);
        $this->assertInstanceOf(Camt054Document::class, $document);

        $xml = $document->toXml();

        $this->assertStringContainsString('<?xml version="1.0"', $xml);
        $this->assertStringContainsString('BkToCstmrDbtCdtNtfctn', $xml);
        $this->assertStringContainsString('Ntfctn', $xml);
        $this->assertStringContainsString('camt.054', $xml);
    }

    // ============================================================
    // Edge Cases & Error Handling
    // ============================================================

    public function testParseInvalidXmlThrowsException(): void {
        $this->expectException(\RuntimeException::class);
        CamtParser::parse('not valid xml');
    }

    public function testParseUnknownTypeThrowsException(): void {
        $xml = '<?xml version="1.0"?><Document>
    <Unknown />
</Document>';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unbekannter CAMT-Dokumenttyp');
        CamtParser::parse($xml);
    }

    public function testParseFileNotFoundThrowsException(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Datei nicht gefunden');
        CamtParser::parseFile('/nonexistent/path/file.xml');
    }

    // ============================================================
    // XSD-Validierung Tests
    // ============================================================

    public function testParseWithValidation(): void {
        $file = $this->samplesPath . '01_EBICS_camt.052_Bareinzahlung_auf_Dot.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        // Ohne Validierung (Standard)
        $document = CamtParser::parseFile($file);
        $this->assertInstanceOf(Camt052Document::class, $document);

        // Mit Validierung
        $document = CamtParser::parseFile($file, validate: true);
        $this->assertInstanceOf(Camt052Document::class, $document);
    }

    public function testParseWithValidationFailsOnInvalidXml(): void {
        // Erstelle ein XML das zwar CAMT-ähnlich aussieht aber nicht valide ist
        $invalidXml = '<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.052.001.02">
    <BkToCstmrAcctRpt>
        <GrpHdr>
            <MsgId>INVALID</MsgId>
        </GrpHdr>
    </BkToCstmrAcctRpt>
</Document>';

        // Mit Validierung sollte eine Exception geworfen werden
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('XSD-Validierung fehlgeschlagen');
        CamtParser::parse($invalidXml, validate: true);
    }

    // ============================================================
    // Roundtrip Tests
    // ============================================================

    public function testCamt052Roundtrip(): void {
        $file = $this->samplesPath . '01_EBICS_camt.052_Bareinzahlung_auf_Dot.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $document1 = CamtParser::parseFile($file);
        $xml = $document1->toXml();
        $document2 = CamtParser::parseCamt052($xml);

        $this->assertEquals($document1->getId(), $document2->getId());
        $this->assertEquals($document1->getAccountIdentifier(), $document2->getAccountIdentifier());
        $this->assertEquals($document1->countEntries(), $document2->countEntries());
    }
}
