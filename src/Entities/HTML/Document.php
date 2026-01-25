<?php
/*
 * Created on   : Sat Jan 25 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\HTML;

/**
 * Repräsentiert ein vollständiges HTML-Dokument.
 * 
 * Immutable Value Object für HTML-Dokumente mit DOCTYPE, Head und Body.
 * 
 * @package CommonToolkit\Entities\HTML
 */
class Document {
    public const DOCTYPE_HTML5 = '<!DOCTYPE html>';
    public const DOCTYPE_XHTML = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';

    private string $doctype;
    private string $lang;
    private string $charset;
    private ?string $title;

    /** @var Element[] Meta-Tags im Head */
    private array $meta = [];

    /** @var Element[] Link-Tags im Head (CSS, etc.) */
    private array $links = [];

    /** @var Element[] Script-Tags im Head */
    private array $headScripts = [];

    /** @var Element[] Style-Blöcke im Head */
    private array $styles = [];

    /** @var Element[] Inhalt des Body */
    private array $body = [];

    /** @var Element[] Script-Tags am Ende des Body */
    private array $bodyScripts = [];

    /** @var array<string, string> Attribute für das html-Tag */
    private array $htmlAttributes = [];

    /** @var array<string, string> Attribute für das body-Tag */
    private array $bodyAttributes = [];

    public function __construct(
        ?string $title = null,
        string $lang = 'de',
        string $charset = 'UTF-8',
        string $doctype = self::DOCTYPE_HTML5
    ) {
        $this->title = $title;
        $this->lang = $lang;
        $this->charset = $charset;
        $this->doctype = $doctype;
    }

    public function getDoctype(): string {
        return $this->doctype;
    }

    public function getLang(): string {
        return $this->lang;
    }

    public function getCharset(): string {
        return $this->charset;
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    /**
     * @return Element[]
     */
    public function getMeta(): array {
        return $this->meta;
    }

    /**
     * @return Element[]
     */
    public function getLinks(): array {
        return $this->links;
    }

    /**
     * @return Element[]
     */
    public function getHeadScripts(): array {
        return $this->headScripts;
    }

    /**
     * @return Element[]
     */
    public function getStyles(): array {
        return $this->styles;
    }

    /**
     * @return Element[]
     */
    public function getBody(): array {
        return $this->body;
    }

    /**
     * @return Element[]
     */
    public function getBodyScripts(): array {
        return $this->bodyScripts;
    }

    /**
     * @return array<string, string>
     */
    public function getHtmlAttributes(): array {
        return $this->htmlAttributes;
    }

    /**
     * @return array<string, string>
     */
    public function getBodyAttributes(): array {
        return $this->bodyAttributes;
    }

    /**
     * Gibt eine neue Instanz mit geändertem Titel zurück.
     */
    public function withTitle(string $title): self {
        $clone = clone $this;
        $clone->title = $title;
        return $clone;
    }

    /**
     * Gibt eine neue Instanz mit geänderter Sprache zurück.
     */
    public function withLang(string $lang): self {
        $clone = clone $this;
        $clone->lang = $lang;
        return $clone;
    }

    /**
     * Gibt eine neue Instanz mit zusätzlichem Meta-Tag zurück.
     */
    public function withMeta(Element $meta): self {
        $clone = clone $this;
        $clone->meta[] = $meta;
        return $clone;
    }

    /**
     * Gibt eine neue Instanz mit zusätzlichem Link-Tag zurück.
     */
    public function withLink(Element $link): self {
        $clone = clone $this;
        $clone->links[] = $link;
        return $clone;
    }

    /**
     * Gibt eine neue Instanz mit zusätzlichem Head-Script zurück.
     */
    public function withHeadScript(Element $script): self {
        $clone = clone $this;
        $clone->headScripts[] = $script;
        return $clone;
    }

    /**
     * Gibt eine neue Instanz mit zusätzlichem Style-Block zurück.
     */
    public function withStyle(Element $style): self {
        $clone = clone $this;
        $clone->styles[] = $style;
        return $clone;
    }

    /**
     * Gibt eine neue Instanz mit zusätzlichem Body-Element zurück.
     */
    public function withBodyElement(Element $element): self {
        $clone = clone $this;
        $clone->body[] = $element;
        return $clone;
    }

    /**
     * Gibt eine neue Instanz mit zusätzlichen Body-Elementen zurück.
     * 
     * @param Element[] $elements
     */
    public function withBodyElements(array $elements): self {
        $clone = clone $this;
        $clone->body = array_merge($clone->body, $elements);
        return $clone;
    }

    /**
     * Gibt eine neue Instanz mit zusätzlichem Body-Script zurück.
     */
    public function withBodyScript(Element $script): self {
        $clone = clone $this;
        $clone->bodyScripts[] = $script;
        return $clone;
    }

    /**
     * Gibt eine neue Instanz mit HTML-Attribut zurück.
     */
    public function withHtmlAttribute(string $name, string $value): self {
        $clone = clone $this;
        $clone->htmlAttributes[$name] = $value;
        return $clone;
    }

    /**
     * Gibt eine neue Instanz mit Body-Attribut zurück.
     */
    public function withBodyAttribute(string $name, string $value): self {
        $clone = clone $this;
        $clone->bodyAttributes[$name] = $value;
        return $clone;
    }

    /**
     * Rendert das vollständige HTML-Dokument.
     */
    public function render(bool $pretty = true): string {
        $newline = $pretty ? "\n" : '';
        $indent = $pretty ? '  ' : '';

        $html = $this->doctype . $newline;

        // HTML-Tag mit Attributen
        $htmlAttrs = array_merge(['lang' => $this->lang], $this->htmlAttributes);
        $html .= '<html';
        foreach ($htmlAttrs as $name => $value) {
            $html .= ' ' . htmlspecialchars($name) . '="' . htmlspecialchars($value) . '"';
        }
        $html .= '>' . $newline;

        // Head
        $html .= $indent . '<head>' . $newline;
        $html .= $indent . $indent . '<meta charset="' . htmlspecialchars($this->charset) . '">' . $newline;

        if ($this->title !== null) {
            $html .= $indent . $indent . '<title>' . htmlspecialchars($this->title) . '</title>' . $newline;
        }

        foreach ($this->meta as $meta) {
            $html .= $indent . $indent . $meta->render(false) . $newline;
        }

        foreach ($this->links as $link) {
            $html .= $indent . $indent . $link->render(false) . $newline;
        }

        foreach ($this->styles as $style) {
            $html .= $indent . $indent . $style->renderRaw(false) . $newline;
        }

        foreach ($this->headScripts as $script) {
            $html .= $indent . $indent . $script->renderRaw(false) . $newline;
        }

        $html .= $indent . '</head>' . $newline;

        // Body
        $html .= $indent . '<body';
        foreach ($this->bodyAttributes as $name => $value) {
            $html .= ' ' . htmlspecialchars($name) . '="' . htmlspecialchars($value) . '"';
        }
        $html .= '>' . $newline;

        foreach ($this->body as $element) {
            $html .= $element->render($pretty, 2);
        }

        foreach ($this->bodyScripts as $script) {
            $html .= $indent . $indent . $script->renderRaw(false) . $newline;
        }

        $html .= $indent . '</body>' . $newline;
        $html .= '</html>' . $newline;

        return $html;
    }

    public function __toString(): string {
        return $this->render();
    }
}
