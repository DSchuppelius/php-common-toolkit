<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XmlDocumentBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders;

use CommonToolkit\Builders\XmlDocumentBuilder;
use CommonToolkit\Entities\XML\Document;
use CommonToolkit\Entities\XML\Element;
use PHPUnit\Framework\TestCase;

class XmlDocumentBuilderTest extends TestCase {

    public function testSimpleDocument(): void {
        $doc = XmlDocumentBuilder::create('root')
            ->build();

        $this->assertInstanceOf(Document::class, $doc);
        $this->assertSame('root', $doc->getRootElementName());
    }

    public function testWithNamespace(): void {
        $doc = XmlDocumentBuilder::create('root')
            ->withNamespace('http://example.com', 'ex')
            ->build();

        $this->assertSame('http://example.com', $doc->getNamespace());
    }

    public function testWithVersionAndEncoding(): void {
        $doc = XmlDocumentBuilder::create('root')
            ->withVersion('1.1')
            ->withEncoding('ISO-8859-1')
            ->build();

        $this->assertSame('1.1', $doc->getVersion());
        $this->assertSame('ISO-8859-1', $doc->getEncoding());
    }

    public function testAddAttribute(): void {
        $doc = XmlDocumentBuilder::create('root')
            ->addAttribute('version', '1.0')
            ->addAttribute('id', '123')
            ->build();

        $root = $doc->getRootElement();
        $this->assertSame('1.0', $root->getAttributeValue('version'));
        $this->assertSame('123', $root->getAttributeValue('id'));
    }

    public function testAddChild(): void {
        $doc = XmlDocumentBuilder::create('person')
            ->addChild('name', 'John')
            ->addChild('age', '30')
            ->build();

        $root = $doc->getRootElement();
        $this->assertSame('John', $root->getChildValue('name'));
        $this->assertSame('30', $root->getChildValue('age'));
    }

    public function testAddElement(): void {
        $child = Element::simple('custom', 'value');

        $doc = XmlDocumentBuilder::create('root')
            ->addElement($child)
            ->build();

        $root = $doc->getRootElement();
        $this->assertTrue($root->hasChild('custom'));
    }

    public function testAddElements(): void {
        $elements = [
            Element::simple('a', '1'),
            Element::simple('b', '2'),
            Element::simple('c', '3'),
        ];

        $doc = XmlDocumentBuilder::create('root')
            ->addElements($elements)
            ->build();

        $root = $doc->getRootElement();
        $this->assertCount(3, $root->getChildren());
    }

    public function testStartElement(): void {
        $doc = XmlDocumentBuilder::create('root')
            ->startElement('container')
            ->addChild('item', 'value')
            ->addAttribute('id', '1')
            ->end()
            ->build();

        $root = $doc->getRootElement();
        $container = $root->getFirstChildByName('container');

        $this->assertNotNull($container);
        $this->assertSame('1', $container->getAttributeValue('id'));
        $this->assertSame('value', $container->getChildValue('item'));
    }

    public function testToString(): void {
        $xml = XmlDocumentBuilder::create('greeting')
            ->addChild('message', 'Hello World')
            ->toString();

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
$this->assertStringContainsString('<greeting>', $xml);
    $this->assertStringContainsString('<message>Hello World</message>', $xml);
    }

    public function testComplexDocument(): void {
    $doc = XmlDocumentBuilder::create('Document')
    ->withNamespace('urn:iso:std:iso:20022:tech:xsd:pain.001.003.03', 'pain')
    ->startElement('CstmrCdtTrfInitn')
    ->startElement('GrpHdr')
    ->addChild('MsgId', 'MSG-001')
    ->addChild('CreDtTm', '2026-01-01T12:00:00')
    ->end()
    ->end()
    ->build();

    $xml = $doc->toString();

    $this->assertStringContainsString('pain:Document', $xml);
    $this->assertStringContainsString('urn:iso:std:iso:20022:tech:xsd:pain.001.003.03', $xml);
    $this->assertStringContainsString('MSG-001', $xml);
    }

    public function testChainedBuilding(): void {
    $xml = XmlDocumentBuilder::create('config')
    ->addAttribute('version', '1.0')
    ->addChild('setting1', 'value1')
    ->addChild('setting2', 'value2')
    ->startElement('nested')
    ->addChild('deep', 'content')
    ->end()
    ->addChild('setting3', 'value3')
    ->toString();

    $doc = Document::fromString($xml);
    $root = $doc->getRootElement();

    $this->assertCount(4, $root->getChildren());
    $this->assertSame('value1', $root->getChildValue('setting1'));
    $this->assertSame('value3', $root->getChildValue('setting3'));

    $nested = $root->getFirstChildByName('nested');
    $this->assertNotNull($nested);
    $this->assertSame('content', $nested->getChildValue('deep'));
    }

    public function testWithFormatOutput(): void {
    $formatted = XmlDocumentBuilder::create('root')
    ->withFormatOutput(true)
    ->addChild('child', 'value')
    ->toString();

    $compact = XmlDocumentBuilder::create('root')
    ->withFormatOutput(false)
    ->addChild('child', 'value')
    ->toString();

    // Formatiert sollte Whitespace/Newlines haben
    $this->assertGreaterThan(strlen($compact), strlen($formatted));
    }
    }