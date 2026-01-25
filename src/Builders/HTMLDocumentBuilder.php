<?php
/*
 * Created on   : Sat Jan 25 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HTMLDocumentBuilder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Builders;

use CommonToolkit\Entities\HTML\Document;
use CommonToolkit\Entities\HTML\Element;
use CommonToolkit\Helper\FileSystem\File;

/**
 * Fluent Builder für HTML-Dokumente.
 * 
 * Ermöglicht den strukturierten Aufbau von HTML-Dokumenten
 * mit einer intuitiven API. Ideal für die Generierung von
 * HTML-Dokumenten, die später z.B. als PDF gerendert werden können.
 * 
 * Beispiel:
 * ```php
 * $html = HTMLDocumentBuilder::create('Mein Dokument')
 *     ->addCss('styles.css')
 *     ->addInlineStyle('body { font-family: Arial; }')
 *     ->h1('Überschrift')
 *     ->p('Ein Absatz mit Text.')
 *     ->table()
 *         ->tr()->th('Spalte 1')->th('Spalte 2')->end()
 *         ->tr()->td('Wert 1')->td('Wert 2')->end()
 *     ->endTable()
 *     ->build();
 * ```
 * 
 * @package CommonToolkit\Builders
 */
class HTMLDocumentBuilder {
    private Document $document;

    /** @var Element[] Aktueller Element-Stack für verschachtelte Elemente */
    private array $elementStack = [];

    /** @var Element[] Gesammelte Body-Elemente */
    private array $bodyElements = [];

    private function __construct(?string $title = null, string $lang = 'de') {
        $this->document = new Document($title, $lang);
    }

    /**
     * Erstellt einen neuen HTML-Builder.
     */
    public static function create(?string $title = null, string $lang = 'de'): self {
        return new self($title, $lang);
    }

    // ========== HEAD-Methoden ==========

    /**
     * Setzt den Dokumenttitel.
     */
    public function title(string $title): self {
        $this->document = $this->document->withTitle($title);
        return $this;
    }

    /**
     * Fügt ein Meta-Tag hinzu.
     */
    public function meta(string $name, string $content): self {
        $this->document = $this->document->withMeta(
            Element::withAttributes('meta', ['name' => $name, 'content' => $content])
        );
        return $this;
    }

    /**
     * Fügt ein Meta-Tag mit http-equiv hinzu.
     */
    public function metaHttpEquiv(string $httpEquiv, string $content): self {
        $this->document = $this->document->withMeta(
            Element::withAttributes('meta', ['http-equiv' => $httpEquiv, 'content' => $content])
        );
        return $this;
    }

    /**
     * Fügt ein Viewport-Meta-Tag hinzu (für responsive Design).
     */
    public function viewport(string $content = 'width=device-width, initial-scale=1.0'): self {
        return $this->meta('viewport', $content);
    }

    /**
     * Fügt ein externes CSS-Stylesheet hinzu.
     */
    public function addCss(string $href, string $media = 'all'): self {
        $this->document = $this->document->withLink(
            Element::withAttributes('link', [
                'rel' => 'stylesheet',
                'href' => $href,
                'media' => $media
            ])
        );
        return $this;
    }

    /**
     * Fügt Inline-CSS hinzu.
     */
    public function addInlineStyle(string $css): self {
        $this->document = $this->document->withStyle(
            new Element('style', $css)
        );
        return $this;
    }

    /**
     * Lädt CSS aus einer Datei und fügt es inline hinzu.
     */
    public function addInlineStyleFromFile(string $path): self {
        $css = File::read($path);
        return $this->addInlineStyle($css);
    }

    /**
     * Fügt ein externes JavaScript im Head hinzu.
     */
    public function addHeadScript(string $src, bool $defer = false, bool $async = false): self {
        $attrs = ['src' => $src];
        if ($defer) $attrs['defer'] = true;
        if ($async) $attrs['async'] = true;

        $this->document = $this->document->withHeadScript(
            Element::withAttributes('script', $attrs)
        );
        return $this;
    }

    /**
     * Fügt Inline-JavaScript im Head hinzu.
     */
    public function addHeadInlineScript(string $js): self {
        $this->document = $this->document->withHeadScript(
            new Element('script', $js)
        );
        return $this;
    }

    /**
     * Fügt ein externes JavaScript am Ende des Body hinzu.
     */
    public function addScript(string $src, bool $defer = false, bool $async = false): self {
        $attrs = ['src' => $src];
        if ($defer) $attrs['defer'] = true;
        if ($async) $attrs['async'] = true;

        $this->document = $this->document->withBodyScript(
            Element::withAttributes('script', $attrs)
        );
        return $this;
    }

    /**
     * Fügt Inline-JavaScript am Ende des Body hinzu.
     */
    public function addInlineScript(string $js): self {
        $this->document = $this->document->withBodyScript(
            new Element('script', $js)
        );
        return $this;
    }

