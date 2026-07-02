<?php
/*
 * Created on   : Thu Jul 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EncodeFputcsvParityTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper\CSV;

use CommonToolkit\Enums\Common\CSV\QuotingStyle;
use CommonToolkit\Helper\Data\CSV\StringHelper;
use Tests\Contracts\BaseTestCase;

/**
 * Byte-Paritäts-Tests: QuotingStyle::FPUTCSV muss exakt das Quoting von PHPs
 * fputcsv() nachbilden.
 *
 * Terminator-Konvention: fputcsv() hängt "\n" an, encodeLine() nicht –
 * für den Vergleich wird daher encodeLine(...) . "\n" verwendet.
 */
class EncodeFputcsvParityTest extends BaseTestCase {
    /**
     * Edge-Case-Korpus: Umlaute, Semikolon, Komma, Anführungszeichen,
     * Leerzeichen, Tab, Backslash, leer, 0, Zeilenumbruch im Feld.
     *
     * @var array<int, string>
     */
    private const CORPUS = [
        'plain',
        'Grüße aus München',       // Umlaute
        'a;b',                     // Semikolon
        'a,b',                     // Komma
        'he"llo',                  // Anführungszeichen
        '"',                       // nur Anführungszeichen
        '""',                      // doppelte Anführungszeichen
        'a b',                     // Leerzeichen mittig
        ' leading',                // führendes Leerzeichen
        'trailing ',               // schließendes Leerzeichen
        "a\tb",                    // Tab
        'a\\b',                    // Backslash
        '\\"',                     // Backslash + Quote (fputcsv-Escape-Semantik!)
        'a\\\\"b',                 // doppelter Backslash + Quote
        '',                        // leer
        '0',                       // Null-String
        "line1\nline2",            // Zeilenumbruch im Feld
        "line1\r\nline2",          // CRLF im Feld
        'Straße "Nr. 7"; Hof, Tor 2',
    ];

    /**
     * Referenz-Encoding über echtes fputcsv() nach php://temp.
     *
     * @param array<int, string> $fields
     */
    private function fputcsvReference(array $fields, string $delimiter, string $enclosure, string $escape): string {
        $handle = fopen('php://temp', 'r+');
        $this->assertNotFalse($handle);

        fputcsv($handle, $fields, $delimiter, $enclosure, $escape);
        rewind($handle);
        $line = stream_get_contents($handle);
        fclose($handle);

        $this->assertIsString($line);
        return $line;
    }

    public function test_byte_parity_per_field_against_fputcsv(): void {
        foreach ([',', ';'] as $delimiter) {
            foreach (['\\', ''] as $escape) {
                foreach (self::CORPUS as $value) {
                    $expected = $this->fputcsvReference([$value], $delimiter, '"', $escape);
                    $actual = StringHelper::encodeLine([$value], $delimiter, '"', QuotingStyle::FPUTCSV, $escape) . "\n";

                    $this->assertSame(
                        $expected,
                        $actual,
                        sprintf(
                            'Byte-Parität verletzt für Feld %s (delimiter=%s, escape=%s): fputcsv=%s, encodeLine=%s',
                            json_encode($value),
                            json_encode($delimiter),
                            json_encode($escape),
                            json_encode($expected),
                            json_encode($actual)
                        )
                    );
                }
            }
        }
    }

    public function test_byte_parity_full_corpus_as_single_line(): void {
        foreach ([',', ';'] as $delimiter) {
            foreach (['\\', ''] as $escape) {
                $expected = $this->fputcsvReference(self::CORPUS, $delimiter, '"', $escape);
                $actual = StringHelper::encodeLine(self::CORPUS, $delimiter, '"', QuotingStyle::FPUTCSV, $escape) . "\n";

                $this->assertSame($expected, $actual, "Byte-Parität der Gesamtzeile verletzt (delimiter=$delimiter, escape=" . json_encode($escape) . ')');
            }
        }
    }

    public function test_fputcsv_quotes_space_tab_and_escape_char(): void {
        // fputcsv quotet bei Leerzeichen, Tab und Escape-Zeichen – RFC-4180-minimal nicht.
        $this->assertSame('"a b"', StringHelper::encodeField('a b', ',', '"', QuotingStyle::FPUTCSV));
        $this->assertSame("\"a\tb\"", StringHelper::encodeField("a\tb", ',', '"', QuotingStyle::FPUTCSV));
        $this->assertSame('"a\\b"', StringHelper::encodeField('a\\b', ',', '"', QuotingStyle::FPUTCSV));

        // Minimal-Modus quotet diese Fälle NICHT (Verhaltensunterschied dokumentiert):
        $this->assertSame('a b', StringHelper::encodeField('a b', ',', '"', QuotingStyle::MINIMAL));
        $this->assertSame("a\tb", StringHelper::encodeField("a\tb", ',', '"', QuotingStyle::MINIMAL));
        $this->assertSame('a\\b', StringHelper::encodeField('a\\b', ',', '"', QuotingStyle::MINIMAL));
    }

    public function test_fputcsv_does_not_double_quote_after_escape_char(): void {
        // fputcsv-Eigenheit: ein Enclosure direkt nach dem Escape-Zeichen wird NICHT verdoppelt.
        $this->assertSame('"a\\"b"', StringHelper::encodeField('a\\"b', ',', '"', QuotingStyle::FPUTCSV, '\\'));
        // Ohne Escape-Zeichen greift die normale Verdopplung.
        $this->assertSame('"a\\""b"', StringHelper::encodeField('a\\"b', ',', '"', QuotingStyle::FPUTCSV, ''));
    }

    public function test_plain_values_stay_unquoted_in_fputcsv_mode(): void {
        $this->assertSame('plain', StringHelper::encodeField('plain', ',', '"', QuotingStyle::FPUTCSV));
        $this->assertSame('0', StringHelper::encodeField('0', ',', '"', QuotingStyle::FPUTCSV));
        $this->assertSame('', StringHelper::encodeField('', ',', '"', QuotingStyle::FPUTCSV));
        // Umlaute allein lösen kein Quoting aus.
        $this->assertSame('Bär', StringHelper::encodeField('Bär', ',', '"', QuotingStyle::FPUTCSV));
    }

    public function test_bool_parameter_stays_backward_compatible(): void {
        // Deprecated Bool-Alias: true = ALWAYS, false = MINIMAL.
        $this->assertSame('"plain"', StringHelper::encodeField('plain', ';', '"', true));
        $this->assertSame('plain', StringHelper::encodeField('plain', ';', '"', false));
        $this->assertSame(
            StringHelper::encodeLine(['a', 'b', null], ';', '"', true),
            StringHelper::encodeLine(['a', 'b', null], ';', '"', QuotingStyle::ALWAYS)
        );
        $this->assertSame(
            StringHelper::encodeLine(['a;b', 'c'], ';', '"', false),
            StringHelper::encodeLine(['a;b', 'c'], ';', '"', QuotingStyle::MINIMAL)
        );
    }
}
