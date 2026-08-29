<?php
/*
 * Created on   : Sat Aug 29 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AmountTokenizerTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\AmountTokenizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

class AmountTokenizerTest extends BaseTestCase {
    /** @return array<string, array{0: string, 1: float}> */
    public static function forms(): array {
        return [
            'DE mit Tausenderpunkt' => ['1.234,56', 1234.56],
            'DE ohne Tausender' => ['39,99', 39.99],
            'EN mit Komma' => ['1,234.56', 1234.56],
            'EN klein' => ['0.50', 0.5],
            'CH Apostroph' => ["1'234.56", 1234.56],
            'FR Leerzeichen' => ['1 234,56', 1234.56],
        ];
    }

    #[DataProvider('forms')]
    public function test_to_float(string $raw, float $expected): void {
        $this->assertSame($expected, AmountTokenizer::toFloat($raw));
    }

    /** @return array<string, array{0: string, 1: float, 2: bool, 3: ?string}> */
    public static function lines(): array {
        return [
            'Vorzeichen vorn, Währung hinten' => ['01.12.23  Bsp GmbH   +2.082,50 EUR', 2082.5, true, 'EUR'],
            'negativ' => ['05.09.2023 Basislastschrift   -107,00', -107.0, true, null],
            'H = Haben' => ['12.10. 12.10. Gutschrift   175,00 H', 175.0, true, null],
            'S = Soll' => ['31.10. 31.10. siehe Anlage 1   1,61 S', -1.61, true, null],
            'Euro-Zeichen ohne Leerzeichen' => ['BS HERCEG NOVI   01.08.2023   -30,30€', -30.3, true, '€'],
            'EN ohne Vorzeichen' => ['12/11/2024  IBTRF  30,000.00', 30000.0, false, null],
            'CH mit Währung davor' => ['Zahlung CHF 1\'234.56', 1234.56, false, 'CHF'],
            'nachgestelltes Minus' => ['Zinsen   5,68-', -5.68, true, null],
            'Unicode-Minus' => ['Gebühr −12,00', -12.0, true, null],
            'DR = Soll' => ['Payment 250.00 DR', -250.0, true, null],
            'Vorzeichen mit Leerzeichen (Qonto)' => ['02/10   STRIPE   + 5332.34 EUR', 5332.34, true, 'EUR'],
        ];
    }

    #[DataProvider('lines')]
    public function test_tokens(string $line, float $value, bool $hasSign, ?string $currency): void {
        $tokens = AmountTokenizer::tokens($line);
        $this->assertCount(1, $tokens);
        $this->assertSame($value, $tokens[0]->value);
        $this->assertSame($hasSign, $tokens[0]->hasSign);
        $this->assertSame($currency, $tokens[0]->currency);
    }

    public function test_keine_betraege(): void {
        $this->assertSame([], AmountTokenizer::tokens('Ab 01.10.2023 neuer Zinssatz  9,9000 v.H. für'));
        $this->assertSame([], AmountTokenizer::tokens('Kurs 1,0842 EUR/USD'));
        $this->assertSame([], AmountTokenizer::tokens('Referenz 127.541.963-1 ABS. 05/23'));
        $this->assertNull(AmountTokenizer::first('nichts'));
    }

    public function test_positionen_und_mehrere_betraege(): void {
        $line = '05.11.2024   PUR Noon Dubai       2,215.67             0.00     650,916.85';
        $tokens = AmountTokenizer::tokens($line);
        $this->assertCount(3, $tokens);
        $this->assertSame('2,215.67', $tokens[0]->raw);
        $this->assertTrue($tokens[1]->isZero());
        $this->assertSame(650916.85, AmountTokenizer::last($line)?->value);
        $this->assertSame(mb_strpos($line, '2,215.67'), $tokens[0]->start);
        $this->assertSame($tokens[0]->start + 8, $tokens[0]->end);
        $this->assertNull(AmountTokenizer::parse($line), 'parse() verlangt genau einen Betrag');
    }

    public function test_parse_einzelwert(): void {
        $this->assertSame(-1234.56, AmountTokenizer::parse('-1.234,56 EUR'));
        $this->assertSame(490.0, AmountTokenizer::parse('490,00+'));
        $this->assertSame(-490.0, AmountTokenizer::parse('490,00-'));
        $this->assertNull(AmountTokenizer::parse('1234.5'), 'eine Nachkommastelle ist kein Betrag');
    }

    public function test_umlaute_verschieben_positionen_nicht(): void {
        $line = 'Überweisung Müller   -50,00';
        $token = AmountTokenizer::first($line);
        $this->assertNotNull($token);
        $this->assertSame(mb_strpos($line, '50,00'), $token->start);
    }
}
