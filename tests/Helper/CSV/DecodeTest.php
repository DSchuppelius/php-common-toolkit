<?php
/*
 * Created on   : Thu Oct 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DecodeTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper\CSV;

use CommonToolkit\Helper\Data\CSV\StringHelper;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

/**
 * Feld-INHALTE nach RFC 4180 (decodeField/parseLineToValues), Leerraum
 * außerhalb der Enclosures im Tokenizer und die Trennzeichen-Erkennung mit
 * Enclosures. Die rohe Round-Trip-Form von parseLineToFields()/getValue()
 * bleibt unverändert — das prüfen LineTest und DataLineTest.
 */
class DecodeTest extends BaseTestCase {
    public function test_decode_field_variants(): void {
        $cases = [
            // ungequotet: unverändert, Leerraum ist Inhalt
            ['1234,56', '1234,56'],
            ['  a b  ', '  a b  '],
            // RFC 4180
            ['"1234,56"', '1234,56'],
            ['"A ""quoted"" text"', 'A "quoted" text'],
            ['"{""order_id"":5227}"', '{"order_id":5227}'],
            ['"x""y"', 'x"y'],
            ['"""abc"""', '"abc"'],
            ['"""Muster"" GmbH"', '"Muster" GmbH'],
            ["\"Zeile 1\nZeile 2\"", "Zeile 1\nZeile 2"],
            // Leerraum außerhalb der Enclosures
            ['  "x"  ', 'x'],
            // mehrfach gewrappt (Feldmodell-Semantik)
            ['""60,00""', '60,00'],
            ['"""2000,00"""', '"2000,00"'],
            // nur Enclosures: leer, wie im Feldmodell
            ['""', ''],
            ['""""', ''],
        ];

        foreach ($cases as [$raw, $expected]) {
            $this->assertSame($expected, StringHelper::decodeField($raw), 'decodeField(' . json_encode($raw) . ')');
        }
    }

    public function test_parse_line_to_values_decodes_each_field(): void {
        $line = '1234,56;"1.234,56";"A ""q"" t";""60,00"";"";"a;b"';

        $this->assertSame(
            ['1234,56', '1.234,56', 'A "q" t', '60,00', '', 'a;b'],
            StringHelper::parseLineToValues($line, ';')
        );
        // Rohform unverändert
        $this->assertSame(
            ['1234,56', '"1.234,56"', '"A ""q"" t"', '""60,00""', '""', '"a;b"'],
            StringHelper::parseLineToFields($line, ';', '"')
        );
    }

    public function test_space_between_delimiter_and_enclosure_is_tolerated(): void {
        $this->assertSame(['a', 'b', 'c', 'd'], StringHelper::parseLineToValues('a, "b", "c" ,d', ','));
        $this->assertSame(['x', 'Z'], StringHelper::parseLineToValues('"x"  ;Z', ';'));
        $this->assertSame(['Z', 'y'], StringHelper::parseLineToValues('Z;  "y"', ';'));
        // Exporte mit Kopfzeilen der Form "Account Number: ..." , (ABCB Bank)
        $this->assertSame(['Account Number: 1319 AED', ''], StringHelper::parseLineToValues('"Account Number: 1319 AED" ,', ','));
    }

    public function test_tab_delimiter_is_not_treated_as_outer_space(): void {
        $this->assertSame(['x', 'y', 'z'], StringHelper::parseLineToValues("x\t \"y\" \tz", "\t"));
        $this->assertSame(['', 'a', ''], StringHelper::parseLineToValues("\t\"a\"\t", "\t"));
    }

    /** Das zweite Quote eines escapten Paares bleibt Inhalt, auch vor Leerraum + Trennzeichen. */
    public function test_escaped_pair_before_space_and_delimiter_stays_content(): void {
        $this->assertSame(
            ['Er sagte "Hallo" ; dann', 'Z'],
            StringHelper::parseLineToValues('"Er sagte ""Hallo"" ; dann";Z', ';')
        );
    }

    public function test_text_after_closing_enclosure_is_still_rejected(): void {
        $this->expectException(RuntimeException::class);
        StringHelper::parseLineToValues('"ab"c;Z', ';');
    }

    /** Ein Quote direkt nach Text ist Inhalt (Zoll-Angabe) und öffnet kein mehrzeiliges Feld. */
    public function test_literal_quote_in_unquoted_field_does_not_join_lines(): void {
        $this->assertFalse(StringHelper::hasMultilineFields('Monitor;27"', ';'));
        $this->assertFalse(StringHelper::hasMultilineFields('5" Diskette;3,50', ';'));
        $this->assertSame(
            ['Artikel;Groesse', 'Monitor;27"', 'Kabel;2m'],
            iterator_to_array(StringHelper::iterateLogicalLines(['Artikel;Groesse', 'Monitor;27"', 'Kabel;2m'], ';'), false)
        );
    }