    /**
     * Setzt ein HTML-Attribut (z.B. data-* Attribute).
     */
    public function htmlAttribute(string $name, string $value): self {
        $this->document = $this->document->withHtmlAttribute($name, $value);
        return $this;
    }

    /**
     * Setzt ein Body-Attribut.
     */
    public function bodyAttribute(string $name, string $value): self {
        $this->document = $this->document->withBodyAttribute($name, $value);
        return $this;
    }

    /**
     * Setzt die Body-Klasse.
     */
    public function bodyClass(string $class): self {
        return $this->bodyAttribute('class', $class);
    }

    // ========== BODY-Elemente (Kurzformen) ==========

    /**
     * Fügt ein beliebiges Element hinzu.
     */
    public function element(string $tag, ?string $content = null, array $attributes = []): self {
        $this->addElement(new Element($tag, $content, $attributes));
        return $this;
    }

    /**
     * Fügt ein Element mit Raw-HTML-Inhalt hinzu (nicht escaped).
     */
    public function rawHtml(string $html): self {
        // Wrapper-div mit Raw-Content
        $element = new Element('div', $html);
        $this->bodyElements[] = $element;
        return $this;
    }

    /**
     * Fügt eine Überschrift H1 hinzu.
     */
    public function h1(string $text, array $attributes = []): self {
        return $this->element('h1', $text, $attributes);
    }

    /**
     * Fügt eine Überschrift H2 hinzu.
     */
    public function h2(string $text, array $attributes = []): self {
        return $this->element('h2', $text, $attributes);
    }

    /**
     * Fügt eine Überschrift H3 hinzu.
     */
    public function h3(string $text, array $attributes = []): self {
        return $this->element('h3', $text, $attributes);
    }

    /**
     * Fügt eine Überschrift H4 hinzu.
     */
    public function h4(string $text, array $attributes = []): self {
        return $this->element('h4', $text, $attributes);
    }

    /**
     * Fügt eine Überschrift H5 hinzu.
     */
    public function h5(string $text, array $attributes = []): self {
        return $this->element('h5', $text, $attributes);
    }

    /**
     * Fügt eine Überschrift H6 hinzu.
     */
    public function h6(string $text, array $attributes = []): self {
        return $this->element('h6', $text, $attributes);
    }

    /**
     * Fügt einen Absatz hinzu.
     */
    public function p(string $text, array $attributes = []): self {
        return $this->element('p', $text, $attributes);
    }

    /**
     * Fügt einen Zeilenumbruch hinzu.
     */
    public function br(): self {
        return $this->element('br');
    }

    /**
     * Fügt eine horizontale Linie hinzu.
     */
    public function hr(array $attributes = []): self {
        return $this->element('hr', null, $attributes);
    }

    /**
     * Fügt einen Div-Container hinzu.
     */
    public function div(?string $content = null, array $attributes = []): self {
        return $this->element('div', $content, $attributes);
    }

    /**
     * Fügt ein Span-Element hinzu.
     */
    public function span(string $text, array $attributes = []): self {
        return $this->element('span', $text, $attributes);
    }

    /**
     * Fügt ein Bild hinzu.
     */
    public function img(string $src, string $alt = '', array $attributes = []): self {
        $attrs = array_merge(['src' => $src, 'alt' => $alt], $attributes);
        return $this->element('img', null, $attrs);
    }

    /**
     * Fügt einen Link hinzu.
     */
    public function a(string $href, string $text, array $attributes = []): self {
        $attrs = array_merge(['href' => $href], $attributes);
        return $this->element('a', $text, $attrs);
    }

    /**
     * Fügt fett formatierten Text hinzu.
     */
    public function strong(string $text, array $attributes = []): self {
        return $this->element('strong', $text, $attributes);
    }

    /**
     * Fügt kursiv formatierten Text hinzu.
     */
    public function em(string $text, array $attributes = []): self {
        return $this->element('em', $text, $attributes);
    }

    /**
     * Fügt Code-Text hinzu.
     */
    public function code(string $text, array $attributes = []): self {
        return $this->element('code', $text, $attributes);
    }

    /**
     * Fügt vorformatierten Text hinzu.
     */
    public function pre(string $text, array $attributes = []): self {
        return $this->element('pre', $text, $attributes);
    }

    /**
     * Fügt ein Blockquote hinzu.
     */
    public function blockquote(string $text, array $attributes = []): self {
        return $this->element('blockquote', $text, $attributes);
    }

    // ========== Container/Verschachtelte Elemente ==========

    /**
     * Startet einen Div-Container (verschachtelt).
     */
    public function startDiv(array $attributes = []): self {
        return $this->startElement('div', $attributes);
    }

