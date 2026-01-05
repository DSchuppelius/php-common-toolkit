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

    public function testDefaultEncoding(): void {
        $doc = new Document();
        $this->assertEquals('UTF-8', $doc->getEncoding());
    }

    public function testSetEncoding(): void {
        $doc = new Document();
        $result = $doc->setEncoding('ISO-8859-1');

        $this->assertSame($doc, $result, 'setEncoding sollte Fluent Interface unterstützen');
        $this->assertEquals('ISO-8859-1', $doc->getEncoding());
    }

    public function testDefaultBomIsFalse(): void {
        $doc = new Document();
        $this->assertFalse($doc->hasBom());
    }

    public function testWithBom(): void {
        $doc = new Document();
        $result = $doc->withBom();

        $this->assertSame($doc, $result, 'withBom sollte Fluent Interface unterstützen');
        $this->assertTrue($doc->hasBom());
    }

    public function testWithoutBom(): void {
        $doc = new Document();
        $doc->withBom();
        $result = $doc->withoutBom();

        $this->assertSame($doc, $result, 'withoutBom sollte Fluent Interface unterstützen');
        $this->assertFalse($doc->hasBom());
    }

    public function testSetBom(): void {
        $doc = new Document();

        $doc->setBom(true);
        $this->assertTrue($doc->hasBom());

        $doc->setBom(false);
        $this->assertFalse($doc->hasBom());
    }

    public function testDefaultLineEnding(): void {
        $doc = new Document();
        $this->assertEquals("\n", $doc->getLineEnding());
    }

    public function testWithWindowsLineEnding(): void {
        $doc = new Document();
        $result = $doc->withWindowsLineEnding();

        $this->assertSame($doc, $result, 'withWindowsLineEnding sollte Fluent Interface unterstützen');
        $this->assertEquals("\r\n", $doc->getLineEnding());
    }

    public function testWithUnixLineEnding(): void {
        $doc = new Document();
        $doc->withWindowsLineEnding();
        $result = $doc->withUnixLineEnding();

        $this->assertSame($doc, $result, 'withUnixLineEnding sollte Fluent Interface unterstützen');
        $this->assertEquals("\n", $doc->getLineEnding());
    }

    public function testSetLineEnding(): void {
        $doc = new Document();
        $result = $doc->setLineEnding("\r");

        $this->assertSame($doc, $result, 'setLineEnding sollte Fluent Interface unterstützen');
        $this->assertEquals("\r", $doc->getLineEnding());
    }

    public function testGetBomBytesWhenDisabled(): void {
        $doc = new Document();
        $this->assertEquals('', $doc->getBomBytes());
    }

    public function testGetBomBytesForUtf8(): void {
        $doc = new Document();
        $doc->setEncoding('UTF-8')->withBom();

        $this->assertEquals("\xEF\xBB\xBF", $doc->getBomBytes());
    }

    public function testGetBomBytesForUtf16LE(): void {
        $doc = new Document();
        $doc->setEncoding('UTF-16LE')->withBom();

        $this->assertEquals("\xFF\xFE", $doc->getBomBytes());
    }

    public function testGetBomBytesForUtf16BE(): void {
        $doc = new Document();
        $doc->setEncoding('UTF-16BE')->withBom();

        $this->assertEquals("\xFE\xFF", $doc->getBomBytes());
    }

    public function testSupportsBom(): void {
        $doc = new Document();

        $doc->setEncoding('UTF-8');
        $this->assertTrue($doc->supportsBom());

        $doc->setEncoding('UTF-16LE');
        $this->assertTrue($doc->supportsBom());

        $doc->setEncoding('ISO-8859-1');
        $this->assertFalse($doc->supportsBom());

        $doc->setEncoding('ASCII');
        $this->assertFalse($doc->supportsBom());
    }

    public function testFluentChaining(): void {
        $doc = new Document();

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
