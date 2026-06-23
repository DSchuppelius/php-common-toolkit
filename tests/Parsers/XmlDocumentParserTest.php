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

use CommonToolkit\Entities\XML\{Document, Element};
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

    public function test_from_string(): void {
        $xml = '<test>value</test>';
        $doc = XmlDocumentParser::fromString($xml);

        $this->assertInstanceOf(Document::class, $doc);
        $this->assertSame('test', $doc->getRootElementName());
    }

    public function test_xpath(): void {
        $elements = XmlDocumentParser::xpath(
            $this->document,
            '//*[local-name()="person"]'
        );

        $this->assertCount(2, $elements);
        $this->assertContainsOnlyInstancesOf(Element::class, $elements);
    }

    public function test_xpath_with_namespace(): void {
        $elements = XmlDocumentParser::xpath(
            $this->document,
            '//ns:person',
            ['ns' => 'http://example.com']
        );

        $this->assertCount(2, $elements);
    }

    public function test_xpath_first(): void {
        $element = XmlDocumentParser::xpathFirst(
            $this->document,
            '//*[local-name()="person"]'
        );

        $this->assertInstanceOf(Element::class, $element);
        $this->assertSame('person', $element->getName());
        $this->assertSame('1', $element->getAttributeValue('id'));
    }

    public function test_xpath_first_null(): void {
        $element = XmlDocumentParser::xpathFirst(
            $this->document,
            '//*[local-name()="unknown"]'
        );

        $this->assertNull($element);
    }

    public function test_xpath_values(): void {
        $values = XmlDocumentParser::xpathValues(
            $this->document,
            '//*[local-name()="item"]'
        );

        $this->assertCount(3, $values);
        $this->assertSame(['A', 'B', 'C'], $values);
    }

    public function test_xpath_value(): void {
        $value = XmlDocumentParser::xpathValue(
            $this->document,
            '//*[local-name()="name"][1]'
        );

        $this->assertSame('John Doe', $value);
    }

    public function test_xpath_value_default(): void {
        $value = XmlDocumentParser::xpathValue(
            $this->document,
            '//*[local-name()="unknown"]',
            [],
            'default'
        );

        $this->assertSame('default', $value);
    }

    public function test_get_metadata(): void {
        $meta = XmlDocumentParser::getMetadata($this->document);

        $this->assertSame('root', $meta['rootElement']);
        $this->assertSame('http://example.com', $meta['namespace']);
        $this->assertSame('UTF-8', $meta['encoding']);
        $this->assertSame('1.0', $meta['version']);
        $this->assertSame(3, $meta['childCount']);
    }

    public function test_extract_namespaces(): void {
        $namespaces = XmlDocumentParser::extractNamespaces($this->document);

        // Namespace-Extraktion gibt alle deklarierten Namespaces zurück
        $this->assertNotEmpty($namespaces);
        // Der Default-Namespace hat einen leeren Prefix oder 'xmlns'
        $this->assertTrue(
            in_array('http://example.com', $namespaces) ||
                in_array('http://custom.example.com', $namespaces)
        );
    }

    public function test_count(): void {
        $count = XmlDocumentParser::count(
            $this->document,
            '//*[local-name()="item"]'
        );

        $this->assertSame(3, $count);
    }

    public function test_exists(): void {
        $this->assertTrue(XmlDocumentParser::exists(
            $this->document,
            '//*[local-name()="person"]'
        ));

        $this->assertFalse(XmlDocumentParser::exists(
            $this->document,
            '//*[local-name()="unknown"]'
        ));
    }

    public function test_to_array(): void {
        $simpleDoc = Document::fromString('<root><name>Test</name><value>123</value></root>');
        $array = XmlDocumentParser::toArray($simpleDoc);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('value', $array);
    }

    public function test_to_array_with_attributes(): void {
        $doc = Document::fromString('<root id="1"><child attr="value">text</child></root>');
        $array = XmlDocumentParser::toArray($doc, true, false);

        $this->assertArrayHasKey('@attributes', $array);
        $this->assertSame('1', $array['@attributes']['id']);
    }
}
