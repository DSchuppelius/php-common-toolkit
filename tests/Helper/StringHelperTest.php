<?php
/*
 * Created on   : Wed Apr 02 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StringHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Enums\CaseType;
use CommonToolkit\Helper\Data\StringHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

class StringHelperTest extends BaseTestCase {
    public function test_utf8_to_iso8859_1(): void {
        $utf8 = "Grüße";
        $iso = StringHelper::utf8ToIso8859_1($utf8);
        $this->assertNotSame($utf8, $iso);
    }

    public function test_convert_encoding(): void {
        $original = '€ Zeichen';
        $converted = StringHelper::convertEncoding($original, 'UTF-8', 'ISO-8859-1');
        $this->assertNotSame($original, $converted);
    }

    public function test_sanitize_printable(): void {
        $string = "abc" . chr(7) . "def";
        $cleaned = StringHelper::sanitizePrintable($string);
        $this->assertStringNotContainsString(chr(7), $cleaned);
    }

    public function test_strip_invalid_xml_chars(): void {
        // C0-Steuerzeichen (außer Tab/LF/CR) werden entfernt …
        $input = "Zahlung" . chr(0x0C) . "ABC" . chr(0x00) . chr(0x1F) . " Ende";
        $this->assertSame('ZahlungABC Ende', StringHelper::stripInvalidXmlChars($input));
        // … Tab (0x09), LF (0x0A) und CR (0x0D) bleiben erhalten …
        $this->assertSame("a\tb\nc\rd", StringHelper::stripInvalidXmlChars("a\tb\nc\rd"));
        // … und Umlaute/UTF-8 bleiben unangetastet (anders als sanitizePrintable).
        $this->assertSame('Müller & Söhne', StringHelper::stripInvalidXmlChars('Müller & Söhne'));
        $this->assertSame('', StringHelper::stripInvalidXmlChars(null));
    }

    public function test_to_sepa_restricted_charset(): void {
        // Umlaute/Akzente gefaltet; erlaubte Sonderzeichen (/ - ? : ( ) . , ' +) bleiben.
        $this->assertSame('Rechnung Nr. 12/2026 (Jan)', StringHelper::toSepaRestrictedCharset('Rechnung Nr. 12/2026 (Jan)'));
        $this->assertSame('Francois Lefevre, Paris', StringHelper::toSepaRestrictedCharset('François Lefèvre, Paris'));
        // EPC-Best-Practice-Umsetzung: & -> +, * -> ., $ -> ., % -> . (DFÜ-Abkommen Anlage 3).
        $this->assertSame('Mueller + Soehne GmbH', StringHelper::toSepaRestrictedCharset('Müller & Söhne GmbH'));
        $this->assertSame('Preis .100 + mehr', StringHelper::toSepaRestrictedCharset('Preis $100 & mehr'));
        // Nicht abgedeckte Zeichen (< > ") werden entfernt.
        $this->assertSame('A + B Co x', StringHelper::toSepaRestrictedCharset('A & B <Co> "x"'));
        $this->assertSame('', StringHelper::toSepaRestrictedCharset(null));
    }

    public function test_remove_non_ascii(): void {
        $input = "ÄÖÜabc";
        $ascii = StringHelper::removeNonAscii($input);
        $this->assertSame('abc', $ascii);
    }

    public function test_to_ascii(): void {
        // Deutsche Umlaute werden ausgeschrieben (nicht entfernt).
        $this->assertSame('Gruesse Mueller Strasse', StringHelper::toAscii('Grüße Müller Straße'));
        $this->assertSame('Aepfel Oel Ueber', StringHelper::toAscii('Äpfel Öl Über'));
        // Diakritika werden gefaltet, Trim am Rand.
        $this->assertSame('Cafe Resume', StringHelper::toAscii('  Café Résumé  '));
        // Leer/null.
        $this->assertSame('', StringHelper::toAscii(''));
        $this->assertSame('', StringHelper::toAscii(null));
        // Reiner ASCII-Text bleibt unverändert.
        $this->assertSame('Hello World 123', StringHelper::toAscii('Hello World 123'));
    }

    /**
     * Charakterisierung: Ausgaben der bisherigen iconv-Implementierung (unter der
     * PHP-Standard-Locale C.UTF-8), die byte-gleich erhalten bleiben muessen.
     *
     * @return array<string, array{string, string}>
     */
    public static function asciiCharacterizationProvider(): array {
        return [
            'Umlaute klein' => ['aeoeue', "\u{00E4}\u{00F6}\u{00FC}"],
            'Umlaute gross' => ['AeOeUe', "\u{00C4}\u{00D6}\u{00DC}"],
            'Eszett' => ['Strasse', "Stra\u{00DF}e"],
            'Versal-Eszett' => ['SS', "\u{1E9E}"],
            'Euro' => ['10 EUR', "10 \u{20AC}"],
            'Pfund' => ['GBP', "\u{00A3}"],
            'Anfuehrungszeichen deutsch' => [',,Zitat"', "\u{201E}Zitat\u{201C}"],
            'Anfuehrungszeichen einfach' => [",x'", "\u{201A}x\u{2018}"],
            'Guillemets' => ['<<y>>', "\u{00AB}y\u{00BB}"],
            'Gerade Zeichen' => ['"z" \'q\'', '"z" \'q\''],
            'Halbgeviertstrich' => ['A - B', "A \u{2013} B"],
            'Geviertstrich' => ['A -- B', "A \u{2014} B"],
            'Auslassung' => ['...', "\u{2026}"],
            'Akzente' => ['Cafe Resume', "Caf\u{00E9} R\u{00E9}sum\u{00E9}"],
            'Ligaturen' => ['ae oe', "\u{00E6} \u{0153}"],
            'Nordisch' => ['o O a', "\u{00F8} \u{00D8} \u{00E5}"],
            'Tilde' => ['n', "\u{00F1}"],
            'Copyright/Trademark' => ['(C) (TM)', "\u{00A9} \u{2122}"],
            'Bruch' => ['1 1/2', "1\u{00BD}"],
            'Mal-Zeichen' => ['2x3', "2\u{00D7}3"],
            'Aufzaehlungspunkt' => ['o Punkt', "\u{2022} Punkt"],
            'Mikro' => ['ug', "\u{00B5}g"],
            'Geschuetztes Leerzeichen' => ['A B', "A\u{00A0}B"],
            'Nullbreite' => ['ZeroWidth', "Zero\u{200B}Width"],
            'Variantenwaehler' => ['I', "I\u{FE0F}"],
            'Kombinierendes Trema' => ['Tast', "Ta\u{0308}st"],
            'ASCII-Fragezeichen bleibt' => ['Was?', 'Was?'],
            'ASCII-Sonderzeichen' => ['x & y * $ % <> "@#', 'x & y * $ % <> "@#'],
            'Steuerzeichen im ASCII bleiben' => ["a\tb", "a\tb"],
            'Trim' => ['Gruesse', "  Gr\u{00FC}\u{00DF}e  "],
        ];
    }

    #[DataProvider('asciiCharacterizationProvider')]
    public function test_to_ascii_characterization(string $expected, string $input): void {
        $this->assertSame($expected, StringHelper::toAscii($input));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function asciiFoldingProvider(): array {
        return [
            'Tuerkisch' => ['Yatirim Islemleri sg', "Yat\u{0131}r\u{0131}m \u{0130}\u{015F}lemleri \u{015F}\u{011F}"],
            'Polnisch' => ['Lodz l', "\u{0141}\u{00F3}d\u{017A} \u{0142}"],
            'Franzoesisch' => ['Ca va, ete', "\u{00C7}a va, \u{00E9}t\u{00E9}"],
            'Kyrillisch' => ['Z', "\u{0416}"],
            'Rahmenlinie' => ['Rosenmontag', "Rosenmontag\u{2500}\u{2500}\u{2500}\u{2500}"],
            'Block und Dingbat' => ['ab', "a\u{2588}\u{2714}b"],
            'Emoji' => ['Party  ok', "Party \u{1F389} ok"],
            'Emoji mit ZWJ' => ['xy', "x\u{1F468}\u{200D}\u{1F469}y"],
            'Unabbildbare Symbole' => ['Nr. 5', "Nr.\u{00A7} 5\u{00B0}"],
        ];
    }

    #[DataProvider('asciiFoldingProvider')]
    public function test_to_ascii_folds_foreign_letters_and_drops_symbols(string $expected, string $input): void {
        $this->assertSame($expected, StringHelper::toAscii($input));
    }

    public function test_to_ascii_is_locale_independent_and_never_emits_question_marks(): void {
        $previous = setlocale(LC_CTYPE, '0');
        try {
            setlocale(LC_CTYPE, 'C');
            $input = "Yat\u{0131}r\u{0131}m \u{00E9} \u{0142} \u{2500} \u{1F389} \u{4E2D} \u{00A7} Gr\u{00FC}\u{00DF}e 10 \u{20AC} \u{201E}x\u{201C} \u{2014}";
            $result = StringHelper::toAscii($input);
            $this->assertSame('Yatirim e l   zhong  Gruesse 10 EUR ,,x" --', $result);
            $this->assertStringNotContainsString('?', $result);
            // Die Locale des Aufrufers bleibt unangetastet.
            $this->assertSame('C', setlocale(LC_CTYPE, '0'));
        } finally {
            if (is_string($previous)) {
                setlocale(LC_CTYPE, $previous);
            }
        }
    }

    public function test_to_ascii_drops_invalid_utf8_bytes(): void {
        $this->assertSame("ab\tc e", StringHelper::toAscii("a\xFFb\tc \u{00E9}"));
    }

    public function test_to_sepa_restricted_charset_characterization(): void {
        $this->assertSame(',,Zitat ,x\' y z \'q\'', StringHelper::toSepaRestrictedCharset("\u{201E}Zitat\u{201C} \u{201A}x\u{2018} \u{00AB}y\u{00BB} \"z\" 'q'"));
        $this->assertSame('10 EUR - A -- B ...', StringHelper::toSepaRestrictedCharset("10 \u{20AC} \u{2013} A \u{2014} B \u{2026}"));
        $this->assertSame('x + y . . .', StringHelper::toSepaRestrictedCharset('x & y * $ % <> "@#'));
        $this->assertSame('Strasse 1 1/2', StringHelper::toSepaRestrictedCharset("Stra\u{00DF}e 1\u{00BD}"));
    }

    public function test_truncate(): void {
        $text = "Das ist ein sehr langer Text";
        $short = StringHelper::truncate($text, 10);
        $this->assertSame('Das ist...', $short);
    }

    public function test_is_ascii(): void {
        $ascii = "abc";
        $utf8 = "ü";
        $this->assertTrue(StringHelper::isAscii($ascii));
        $this->assertFalse(StringHelper::isAscii($utf8));
    }

    public function test_html_entities_to_text(): void {
        $html = "K&auml;se &amp; Brot";
        $text = StringHelper::htmlEntitiesToText($html);
        $this->assertSame("Käse & Brot", $text);
    }

    public function test_normalize_whitespace(): void {
        $input = "Text   mit \n  Tabs\tund\nZeilen";
        $normalized = StringHelper::normalizeWhitespace($input);
        $this->assertSame("Text mit Tabs und Zeilen", $normalized);
    }

    public function test_to_lower_and_upper(): void {
        $upper = StringHelper::toUpper("Straße");
        $lower = StringHelper::toLower("Straße");
        $this->assertSame("STRASSE", $upper);
        $this->assertSame("straße", $lower);
    }

    public function test_normalize_column_name(): void {
        $this->assertSame('beginn', StringHelper::normalizeColumnName(' Beginn '));
        $this->assertSame('beginn', StringHelper::normalizeColumnName('BEGINN'));
        $this->assertSame('beginn', StringHelper::normalizeColumnName(StringHelper::BOM_UTF8 . "\tBeginn\n"));
        $this->assertSame('ende (datum)', StringHelper::normalizeColumnName("Ende\u{00A0}(Datum)"));
        $this->assertSame('dauer in stunden', StringHelper::normalizeColumnName("Dauer  in \t stunden"));
        $this->assertSame('strasse', StringHelper::normalizeColumnName('STRASSE'));
        $this->assertSame('straße', StringHelper::normalizeColumnName('Straße'));
        $this->assertSame('', StringHelper::normalizeColumnName(null));
        $this->assertSame('', StringHelper::normalizeColumnName('   '));
        // Ungültiges UTF-8 wird nicht verschluckt (kein leerer Schlüssel), sondern byteweise behandelt
        $invalid = StringHelper::normalizeColumnName(" NA\xFFME ");
        $this->assertStringStartsWith('na', $invalid);
        $this->assertStringEndsWith('me', $invalid);
    }

    public function test_strip_bom(): void {
        // UTF-8 BOM
        $bomUtf8 = StringHelper::BOM_UTF8 . "Hallo";
        $this->assertSame("Hallo", StringHelper::stripBom($bomUtf8));

        // UTF-16 LE BOM
        $bomUtf16Le = StringHelper::BOM_UTF16_LE . "Test";
        $this->assertSame("Test", StringHelper::stripBom($bomUtf16Le));

        // UTF-16 BE BOM
        $bomUtf16Be = StringHelper::BOM_UTF16_BE . "Test";
        $this->assertSame("Test", StringHelper::stripBom($bomUtf16Be));

        // Kein BOM
        $noBom = "Hallo";
        $this->assertSame("Hallo", StringHelper::stripBom($noBom));
    }

    public function test_detect_encoding(): void {
        $text = "Grüße aus München"; // UTF-8
        $encoding = StringHelper::detectEncoding($text);

        $this->assertIsString($encoding, "Erkannte Kodierung sollte ein String sein");
        $this->assertMatchesRegularExpression('/utf-?8|iso-8859/i', $encoding, "Kodierung sollte plausibel sein (UTF-8 oder ISO)");
    }

    public function test_is_case_with_extras(): void {
        $tests = [
            ['text' => "hallo welt\n\t\\mit käse.\n", 'case' => CaseType::LOWER, 'expected' => true],
            ['text' => "HALLO WELT MIT KÄSE.", 'case' => CaseType::UPPER, 'expected' => true],
            ['text' => "halloWeltMitKäse", 'case' => CaseType::CAMEL, 'expected' => true],
            ['text' => "HalloWelt", 'case' => CaseType::CAMEL, 'expected' => false],
            ['text' => "HalloWelt", 'case' => CaseType::LOOSE_CAMEL, 'expected' => true],
            ['text' => "Hallo Welt Mit Käse.", 'case' => CaseType::TITLE, 'expected' => true],
            ['text' => "nichtTitleCase", 'case' => CaseType::TITLE, 'expected' => false],
            ['text' => "camelCaseWith\$Sonderzeichen", 'case' => CaseType::CAMEL, 'expected' => false],
        ];

        foreach ($tests as $test) {
            $result = StringHelper::isCaseWithExtras($test['text'], $test['case']);
            $this->assertSame(
                $test['expected'],
                $result,
                sprintf(
                    "Fehlgeschlagen bei '%s' mit CaseType::%s – erwartet %s, erhalten %s",
                    $test['text'],
                    $test['case']->name,
                    $test['expected'] ? 'true' : 'false',
                    $result ? 'true' : 'false'
                )
            );
        }
    }

    /**
     * Test detectLegacyEncoding für CP850 (DOS deutsche Umlaute)
     */
    public function test_detect_legacy_encoding_c_p850(): void {
        // CP850: ü=0x81, ä=0x84, ö=0x94, Ä=0x8E, Ö=0x99, Ü=0x9A, ß=0xE1
        $cp850Text = "M\x81ller;K\x84the;J\x94rn"; // Müller;Käthe;Jörn

        $result = StringHelper::detectLegacyEncoding($cp850Text);
        $this->assertSame('CP850', $result, 'Sollte CP850 für deutsche DOS-Umlaute erkennen');
    }

    /**
     * Test detectLegacyEncoding für Windows-1252 (typografische Zeichen)
     */
    public function test_detect_legacy_encoding_windows1252(): void {
        // Windows-1252: €=0x80 (vor Zahl), "=0x93, "=0x94
        $win1252Text = "\x80100"; // €100

        $result = StringHelper::detectLegacyEncoding($win1252Text);
        $this->assertSame('Windows-1252', $result, 'Sollte Windows-1252 für Euro-Zeichen vor Zahl erkennen');

        // Typografische Anführungszeichen isoliert
        $win1252Quotes = " \x84 \x93 "; // „ " isoliert
        $result2 = StringHelper::detectLegacyEncoding($win1252Quotes);
        $this->assertSame('Windows-1252', $result2, 'Sollte Windows-1252 für isolierte typografische Zeichen erkennen');
    }

    /**
     * Test detectLegacyEncoding für MacRoman
     */
    public function test_detect_legacy_encoding_mac_roman(): void {
        // MacRoman: Ä=0x80, ö=0x9A, ü=0x9F, ä=0x8A
        $macRomanText = "Test\x80pfel"; // TestÄpfel (Ä im Buchstabenkontext)

        $result = StringHelper::detectLegacyEncoding($macRomanText);
        $this->assertSame('MacRoman', $result, 'Sollte MacRoman für Ä im Buchstabenkontext erkennen');
    }

    /**
     * Test detectLegacyEncoding für ISO-8859-15 (Euro an 0xA4)
     */
    public function test_detect_legacy_encoding_is_o885915(): void {
        // ISO-8859-15: €=0xA4 (im Zahlenkontext)
        $latin9Text = "Preis: 50\xA4"; // 50€

        $result = StringHelper::detectLegacyEncoding($latin9Text);
        $this->assertSame('ISO-8859-15', $result, 'Sollte ISO-8859-15 für Euro an 0xA4 im Zahlenkontext erkennen');
    }

    /**
     * Test detectLegacyEncoding für UTF-8
     */
    public function test_detect_legacy_encoding_ut_f8(): void {
        $utf8Text = "Größe und Äpfel";

        $result = StringHelper::detectLegacyEncoding($utf8Text);
        $this->assertSame('UTF-8', $result, 'Sollte gültiges UTF-8 erkennen');
    }

    /**
     * Test detectLegacyEncoding für reines ASCII
     */
    public function test_detect_legacy_encoding_ascii(): void {
        $asciiText = "Hello World";

        $result = StringHelper::detectLegacyEncoding($asciiText);
        $this->assertNull($result, 'Sollte null für reines ASCII zurückgeben');
    }

    /**
     * Test detectLegacyEncoding für leeren String
     */
    public function test_detect_legacy_encoding_empty(): void {
        $result = StringHelper::detectLegacyEncoding('');
        $this->assertNull($result, 'Sollte null für leeren String zurückgeben');
    }

    /**
     * Test detectDosVsWindowsEncoding (deprecated Wrapper)
     */
    public function test_detect_dos_vs_windows_encoding_legacy(): void {
        // CP850 sollte als CP850 zurückgegeben werden
        $cp850Text = "M\x81ller"; // Müller
        $result = StringHelper::detectDosVsWindowsEncoding($cp850Text);
        $this->assertSame('CP850', $result);

        // Windows-1252 sollte als Windows-1252 zurückgegeben werden
        $win1252Text = "\x80100"; // €100
        $result2 = StringHelper::detectDosVsWindowsEncoding($win1252Text);
        $this->assertSame('Windows-1252', $result2);
    }

    public function test_is_mb_encoding_supported_rejects_unknown_encodings(): void {
        // Von mbstring unterstützte Encodings
        $this->assertTrue(StringHelper::isMbEncodingSupported('UTF-8'));
        $this->assertTrue(StringHelper::isMbEncodingSupported('ISO-8859-15'));
        $this->assertTrue(StringHelper::isMbEncodingSupported('Windows-1252'));

        // mbstring-fremde Encodings (DOS-Codepages, chardet-Fehlerkennungen) -> iconv-Pfad
        $this->assertFalse(StringHelper::isMbEncodingSupported('CP850'));
        $this->assertFalse(StringHelper::isMbEncodingSupported('JOHAB'));
        $this->assertFalse(StringHelper::isMbEncodingSupported('UNSINN-99'));
    }

    public function test_convert_to_utf8_survives_unknown_encoding(): void {
        // mb_convert_encoding() würde bei unbekanntem Encoding einen ValueError werfen —
        // convertToUtf8 muss stattdessen über iconv bzw. Fallback ein Ergebnis liefern.
        $result = StringHelper::convertToUtf8("test \xB0\xA1", 'JOHAB');
        $this->assertNotSame('', $result);

        $unchanged = StringHelper::convertToUtf8('nur ascii', 'UNSINN-99');
        $this->assertSame('nur ascii', $unchanged);
    }

    public function test_similarity_identical_and_empty(): void {
        $this->assertSame(1.0, StringHelper::similarity('Müller GmbH', 'Müller GmbH'));
        $this->assertSame(0.0, StringHelper::similarity(null, 'abc'));
        $this->assertSame(0.0, StringHelper::similarity('abc', null));
        $this->assertSame(0.0, StringHelper::similarity('', 'abc'));
        $this->assertSame(0.0, StringHelper::similarity(null, null));
        $this->assertSame(0.0, StringHelper::similarity('', ''));
    }

    public function test_similarity_partial_matches(): void {
        // Tippfehler-Nähe: hoher, aber nicht perfekter Score
        $score = StringHelper::similarity('Schuppelius', 'Schupelius');
        $this->assertGreaterThan(0.9, $score);
        $this->assertLessThan(1.0, $score);

        $this->assertGreaterThan(0.0, StringHelper::similarity('abc', 'xbc'));
        $this->assertSame(0.0, StringHelper::similarity('abc', 'xyz'));
    }

    public function test_is_uuid(): void {
        $this->assertTrue(\CommonToolkit\Helper\Data\StringHelper::isUuid('9fe4b3f2-6b1e-4c2a-8f1d-2a3b4c5d6e7f'));
        $this->assertTrue(\CommonToolkit\Helper\Data\StringHelper::isUuid('9FE4B3F2-6B1E-4C2A-AF1D-2A3B4C5D6E7F'));
        $this->assertTrue(\CommonToolkit\Helper\Data\StringHelper::isUuid('01890a5d-ac96-774b-bcce-b302099a8057'), 'Version 7 zählt.');
        $this->assertFalse(\CommonToolkit\Helper\Data\StringHelper::isUuid('00000000-0000-0000-0000-000000000000'), 'Nil-UUID ist keine gültige Kennung.');
        $this->assertFalse(\CommonToolkit\Helper\Data\StringHelper::isUuid('9fe4b3f2-6b1e-4c2a-cf1d-2a3b4c5d6e7f'), 'Falsche Variante.');
        $this->assertFalse(\CommonToolkit\Helper\Data\StringHelper::isUuid('9fe4b3f26b1e4c2a8f1d2a3b4c5d6e7f'));
        $this->assertFalse(\CommonToolkit\Helper\Data\StringHelper::isUuid(' 9fe4b3f2-6b1e-4c2a-8f1d-2a3b4c5d6e7f'));
        $this->assertFalse(\CommonToolkit\Helper\Data\StringHelper::isUuid("9fe4b3f2-6b1e-4c2a-8f1d-2a3b4c5d6e7f\n"), 'Ein abschließender Zeilenumbruch darf nicht durchrutschen.');
    }

    /** Die gemeinsame Einzelzeichen-Faltung: Buchstaben romanisiert, Zeichen ohne Buchstabenwert weg, nie "?". */
    public function test_fold_char_to_ascii(): void {
        $this->assertSame('i', StringHelper::foldCharToAscii('ı'));
        $this->assertSame('I', StringHelper::foldCharToAscii('İ'));
        $this->assertSame('s', StringHelper::foldCharToAscii('ş'));
        $this->assertSame('l', StringHelper::foldCharToAscii('ł'));
        $this->assertSame('e', StringHelper::foldCharToAscii('é'));
        $this->assertSame('EUR', StringHelper::foldCharToAscii('€'));
        $this->assertSame('x', StringHelper::foldCharToAscii('×'));
        $this->assertSame('--', StringHelper::foldCharToAscii('—'));
        $this->assertSame('', StringHelper::foldCharToAscii('─'));
        $this->assertSame('', StringHelper::foldCharToAscii('🎉'));
    }
}
