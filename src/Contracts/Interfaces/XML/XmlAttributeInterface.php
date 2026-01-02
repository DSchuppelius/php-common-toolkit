<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XmlAttributeInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Interfaces\XML;

/**
 * Interface für XML-Attribute.
 */
interface XmlAttributeInterface {
    /**
     * Gibt den Attribut-Namen zurück.
     */
    public function getName(): string;

    /**
     * Gibt den Attribut-Wert zurück.
     */
    public function getValue(): string;

    /**
     * Gibt den Namespace-URI zurück (oder null).
     */
    public function getNamespaceUri(): ?string;

    /**
     * Gibt den Namespace-Prefix zurück (oder null).
     */
    public function getPrefix(): ?string;
}
