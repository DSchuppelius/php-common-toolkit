<?php
/*
 * Created on   : Thu Aug 20 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CellCanonicalStringTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Entities\XLSX;

use CommonToolkit\Entities\XLSX\Cell;
use DateTimeImmutable;
use Tests\Contracts\BaseTestCase;

/**
 * Kanonische String-Fassung einer Zelle für Text-/CSV-Pfade.
 */
class CellCanonicalStringTest extends BaseTestCase {
    public function test_date_without_time_keeps_date_only(): void {
        $cell = new Cell(new DateTimeImmutable('2026-08-20 00:00:00'), 'd');

        $this->assertSame('2026-08-20', $cell->toCanonicalString());
    }

    public function test_date_with_time_keeps_time(): void {
        $cell = new Cell(new DateTimeImmutable('2026-08-20 07:30:15'), 'd');

        $this->assertSame('2026-08-20 07:30:15', $cell->toCanonicalString());
    }

    /** Der (string)-Cast liefert hier 1.0E+25 — im CSV unbrauchbar. */
    public function test_large_float_stays_in_fixed_notation(): void {
        $cell = new Cell(1.0E+25, 'n');

        $this->assertSame('10000000000000000905969664', $cell->toCanonicalString());
    }

    public function test_float_drops_trailing_zeros(): void {
        $this->assertSame('12.5', (new Cell(12.50, 'n'))->toCanonicalString());
        $this->assertSame('12', (new Cell(12.0, 'n'))->toCanonicalString());
    }

    public function test_null_bool_and_string_fall_back_to_string_value(): void {
        $this->assertSame('', (new Cell(null))->toCanonicalString());
        $this->assertSame('1', (new Cell(true, 'b'))->toCanonicalString());
        $this->assertSame('0', (new Cell(false, 'b'))->toCanonicalString());
        $this->assertSame('Text', (new Cell('Text', 's'))->toCanonicalString());
    }

    /** Ganzzahlen bleiben unangetastet (kein Float-Pfad). */
    public function test_integer_is_unchanged(): void {
        $this->assertSame('42', (new Cell(42, 'n'))->toCanonicalString());
    }
}
