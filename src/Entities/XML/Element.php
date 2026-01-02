<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Element.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\XML;

use CommonToolkit\Contracts\Interfaces\XML\XmlAttributeInterface;
use CommonToolkit\Contracts\Interfaces\XML\XmlElementInterface;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Repräsentiert ein XML-Element.
 * 
 * Flexibles Value Object für XML-Elemente mit Namespace-Unterstützung,
 * Attributen und Kind-Elementen.
 * 
 * @package CommonToolkit\Entities\XML
 */
class Element implements XmlElementInterface {
    private string $name;
    private ?string $value;
    private ?string $namespaceUri;
    private ?string $prefix;

    /** @var XmlAttributeInterface[] */
    private array $attributes = [];

    /** @var XmlElementInterface[] */
    private array $children = [];

    /**
     * @param string $name Element-Name
     * @param string|null $value Textinhalt (optional)
     * @param string|null $namespaceUri Namespace-URI (optional)
     * @param string|null $prefix Namespace-Prefix (optional)
     * @param XmlAttributeInterface[] $attributes Attribute (optional)
     * @param XmlElementInterface[] $children Kind-Elemente (optional)
     */
    public function __construct(
        string $name,
        ?string $value = null,
        ?string $namespaceUri = null,
        ?string $prefix = null,
        array $attributes = [],
        array $children = []
    ) {
        $this->name = $name;
        $this->value = $value;
        $this->namespaceUri = $namespaceUri;
        $this->prefix = $prefix;
        $this->attributes = $attributes;
        $this->children = $children;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getValue(): ?string {
        return $this->value;
    }

    public function getNamespaceUri(): ?string {
        return $this->namespaceUri;
    }

    public function getPrefix(): ?string {
        return $this->prefix;
    }

    /**
     * Gibt den vollqualifizierten Namen zurück (prefix:name oder name).
     */
    public function getQualifiedName(): string {
        return $this->prefix !== null ? "{$this->prefix}:{$this->name}" : $this->name;
    }

    /**
     * @return XmlAttributeInterface[]
     */
    public function getAttributes(): array {
        return $this->attributes;
    }

    public function getAttribute(string $name): ?XmlAttributeInterface {
        foreach ($this->attributes as $attr) {
            if ($attr->getName() === $name) {
                return $attr;
            }
        }
        return null;
    }

    /**
     * Gibt den Wert eines Attributs zurück.
     */
    public function getAttributeValue(string $name, ?string $default = null): ?string {
        $attr = $this->getAttribute($name);
        return $attr?->getValue() ?? $default;
    }

    /**
     * Prüft ob ein Attribut existiert.
     */
    public function hasAttribute(string $name): bool {
        return $this->getAttribute($name) !== null;
    }

    /**
     * @return XmlElementInterface[]
     */
    public function getChildren(): array {
        return $this->children;
    }

    /**
     * @return XmlElementInterface[]
     */
    public function getChildrenByName(string $name): array {
        return array_values(array_filter(
            $this->children,
            fn(XmlElementInterface $child) => $child->getName() === $name
        ));
    }

    public function getFirstChildByName(string $name): ?XmlElementInterface {
        foreach ($this->children as $child) {
            if ($child->getName() === $name) {
                return $child;
            }
        }
        return null;
    }

    public function hasChild(string $name): bool {
        return $this->getFirstChildByName($name) !== null;
    }

    /**
     * Gibt den Textinhalt eines Kind-Elements zurück.
     */
    public function getChildValue(string $name, ?string $default = null): ?string {
        $child = $this->getFirstChildByName($name);
        return $child?->getValue() ?? $default;
    }

    /**
     * Zählt die Kind-Elemente.
     */
    public function countChildren(): int {
        return count($this->children);
    }

    /**
     * Zählt Kind-Elemente mit einem bestimmten Namen.
     */
    public function countChildrenByName(string $name): int {
        return count($this->getChildrenByName($name));
    }

    public function toDomNode(DOMDocument $doc): DOMNode {
        if ($this->namespaceUri !== null) {
            $element = $doc->createElementNS($this->namespaceUri, $this->getQualifiedName());
        } else {
            $element = $doc->createElement($this->name);
        }

        // Attribute hinzufügen
        foreach ($this->attributes as $attr) {
            if ($attr->getNamespaceUri() !== null) {
                $element->setAttributeNS(
                    $attr->getNamespaceUri(),
                    $attr instanceof Attribute ? $attr->getQualifiedName() : $attr->getName(),
                    $attr->getValue()
                );
            } else {
                $element->setAttribute($attr->getName(), $attr->getValue());
            }
        }

        // Textinhalt hinzufügen
        if ($this->value !== null && empty($this->children)) {
            $element->textContent = $this->value;
        }

        // Kind-Elemente hinzufügen
        foreach ($this->children as $child) {
            $element->appendChild($child->toDomNode($doc));
        }

        return $element;
    }

    public function toString(): string {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->appendChild($this->toDomNode($doc));
        $doc->formatOutput = true;

        return $doc->saveXML($doc->documentElement) ?: '';
    }

    // ===== Builder-Methoden (Fluent API für immutable Objekte) =====

    /**
     * Erstellt eine Kopie mit neuem Wert.
     */
    public function withValue(?string $value): self {
        return new self(
            $this->name,
            $value,
            $this->namespaceUri,
            $this->prefix,
            $this->attributes,
            $this->children
        );
    }

    /**
     * Erstellt eine Kopie mit zusätzlichem Attribut.
     */
    public function withAttribute(XmlAttributeInterface $attribute): self {
        $attributes = $this->attributes;
        $attributes[] = $attribute;

        return new self(
            $this->name,
            $this->value,
            $this->namespaceUri,
            $this->prefix,
            $attributes,
            $this->children
        );
    }

    /**
     * Erstellt eine Kopie mit zusätzlichem Kind-Element.
     */
    public function withChild(XmlElementInterface $child): self {
        $children = $this->children;
        $children[] = $child;

        return new self(
            $this->name,
            $this->value,
            $this->namespaceUri,
            $this->prefix,
            $this->attributes,
            $children
        );
    }

    /**
     * Erstellt eine Kopie mit mehreren Kind-Elementen.
     * 
     * @param XmlElementInterface[] $children
     */
    public function withChildren(array $children): self {
        return new self(
            $this->name,
            $this->value,
            $this->namespaceUri,
            $this->prefix,
            $this->attributes,
            array_merge($this->children, $children)
        );
    }

    /**
     * Erstellt eine Kopie mit Namespace.
     */
    public function withNamespace(string $namespaceUri, ?string $prefix = null): self {
        return new self(
            $this->name,
            $this->value,
            $namespaceUri,
            $prefix,
            $this->attributes,
            $this->children
        );
    }

    // ===== Factory-Methoden =====

    /**
     * Erstellt ein Element aus einem DOMElement.
     */
    public static function fromDomElement(DOMElement $domElement): self {
        $attributes = [];
        if ($domElement->hasAttributes()) {
            foreach ($domElement->attributes as $attr) {
                if ($attr instanceof \DOMAttr) {
                    $attributes[] = Attribute::fromDomAttr($attr);
                }
            }
        }

        $children = [];
        $textContent = null;
        $hasChildElements = false;

        foreach ($domElement->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $hasChildElements = true;
                $children[] = self::fromDomElement($childNode);
            }
        }

        // Nur direkten Textinhalt nehmen, wenn keine Kind-Elemente vorhanden
        if (!$hasChildElements) {
            $text = trim($domElement->textContent);
            $textContent = $text !== '' ? $text : null;
        }

        return new self(
            $domElement->localName,
            $textContent,
            $domElement->namespaceURI ?: null,
            $domElement->prefix ?: null,
            $attributes,
            $children
        );
    }

