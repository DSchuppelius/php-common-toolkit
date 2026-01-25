<?php
/*
 * Created on   : Sat Jan 25 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Element.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\HTML;

/**
 * Repräsentiert ein HTML-Element.
 * 
 * Immutable Value Object für HTML-Elemente mit Attributen und Kind-Elementen.
 * 
 * @package CommonToolkit\Entities\HTML
 */
class Element {
    /** @var string[] Self-closing Tags die kein </tag> benötigen */
    private const VOID_ELEMENTS = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr'
    ];

    private string $tag;
    private ?string $content;

    /** @var array<string, string|bool> */
    private array $attributes;

    /** @var Element[] */
    private array $children;

    /**
     * @param string $tag HTML-Tag-Name
     * @param string|null $content Textinhalt (optional)
     * @param array<string, string|bool> $attributes Attribute (optional)
     * @param Element[] $children Kind-Elemente (optional)
     */
    public function __construct(
        string $tag,
        ?string $content = null,
        array $attributes = [],
        array $children = []
    ) {
        $this->tag = strtolower($tag);
        $this->content = $content;
        $this->attributes = $attributes;
        $this->children = $children;
    }

    /**
     * Erstellt ein einfaches Element mit optionalem Inhalt.
     */
    public static function create(string $tag, ?string $content = null): self {
        return new self($tag, $content);
    }

    /**
     * Erstellt ein Element mit Attributen.
     * 
     * @param array<string, string|bool> $attributes
     */
    public static function withAttributes(string $tag, array $attributes, ?string $content = null): self {
        return new self($tag, $content, $attributes);
    }

    /**
     * Erstellt ein Element mit Kind-Elementen.
     * 
     * @param Element[] $children
     */
    public static function withChildren(string $tag, array $children): self {
        return new self($tag, null, [], $children);
    }

    public function getTag(): string {
        return $this->tag;
    }

    public function getContent(): ?string {
        return $this->content;
    }

    /**
     * @return array<string, string|bool>
     */
    public function getAttributes(): array {
        return $this->attributes;
    }

    public function getAttribute(string $name): string|bool|null {
        return $this->attributes[$name] ?? null;
    }

    public function hasAttribute(string $name): bool {
        return isset($this->attributes[$name]);
    }

    /**
     * @return Element[]
     */
    public function getChildren(): array {
        return $this->children;
    }

    public function hasChildren(): bool {
        return !empty($this->children);
    }

    /**
     * Prüft ob das Element ein Void-Element ist (self-closing).
     */
    public function isVoid(): bool {
        return in_array($this->tag, self::VOID_ELEMENTS, true);
    }

    /**
     * Gibt eine neue Instanz mit zusätzlichem Attribut zurück.
     */
    public function withAttribute(string $name, string|bool $value): self {
        $attributes = $this->attributes;
        $attributes[$name] = $value;
        return new self($this->tag, $this->content, $attributes, $this->children);
    }

    /**
     * Gibt eine neue Instanz mit zusätzlichem Kind-Element zurück.
     */
    public function withChild(Element $child): self {
        $children = $this->children;
        $children[] = $child;
        return new self($this->tag, $this->content, $this->attributes, $children);
    }

    /**
     * Gibt eine neue Instanz mit zusätzlichen Kind-Elementen zurück.
     * 
     * @param Element[] $newChildren
     */
    public function appendChildren(array $newChildren): self {
        return new self($this->tag, $this->content, $this->attributes, array_merge($this->children, $newChildren));
    }

    /**
     * Gibt eine neue Instanz mit geändertem Inhalt zurück.
     */
    public function withContent(?string $content): self {
        return new self($this->tag, $content, $this->attributes, $this->children);
    }

    /**
     * Gibt eine neue Instanz mit CSS-Klasse zurück.
     */
    public function withClass(string $class): self {
        $existingClass = $this->attributes['class'] ?? '';
        $newClass = trim($existingClass . ' ' . $class);
        return $this->withAttribute('class', $newClass);
    }

    /**
     * Gibt eine neue Instanz mit ID zurück.
     */
    public function withId(string $id): self {
        return $this->withAttribute('id', $id);
    }

    /**
     * Gibt eine neue Instanz mit Style zurück.
     */
    public function withStyle(string $style): self {
        $existingStyle = $this->attributes['style'] ?? '';
        $newStyle = rtrim($existingStyle, '; ') . ($existingStyle ? '; ' : '') . $style;
        return $this->withAttribute('style', $newStyle);
    }

    /**
     * Rendert das Element als HTML-String.
     */
    public function render(bool $pretty = false, int $indent = 0): string {
        $indentation = $pretty ? str_repeat('  ', $indent) : '';
        $newline = $pretty ? "\n" : '';

        $html = $indentation . '<' . $this->tag;

        // Attribute rendern
        foreach ($this->attributes as $name => $value) {
            if ($value === true) {
                $html .= ' ' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5);
            } elseif ($value !== false) {
                $html .= ' ' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '="' . htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5) . '"';
            }
        }

        // Void-Elemente haben kein Closing-Tag
        if ($this->isVoid()) {
            return $html . '>' . $newline;
        }

        $html .= '>';

        // Inhalt oder Kinder rendern
        if ($this->content !== null) {
            $html .= htmlspecialchars($this->content, ENT_QUOTES | ENT_HTML5);
        } elseif (!empty($this->children)) {
            $html .= $newline;
            foreach ($this->children as $child) {
                $html .= $child->render($pretty, $indent + 1);
            }
            $html .= $indentation;
        }

        $html .= '</' . $this->tag . '>' . $newline;

        return $html;
    }

    /**
     * Rendert das Element als HTML-String mit Raw-Content (nicht escaped).
     */
    public function renderRaw(bool $pretty = false, int $indent = 0): string {
        $indentation = $pretty ? str_repeat('  ', $indent) : '';
        $newline = $pretty ? "\n" : '';

        $html = $indentation . '<' . $this->tag;

        foreach ($this->attributes as $name => $value) {
            if ($value === true) {
                $html .= ' ' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5);
            } elseif ($value !== false) {
                $html .= ' ' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '="' . htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5) . '"';
            }
        }

        if ($this->isVoid()) {
            return $html . '>' . $newline;
        }

        $html .= '>';

        if ($this->content !== null) {
            $html .= $this->content; // Raw content, nicht escaped
        } elseif (!empty($this->children)) {
            $html .= $newline;
            foreach ($this->children as $child) {
                $html .= $child->renderRaw($pretty, $indent + 1);
            }
            $html .= $indentation;
        }

        $html .= '</' . $this->tag . '>' . $newline;

        return $html;
    }

    public function __toString(): string {
        return $this->render();
    }
}
