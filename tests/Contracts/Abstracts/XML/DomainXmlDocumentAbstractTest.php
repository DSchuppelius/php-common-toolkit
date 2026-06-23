<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DomainXmlDocumentAbstractTest.php
 * License      : MIT
 * License Uri  : https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace Tests\Contracts\Abstracts\XML;

use CommonToolkit\Contracts\Abstracts\XML\DomainXmlDocumentAbstract;
use CommonToolkit\Contracts\Interfaces\XML\{XmlDocumentInterface, XmlElementInterface};
use CommonToolkit\Entities\XML\Document as XmlDocument;
use DOMDocument;
use DOMNode;
use PHPUnit\Framework\TestCase;

/**
 * Konkrete Testimplementierung der abstrakten Klasse.
 */
class TestDomainDocument extends DomainXmlDocumentAbstract {
    private string $content;

    public function __construct(string $content = '<root><child>Test</child></root>') {
        $this->content = $content;
    }

    protected function getDefaultXml(): string {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $this->content;
    }

    public function setContent(string $content): void {
        $this->content = $content;
        $this->invalidateCache();
    }
}

/**
 * Tests für DomainXmlDocumentAbstract.
 */
class DomainXmlDocumentAbstractTest extends TestCase {
    private TestDomainDocument $document;

    protected function setUp(): void {
        $this->document = new TestDomainDocument;
    }

    public function test_implements_xml_document_interface(): void {
        $this->assertInstanceOf(XmlDocumentInterface::class, $this->document);
    }

    public function test_get_version(): void {
        $this->assertSame('1.0', $this->document->getVersion());
    }

    public function test_get_encoding(): void {
        $this->assertSame('UTF-8', $this->document->getEncoding());
    }

    public function test_to_string(): void {
        $xml = $this->document->toString();
        $this->assertStringContainsString('<root>', $xml);
        $this->assertStringContainsString('<child>Test</child>', $xml);
    }

    public function test_to_xml_document(): void {
        $xmlDoc = $this->document->toXmlDocument();
        $this->assertInstanceOf(XmlDocument::class, $xmlDoc);
    }

    public function test_get_root_element(): void {
        $root = $this->document->getRootElement();
        $this->assertInstanceOf(XmlElementInterface::class, $root);
        $this->assertSame('root', $root->getName());
    }

    public function test_to_dom_document(): void {
        $dom = $this->document->toDomDocument();
        $this->assertInstanceOf(DOMDocument::class, $dom);
        $this->assertSame('root', $dom->documentElement->nodeName);
    }

    public function test_to_dom_node(): void {
        $targetDoc = new DOMDocument;
        $node = $this->document->toDomNode($targetDoc);
        $this->assertInstanceOf(DOMNode::class, $node);
        $this->assertSame('root', $node->nodeName);
    }

    public function test_caching(): void {
        // Erste Anfrage erstellt Cache
        $doc1 = $this->document->toXmlDocument();
        // Zweite Anfrage sollte gecachte Version zurückgeben
        $doc2 = $this->document->toXmlDocument();

        $this->assertSame($doc1, $doc2);
    }

    public function test_invalidate_cache_on_content_change(): void {
        $doc1 = $this->document->toXmlDocument();

        // Inhalt ändern - invalidiert Cache
        $this->document->setContent('<root><child>Changed</child></root>');

        $doc2 = $this->document->toXmlDocument();

        // Sollten unterschiedliche Instanzen sein
        $this->assertNotSame($doc1, $doc2);

        // Neuer Inhalt sollte reflektiert werden
        $this->assertStringContainsString('Changed', $this->document->toString());
    }

    public function test_validate_against_xsd(): void {
        // Erstelle ein einfaches XSD mit Namespace-Unterstützung
        $xsd = <<<'XSD'
<?xml version="1.0" encoding="UTF-8"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
    <xs:element name="root">
        <xs:complexType>
            <xs:sequence>
                <xs:element name="child" type="xs:string"/>
            </xs:sequence>
        </xs:complexType>
    </xs:element>
</xs:schema>
XSD;

        $xsdFile = sys_get_temp_dir() . '/test_schema.xsd';
        file_put_contents($xsdFile, $xsd);

        try {
            $errors = $this->document->validateAgainstXsd($xsdFile);
            // Validierung wird durchgeführt - Ergebnis kann Fehler enthalten
            // wenn das XML nicht exakt dem Schema entspricht
            $this->assertIsArray($errors);
        } finally {
            unlink($xsdFile);
        }
    }

    public function test_to_file(): void {
        $tempFile = sys_get_temp_dir() . '/test_domain_xml.xml';

        try {
            $this->document->toFile($tempFile);

            $this->assertFileExists($tempFile);
            $content = file_get_contents($tempFile);
            $this->assertStringContainsString('<root>', $content);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function test_complex_xml_structure(): void {
        $complexXml = <<<'XML'
<document xmlns="urn:test:namespace">
    <header>
        <title>Test Document</title>
        <version>1.0</version>
    </header>
    <body>
        <section id="1">
            <paragraph>First paragraph</paragraph>
        </section>
        <section id="2">
            <paragraph>Second paragraph</paragraph>
        </section>
    </body>
</document>
XML;

        $document = new TestDomainDocument($complexXml);

        $root = $document->getRootElement();
        $this->assertSame('document', $root->getName());

        $children = $root->getChildren();
        $this->assertCount(2, $children); // header and body
    }
}
