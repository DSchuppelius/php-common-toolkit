<?php
/*
 * Created on   : Thu Jul 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StringHelperBomPrependTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\StringHelper;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für StringHelper::prependBom() (Gegenstück zu stripBom()).
 */
class StringHelperBomPrependTest extends BaseTestCase {
    public function test_prepends_utf8_bom_by_default(): void {
        $this->assertSame(StringHelper::BOM_UTF8 . 'Hallo', StringHelper::prependBom('Hallo'));
    }

    public function test_is_idempotent_for_existing_utf8_bom(): void {
        $withBom = StringHelper::BOM_UTF8 . 'Hallo';

        $this->assertSame($withBom, StringHelper::prependBom($withBom));
        $this->assertSame($withBom, StringHelper::prependBom(StringHelper::prependBom('Hallo')));
    }

    public function test_prepends_bom_for_other_encodings(): void {
        $this->assertSame(StringHelper::BOM_UTF16_LE . 'X', StringHelper::prependBom('X', 'UTF-16LE'));
        $this->assertSame(StringHelper::BOM_UTF16_BE . 'X', StringHelper::prependBom('X', 'UTF-16BE'));
        $this->assertSame(StringHelper::BOM_UTF32_LE . 'X', StringHelper::prependBom('X', 'UTF-32LE'));
    }

    public function test_idempotent_for_utf16_bom(): void {
        $withBom = StringHelper::BOM_UTF16_LE . 'X';
        $this->assertSame($withBom, StringHelper::prependBom($withBom, 'UTF-16LE'));
    }

    public function test_encoding_without_bom_returns_content_unchanged(): void {
        $this->assertSame('Hallo', StringHelper::prependBom('Hallo', 'ISO-8859-1'));
        $this->assertSame('Hallo', StringHelper::prependBom('Hallo', 'unbekannt'));
    }

    public function test_empty_content_gets_bom(): void {
        $this->assertSame(StringHelper::BOM_UTF8, StringHelper::prependBom(''));
    }

    public function test_roundtrip_with_strip_bom(): void {
        $content = 'Grüße aus München';
        $this->assertSame($content, StringHelper::stripBom(StringHelper::prependBom($content)));
    }
}
