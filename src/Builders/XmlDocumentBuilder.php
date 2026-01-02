<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XmlDocumentBuilder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Builders;

use CommonToolkit\Contracts\Interfaces\XML\XmlElementInterface;
use CommonToolkit\Entities\XML\Attribute;
use CommonToolkit\Entities\XML\Document;
use CommonToolkit\Entities\XML\Element;

/**
 * Fluent Builder für XML-Dokumente.
 * 
 * Ermöglicht den strukturierten Aufbau von XML-Dokumenten
 * mit einer intuitiven API.
 * 
 * Beispiel:
 * ```php
 * $doc = XmlDocumentBuilder::create('root')
 *     ->withNamespace('http://example.com', 'ex')
 *     ->addChild('child1', 'value1')
 *     ->addChild('child2')
 *         ->addChild('grandchild', 'value2')
 *         ->end()
 *     ->build();
 * ```
 * 
 * @package CommonToolkit\Builders
 */
class XmlDocumentBuilder {
    private string $rootName;
    private ?string $namespaceUri = null;
    private ?string $prefix = null;
    private string $version = '1.0';
    private string $encoding = 'UTF-8';
    private bool $formatOutput = true;

    /** @var array<string, string> */
    private array $rootAttributes = [];

    /** @var XmlElementInterface[] */
    private array $children = [];

    /** @var XmlElementBuilder[] Stack für verschachtelte Builder */
    private array $builderStack = [];

    private function __construct(string $rootName) {
        $this->rootName = $rootName;
    }

    /**
     * Erstellt einen neuen Builder.
     */
    public static function create(string $rootName): self {
        return new self($rootName);
    }