    /**
     * Beendet den aktuellen Div-Container.
     */
    public function endDiv(): self {
        return $this->endElement();
    }

    /**
     * Startet ein Section-Element.
     */
    public function startSection(array $attributes = []): self {
        return $this->startElement('section', $attributes);
    }

    /**
     * Beendet das Section-Element.
     */
    public function endSection(): self {
        return $this->endElement();
    }

    /**
     * Startet ein Article-Element.
     */
    public function startArticle(array $attributes = []): self {
        return $this->startElement('article', $attributes);
    }

    /**
     * Beendet das Article-Element.
     */
    public function endArticle(): self {
        return $this->endElement();
    }

    /**
     * Startet ein Header-Element.
     */
    public function startHeader(array $attributes = []): self {
        return $this->startElement('header', $attributes);
    }

    /**
     * Beendet das Header-Element.
     */
    public function endHeader(): self {
        return $this->endElement();
    }

    /**
     * Startet ein Footer-Element.
     */
    public function startFooter(array $attributes = []): self {
        return $this->startElement('footer', $attributes);
    }

    /**
     * Beendet das Footer-Element.
     */
    public function endFooter(): self {
        return $this->endElement();
    }

    /**
     * Startet ein Nav-Element.
     */
    public function startNav(array $attributes = []): self {
        return $this->startElement('nav', $attributes);
    }

    /**
     * Beendet das Nav-Element.
     */
    public function endNav(): self {
        return $this->endElement();
    }

    /**
     * Startet ein Main-Element.
     */
    public function startMain(array $attributes = []): self {
        return $this->startElement('main', $attributes);
    }

    /**
     * Beendet das Main-Element.
     */
    public function endMain(): self {
        return $this->endElement();
    }

    // ========== Listen ==========

    /**
     * Startet eine ungeordnete Liste.
     */
    public function startUl(array $attributes = []): self {
        return $this->startElement('ul', $attributes);
    }

    /**
     * Beendet die ungeordnete Liste.
     */
    public function endUl(): self {
        return $this->endElement();
    }

    /**
     * Startet eine geordnete Liste.
     */
    public function startOl(array $attributes = []): self {
        return $this->startElement('ol', $attributes);
    }

    /**
     * Beendet die geordnete Liste.
     */
    public function endOl(): self {
        return $this->endElement();
    }

    /**
     * Fügt ein Listen-Element hinzu.
     */
    public function li(string $text, array $attributes = []): self {
        return $this->element('li', $text, $attributes);
    }

    /**
     * Erstellt eine komplette ungeordnete Liste.
     * 
     * @param string[] $items
     */
    public function ul(array $items, array $attributes = []): self {
        $children = array_map(fn($item) => Element::create('li', $item), $items);
        $this->addElement(Element::withChildren('ul', $children)->withAttribute('class', $attributes['class'] ?? ''));
        return $this;
    }

    /**
     * Erstellt eine komplette geordnete Liste.
     * 
     * @param string[] $items
     */
    public function ol(array $items, array $attributes = []): self {
        $children = array_map(fn($item) => Element::create('li', $item), $items);
        $this->addElement(Element::withChildren('ol', $children)->withAttribute('class', $attributes['class'] ?? ''));
        return $this;
    }

    // ========== Tabellen ==========

    /**
     * Startet eine Tabelle.
     */
    public function startTable(array $attributes = []): self {
        return $this->startElement('table', $attributes);
    }

    /**
     * Beendet die Tabelle.
     */
    public function endTable(): self {
        return $this->endElement();
    }

    /**
     * Startet einen Tabellenkopf.
     */
    public function startThead(array $attributes = []): self {
        return $this->startElement('thead', $attributes);
    }

    /**
     * Beendet den Tabellenkopf.
     */
    public function endThead(): self {
        return $this->endElement();
    }

    /**
     * Startet einen Tabellenkörper.
     */
    public function startTbody(array $attributes = []): self {
        return $this->startElement('tbody', $attributes);
    }

    /**
     * Beendet den Tabellenkörper.
     */
    public function endTbody(): self {
        return $this->endElement();
    }

    /**
     * Startet einen Tabellenfuß.
     */
    public function startTfoot(array $attributes = []): self {
        return $this->startElement('tfoot', $attributes);
    }

    /**
     * Beendet den Tabellenfuß.
     */
    public function endTfoot(): self {
        return $this->endElement();
    }

    /**
     * Startet eine Tabellenzeile.
     */
    public function startTr(array $attributes = []): self {
        return $this->startElement('tr', $attributes);
    }

    /**
     * Beendet die Tabellenzeile.
     */
    public function endTr(): self {
        return $this->endElement();
    }

    /**
     * Fügt eine Tabellenkopfzelle hinzu.
     */
    public function th(string $text, array $attributes = []): self {
        return $this->element('th', $text, $attributes);
    }

