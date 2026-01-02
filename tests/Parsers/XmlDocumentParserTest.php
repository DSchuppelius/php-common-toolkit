<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XmlDocumentParserTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Parsers;

use CommonToolkit\Entities\XML\Document;
use CommonToolkit\Entities\XML\Element;
use CommonToolkit\Parsers\XmlDocumentParser;
use PHPUnit\Framework\TestCase;

class XmlDocumentParserTest extends TestCase {
    private Document $document;

    protected function setUp(): void {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<root xmlns="http://example.com" xmlns:custom="http://custom.example.com">
    <person id="1">
        <name>John Doe</name>
        <age>30</age>
    </person>
    <person id="2">
        <name>Jane Doe</name>
        <age>25</age>
    </person>
    <items>
        <item>A</item>
        <item>B</item>
        <item>C</item>
    </items>
</root>';

        $this->document = Document::fromString($xml);
    }

    public function testFromString(): void {
        $xml = '<test>value</test>';
        $doc = XmlDocumentParser::fromString($xml);

        $this->assertInstanceOf(Document::class, $doc);
        $this->assertSame('test', $doc->getRootElementName());
    }

    public function testXpath(): void {
        $elements = XmlDocumentParser::xpath(
            $this->document,
            '//*[local-name()="person"]'
        );

        $this->assertCount(2, $elements);
        $this->assertContainsOnlyInstancesOf(Element::class, $elements);
    }

    public function testXpathWithNamespace(): void {
        $elements = XmlDocumentParser::xpath(
            $this->document,
            '//ns:person',
            ['ns' => 'http://example.com']
        );

        $this->assertCount(2, $elements);
    }

    public function testXpathFirst(): void {
        $element = XmlDocumentParser::xpathFirst(
            $this->document,
            '//*[local-name()="person"]'
        );

        $this->assertInstanceOf(Element::class, $element);
        $this->assertSame('person', $element->getName());
        $this->assertSame('1', $element->getAttributeValue('id'));
    }

    public function testXpathFirstNull(): void {
        $element = XmlDocumentParser::xpathFirst(
            $this->document,
            '//*[local-name()="unknown"]'
        );

        $this->assertNull($element);
    }

    public function testXpathValues(): void {
        $values = XmlDocumentParser::xpathValues(
            $this->document,
            '//*[local-name()="item"]'
        );

        $this->assertCount(3, $values);
        $this->assertSame(['A', 'B', 'C'], $values);
    }

    public function testXpathValue(): void {
        $value = XmlDocumentParser::xpathValue(
            $this->document,
            '//*[local-name()="name"][1]'
        );

        $this->assertSame('John Doe', $value);
    }

    public function testXpathValueDefault(): void {
        $value = XmlDocumentParser::xpathValue(
            $this->document,
            '//*[local-name()="unknown"]',
            [],
            'default'
        );

        $this->assertSame('default', $value);
    }

    public function testGetMetadata(): void {
        $meta = XmlDocumentParser::getMetadata($this->document);

        $this->assertSame('root', $meta['rootElement']);
        $this->assertSame('http://example.com', $meta['namespace']);
        $this->assertSame('UTF-8', $meta['encoding']);
        $this->assertSame('1.0', $meta['version']);
        $this->assertSame(3, $meta['childCount']);
    }

    public function testExtractNamespaces(): void {
        $namespaces = XmlDocumentParser::extractNamespaces($this->document);

        // Namespace-Extraktion gibt alle deklarierten Namespaces zurück
        $this->assertNotEmpty($namespaces);
        // Der Default-Namespace hat einen leeren Prefix oder 'xmlns'
        $this->assertTrue(
            in_array('http://example.com', $namespaces) ||
                in_array('http://custom.example.com', $namespaces)
        );
    }

    public function testCount(): void {
        $count = XmlDocumentParser::count(
            $this->document,
            '//*[local-name()="item"]'
        );

        $this->assertSame(3, $count);
    }

    public function testExists(): void {
        $this->assertTrue(XmlDocumentParser::exists(
            $this->document,
            '//*[local-name()="person"]'
        ));

        $this->assertFalse(XmlDocumentParser::exists(
            $this->document,
            '//*[local-name()="unknown"]'
        ));
    }

    public function testToArray(): void {
        $simpleDoc = Document::fromString('<root><name>Test</name><value>123</value></root>');
        $array = XmlDocumentParser::toArray($simpleDoc);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('value', $array);
    }

    public function testToArrayWithAttributes(): void {
        $doc = Document::fromString('<root id="1"><child attr="value">text</child></root>');
        $array = XmlDocumentParser::toArray($doc, true, false);

        $this->assertArrayHasKey('@attributes', $array);
        $this->assertSame('1', $array['@attributes']['id']);
    }
}
