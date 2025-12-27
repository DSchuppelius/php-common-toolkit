<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SwiftMessageParserTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Parsers;

use CommonToolkit\Entities\Common\Banking\Swift\Message;
use CommonToolkit\Enums\Common\Banking\MtType;
use CommonToolkit\Parsers\SwiftMessageParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

final class SwiftMessageParserTest extends BaseTestCase {
    private const SAMPLE_DIR = __DIR__ . '/../../.samples/Banking/SWIFT/';
    private const MT_SAMPLE_DIR = __DIR__ . '/../../.samples/Banking/MT/';

    public function testHasEnvelopeWithEnvelope(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'envelope.mt940');
        $this->assertTrue(SwiftMessageParser::hasEnvelope($content));
    }

    public function testHasEnvelopeWithoutEnvelope(): void {
        $content = file_get_contents(self::MT_SAMPLE_DIR . 'example.mt940');
        $this->assertFalse(SwiftMessageParser::hasEnvelope($content));
    }

    public function testParseMt940WithEnvelope(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'envelope.mt940');
        $message = SwiftMessageParser::parse($content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(MtType::MT940, $message->getMessageType());
        $this->assertTrue($message->isOutput());
        $this->assertFalse($message->isInput());
        $this->assertEquals('GSCRUS33', $message->getSenderBic());
    }

    public function testParseMt103WithEnvelope(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'envelope.mt103');
        $message = SwiftMessageParser::parse($content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(MtType::MT103, $message->getMessageType());
        $this->assertTrue($message->isInput());
        $this->assertFalse($message->isOutput());
        $this->assertEquals('BANKDEFFXXXX', $message->getReceiverBic());
        $this->assertTrue($message->isStp());
    }

    public function testParseMt900WithEnvelope(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'envelope.mt900');
        $message = SwiftMessageParser::parse($content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(MtType::MT900, $message->getMessageType());
        $this->assertTrue($message->isOutput());
    }

    public function testBasicHeader(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'envelope.mt940');
        $message = SwiftMessageParser::parse($content);

        $basicHeader = $message->getBasicHeader();
        $this->assertEquals('F', $basicHeader->getApplicationId());
        $this->assertEquals('01', $basicHeader->getServiceId());
        $this->assertEquals('GSCRUS33', $basicHeader->getBic());
        $this->assertTrue($basicHeader->isFin());
        $this->assertFalse($basicHeader->isGpa());
    }

    public function testApplicationHeaderOutput(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'envelope.mt940');
        $message = SwiftMessageParser::parse($content);

        $appHeader = $message->getApplicationHeader();
        $this->assertTrue($appHeader->isOutput());
        $this->assertEquals(MtType::MT940, $appHeader->getMessageType());
        $this->assertEquals('1200', $appHeader->getInputTime());
        $this->assertEquals('Normal', $appHeader->getPriorityDescription());
    }

    public function testApplicationHeaderInput(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'envelope.mt103');
        $message = SwiftMessageParser::parse($content);

        $appHeader = $message->getApplicationHeader();
        $this->assertTrue($appHeader->isInput());
        $this->assertFalse($appHeader->isOutput());
        $this->assertEquals(MtType::MT103, $appHeader->getMessageType());
        $this->assertEquals('BANKDEFFXXXX', $appHeader->getReceiverBic());
        $this->assertEquals('N', $appHeader->getPriority());
    }

    public function testUserHeader(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'envelope.mt940');
        $message = SwiftMessageParser::parse($content);

        $userHeader = $message->getUserHeader();
        $this->assertNotNull($userHeader);
        $this->assertEquals('MT940REF001', $userHeader->getMur());
        $this->assertTrue($userHeader->hasField('121'));
    }

    public function testUserHeaderStp(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'envelope.mt103');
        $message = SwiftMessageParser::parse($content);

        $userHeader = $message->getUserHeader();
        $this->assertNotNull($userHeader);
        $this->assertTrue($userHeader->isStp());
        $this->assertEquals('STP', $userHeader->getValidationFlag());
    }

    public function testTrailer(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'envelope.mt940');
        $message = SwiftMessageParser::parse($content);

        $trailer = $message->getTrailer();
        $this->assertNotNull($trailer);
        $this->assertEquals('123456789ABC', $trailer->getChecksum());
        $this->assertTrue($trailer->isTraining());
        $this->assertFalse($trailer->isPossibleDuplicateEmission());
    }

    public function testTextBlockExtraction(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'envelope.mt940');
        $textBlock = SwiftMessageParser::extractTextBlock($content);

        $this->assertStringContainsString(':20:STMT2025001', $textBlock);
        $this->assertStringContainsString(':25:DE89370400440532013000', $textBlock);
        $this->assertStringContainsString(':60F:C250101EUR5000,00', $textBlock);
        $this->assertStringContainsString(':62F:C250131EUR5500,00', $textBlock);
    }

    public function testTextBlockExtractionWithoutEnvelope(): void {
        $content = file_get_contents(self::MT_SAMPLE_DIR . 'example.mt940');
        $textBlock = SwiftMessageParser::extractTextBlock($content);

        // Bei Raw-Daten sollte der Input unverändert zurückgegeben werden
        $this->assertStringContainsString(':20:', $textBlock);
    }

    public function testValidateWithEnvelope(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'envelope.mt940');
        $result = SwiftMessageParser::validate($content);

        $this->assertTrue($result['valid']);
        $this->assertTrue($result['hasEnvelope']);
        $this->assertEmpty($result['errors']);
        $this->assertContains(1, $result['blocks']);
        $this->assertContains(2, $result['blocks']);
        $this->assertContains(4, $result['blocks']);
    }

    public function testValidateWithoutEnvelope(): void {
        $content = file_get_contents(self::MT_SAMPLE_DIR . 'example.mt940');
        $result = SwiftMessageParser::validate($content);

        $this->assertTrue($result['valid']);
        $this->assertFalse($result['hasEnvelope']);
    }

    public function testParseThrowsOnMissingEnvelope(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Keine SWIFT-Envelope gefunden');

        $content = file_get_contents(self::MT_SAMPLE_DIR . 'example.mt940');
        SwiftMessageParser::parse($content);
    }

    public function testToString(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'envelope.mt940');
        $message = SwiftMessageParser::parse($content);

        $output = (string)$message;
        $this->assertStringContainsString('{1:', $output);
        $this->assertStringContainsString('{2:', $output);
        $this->assertStringContainsString('{4:', $output);
        $this->assertStringContainsString('-}', $output);
    }

    public function testMessageConvenienceMethods(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'envelope.mt940');
        $message = SwiftMessageParser::parse($content);

        $this->assertEquals('123456789ABC', $message->getChecksum());
        $this->assertEquals('MT940REF001', $message->getMur());
        $this->assertTrue($message->isTraining());
    }

    public function testParseMultiple(): void {
        // Zwei Nachrichten hintereinander
        $content1 = file_get_contents(self::SAMPLE_DIR . 'envelope.mt940');
        $content2 = file_get_contents(self::SAMPLE_DIR . 'envelope.mt103');

        $combined = $content1 . "\n" . $content2;
        $messages = SwiftMessageParser::parseMultiple($combined);

        $this->assertCount(2, $messages);
        $this->assertEquals(MtType::MT940, $messages[0]->getMessageType());
        $this->assertEquals(MtType::MT103, $messages[1]->getMessageType());
    }

    // ========== Tests für neue Sample-Dateien ==========

    public function testParseItemizedMt940(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'itemized.mt940');
        $message = SwiftMessageParser::parse($content);

        $this->assertEquals(MtType::MT940, $message->getMessageType());
        $this->assertTrue($message->isInput());
        $this->assertEquals('ZYTADEF0', $message->getSenderBic());
        $this->assertNull($message->getUserHeader());
        $this->assertNull($message->getTrailer());

        // Prüfe Text-Block Inhalt
        $textBlock = $message->getTextBlock();
        $this->assertStringContainsString(':20:13762301952', $textBlock);
        $this->assertStringContainsString(':25:DE20514304205000000651', $textBlock);
        $this->assertStringContainsString(':60F:C240520EUR19967283027578', $textBlock);
    }

    public function testParseNonItemizedMt940(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'nonitemzed.mt940');
        $message = SwiftMessageParser::parse($content);

        $this->assertEquals(MtType::MT940, $message->getMessageType());
        $this->assertTrue($message->isInput());
        $this->assertEquals('GSCRUS30', $message->getSenderBic());

        $textBlock = $message->getTextBlock();
        $this->assertStringContainsString(':20:15486025400', $textBlock);
        $this->assertStringContainsString(':60M:C250218USD2732398848,02', $textBlock);
    }

    public function testParseNonItemizedMt101(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'nonitemized.mt101');
        $message = SwiftMessageParser::parse($content);

        $this->assertEquals(MtType::MT101, $message->getMessageType());
        $this->assertTrue($message->isOutput());
        $this->assertEquals('GSCRUS30', $message->getSenderBic());

        $textBlock = $message->getTextBlock();
        $this->assertStringContainsString(':20:GS0DX1QH8IU8IR2', $textBlock);
        $this->assertStringContainsString(':32B:USD330,21', $textBlock);
    }

    public function testParseNonItemizedMt103(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'nonitemized.mt103');
        $message = SwiftMessageParser::parse($content);

        $this->assertEquals(MtType::MT103, $message->getMessageType());
        $this->assertTrue($message->isOutput());
        $this->assertEquals('GSCRUS30', $message->getSenderBic());

        // User Header vorhanden
        $userHeader = $message->getUserHeader();
        $this->assertNotNull($userHeader);
        $this->assertEquals('202241345241SG06', $userHeader->getMur());
        $this->assertEquals('ef116b72-18b6-48c1-a6d1-09f44d0d2945', $userHeader->getUetr());
        $this->assertTrue($userHeader->hasField('111'));

        // Trailer vorhanden
        $trailer = $message->getTrailer();
        $this->assertNotNull($trailer);
        $this->assertEquals('305199LKJH2C', $trailer->getChecksum());
    }

    public function testParseNonItemizedMt900(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'nonitemized.mt900');
        $message = SwiftMessageParser::parse($content);

        $this->assertEquals(MtType::MT900, $message->getMessageType());
        $this->assertTrue($message->isInput());
        $this->assertEquals('GSLDGB20', $message->getSenderBic());

        $textBlock = $message->getTextBlock();
        $this->assertStringContainsString(':20:GI7458960008536', $textBlock);
        $this->assertStringContainsString(':32A:250428GBP90,01', $textBlock);
    }

    public function testParseNonItemizedMt910(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'nonitemized.mt910');
        $message = SwiftMessageParser::parse($content);

        $this->assertEquals(MtType::MT910, $message->getMessageType());
        $this->assertTrue($message->isInput());
        $this->assertEquals('GSCRUS30', $message->getSenderBic());

        $textBlock = $message->getTextBlock();
        $this->assertStringContainsString(':20:GI2508600017845', $textBlock);
        $this->assertStringContainsString(':32A:250327USD13,01', $textBlock);
        $this->assertStringContainsString(':50K:/107044863', $textBlock);
    }

    public function testParseNonItemizedMt942(): void {
        $content = file_get_contents(self::SAMPLE_DIR . 'nonitemized.mt942');
        $message = SwiftMessageParser::parse($content);

        $this->assertEquals(MtType::MT942, $message->getMessageType());
        $this->assertTrue($message->isInput());
        $this->assertEquals('GSCRUS30', $message->getSenderBic());

        $textBlock = $message->getTextBlock();
        $this->assertStringContainsString(':20:15689433400', $textBlock);
        $this->assertStringContainsString(':90D:1USD245,', $textBlock);
        $this->assertStringContainsString(':90C:2USD595,', $textBlock);
    }

    public function testAllSwiftSamplesCanBeParsed(): void {
        $files = glob(self::SAMPLE_DIR . '*.mt*');
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $this->assertTrue(
                SwiftMessageParser::hasEnvelope($content),
                "Datei " . basename($file) . " sollte ein SWIFT-Envelope haben"
            );

            $message = SwiftMessageParser::parse($content);
            $this->assertInstanceOf(Message::class, $message, "Datei " . basename($file));
            $this->assertNotEmpty($message->getTextBlock(), "Datei " . basename($file) . " sollte einen Text-Block haben");
        }
    }

    #[DataProvider('swiftSampleFilesProvider')]
    public function testSwiftSampleFile(string $filename, MtType $expectedType): void {
        $content = file_get_contents(self::SAMPLE_DIR . $filename);
        $message = SwiftMessageParser::parse($content);

        $this->assertEquals($expectedType, $message->getMessageType());
    }

    /**
     * @return array<string, array{string, MtType}>
     */
    public static function swiftSampleFilesProvider(): array {
        return [
            'envelope.mt103' => ['envelope.mt103', MtType::MT103],
            'envelope.mt900' => ['envelope.mt900', MtType::MT900],
            'envelope.mt940' => ['envelope.mt940', MtType::MT940],
            'itemized.mt940' => ['itemized.mt940', MtType::MT940],
            'nonitemized.mt101' => ['nonitemized.mt101', MtType::MT101],
            'nonitemized.mt103' => ['nonitemized.mt103', MtType::MT103],
            'nonitemized.mt900' => ['nonitemized.mt900', MtType::MT900],
            'nonitemized.mt910' => ['nonitemized.mt910', MtType::MT910],
            'nonitemized.mt942' => ['nonitemized.mt942', MtType::MT942],
            'nonitemzed.mt940' => ['nonitemzed.mt940', MtType::MT940],
        ];
    }
}
