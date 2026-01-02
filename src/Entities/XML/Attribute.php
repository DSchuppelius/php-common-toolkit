<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Attribute.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\XML;

use CommonToolkit\Contracts\Interfaces\XML\XmlAttributeInterface;

/**
 * Repräsentiert ein XML-Attribut.
 * 
 * Immutable Value Object für XML-Attribute mit optionaler Namespace-Unterstützung.
 * 
 * @package CommonToolkit\Entities\XML
 */
class Attribute implements XmlAttributeInterface {
    private string $name;
    private string $value;
    private ?string $namespaceUri;
    private ?string $prefix;

    /**
     * @param string $name Attribut-Name
     * @param string $value Attribut-Wert
     * @param string|null $namespaceUri Namespace-URI (optional)
     * @param string|null $prefix Namespace-Prefix (optional)
     */
    public function __construct(
        string $name,
        string $value,
        ?string $namespaceUri = null,
        ?string $prefix = null
    ) {
        $this->name = $name;
        $this->value = $value;
        $this->namespaceUri = $namespaceUri;
        $this->prefix = $prefix;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getValue(): string {
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
     * Erstellt eine Kopie mit neuem Wert.
     */
    public function withValue(string $value): self {
        return new self($this->name, $value, $this->namespaceUri, $this->prefix);
    }

    /**
     * Erstellt ein Attribut aus einem DOMAttr.
     */
    public static function fromDomAttr(\DOMAttr $attr): self {
        return new self(
            $attr->localName,
            $attr->value,
            $attr->namespaceURI ?: null,
            $attr->prefix ?: null
        );
    }
}
