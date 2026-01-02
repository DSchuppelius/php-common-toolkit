<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XmlElementInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Interfaces\XML;

/**
 * Interface für XML-Elemente.
 */
interface XmlElementInterface extends XmlNodeInterface {
    /**
     * Gibt den Element-Namen zurück.
     */
    public function getName(): string;

    /**
     * Gibt den Namespace-URI zurück (oder null).
     */
    public function getNamespaceUri(): ?string;

    /**
     * Gibt den Namespace-Prefix zurück (oder null).
     */
    public function getPrefix(): ?string;

    /**
     * Gibt den Textinhalt zurück (oder null).
     */
    public function getValue(): ?string;

    /**
     * Gibt alle Attribute zurück.
     * @return XmlAttributeInterface[]
     */
    public function getAttributes(): array;

    /**
     * Gibt ein Attribut nach Name zurück.
     */
    public function getAttribute(string $name): ?XmlAttributeInterface;

    /**
     * Gibt den Wert eines Attributs zurück.
     */
    public function getAttributeValue(string $name, ?string $default = null): ?string;

    /**
     * Gibt alle Kind-Elemente zurück.
     * @return XmlElementInterface[]
     */
    public function getChildren(): array;

    /**
     * Gibt Kind-Elemente nach Name zurück.
     * @return XmlElementInterface[]
     */
    public function getChildrenByName(string $name): array;

    /**
     * Gibt das erste Kind-Element nach Name zurück.
     */
    public function getFirstChildByName(string $name): ?XmlElementInterface;

    /**
     * Gibt den Wert eines Kind-Elements zurück.
     */
    public function getChildValue(string $name, ?string $default = null): ?string;

    /**
     * Prüft ob ein Kind-Element existiert.
     */
    public function hasChild(string $name): bool;

    /**
     * Gibt die Anzahl der Kind-Elemente zurück.
     */
    public function countChildren(): int;

    /**
     * Gibt die Anzahl der Kind-Elemente mit einem bestimmten Namen zurück.
     */
    public function countChildrenByName(string $name): int;

    /**
     * Prüft ob das Element einen bestimmten Namen hat.
     */
    public function hasName(string $name): bool;

    /**
     * Prüft ob das Element ein Attribut hat.
     */
    public function hasAttribute(string $name): bool;

    /**
     * Gibt alle Kind-Element-Namen zurück.
     * @return string[]
     */
    public function getChildNames(): array;

    /**
     * Gibt den Textinhalt aller Kind-Elemente konkateniert zurück.
     */
    public function getTextContent(): string;

    /**
     * Prüft ob das Element leer ist (kein Wert und keine Kinder).
     */
    public function isEmpty(): bool;
}
