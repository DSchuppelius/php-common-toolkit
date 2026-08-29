<?php
/*
 * Created on   : Sat Aug 29 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DateTokenizerTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\DateTokenizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

class DateTokenizerTest extends BaseTestCase {
    /** @return array<string, array{0: string, 1: string, 2: int, 3: int, 4: ?int}> */
    public static function lines(): array {
        return [
            'TT.MM.JJJJ' => ['05.09.2023 Basislastschrift', 'dmy', 5, 9, 2023],
            'TT.MM.JJ' => ['01.12.23  Bsp GmbH', 'dmy2', 1, 12, 2023],
            'TT.MM.' => ['12.10. 12.10. Gutschrift', 'dm', 12, 10, null],
            'ISO' => ['2024-01-15  Zahlung', 'iso', 15, 1, 2024],
            'TT-MM-JJJJ' => ['27-05-2025  Belastingdienst', 'dmy-dash', 27, 5, 2025],
            'TT/MM/JJJJ' => ['12/11/2024  12/11/2024  IBTRF', 'dmy-slash', 12, 11, 2024],
            'TT/MM' => ['01/08  media control GmbH', 'dm-slash', 1, 8, null],
            'd. Monat JJJJ' => ['8. Juni 2023  8. Juni 2023  1713538408', 'd-month-y', 8, 6, 2023],
            'd Mon JJ' => ['3 Jan 24  Payment', 'd-mon-y2', 3, 1, 2024],
            'engl. Monat' => ['15 March 2024  Transfer', 'd-month-y', 15, 3, 2024],
            'März' => ['31. März 2024 Abschluss', 'd-month-y', 31, 3, 2024],
        ];
    }

    #[DataProvider('lines')]
    public function test_first(string $line, string $form, int $day, int $month, ?int $year): void {
        $token = DateTokenizer::first($line);
        $this->assertNotNull($token);
        $this->assertSame($form, $token->form);
        $this->assertSame([$day, $month, $year], [$token->day, $token->month, $token->year]);
    }

    public function test_mehrere_daten_mit_positionen(): void {
        $line = '02.04.2024    02.04.2024    Lastschrift';
        $tokens = DateTokenizer::tokens($line);
        $this->assertCount(2, $tokens);
        $this->assertSame(0, $tokens[0]->start);
        $this->assertSame(10, $tokens[0]->end);
        $this->assertSame(14, $tokens[1]->start);
    }

    public function test_ungueltige_und_keine_daten(): void {
        $this->assertSame([], DateTokenizer::tokens('Betrag 1.234,56 EUR'));
        $this->assertSame([], DateTokenizer::tokens('32.13.2024'), 'Tag/Monat außerhalb des Kalenders');
        $this->assertSame([], DateTokenizer::tokens('Version 1.2.3'));
    }

    public function test_resolve_mit_ersatzjahr(): void {
        $token = DateTokenizer::first('12.10. Gutschrift');
        $this->assertNotNull($token);
        $this->assertFalse($token->hasYear());
        $this->assertNull($token->resolve(null));
        $this->assertSame('2023-10-12', $token->resolve(2023)?->format('Y-m-d'));

        $leap = DateTokenizer::first('29.02. Zinsen');
        $this->assertNotNull($leap);
        $this->assertNull($leap->resolve(2023), '29.02.2023 gibt es nicht');
        $this->assertSame('2024-02-29', $leap->resolve(2024)?->format('Y-m-d'));
    }

    public function test_indent(): void {
        $this->assertSame(4, DateTokenizer::indent('    12.10. Gutschrift'));
        $this->assertSame(0, DateTokenizer::indent('12.10.'));
    }

    public function test_us_daten_mit_monat_zuerst(): void {
        $tokens = DateTokenizer::tokens('05/13/2025 CHECKCARD PURCHASE 05/12', true);
        $this->assertCount(2, $tokens);
        $this->assertSame([13, 5, 2025], [$tokens[0]->day, $tokens[0]->month, $tokens[0]->year]);
        $this->assertSame([12, 5, null], [$tokens[1]->day, $tokens[1]->month, $tokens[1]->year]);
        $this->assertSame('2025-05-13', $tokens[0]->resolve(null)?->format('Y-m-d'));

        $this->assertSame([], DateTokenizer::tokens('05/13/2025', false), 'Ohne Monat-zuerst ist 05/13 kein Datum');
    }
}
