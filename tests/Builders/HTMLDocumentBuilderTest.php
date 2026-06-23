<?php
/*
 * Created on   : Sat Jan 25 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HTMLDocumentBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders;

use CommonToolkit\Builders\HTMLDocumentBuilder;
use CommonToolkit\Entities\HTML\Document;
use Tests\Contracts\BaseTestCase;

class HTMLDocumentBuilderTest extends BaseTestCase {
    public function test_create_empty_document(): void {
        $doc = HTMLDocumentBuilder::create()->build();

        $this->assertInstanceOf(Document::class, $doc);
        $html = $doc->render();
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('</html>', $html);
    }

    public function test_create_with_title(): void {
        $html = HTMLDocumentBuilder::create('Test Page')
            ->render();

        $this->assertStringContainsString('<title>Test Page</title>', $html);
    }

    public function test_add_meta(): void {
        $html = HTMLDocumentBuilder::create()
            ->meta('description', 'Test description')
            ->meta('keywords', 'test, php')
            ->render();

        $this->assertStringContainsString('name="description"', $html);
        $this->assertStringContainsString('content="Test description"', $html);
        $this->assertStringContainsString('name="keywords"', $html);
    }

    public function test_viewport(): void {
        $html = HTMLDocumentBuilder::create()
            ->viewport()
            ->render();

        $this->assertStringContainsString('name="viewport"', $html);
        $this->assertStringContainsString('width=device-width', $html);
    }

    public function test_add_inline_style(): void {
        $html = HTMLDocumentBuilder::create()
            ->addInlineStyle('body { color: red; }')
            ->render();

        $this->assertStringContainsString('<style>', $html);
        $this->assertStringContainsString('body { color: red; }', $html);
        $this->assertStringContainsString('</style>', $html);
    }

    public function test_headings(): void {
        $html = HTMLDocumentBuilder::create()
            ->h1('Heading 1')
            ->h2('Heading 2')
            ->h3('Heading 3')
            ->render();

        $this->assertStringContainsString('<h1>Heading 1</h1>', $html);
        $this->assertStringContainsString('<h2>Heading 2</h2>', $html);
        $this->assertStringContainsString('<h3>Heading 3</h3>', $html);
    }

    public function test_paragraph(): void {
        $html = HTMLDocumentBuilder::create()
            ->p('This is a paragraph.')
            ->render();

        $this->assertStringContainsString('<p>This is a paragraph.</p>', $html);
    }

    public function test_link(): void {
        $html = HTMLDocumentBuilder::create()
            ->a('https://example.com', 'Click here')
            ->render();

        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertStringContainsString('>Click here</a>', $html);
    }

    public function test_image(): void {
        $html = HTMLDocumentBuilder::create()
            ->img('image.png', 'Alt text')
            ->render();

        $this->assertStringContainsString('src="image.png"', $html);
        $this->assertStringContainsString('alt="Alt text"', $html);
    }

    public function test_nested_div(): void {
        $html = HTMLDocumentBuilder::create()
            ->startDiv(['class' => 'container'])
            ->h1('Title')
            ->p('Content')
            ->endDiv()
            ->render();

        $this->assertStringContainsString('class="container"', $html);
        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringContainsString('<p>Content</p>', $html);
    }

    public function test_unordered_list(): void {
        $html = HTMLDocumentBuilder::create()
            ->ul(['Item 1', 'Item 2', 'Item 3'])
            ->render();

        $this->assertStringContainsString('<ul', $html);
        $this->assertStringContainsString('<li>Item 1</li>', $html);
        $this->assertStringContainsString('<li>Item 2</li>', $html);
        $this->assertStringContainsString('<li>Item 3</li>', $html);
        $this->assertStringContainsString('</ul>', $html);
    }

    public function test_ordered_list(): void {
        $html = HTMLDocumentBuilder::create()
            ->ol(['First', 'Second', 'Third'])
            ->render();

        $this->assertStringContainsString('<ol', $html);
        $this->assertStringContainsString('<li>First</li>', $html);
        $this->assertStringContainsString('</ol>', $html);
    }

    public function test_manual_list(): void {
        $html = HTMLDocumentBuilder::create()
            ->startUl(['class' => 'menu'])
            ->li('Home')
            ->li('About')
            ->li('Contact')
            ->endUl()
            ->render();

        $this->assertStringContainsString('class="menu"', $html);
        $this->assertStringContainsString('<li>Home</li>', $html);
    }

    public function test_simple_table(): void {
        $html = HTMLDocumentBuilder::create()
            ->table(
                ['Name', 'Age'],
                [
                    ['Alice', '30'],
                    ['Bob', '25'],
                ]
            )
            ->render();

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<thead>', $html);
        $this->assertStringContainsString('<th>Name</th>', $html);
        $this->assertStringContainsString('<th>Age</th>', $html);
        $this->assertStringContainsString('<tbody>', $html);
        $this->assertStringContainsString('<td>Alice</td>', $html);
        $this->assertStringContainsString('<td>30</td>', $html);
        $this->assertStringContainsString('</table>', $html);
    }

    public function test_manual_table(): void {
        $html = HTMLDocumentBuilder::create()
            ->startTable(['class' => 'data-table'])
            ->startThead()
            ->startTr()
            ->th('Column 1')
            ->th('Column 2')
            ->endTr()
            ->endThead()
            ->startTbody()
            ->startTr()
            ->td('Value 1')
            ->td('Value 2')
            ->endTr()
            ->endTbody()
            ->endTable()
            ->render();

        $this->assertStringContainsString('class="data-table"', $html);
        $this->assertStringContainsString('<th>Column 1</th>', $html);
        $this->assertStringContainsString('<td>Value 1</td>', $html);
    }

    public function test_form(): void {
        $html = HTMLDocumentBuilder::create()
            ->startForm('/submit', 'post', ['id' => 'myform'])
            ->label('name', 'Your Name:')
            ->textInput('name', '', ['id' => 'name', 'required' => true])
            ->submit('Send')
            ->endForm()
            ->render();

        $this->assertStringContainsString('action="/submit"', $html);
        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringContainsString('id="myform"', $html);
        $this->assertStringContainsString('for="name"', $html);
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('type="submit"', $html);
    }

    public function test_semantic_elements(): void {
        $html = HTMLDocumentBuilder::create()
            ->startHeader()
            ->h1('Site Title')
            ->endHeader()
            ->startMain()
            ->startArticle()
            ->h2('Article Title')
            ->p('Article content.')
            ->endArticle()
            ->endMain()
            ->startFooter()
            ->p('Copyright 2025')
            ->endFooter()
            ->render();

        $this->assertStringContainsString('<header>', $html);
        $this->assertStringContainsString('</header>', $html);
        $this->assertStringContainsString('<main>', $html);
        $this->assertStringContainsString('</main>', $html);
        $this->assertStringContainsString('<article>', $html);
        $this->assertStringContainsString('</article>', $html);
        $this->assertStringContainsString('<footer>', $html);
        $this->assertStringContainsString('</footer>', $html);
    }

    public function test_body_class(): void {
        $html = HTMLDocumentBuilder::create()
            ->bodyClass('dark-mode')
            ->render();

        $this->assertStringContainsString('class="dark-mode"', $html);
    }

    public function test_html_attribute(): void {
        $html = HTMLDocumentBuilder::create('Test', 'en')
            ->htmlAttribute('data-theme', 'light')
            ->render();

        $this->assertStringContainsString('lang="en"', $html);
        $this->assertStringContainsString('data-theme="light"', $html);
    }

    public function test_complex_document(): void {
        $html = HTMLDocumentBuilder::create('My Website', 'de')
            ->viewport()
            ->meta('description', 'Eine tolle Webseite')
            ->addInlineStyle('body { font-family: Arial, sans-serif; }')
            ->startDiv(['class' => 'wrapper'])
            ->startHeader(['class' => 'site-header'])
            ->h1('Willkommen')
            ->endHeader()
            ->startMain()
            ->startSection(['id' => 'intro'])
            ->h2('Einführung')
            ->p('Dies ist ein Test.')
            ->endSection()
            ->endMain()
            ->startFooter()
            ->p('© 2025')
            ->endFooter()
            ->endDiv()
            ->render();

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('lang="de"', $html);
        $this->assertStringContainsString('<title>My Website</title>', $html);
        $this->assertStringContainsString('class="wrapper"', $html);
        $this->assertStringContainsString('class="site-header"', $html);
        $this->assertStringContainsString('id="intro"', $html);
    }

    public function test_to_string(): void {
        $builder = HTMLDocumentBuilder::create('Test')
            ->p('Content');

        $html = (string) $builder;

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<title>Test</title>', $html);
        $this->assertStringContainsString('<p>Content</p>', $html);
    }

    public function test_scripts(): void {
        $html = HTMLDocumentBuilder::create()
            ->addHeadScript('head.js', defer: true)
            ->addScript('footer.js')
            ->addInlineScript('console.log("test");')
            ->render();

        $this->assertStringContainsString('src="head.js"', $html);
        $this->assertStringContainsString('defer', $html);
        $this->assertStringContainsString('src="footer.js"', $html);
        $this->assertStringContainsString('console.log("test")', $html);
    }
}
