<?php
/*
 * Created on   : Sat Jan 25 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HTMLDocumentParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Entities\HTML\Document;
use CommonToolkit\Entities\HTML\Element;
use CommonToolkit\Helper\FileSystem\File;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Parser für HTML-Dokumente.
 * 
 * Konvertiert HTML-Strings oder -Dateien in Document/Element-Objekte.
 * Unterstützt sowohl vollständige HTML-Dokumente als auch HTML-Fragmente.
 * 
 * Beispiel:
 * ```php
 * // HTML-String parsen
 * $doc = HTMLDocumentParser::parseString('<html><body><h1>Test</h1></body></html>');
 * 
 * // Datei parsen
 * $doc = HTMLDocumentParser::parseFile('/path/to/file.html');
 * 
 * // Fragment parsen (nur Elemente)
 * $elements = HTMLDocumentParser::parseFragment('<p>Text</p><div>Content</div>');
 * ```
 * 
 * @package CommonToolkit\Parsers
 */
class HTMLDocumentParser {
    /**
     * Parst einen HTML-String zu einem Document-Objekt.
     */
    public static function parseString(string $html): Document {
        $dom = self::createDOMDocument($html);
        return self::domToDocument($dom);
    }

    /**
     * Parst eine HTML-Datei zu einem Document-Objekt.
     */
    public static function parseFile(string $path): Document {
        $html = File::read($path);
        return self::parseString($html);
    }

    /**
     * Parst ein HTML-Fragment zu einem Array von Element-Objekten.
     * 
     * @return Element[]
     */
    public static function parseFragment(string $html): array {
        $dom = self::createDOMDocument("<html><body>{$html}</body></html>");
        $body = $dom->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return [];
        }

        $elements = [];
        foreach ($body->childNodes as $node) {
            if ($node instanceof DOMElement) {
                $elements[] = self::domElementToElement($node);
            }
        }