    /**
     * Erstellt ein einfaches Element mit Textinhalt.
     */
    public static function simple(string $name, ?string $value = null, ?string $namespaceUri = null, ?string $prefix = null): self {
        return new self($name, $value, $namespaceUri, $prefix);
    }

    /**
     * Erstellt ein Element mit Kind-Elementen.
     * 
     * @param XmlElementInterface[] $children
     */
    public static function withChildElements(string $name, array $children, ?string $namespaceUri = null, ?string $prefix = null): self {
        return new self($name, null, $namespaceUri, $prefix, [], $children);
    }

    /**
     * Prüft ob das Element einen bestimmten Namen hat.
     */
    public function hasName(string $name): bool {
        return $this->name === $name;
    }

    /**
     * Gibt alle Kind-Element-Namen zurück.
     * 
     * @return string[]
     */
    public function getChildNames(): array {
        $names = [];
        foreach ($this->children as $child) {
            $name = $child->getName();
            if (!in_array($name, $names, true)) {
                $names[] = $name;
            }
        }
        return $names;
    }

    /**
     * Gibt den Textinhalt aller Kind-Elemente konkateniert zurück.
     */
    public function getTextContent(): string {
        if (empty($this->children)) {
            return $this->value ?? '';
        }

        $text = '';
        foreach ($this->children as $child) {
            $text .= $child->getTextContent();
        }
        return $text;
    }

    /**
     * Sucht rekursiv nach Kind-Elementen mit einem bestimmten Namen.
     * 
     * @return Element[]
     */
    public function findDescendants(string $name): array {
        $result = [];

        foreach ($this->children as $child) {
            if ($child->getName() === $name) {
                $result[] = $child;
            }
            if ($child instanceof self) {
                $result = array_merge($result, $child->findDescendants($name));
            }
        }

        return $result;
    }

    /**
     * Sucht rekursiv nach dem ersten Kind-Element mit einem bestimmten Namen.
     */
    public function findFirstDescendant(string $name): ?Element {
        foreach ($this->children as $child) {
            if ($child->getName() === $name) {
                return $child instanceof self ? $child : null;
            }
            if ($child instanceof self) {
                $found = $child->findFirstDescendant($name);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
    }

    /**
     * Prüft ob das Element leer ist (kein Wert und keine Kinder).
     */
    public function isEmpty(): bool {
        return ($this->value === null || $this->value === '') && empty($this->children);
    }
}
