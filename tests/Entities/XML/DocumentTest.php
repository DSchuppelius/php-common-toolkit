<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DocumentTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Entities\XML;

use CommonToolkit\Entities\XML\Document;
use CommonToolkit\Entities\XML\Element;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class DocumentTest extends TestCase {

    public function testCreateDocument(): void {
        $root = Element::simple('root', 'content');
        $doc = new Document($root);

        $this->assertSame('1.0', $doc->getVersion());
        $this->assertSame('UTF-8', $doc->getEncoding());
        $this->assertSame('root', $doc->getRootElementName());
        $this->assertNull($doc->getNamespace());
    }

    public function testCreateWithNamespace(): void {
        $root = Element::simple('root', null, 'http://example.com', 'ex');
        $doc = new Document($root);

        $this->assertSame('http://example.com', $doc->getNamespace());
    }

    public function testToString(): void {
        $root = Element::simple('greeting', 'Hello World');
        $doc = new Document($root);

        $xml = $doc->toString();

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<greeting>Hello World</greeting>', $xml);
    }

    public function testFromString(): void {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><root><child>value</child></root>';
        $doc = Document::fromString($xml);

        $this->assertSame('root', $doc->getRootElementName());

        $root = $doc->getRootElement();
        $this->assertTrue($root->hasChild('child'));
        $this->assertSame('value', $root->getChildValue('child'));
    }

    public function testFromStringInvalid(): void {
        $this->expectException(InvalidArgumentException::class);
        Document::fromString('<invalid><xml>');
    }

    public function testFromStringNoRoot(): void {
        $this->expectException(InvalidArgumentException::class);
        Document::fromString('<?xml version="1.0"?>');
    }

    public function testToDomDocument(): void {
        $root = Element::simple('root');
        $doc = new Document($root);

        $dom = $doc->toDomDocument();

        $this->assertInstanceOf(\DOMDocument::class, $dom);
        $this->assertSame('root', $dom->documentElement->tagName);
    }

    public function testIsWellFormed(): void {
        $root = Element::simple('root');
        $doc = new Document($root);

        $this->assertTrue($doc->isWellFormed());
    }

    public function testWithMethods(): void {
        $root = Element::simple('root');
        $doc = new Document($root);

        // withEncoding
        $newDoc = $doc->withEncoding('ISO-8859-1');
        $this->assertSame('UTF-8', $doc->getEncoding()); // Original unverändert
        $this->assertSame('ISO-8859-1', $newDoc->getEncoding());

        // withFormatOutput
        $compact = $doc->withFormatOutput(false);
        $this->assertNotSame($doc, $compact);

        // withRootElement
        $newRoot = Element::simple('newRoot');
        $withNewRoot = $doc->withRootElement($newRoot);
        $this->assertSame('root', $doc->getRootElementName());
        $this->assertSame('newRoot', $withNewRoot->getRootElementName());
    }

    public function testCreate(): void {
        $doc = Document::create('root');

        $this->assertSame('root', $doc->getRootElementName());
        $this->assertNull($doc->getNamespace());
    }

    public function testCreateWithNamespaceFactory(): void {
        $doc = Document::create('root', 'http://example.com', 'ex');

        $this->assertSame('root', $doc->getRootElementName());
        $this->assertSame('http://example.com', $doc->getNamespace());
    }

    public function testComplexDocument(): void {
        $children = [
            Element::simple('name', 'John'),
            Element::simple('age', '30'),
            Element::withChildElements('address', [
                Element::simple('city', 'Berlin'),
                Element::simple('country', 'Germany'),
            ]),
        ];

        $root = Element::withChildElements('person', $children);
        $doc = new Document($root);

        $xml = $doc->toString();

        $this->assertStringContainsString('<person>', $xml);
        $this->assertStringContainsString('<name>John</name>', $xml);
        $this->assertStringContainsString('<age>30</age>', $xml);
        $this->assertStringContainsString('<address>', $xml);
        $this->assertStringContainsString('<city>Berlin</city>', $xml);
    }

    public function testRoundTrip(): void {
        $originalXml = '<?xml version="1.0" encoding="UTF-8"?>
<root>
  <item id="1">First</item>
  <item id="2">Second</item>
</root>';

        $doc = Document::fromString($originalXml);
        $regenerated = $doc->toString();

        // Parse again
        $doc2 = Document::fromString($regenerated);

        $this->assertSame($doc->getRootElementName(), $doc2->getRootElementName());

        $items1 = $doc->getRootElement()->getChildrenByName('item');
        $items2 = $doc2->getRootElement()->getChildrenByName('item');

        $this->assertCount(2, $items1);
        $this->assertCount(2, $items2);
    }
}