    /**
     * Setzt den Namespace für das Root-Element.
     */
    public function withNamespace(string $namespaceUri, ?string $prefix = null): self {
        $this->namespaceUri = $namespaceUri;
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Setzt die XML-Version.
     */
    public function withVersion(string $version): self {
        $this->version = $version;
        return $this;
    }

    /**
     * Setzt das Encoding.
     */
    public function withEncoding(string $encoding): self {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * Aktiviert/deaktiviert Pretty-Print.
     */
    public function withFormatOutput(bool $formatOutput): self {
        $this->formatOutput = $formatOutput;
        return $this;
    }

    /**
     * Fügt ein Attribut zum Root-Element hinzu.
     */
    public function addAttribute(string $name, string $value): self {
        $this->rootAttributes[$name] = $value;
        return $this;
    }

    /**
     * Fügt ein einfaches Kind-Element hinzu.
     */
    public function addChild(string $name, ?string $value = null): self {
        $this->children[] = Element::simple($name, $value, $this->namespaceUri, $this->prefix);
        return $this;
    }

    /**
     * Fügt ein Kind-Element mit eigenem Namespace hinzu.
     */
    public function addChildNS(string $name, ?string $value, string $namespaceUri, ?string $prefix = null): self {
        $this->children[] = Element::simple($name, $value, $namespaceUri, $prefix);
        return $this;
    }

    /**
     * Fügt ein bereits erstelltes Element hinzu.
     */
    public function addElement(XmlElementInterface $element): self {
        $this->children[] = $element;
        return $this;
    }

    /**
     * Fügt mehrere Elemente hinzu.
     * 
     * @param XmlElementInterface[] $elements
     */
    public function addElements(array $elements): self {
        foreach ($elements as $element) {
            $this->children[] = $element;
        }
        return $this;
    }

    /**
     * Startet einen verschachtelten Element-Builder.
     */
    public function startElement(string $name): XmlElementBuilder {
        $builder = new XmlElementBuilder($name, $this->namespaceUri, $this->prefix, $this);
        $this->builderStack[] = $builder;
        return $builder;
    }

    /**
     * Wird vom Element-Builder aufgerufen, wenn end() aufgerufen wird.
     * @internal
     */
    public function endElement(XmlElementInterface $element): self {
        array_pop($this->builderStack);
        $this->children[] = $element;
        return $this;
    }

    /**
     * Baut das XML-Dokument.
     */
    public function build(): Document {
        $attributes = [];
        foreach ($this->rootAttributes as $name => $value) {
            $attributes[] = new Attribute($name, $value);
        }

        $rootElement = new Element(
            $this->rootName,
            null,
            $this->namespaceUri,
            $this->prefix,
            $attributes,
            $this->children
        );

        return new Document($rootElement, $this->version, $this->encoding, $this->formatOutput);
    }

    /**
     * Baut und gibt den XML-String zurück.
     */
    public function toString(): string {
        return $this->build()->toString();
    }

    /**
     * Baut und speichert in eine Datei.
     */
    public function toFile(string $filePath): void {
        $this->build()->toFile($filePath);
    }
}

/**
 * Builder für verschachtelte XML-Elemente.
 * 
 * @package CommonToolkit\Builders
 */
class XmlElementBuilder {
    private string $name;
    private ?string $value = null;
    private ?string $namespaceUri;
    private ?string $prefix;
    private XmlDocumentBuilder $parent;

    /** @var array<string, string> */
    private array $attributes = [];

    /** @var XmlElementInterface[] */
    private array $children = [];

    /**
     * @internal Nur von XmlDocumentBuilder erstellen
     */
    public function __construct(
        string $name,
        ?string $namespaceUri,
        ?string $prefix,
        XmlDocumentBuilder $parent
    ) {
        $this->name = $name;
        $this->namespaceUri = $namespaceUri;
        $this->prefix = $prefix;
        $this->parent = $parent;
    }

    /**
     * Setzt den Textinhalt.
     */
    public function withValue(?string $value): self {
        $this->value = $value;
        return $this;
    }

    /**
     * Fügt ein Attribut hinzu.
     */
    public function addAttribute(string $name, string $value): self {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Fügt ein Kind-Element hinzu.
     */
    public function addChild(string $name, ?string $value = null): self {
        $this->children[] = Element::simple($name, $value, $this->namespaceUri, $this->prefix);
        return $this;
    }

    /**
     * Fügt ein bereits erstelltes Element hinzu.
     */
    public function addElement(XmlElementInterface $element): self {
        $this->children[] = $element;
        return $this;
    }

    /**
     * Startet einen verschachtelten Element-Builder.
     */
    public function startElement(string $name): XmlNestedElementBuilder {
        return new XmlNestedElementBuilder($name, $this->namespaceUri, $this->prefix, $this);
    }

    /**
     * Wird vom verschachtelten Element-Builder aufgerufen.
     * @internal
     */
    public function addBuiltElement(XmlElementInterface $element): self {
        $this->children[] = $element;
        return $this;
    }

    /**
     * Beendet diesen Builder und kehrt zum Dokument-Builder zurück.
     */
    public function end(): XmlDocumentBuilder {
        $attributes = [];
        foreach ($this->attributes as $name => $value) {
            $attributes[] = new Attribute($name, $value);
        }

        $element = new Element(
            $this->name,
            $this->value,
            $this->namespaceUri,
            $this->prefix,
            $attributes,
            $this->children
        );

        return $this->parent->endElement($element);
    }
}

/**
 * Builder für tiefere Verschachtelungen in XmlElementBuilder.
 * 
 * @package CommonToolkit\Builders
 */
class XmlNestedElementBuilder {
    private string $name;
    private ?string $value = null;
    private ?string $namespaceUri;
    private ?string $prefix;
    private XmlElementBuilder $parent;

    /** @var array<string, string> */
    private array $attributes = [];

    /** @var XmlElementInterface[] */
    private array $children = [];

    /**
     * @internal
     */
    public function __construct(
        string $name,
        ?string $namespaceUri,
        ?string $prefix,
        XmlElementBuilder $parent
    ) {
        $this->name = $name;
        $this->namespaceUri = $namespaceUri;
        $this->prefix = $prefix;
        $this->parent = $parent;
    }

    /**
     * Setzt den Textinhalt.
     */
    public function withValue(?string $value): self {
        $this->value = $value;
        return $this;
    }

    /**
     * Fügt ein Attribut hinzu.
     */
    public function addAttribute(string $name, string $value): self {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Fügt ein Kind-Element hinzu.
     */
    public function addChild(string $name, ?string $value = null): self {
        $this->children[] = Element::simple($name, $value, $this->namespaceUri, $this->prefix);
        return $this;
    }

    /**
     * Fügt ein bereits erstelltes Element hinzu.
     */
    public function addElement(XmlElementInterface $element): self {
        $this->children[] = $element;
        return $this;
    }

    /**
     * Beendet diesen Builder und kehrt zum Element-Builder zurück.
     */
    public function end(): XmlElementBuilder {
        $attributes = [];
        foreach ($this->attributes as $name => $value) {
            $attributes[] = new Attribute($name, $value);
        }

        $element = new Element(
            $this->name,
            $this->value,
            $this->namespaceUri,
            $this->prefix,
            $attributes,
            $this->children
        );

        return $this->parent->addBuiltElement($element);
    }
}
