<?php
/*
 * Created on   : Wed Oct 21 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XmlHelperDomTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\XmlHelper;
use Tests\Contracts\BaseTestCase;

class XmlHelperDomTest extends BaseTestCase {
    public function test_safe_load_dom_reads_plain_xml(): void {
        $doc = XmlHelper::safeLoadDom('<w:document xmlns:w="urn:w"><w:p>Hallo</w:p><w:p>Welt</w:p></w:document>');
        $this->assertNotNull($doc);
        $this->assertSame('HalloWelt', $doc->documentElement?->textContent);
    }

    public function test_safe_load_dom_rejects_dtds_and_broken_xml_without_warnings(): void {
        $this->assertNull(XmlHelper::safeLoadDom('<?xml version="1.0"?><!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]><x>&e;</x>'));
        $this->assertNull(XmlHelper::safeLoadDom('<!doctype html><html></html>'));
        $this->assertNull(XmlHelper::safeLoadDom('<x><y></x>'));
        $this->assertNull(XmlHelper::safeLoadDom(''));
        $this->assertSame([], libxml_get_errors(), 'Fehler bleiben nicht im libxml-Puffer stehen.');
    }

    public function test_escape_produces_xml_entities_only(): void {
        $this->assertSame('a &amp; b &lt;c&gt; &quot;d&quot; &apos;e&apos; ä', XmlHelper::escape('a & b <c> "d" \'e\' ä'));
    }
}