    /**
     * Fügt eine Tabellenzelle hinzu.
     */
    public function td(string $text, array $attributes = []): self {
        return $this->element('td', $text, $attributes);
    }

    /**
     * Erstellt eine komplette Tabelle aus einem 2D-Array.
     * 
     * @param string[] $headers Kopfzeilen
     * @param string[][] $rows Datenzeilen
     */
    public function table(array $headers, array $rows, array $attributes = []): self {
        $this->startTable($attributes);

        // Header
        if (!empty($headers)) {
            $this->startThead();
            $this->startTr();
            foreach ($headers as $header) {
                $this->th($header);
            }
            $this->endTr();
            $this->endThead();
        }

        // Body
        $this->startTbody();
        foreach ($rows as $row) {
            $this->startTr();
            foreach ($row as $cell) {
                $this->td((string)$cell);
            }
            $this->endTr();
        }
        $this->endTbody();

        return $this->endTable();
    }

    // ========== Formular ==========

    /**
     * Startet ein Formular.
     */
    public function startForm(string $action = '', string $method = 'post', array $attributes = []): self {
        $attrs = array_merge(['action' => $action, 'method' => $method], $attributes);
        return $this->startElement('form', $attrs);
    }

    /**
     * Beendet das Formular.
     */
    public function endForm(): self {
        return $this->endElement();
    }

    /**
     * Fügt ein Label hinzu.
     */
    public function label(string $for, string $text, array $attributes = []): self {
        $attrs = array_merge(['for' => $for], $attributes);
        return $this->element('label', $text, $attrs);
    }

    /**
     * Fügt ein Input-Feld hinzu.
     */
    public function input(string $type, string $name, ?string $value = null, array $attributes = []): self {
        $attrs = array_merge(['type' => $type, 'name' => $name], $attributes);
        if ($value !== null) {
            $attrs['value'] = $value;
        }
        return $this->element('input', null, $attrs);
    }

    /**
     * Fügt ein Textfeld hinzu.
     */
    public function textInput(string $name, ?string $value = null, array $attributes = []): self {
        return $this->input('text', $name, $value, $attributes);
    }

    /**
     * Fügt eine Textarea hinzu.
     */
    public function textarea(string $name, ?string $content = null, array $attributes = []): self {
        $attrs = array_merge(['name' => $name], $attributes);
        return $this->element('textarea', $content, $attrs);
    }

    /**
     * Fügt einen Button hinzu.
     */
    public function button(string $text, string $type = 'button', array $attributes = []): self {
        $attrs = array_merge(['type' => $type], $attributes);
        return $this->element('button', $text, $attrs);
    }

    /**
     * Fügt einen Submit-Button hinzu.
     */
    public function submit(string $text = 'Absenden', array $attributes = []): self {
        return $this->button($text, 'submit', $attributes);
    }

    // ========== Interne Hilfsmethoden ==========

    /**
     * Fügt ein Element zum aktuellen Kontext hinzu.
     */
    private function addElement(Element $element): void {
        if (!empty($this->elementStack)) {
            // Zum letzten Element im Stack hinzufügen
            $last = array_pop($this->elementStack);
            $this->elementStack[] = $last->withChild($element);
        } else {
            // Direkt zu Body hinzufügen
            $this->bodyElements[] = $element;
        }
    }

    /**
     * Startet ein verschachteltes Element.
     */
    private function startElement(string $tag, array $attributes = []): self {
        $element = new Element($tag, null, $attributes);
        $this->elementStack[] = $element;
        return $this;
    }

    /**
     * Beendet das aktuelle verschachtelte Element.
     */
    private function endElement(): self {
        if (empty($this->elementStack)) {
            return $this;
        }

        $completed = array_pop($this->elementStack);

        if (!empty($this->elementStack)) {
            // Zum Eltern-Element hinzufügen
            $parent = array_pop($this->elementStack);
            $this->elementStack[] = $parent->withChild($completed);
        } else {
            // Zum Body hinzufügen
            $this->bodyElements[] = $completed;
        }

        return $this;
    }

    // ========== Build ==========

    /**
     * Erstellt das fertige HTML-Dokument.
     */
    public function build(): Document {
        // Alle noch offenen Elemente schließen
        while (!empty($this->elementStack)) {
            $this->endElement();
        }

        return $this->document->withBodyElements($this->bodyElements);
    }

    /**
     * Rendert das HTML-Dokument als String.
     */
    public function render(bool $pretty = true): string {
        return $this->build()->render($pretty);
    }

    /**
     * Speichert das HTML-Dokument in eine Datei.
     */
    public function save(string $path, bool $pretty = true): void {
        File::write($path, $this->render($pretty));
    }

    public function __toString(): string {
        return $this->render();
    }
}
