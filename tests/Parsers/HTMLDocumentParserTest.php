<?php
/*
 * Created on   : Sat Jan 25 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HTMLDocumentParserTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Parsers;

use CommonToolkit\Entities\HTML\Document;
use CommonToolkit\Entities\HTML\Element;
use CommonToolkit\Parsers\HTMLDocumentParser;
use Tests\Contracts\BaseTestCase;

class HTMLDocumentParserTest extends BaseTestCase {

    private const SAMPLE_HTML = <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta name="description" content="Test description">
    <title>Test Page</title>
    <style>body { color: red; }</style>
</head>
<body class="main-body">
    <h1>Hello World</h1>
    <p>This is a paragraph.</p>
    <a href="https://example.com">Link</a>
    <img src="image.png" alt="Test">
</body>
</html>
HTML;

    public function testParseStringReturnsDocument(): void {
        $doc = HTMLDocumentParser::parseString(self::SAMPLE_HTML);

        $this->assertInstanceOf(Document::class, $doc);
    }

    public function testParseStringExtractsTitle(): void {
        $doc = HTMLDocumentParser::parseString(self::SAMPLE_HTML);

        $this->assertEquals('Test Page', $doc->getTitle());
    }

    public function testParseStringExtractsLang(): void {
        $doc = HTMLDocumentParser::parseString(self::SAMPLE_HTML);

        $this->assertEquals('de', $doc->getLang());
    }

    public function testParseFragment(): void {
        $html = '<p>First</p><div>Second</div><span>Third</span>';
        $elements = HTMLDocumentParser::parseFragment($html);

        $this->assertCount(3, $elements);
        $this->assertEquals('p', $elements[0]->getTag());
        $this->assertEquals('div', $elements[1]->getTag());
        $this->assertEquals('span', $elements[2]->getTag());
    }

    public function testParseElement(): void {
        $html = '<div class="container"><p>Content</p></div>';
        $element = HTMLDocumentParser::parseElement($html);

        $this->assertInstanceOf(Element::class, $element);
        $this->assertEquals('div', $element->getTag());
        $this->assertEquals('container', $element->getAttribute('class'));
        $this->assertCount(1, $element->getChildren());
    }

    public function testExtractTitle(): void {
        $title = HTMLDocumentParser::extractTitle(self::SAMPLE_HTML);

        $this->assertEquals('Test Page', $title);
    }

    public function testExtractTitleReturnsNullIfMissing(): void {
        $html = '<html><body><p>No title</p></body></html>';
        $title = HTMLDocumentParser::extractTitle($html);

        $this->assertNull($title);
    }

    public function testExtractMeta(): void {
        $meta = HTMLDocumentParser::extractMeta(self::SAMPLE_HTML);

        $this->assertArrayHasKey('description', $meta);
        $this->assertEquals('Test description', $meta['description']);
    }

    public function testExtractLinks(): void {
        $links = HTMLDocumentParser::extractLinks(self::SAMPLE_HTML);

        $this->assertCount(1, $links);
        $this->assertEquals('https://example.com', $links[0]);
    }

    public function testExtractImages(): void {
        $images = HTMLDocumentParser::extractImages(self::SAMPLE_HTML);

        $this->assertCount(1, $images);
        $this->assertEquals('image.png', $images[0]);
    }

    public function testExtractText(): void {
        $html = '<div><p>Hello</p><span>World</span></div>';
        $text = HTMLDocumentParser::extractText($html);

        $this->assertStringContainsString('Hello', $text);
        $this->assertStringContainsString('World', $text);
    }

    public function testQuerySelectorByTag(): void {
        $elements = HTMLDocumentParser::querySelectorAll(self::SAMPLE_HTML, 'p');

        $this->assertCount(1, $elements);
        $this->assertEquals('p', $elements[0]->getTag());
    }

    public function testQuerySelectorById(): void {
        $html = '<div id="test-id">Content</div>';
        $element = HTMLDocumentParser::querySelector($html, '#test-id');

        $this->assertNotNull($element);
        $this->assertEquals('div', $element->getTag());
    }

    public function testQuerySelectorByClass(): void {
        $html = '<p class="highlight">Text</p><p>Normal</p>';
        $elements = HTMLDocumentParser::querySelectorAll($html, '.highlight');

        $this->assertCount(1, $elements);
    }

    public function testQuerySelectorByAttribute(): void {
        $html = '<input type="text" name="email"><input type="password" name="pass">';
        $elements = HTMLDocumentParser::querySelectorAll($html, '[type="text"]');

        $this->assertCount(1, $elements);
        $this->assertEquals('email', $elements[0]->getAttribute('name'));
    }

    public function testQuerySelectorReturnsNullIfNotFound(): void {
        $html = '<div>Content</div>';
        $element = HTMLDocumentParser::querySelector($html, '#nonexistent');

        $this->assertNull($element);
    }

    public function testIsValidWithValidHtml(): void {
        $this->assertTrue(HTMLDocumentParser::isValid('<p>Valid HTML</p>'));
        $this->assertTrue(HTMLDocumentParser::isValid(self::SAMPLE_HTML));
    }

    public function testNestedElements(): void {
        $html = '<div><ul><li>Item 1</li><li>Item 2</li></ul></div>';
        $element = HTMLDocumentParser::parseElement($html);

        $this->assertEquals('div', $element->getTag());
        $this->assertCount(1, $element->getChildren());

        $ul = $element->getChildren()[0];
        $this->assertEquals('ul', $ul->getTag());
        $this->assertCount(2, $ul->getChildren());
    }

    public function testParseAndRenderRoundtrip(): void {
        $html = '<div class="test"><p>Content</p></div>';
        $element = HTMLDocumentParser::parseElement($html);
        $rendered = $element->render();

        $this->assertStringContainsString('class="test"', $rendered);
        $this->assertStringContainsString('<p>Content</p>', $rendered);
    }

    public function testMultipleMetaTags(): void {
        $html = <<<HTML
<html>
<head>
    <meta name="author" content="John">
    <meta name="keywords" content="test, php">
    <meta name="robots" content="index">
</head>
</html>
HTML;
        $meta = HTMLDocumentParser::extractMeta($html);

        $this->assertCount(3, $meta);
        $this->assertEquals('John', $meta['author']);
        $this->assertEquals('test, php', $meta['keywords']);
        $this->assertEquals('index', $meta['robots']);
    }

    public function testMultipleLinksAndImages(): void {
        $html = <<<HTML
<html><body>
    <a href="link1.html">Link 1</a>
    <a href="link2.html">Link 2</a>
    <img src="img1.png">
    <img src="img2.jpg">
    <img src="img3.gif">
</body></html>
HTML;

        $links = HTMLDocumentParser::extractLinks($html);
        $images = HTMLDocumentParser::extractImages($html);

        $this->assertCount(2, $links);
        $this->assertCount(3, $images);
    }

    public function testBodyAttributes(): void {
        $doc = HTMLDocumentParser::parseString(self::SAMPLE_HTML);
        $bodyAttrs = $doc->getBodyAttributes();

        $this->assertArrayHasKey('class', $bodyAttrs);
        $this->assertEquals('main-body', $bodyAttrs['class']);
    }

    public function testEmptyFragment(): void {
        $elements = HTMLDocumentParser::parseFragment('');

        $this->assertIsArray($elements);
        $this->assertEmpty($elements);
    }

    public function testTextOnlyFragment(): void {
        // Nur Text ohne Tags wird nicht als Element geparst
        $elements = HTMLDocumentParser::parseFragment('Just plain text');

        // Text-Nodes werden nicht als Elemente zurückgegeben
        $this->assertIsArray($elements);
    }
}