    /** Echte mehrzeilige Felder bleiben ein Datensatz, auch mit escapten Quotes am Zeilenende. */
    public function test_quoted_multiline_fields_still_join(): void {
        $this->assertTrue(StringHelper::hasMultilineFields('a;"Zeile 1', ';'));
        $this->assertTrue(StringHelper::hasMultilineFields('a;"Er sagte ""', ';'));
        $this->assertTrue(StringHelper::hasMultilineFields('"""ROOM 02, 21/F,""', ','));
        $this->assertFalse(StringHelper::hasMultilineFields('"Datum,""Empfänger"",""Betrag"""', ','));
        $this->assertFalse(StringHelper::hasMultilineFields('""60,00"";""a""', ';'));
        $this->assertSame(
            ["a;\"Zeile 1\nZeile 2\";b", 'c;d'],
            iterator_to_array(StringHelper::iterateLogicalLines(['a;"Zeile 1', 'Zeile 2";b', 'c;d'], ';'), false)
        );
    }

    /** Auch mit einem fremden Trennzeichen (Formatprüfung in der Erkennung) bleibt ein offenes Feld offen. */
    public function test_open_field_is_found_with_foreign_delimiter(): void {
        $this->assertTrue(StringHelper::hasMultilineFields('01.01.2025;"Abrechnung', ','));
        $this->assertTrue(StringHelper::hasMultilineFields("x,\"Verwendungszweck ", ';'));
    }

    public function test_detect_delimiter_ignores_delimiters_inside_enclosures(): void {
        // Je Zeile 1 Semikolon außerhalb, aber 3 Kommas innerhalb der Quotes
        $content = "\"Miete, Strom, Gas\";100\n\"Lohn, Gehalt, Bonus\";200\n";

        $this->assertSame(';', StringHelper::detectDelimiter($content, StringHelper::DEFAULT_DELIMITERS, 10));
    }

    /** Zeilenweise umwickelte Exporte (N26/Penta): das CSV steht IM gequoteten Zeilenfeld. */
    public function test_detect_delimiter_on_line_wrapped_export(): void {
        $content = "\"Datum,\"\"Empfänger\"\",\"\"Betrag (EUR)\"\"\"\n"
            . "\"2023-10-02,\"\"CHECK24 GmbH\"\",\"\"56.64\"\"\"\n";

        $this->assertSame(',', StringHelper::detectDelimiter($content, StringHelper::DEFAULT_DELIMITERS, 10));
    }

    /** SumUp: umwickelte Zeile plus angehängtes Semikolon. */
    public function test_detect_delimiter_on_line_wrapped_export_with_trailing_delimiter(): void {
        $content = "\"Kundennummer,\"\"Datum\"\",\"\"UUID\"\",\"\"Einnahme\"\"\";\n"
            . "\"MDC2,\"\"21.12.2024\"\",\"\"c832\"\",\"\"2000,00\"\"\";\n";

        $this->assertSame(',', StringHelper::detectDelimiter($content, StringHelper::DEFAULT_DELIMITERS, 10));
    }

    /** Stripe-Mischdatei: Semikolon-Kopf, darunter umwickelte Komma-Zeilen mit Excel-Leerzellen. */
    public function test_detect_delimiter_on_mixed_wrapped_file_keeps_outer_delimiter(): void {
        $content = "Type;ID;Created;Amount;Fees;Net;Customer;A;B;C\n"
            . "Charge;ch_1;30.12.2024 08:00;188;3,07;184,93;Natalie;;;\n"
            . "\"Charge,ch_2,2025-01-07 10:32,\"\"306,55\"\",\"\"4,85\"\",Erika\";;;;;;;;;\n";

        $this->assertSame(';', StringHelper::detectDelimiter($content, StringHelper::DEFAULT_DELIMITERS, 10));
    }

    public function test_detect_delimiter_by_consistency_beats_frequent_decimal_commas(): void {
        // Zwei Spalten je Zeile, aber mehr Dezimalkommas als Semikolons
        $content = "Kauf;BCH 38,66016954\n28.12.2023 16:53;258,66 €/BCH - 10.000,00 €\n"
            . "Verkauf;BCH 87,42723288\n28.12.2023 16:47;242,11 €/BCH + 21.167,28 €\n";

        $this->assertSame(',', StringHelper::detectDelimiter($content, StringHelper::DEFAULT_DELIMITERS, 10));
        $this->assertSame(';', StringHelper::detectDelimiterByConsistency($content));
    }

    public function test_detect_delimiter_by_consistency_respects_enclosures(): void {
        $this->assertSame(',', StringHelper::detectDelimiterByConsistency("a,b\n\"1,5\",\"2,5\"\n\"3,5\",x\n"));
        $this->assertSame("\t", StringHelper::detectDelimiterByConsistency("a\tb\tc\n1,5\t2,5\t3\n"));
    }

    public function test_detect_delimiter_by_consistency_without_delimiter_returns_default(): void {
        $this->assertSame(';', StringHelper::detectDelimiterByConsistency("Mitgliederliste\nMeier\nSchulze\n"));
        $this->assertSame('|', StringHelper::detectDelimiterByConsistency("eine Spalte\n", StringHelper::DEFAULT_DELIMITERS, 50, '|'));
    }
}
