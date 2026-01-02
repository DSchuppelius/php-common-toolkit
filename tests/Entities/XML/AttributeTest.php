<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AttributeTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Entities\XML;

use CommonToolkit\Entities\XML\Attribute;
use PHPUnit\Framework\TestCase;

class AttributeTest extends TestCase {

    public function testSimpleAttribute(): void {
        $attr = new Attribute('name', 'value');

        $this->assertSame('name', $attr->getName());
        $this->assertSame('value', $attr->getValue());
        $this->assertNull($attr->getNamespaceUri());
        $this->assertNull($attr->getPrefix());
        $this->assertSame('name', $attr->getQualifiedName());
    }

    public function testAttributeWithNamespace(): void {
        $attr = new Attribute('id', '123', 'http://example.com', 'ex');

        $this->assertSame('id', $attr->getName());
        $this->assertSame('123', $attr->getValue());
        $this->assertSame('http://example.com', $attr->getNamespaceUri());
        $this->assertSame('ex', $attr->getPrefix());
        $this->assertSame('ex:id', $attr->getQualifiedName());
    }

    public function testWithValue(): void {
        $attr = new Attribute('name', 'old');
        $newAttr = $attr->withValue('new');

        // Original unverändert
        $this->assertSame('old', $attr->getValue());

        // Neue Instanz
        $this->assertSame('new', $newAttr->getValue());
        $this->assertSame('name', $newAttr->getName());
    }

    public function testFromDomAttr(): void {
        $doc = new \DOMDocument();
        $doc->loadXML('<root xmlns:ex="http://example.com" ex:id="123" name="test"/>');

        // Normales Attribut
        $nameAttr = $doc->documentElement->getAttributeNode('name');
        if ($nameAttr !== null) {
            $attr = Attribute::fromDomAttr($nameAttr);
            $this->assertSame('name', $attr->getName());
            $this->assertSame('test', $attr->getValue());
        }
    }

    public function testEmptyValue(): void {
        $attr = new Attribute('empty', '');

        $this->assertSame('', $attr->getValue());
    }

    public function testSpecialCharacters(): void {
        $attr = new Attribute('special', 'Müller & Co. <GmbH>');

        $this->assertSame('Müller & Co. <GmbH>', $attr->getValue());
    }
}
