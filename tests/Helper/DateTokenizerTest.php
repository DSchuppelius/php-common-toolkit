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

    public function test_englische_monat_zuerst_form_und_eindeutige_us_daten(): void {
        // Revolut 0402: "Apr 16, 2025   Payment from GESTEAM S R L   €2,040.00"
        $token = DateTokenizer::first('Apr 16, 2025   Payment from GESTEAM S R L');
        $this->assertNotNull($token);
        $this->assertSame('month-d-y', $token->form);
        $this->assertSame([16, 4, 2025], [$token->day, $token->month, $token->year]);
        $this->assertSame([3, 1, 2024], (function (): array {
            $t = DateTokenizer::first('January 3, 24 Payment');
            return [$t?->day, $t?->month, $t?->year];
        })());

        // db direct 0284: "08.13.2025" – der zweite Teil kann kein Monat sein, also MM.DD.JJJJ
        $us = DateTokenizer::first('08.13.2025 Transaction Type:  INSTANT PAYMENT SINGLE');
        $this->assertSame([13, 8, 2025], [$us?->day, $us?->month, $us?->year]);
        // Eindeutig deutsch bleibt deutsch
        $de = DateTokenizer::first('13.08.2025 Lastschrift');
        $this->assertSame([13, 8, 2025], [$de?->day, $de?->month, $de?->year]);
        // Zweideutig: ohne monthFirst deutsch, mit monthFirst amerikanisch – auch mit Punkten
        $this->assertSame([8, 5, 2025], (function (): array {
            $t = DateTokenizer::first('08.05.2025 Zahlung');
            return [$t?->day, $t?->month, $t?->year];
        })());
        // Punktierte Daten bleiben auch mit monthFirst deutsch – eine "12/27"-Referenz im Text
        // darf einen deutschen Auszug nicht kippen (Regression 0134)
        $this->assertSame([8, 5, 2025], (function (): array {
            $t = DateTokenizer::first('08.05.2025 Zahlung', true);
            return [$t?->day, $t?->month, $t?->year];
        })());
    }

    public function test_niederlaendische_monatskuerzel_und_tag_monat_mit_bindestrich(): void {
        // ABN Amro Kreditkarte NL: "17 dec  17 dec  EVERNOTE …"
        $nl = DateTokenizer::tokens('17 dec          17 dec            EVERNOTE       ZURICH');
        $this->assertCount(2, $nl);
        $this->assertSame([17, 12, null], [$nl[0]->day, $nl[0]->month, $nl[0]->year]);
        $volle = DateTokenizer::first('Datum afschrift 16 januari 2023');
        $this->assertSame([16, 1, 2023], [$volle?->day, $volle?->month, $volle?->year]);
        $mrt = DateTokenizer::first('3 mrt 2024 Betaling');
        $this->assertSame([3, 3, 2024], [$mrt?->day, $mrt?->month, $mrt?->year]);

        // ABN Amro Rekeningafschrift: "31-01  SEPA Overboeking  6.122,00" (Jahr aus dem Kopf)
        $dash = DateTokenizer::first('31-01           SEPA Overboeking');
        $this->assertNotNull($dash);
        $this->assertSame('dm-dash', $dash->form);
        $this->assertSame([31, 1, null], [$dash->day, $dash->month, $dash->year]);
        // Volles Datum mit Bindestrich bleibt wie bisher
        $voll = DateTokenizer::first('Datum afschrift 31-01-2022');
        $this->assertSame([31, 1, 2022], [$voll?->day, $voll?->month, $voll?->year]);
        // Kein Datum: Monatsteil > 12 ohne Jahr
        $this->assertSame([], DateTokenizer::tokens('Artikel 12-34'));
        // Wortbestandteile bleiben unberührt
        $this->assertSame([], DateTokenizer::tokens('17 Meierhof'));
    }

    public function test_monatsnamen_unabhaengig_von_gross_kleinschreibung(): void {
        // J.P. Morgan: "Incoming ACH Debit   16 SEP   DDT   16 SEP …"
        $tokens = DateTokenizer::tokens('Incoming ACH Debit                16 SEP          DDT          16 SEP');
        $this->assertCount(2, $tokens);
        $this->assertSame([16, 9, null], [$tokens[0]->day, $tokens[0]->month, $tokens[0]->year]);
        $this->assertSame([8, 6, 2023], (function (): array {
            $t = DateTokenizer::first('8. JUNI 2023 Miete');
            return [$t?->day, $t?->month, $t?->year];
        })());
        // Wortbestandteile bleiben unberührt
        $this->assertSame([], DateTokenizer::tokens('16 SEPTEMBERFEST'));
    }

    public function test_monatsname_ohne_jahr_als_zweites_datum(): void {
        // ApoBank: Buchungsdatum mit Jahr, Valuta ohne Jahr, dann eine Auftragsnummer
        $tokens = DateTokenizer::tokens('19. Apr. 2021   19. Apr.   471970286   Überweisung Online');
        $this->assertCount(2, $tokens);
        $this->assertSame('d-month-y', $tokens[0]->form);
        $this->assertSame(2021, $tokens[0]->year);
        $this->assertSame('d-month', $tokens[1]->form);
        $this->assertSame([19, 4, null], [$tokens[1]->day, $tokens[1]->month, $tokens[1]->year]);
        // Kein Datum: Monatsname als Wortbestandteil oder mit Jahr dahinter bleibt eine Form
        $this->assertCount(1, DateTokenizer::tokens('8. Juni 2023 Miete'));
        $this->assertSame([], DateTokenizer::tokens('3 Maiglöckchen'));
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

        // Eindeutig (13 kann kein Monat sein): auch ohne Monat-zuerst gelesen – seit v1.31
        $eindeutig = DateTokenizer::tokens('05/13/2025', false);
        $this->assertCount(1, $eindeutig);
        $this->assertSame([13, 5, 2025], [$eindeutig[0]->day, $eindeutig[0]->month, $eindeutig[0]->year]);
        // Ohne Jahr bleibt "05/13" ungelesen – dort ist Monat/Jahr gemeint (Belegnummern)
        $this->assertSame([], DateTokenizer::tokens('Beleg 05/13', false));
    }
}
