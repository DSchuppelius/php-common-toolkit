<?php
/*
 * Created on   : Wed Sep 16 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : LogicalLinesTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper\CSV;

use CommonToolkit\Helper\Data\CSV\StringHelper;
use Tests\Contracts\BaseTestCase;

/**
 * Logische CSV-Zeilen aus physischen Zeilen: parseLineToFields() wirft bei
 * offenem Enclosure — wer physische Zeilen einzeln parst, explodiert beim
 * ersten mehrzeiligen quoted Feld. iterateLogicalLines() ist die
 * Zusammenfüge-Logik, die Aufrufer bislang selbst nachbauen mussten.
 */
class LogicalLinesTest extends BaseTestCase {
    public function test_iterate_merges_multiline_quoted_fields(): void {
        $physical = [
            '"A";"eins"',
            '"B";"Zeile 1',
            'Zeile 2"',
            '"C";"drei"',
        ];

        $logical = iterator_to_array(StringHelper::iterateLogicalLines($physical, ';', '"'), false);

        $this->assertSame([
            '"A";"eins"',
            "\"B\";\"Zeile 1\nZeile 2\"",
            '"C";"drei"',
        ], $logical);

        // Jede gelieferte Zeile ist parsebar — genau die Zusicherung des Iterators.
        foreach ($logical as $line) {
            $this->assertCount(2, StringHelper::parseLineToFields($line, ';', '"'));
        }
    }

    public function test_iterate_passes_plain_lines_through(): void {
        $physical = ['a;b;c', 'd;e;f'];

        $this->assertSame($physical, iterator_to_array(StringHelper::iterateLogicalLines($physical, ';', '"'), false));
    }

    public function test_iterate_yields_unclosed_rest_verbatim(): void {
        $physical = ['"A";"offen ohne Ende'];

        $logical = iterator_to_array(StringHelper::iterateLogicalLines($physical, ';', '"'), false);

        // Rest mit offenem Enclosure wird ausgeliefert, nicht verschluckt —
        // der Parser des Aufrufers entscheidet über den Fehler.
        $this->assertSame($physical, $logical);
    }

    public function test_split_delegates_to_iterator(): void {
        $csv = "\"A\";\"eins\"\r\n\"B\";\"Zeile 1\nZeile 2\"\n\"C\";\"drei\"";

        $this->assertSame([
            '"A";"eins"',
            "\"B\";\"Zeile 1\nZeile 2\"",
            '"C";"drei"',
        ], StringHelper::splitCsvByLogicalLine($csv, ';', '"'));
    }
}
