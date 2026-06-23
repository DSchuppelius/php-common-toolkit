<?php
/*
 * Created on   : Sun Jan 05 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TextDocumentAbstractTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Contracts\Abstracts;

use CommonToolkit\Entities\CSV\Document;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für TextDocumentAbstract via CSV\Document.
 */
class TextDocumentAbstractTest extends BaseTestCase {
    public function test_default_encoding(): void {
        $doc = new Document;
        $this->assertEquals('UTF-8', $doc->getEncoding());
    }

    public function test_set_encoding(): void {
        $doc = new Document;
        $result = $doc->setEncoding('ISO-8859-1');

        $this->assertSame($doc, $result, 'setEncoding sollte Fluent Interface unterstützen');
        $this->assertEquals('ISO-8859-1', $doc->getEncoding());
    }

    public function test_default_bom_is_false(): void {
        $doc = new Document;
        $this->assertFalse($doc->hasBom());
    }

    public function test_with_bom(): void {
        $doc = new Document;
        $result = $doc->withBom();

        $this->assertSame($doc, $result, 'withBom sollte Fluent Interface unterstützen');
        $this->assertTrue($doc->hasBom());
    }

    public function test_without_bom(): void {
        $doc = new Document;
        $doc->withBom();
        $result = $doc->withoutBom();

        $this->assertSame($doc, $result, 'withoutBom sollte Fluent Interface unterstützen');
        $this->assertFalse($doc->hasBom());
    }

    public function test_set_bom(): void {
        $doc = new Document;

        $doc->setBom(true);
        $this->assertTrue($doc->hasBom());

        $doc->setBom(false);
        $this->assertFalse($doc->hasBom());
    }

    public function test_default_line_ending(): void {
        $doc = new Document;
        $this->assertEquals("\n", $doc->getLineEnding());
    }

    public function test_with_windows_line_ending(): void {
        $doc = new Document;
        $result = $doc->withWindowsLineEnding();

        $this->assertSame($doc, $result, 'withWindowsLineEnding sollte Fluent Interface unterstützen');
        $this->assertEquals("\r\n", $doc->getLineEnding());
    }

    public function test_with_unix_line_ending(): void {
        $doc = new Document;
        $doc->withWindowsLineEnding();
        $result = $doc->withUnixLineEnding();

        $this->assertSame($doc, $result, 'withUnixLineEnding sollte Fluent Interface unterstützen');
        $this->assertEquals("\n", $doc->getLineEnding());
    }

    public function test_set_line_ending(): void {
        $doc = new Document;
        $result = $doc->setLineEnding("\r");

        $this->assertSame($doc, $result, 'setLineEnding sollte Fluent Interface unterstützen');
        $this->assertEquals("\r", $doc->getLineEnding());
    }

    public function test_get_bom_bytes_when_disabled(): void {
        $doc = new Document;
        $this->assertEquals('', $doc->getBomBytes());
    }

    public function test_get_bom_bytes_for_utf8(): void {
        $doc = new Document;
        $doc->setEncoding('UTF-8')->withBom();

        $this->assertEquals("\xEF\xBB\xBF", $doc->getBomBytes());
    }

    public function test_get_bom_bytes_for_utf16_le(): void {
        $doc = new Document;
        $doc->setEncoding('UTF-16LE')->withBom();

        $this->assertEquals("\xFF\xFE", $doc->getBomBytes());
    }

    public function test_get_bom_bytes_for_utf16_be(): void {
        $doc = new Document;
        $doc->setEncoding('UTF-16BE')->withBom();

        $this->assertEquals("\xFE\xFF", $doc->getBomBytes());
    }

    public function test_supports_bom(): void {
        $doc = new Document;

        $doc->setEncoding('UTF-8');
        $this->assertTrue($doc->supportsBom());

        $doc->setEncoding('UTF-16LE');
        $this->assertTrue($doc->supportsBom());

        $doc->setEncoding('ISO-8859-1');
        $this->assertFalse($doc->supportsBom());

        $doc->setEncoding('ASCII');
        $this->assertFalse($doc->supportsBom());
    }

    public function test_fluent_chaining(): void {
        $doc = new Document;

        $result = $doc
            ->setEncoding('UTF-8')
            ->withBom()
            ->withWindowsLineEnding();

        $this->assertSame($doc, $result);
        $this->assertEquals('UTF-8', $doc->getEncoding());
        $this->assertTrue($doc->hasBom());
        $this->assertEquals("\r\n", $doc->getLineEnding());
    }
}
