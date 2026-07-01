<?php
/*
 * Created on   : Wed Jul 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DocumentEncodeModeTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Entities\CSV;

use CommonToolkit\Builders\CSVDocumentBuilder;
use CommonToolkit\Entities\CSV\{DataLine, HeaderLine};
use CommonToolkit\Entities\CSV\Document;
use CommonToolkit\Helper\Data\CSV\StringHelper;
use Tests\Contracts\BaseTestCase;

class DocumentEncodeModeTest extends BaseTestCase {
    private function buildDoc(): Document {
        return (new CSVDocumentBuilder)
            ->setHeader(new HeaderLine(['a', 'b', 'c'], ';', '"'))
            ->addRow(new DataLine(['Meyer; Sohn', 'sagt "hallo"', 'plain'], ';', '"'))
            ->build();
    }

    public function test_encode_mode_safely_quotes_fresh_values(): void {
        // Opt-in: aus Rohwerten erzeugte Felder werden RFC-4180-sicher encodiert.
        $encoded = $this->buildDoc()->toString(';', '"', null, null, true);

        $this->assertStringContainsString('"Meyer; Sohn";"sagt ""hallo""";plain', $encoded);

        // Die Datenzeile splittet wieder in genau 3 Felder — das ; im Wert ist
        // geschützt (kein zusätzlicher Spaltentrenner).
        $lines = preg_split('/\r\n|\n/', $encoded) ?: [];
        $this->assertCount(3, StringHelper::parseLineToFields($lines[1], ';', '"'));
    }

    public function test_default_is_rfc_safe_and_false_opts_into_roundtrip(): void {
        $doc = $this->buildDoc();

        // Default (encodeValues=true): RFC-4180-sichere Ausgabe.
        $this->assertStringContainsString('"Meyer; Sohn"', $doc->toString(';', '"'));

        // Explizit false = bisheriges round-trip-Verhalten (Opt-out): frisch
        // gebaute Werte werden NICHT umschlossen.
        $this->assertStringNotContainsString('"Meyer; Sohn"', $doc->toString(';', '"', null, null, false));
    }
}
