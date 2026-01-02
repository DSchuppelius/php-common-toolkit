<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ElementTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Entities\XML;

use CommonToolkit\Entities\XML\Attribute;
use CommonToolkit\Entities\XML\Element;
use PHPUnit\Framework\TestCase;

class ElementTest extends TestCase {

    public function testSimpleElement(): void {
        $element = Element::simple('name', 'value');

        $this->assertSame('name', $element->getName());
        $this->assertSame('value', $element->getValue());
        $this->assertNull($element->getNamespaceUri());
        $this->assertNull($element->getPrefix());
        $this->assertEmpty($element->getAttributes());
        $this->assertEmpty($element->getChildren());
    }

    public function testElementWithNamespace(): void {
        $element = Element::simple('name', 'value', 'http://example.com', 'ex');

        $this->assertSame('http://example.com', $element->getNamespaceUri());
        $this->assertSame('ex', $element->getPrefix());
        $this->assertSame('ex:name', $element->getQualifiedName());
    }

    public function testElementWithAttributes(): void {
        $attr = new Attribute('id', '123');
        $element = new Element('item', null, null, null, [$attr]);

        $this->assertCount(1, $element->getAttributes());
        $this->assertSame('123', $element->getAttributeValue('id'));
        $this->assertTrue($element->hasAttribute('id'));
        $this->assertFalse($element->hasAttribute('unknown'));
    }

    public function testElementWithChildren(): void {
        $child1 = Element::simple('child1', 'value1');
        $child2 = Element::simple('child2', 'value2');
        $child3 = Element::simple('child1', 'value3');

        $parent = Element::withChildElements('parent', [$child1, $child2, $child3]);

        $this->assertCount(3, $parent->getChildren());
        $this->assertTrue($parent->hasChild('child1'));
        $this->assertTrue($parent->hasChild('child2'));
        $this->assertFalse($parent->hasChild('unknown'));

        // Erste Kinder nach Name
        $first = $parent->getFirstChildByName('child1');
        $this->assertNotNull($first);
        $this->assertSame('value1', $first->getValue());

        // Alle Kinder mit gleichem Namen
        $children = $parent->getChildrenByName('child1');
        $this->assertCount(2, $children);
    }

    public function testGetChildValue(): void {
        $child = Element::simple('name', 'John');
        $parent = Element::withChildElements('person', [$child]);

        $this->assertSame('John', $parent->getChildValue('name'));
        $this->assertNull($parent->getChildValue('unknown'));
        $this->assertSame('default', $parent->getChildValue('unknown', 'default'));
    }

    public function testWithMethods(): void {
        $element = Element::simple('name', 'value');

        // withValue
        $newElement = $element->withValue('newValue');
        $this->assertSame('value', $element->getValue()); // Original unverändert
        $this->assertSame('newValue', $newElement->getValue());

        // withAttribute
        $attr = new Attribute('id', '1');
        $withAttr = $element->withAttribute($attr);
        $this->assertEmpty($element->getAttributes());
        $this->assertCount(1, $withAttr->getAttributes());

        // withChild
        $child = Element::simple('child', 'test');
        $withChild = $element->withChild($child);
        $this->assertEmpty($element->getChildren());
        $this->assertCount(1, $withChild->getChildren());

        // withNamespace
        $withNs = $element->withNamespace('http://example.com', 'ex');
        $this->assertNull($element->getNamespaceUri());
        $this->assertSame('http://example.com', $withNs->getNamespaceUri());
    }

    public function testToString(): void {
        $element = Element::simple('greeting', 'Hello');
        $xml = $element->toString();

        $this->assertStringContainsString('<greeting>', $xml);
        $this->assertStringContainsString('Hello', $xml);
        $this->assertStringContainsString('</greeting>', $xml);
    }

    public function testToStringWithNamespace(): void {
        $element = Element::simple('greeting', 'Hello', 'http://example.com', 'ex');
        $xml = $element->toString();

        $this->assertStringContainsString('ex:greeting', $xml);
        $this->assertStringContainsString('xmlns:ex="http://example.com"', $xml);
    }

    public function testFromDomElement(): void {
        $doc = new \DOMDocument();
        $doc->loadXML('<root attr="value"><child>text</child></root>');

        $element = Element::fromDomElement($doc->documentElement);

        $this->assertSame('root', $element->getName());
        $this->assertCount(1, $element->getAttributes());
        $this->assertSame('value', $element->getAttributeValue('attr'));
        $this->assertCount(1, $element->getChildren());

        $child = $element->getFirstChildByName('child');
        $this->assertNotNull($child);
        $this->assertSame('text', $child->getValue());
    }

    public function testCountMethods(): void {
        $children = [
            Element::simple('item', 'a'),
            Element::simple('item', 'b'),
            Element::simple('other', 'c'),
        ];
        $parent = Element::withChildElements('list', $children);

        $this->assertSame(3, $parent->countChildren());
        $this->assertSame(2, $parent->countChildrenByName('item'));
        $this->assertSame(1, $parent->countChildrenByName('other'));
        $this->assertSame(0, $parent->countChildrenByName('unknown'));
    }
}