        return $elements;
    }

    /**
     * Parst ein einzelnes HTML-Element.
     */
    public static function parseElement(string $html): ?Element {
        $elements = self::parseFragment($html);
        return $elements[0] ?? null;
    }

    /**
     * Extrahiert den Titel aus einem HTML-String.
     */
    public static function extractTitle(string $html): ?string {
        $dom = self::createDOMDocument($html);
        $titles = $dom->getElementsByTagName('title');
        
        if ($titles->length > 0) {
            return $titles->item(0)->textContent;
        }

        return null;
    }

    /**
     * Extrahiert alle Meta-Tags aus einem HTML-String.
     * 
     * @return array<string, string> Assoziatives Array [name => content]
     */
    public static function extractMeta(string $html): array {
        $dom = self::createDOMDocument($html);
        $metas = $dom->getElementsByTagName('meta');
        $result = [];

        foreach ($metas as $meta) {
            $name = $meta->getAttribute('name');
            $content = $meta->getAttribute('content');
            if ($name !== '' && $content !== '') {
                $result[$name] = $content;
            }
        }

        return $result;
    }

    /**
     * Extrahiert alle Links (href) aus einem HTML-String.
     * 
     * @return string[]
     */
    public static function extractLinks(string $html): array {
        $dom = self::createDOMDocument($html);
        $anchors = $dom->getElementsByTagName('a');
        $links = [];

        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            if ($href !== '') {
                $links[] = $href;
            }
        }

        return $links;
    }

    /**
     * Extrahiert alle Bilder (src) aus einem HTML-String.
     * 
     * @return string[]
     */
    public static function extractImages(string $html): array {
        $dom = self::createDOMDocument($html);
        $images = $dom->getElementsByTagName('img');
        $srcs = [];

        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if ($src !== '') {
                $srcs[] = $src;
            }
        }

        return $srcs;
    }

    /**
     * Extrahiert den Text-Inhalt (ohne HTML-Tags) aus einem HTML-String.
     */
    public static function extractText(string $html): string {
        $dom = self::createDOMDocument($html);
        return trim($dom->textContent ?? '');
    }

    /**
     * Findet Elemente per CSS-Selektor (einfache Unterstützung).
     * 
     * Unterstützte Selektoren:
     * - Tag-Name: 'div', 'p', 'h1'
     * - Klasse: '.class-name'
     * - ID: '#element-id'
     * - Attribut: '[name="value"]'
     * 
     * @return Element[]
     */
    public static function querySelectorAll(string $html, string $selector): array {
        $dom = self::createDOMDocument($html);
        $xpath = new DOMXPath($dom);

        $xpathQuery = self::cssToXPath($selector);
        $nodes = $xpath->query($xpathQuery);

        if ($nodes === false) {
            return [];
        }

        $elements = [];
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $elements[] = self::domElementToElement($node);
            }
        }

        return $elements;
    }

    /**
     * Findet das erste Element per CSS-Selektor.
     */
    public static function querySelector(string $html, string $selector): ?Element {
        $elements = self::querySelectorAll($html, $selector);
        return $elements[0] ?? null;
    }

    /**
     * Prüft, ob der HTML-String valide ist.
     */
    public static function isValid(string $html): bool {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        // Ignoriere Warnungen, nur echte Fehler zählen
        foreach ($errors as $error) {
            if ($error->level === LIBXML_ERR_FATAL) {
                return false;
            }
        }

        return true;
    }

    /**
     * Erstellt ein DOMDocument aus HTML-String.
     */
    private static function createDOMDocument(string $html): DOMDocument {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;

        // HTML5-kompatibles Laden mit UTF-8 Unterstützung
        // Füge Meta-Charset hinzu, falls nicht vorhanden, um UTF-8 korrekt zu handhaben
        if (stripos($html, '<meta') === false || stripos($html, 'charset') === false) {
            $html = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . $html;
        }
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();
        libxml_use_internal_errors(false);

        return $dom;
    }

    /**
     * Konvertiert ein DOMDocument zu einem Document-Objekt.
     */
    private static function domToDocument(DOMDocument $dom): Document {
        $title = null;
        $lang = 'de';
        $metas = [];
        $links = [];
        $styles = [];
        $headScripts = [];
        $bodyScripts = [];
        $bodyElements = [];
        $htmlAttributes = [];
        $bodyAttributes = [];

        // HTML-Element Attribute
        $htmlElements = $dom->getElementsByTagName('html');
        if ($htmlElements->length > 0) {
            $htmlElement = $htmlElements->item(0);
            if ($htmlElement instanceof DOMElement) {
                $lang = $htmlElement->getAttribute('lang') ?: 'de';
                foreach ($htmlElement->attributes as $attr) {
                    if ($attr->name !== 'lang') {
                        $htmlAttributes[$attr->name] = $attr->value;
                    }
                }
            }
        }

        // Head-Elemente
        $heads = $dom->getElementsByTagName('head');
        if ($heads->length > 0) {
            $head = $heads->item(0);
            foreach ($head->childNodes as $node) {
                if (!($node instanceof DOMElement)) {
                    continue;
                }

                match ($node->tagName) {
                    'title' => $title = $node->textContent,
                    'meta' => $metas[] = self::domElementToElement($node),
                    'link' => $links[] = self::domElementToElement($node),
                    'style' => $styles[] = self::domElementToElement($node),
                    'script' => $headScripts[] = self::domElementToElement($node),
                    default => null,
                };
            }
        }

        // Body-Elemente
        $bodies = $dom->getElementsByTagName('body');
        if ($bodies->length > 0) {
            $body = $bodies->item(0);
            if ($body instanceof DOMElement) {
                foreach ($body->attributes as $attr) {
                    $bodyAttributes[$attr->name] = $attr->value;
                }

                foreach ($body->childNodes as $node) {
                    if ($node instanceof DOMElement) {
                        if ($node->tagName === 'script') {
                            $bodyScripts[] = self::domElementToElement($node);
                        } else {
                            $bodyElements[] = self::domElementToElement($node);
                        }
                    }
                }
            }
        }

        $doc = new Document($title, $lang);

        foreach ($metas as $meta) {
            $doc = $doc->withMeta($meta);
        }
        foreach ($links as $link) {
            $doc = $doc->withLink($link);
        }
        foreach ($styles as $style) {
            $doc = $doc->withStyle($style);
        }
        foreach ($headScripts as $script) {
            $doc = $doc->withHeadScript($script);
        }
        foreach ($bodyScripts as $script) {
            $doc = $doc->withBodyScript($script);
        }
        foreach ($htmlAttributes as $name => $value) {
            $doc = $doc->withHtmlAttribute($name, $value);
        }
        foreach ($bodyAttributes as $name => $value) {
            $doc = $doc->withBodyAttribute($name, $value);
        }

        return $doc->withBodyElements($bodyElements);
    }

    /**
     * Konvertiert ein DOMElement zu einem Element-Objekt.
     */
    private static function domElementToElement(DOMElement $domElement): Element {
        $tag = $domElement->tagName;
        $attributes = [];
        $children = [];
        $content = null;

        // Attribute extrahieren
        foreach ($domElement->attributes as $attr) {
            $attributes[$attr->name] = $attr->value;
        }

        // Kinder oder Text-Inhalt
        $hasElementChildren = false;
        foreach ($domElement->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $hasElementChildren = true;
                $children[] = self::domElementToElement($child);
            }
        }

        if (!$hasElementChildren && $domElement->textContent !== '') {
            $content = $domElement->textContent;
        }

        return new Element($tag, $content, $attributes, $children);
    }

    /**
     * Konvertiert einfache CSS-Selektoren zu XPath.
     */
    private static function cssToXPath(string $selector): string {
        $selector = trim($selector);

        // ID-Selektor: #id
        if (str_starts_with($selector, '#')) {
            $id = substr($selector, 1);
            return "//*[@id='{$id}']";
        }

        // Klassen-Selektor: .class
        if (str_starts_with($selector, '.')) {
            $class = substr($selector, 1);
            return "//*[contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')]";
        }

        // Attribut-Selektor: [attr="value"]
        if (preg_match('/^\[(\w+)=["\']([^"\']+)["\']\]$/', $selector, $matches)) {
            return "//*[@{$matches[1]}='{$matches[2]}']";
        }

        // Attribut-Existenz: [attr]
        if (preg_match('/^\[(\w+)\]$/', $selector, $matches)) {
            return "//*[@{$matches[1]}]";
        }

        // Tag-Selektor
        return "//{$selector}";
    }
}