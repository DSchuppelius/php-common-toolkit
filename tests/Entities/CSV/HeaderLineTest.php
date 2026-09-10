<?php

namespace Tests\Entities\CSV;

use CommonToolkit\Entities\CSV\HeaderLine;
use CommonToolkit\Helper\Data\StringHelper;
use Tests\Contracts\BaseTestCase;

class HeaderLineTest extends BaseTestCase {
    public function test_get_column_names(): void {
        $headerLine = new HeaderLine(['Name', 'Age', 'Email']);

        $this->assertEquals(['Name', 'Age', 'Email'], $headerLine->getColumnNames());
    }

    public function test_has_column(): void {
        $headerLine = new HeaderLine(['Name', 'Age', 'Email']);

        $this->assertTrue($headerLine->hasColumn('Name'));
        $this->assertTrue($headerLine->hasColumn('Age'));
        $this->assertTrue($headerLine->hasColumn('Email'));
        $this->assertFalse($headerLine->hasColumn('NonExistent'));
        $this->assertFalse($headerLine->hasColumn('name')); // Case sensitive
    }

    public function test_get_column_index(): void {
        $headerLine = new HeaderLine(['Name', 'Age', 'Email']);

        $this->assertEquals(0, $headerLine->getColumnIndex('Name'));
        $this->assertEquals(1, $headerLine->getColumnIndex('Age'));
        $this->assertEquals(2, $headerLine->getColumnIndex('Email'));
        $this->assertNull($headerLine->getColumnIndex('NonExistent'));
        $this->assertNull($headerLine->getColumnIndex('name')); // Case sensitive
    }

    public function test_with_duplicate_column_names(): void {
        $headerLine = new HeaderLine(['Name', 'Name', 'Age']);

        $this->assertEquals(['Name', 'Name', 'Age'], $headerLine->getColumnNames());
        $this->assertTrue($headerLine->hasColumn('Name'));
        $this->assertEquals(0, $headerLine->getColumnIndex('Name')); // Returns first occurrence
    }

    public function test_with_empty_column_names(): void {
        $headerLine = new HeaderLine(['', 'Name', '']);

        $this->assertEquals(['', 'Name', ''], $headerLine->getColumnNames());
        $this->assertTrue($headerLine->hasColumn(''));
        $this->assertTrue($headerLine->hasColumn('Name'));
        $this->assertEquals(0, $headerLine->getColumnIndex('')); // Returns first occurrence
    }

    public function test_get_column_index_normalized(): void {
        $headerLine = new HeaderLine([StringHelper::BOM_UTF8 . 'Kunde', ' Beginn ', "Ende\u{00A0}(Datum)", 'Dauer  in   Stunden']);

        // Exakter Vergleich bleibt der Standard (CSV-Felder sind bereits beim Parsen getrimmt,
        // Groß-/Kleinschreibung und BOM zählen aber weiterhin)
        $this->assertSame(1, $headerLine->getColumnIndex('Beginn'));
        $this->assertNull($headerLine->getColumnIndex('BEGINN'));
        $this->assertNull($headerLine->getColumnIndex('Kunde'));

        // Toleranter Vergleich: Trim, Groß-/Kleinschreibung, BOM, Whitespace-Kollaps, NBSP
        $this->assertSame(1, $headerLine->getColumnIndex('Beginn', true));
        $this->assertSame(1, $headerLine->getColumnIndex('BEGINN', true));
        $this->assertSame(1, $headerLine->getColumnIndex(' beginn ', true));
        $this->assertSame(0, $headerLine->getColumnIndex('kunde', true));
        $this->assertSame(2, $headerLine->getColumnIndex('Ende (Datum)', true));
        $this->assertSame(3, $headerLine->getColumnIndex('dauer in stunden', true));
        $this->assertNull($headerLine->getColumnIndex('Beginn Ende', true));
    }

    public function test_get_column_index_by_aliases(): void {
        $headerLine = new HeaderLine(['Kunde', ' Beginn ', 'Ende']);

        $this->assertSame(1, $headerLine->getColumnIndexByAliases(['Start', 'Beginn', 'Von']));
        $this->assertSame(1, $headerLine->getColumnIndexByAliases(['start', 'BEGINN']));
        // Erster passender Alias gewinnt, nicht die erste Header-Spalte
        $this->assertSame(2, $headerLine->getColumnIndexByAliases(['Ende', 'Kunde']));
        $this->assertNull($headerLine->getColumnIndexByAliases(['Start', 'Von']));
        $this->assertNull($headerLine->getColumnIndexByAliases([]));

        // Exakter Modus auf Wunsch
        $this->assertNull($headerLine->getColumnIndexByAliases(['BEGINN'], false));
        $this->assertSame(1, $headerLine->getColumnIndexByAliases(['Beginn'], false));
    }
}
