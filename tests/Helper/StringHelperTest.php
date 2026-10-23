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
     * Latin-1-Grossbuchstaben-Umlaute im Wort (Ä=0xC4, Ö=0xD6, Ü=0xDC,
     * ß=0xDF) sind keine CP850-Rahmenzeichen: ein ISO-8859-15-MT940 mit
     * "ÜBERWEISUNG"/"GÜNTER" galt sonst als CP850 ("GNTER", ckonverter
     * 22.10.2026). Echte CP850-Umlaute bleiben CP850.
     */
    public function test_detect_legacy_encoding_latin1_uppercase_umlauts_are_not_cp850(): void {
        $latin1 = ":86:166?00\xDCberweisung?20G\xDCNTER M\xDCLLER STRA\xDFE 5?21\xD6L UND \xC4PFEL";
        $this->assertContains(StringHelper::detectLegacyEncoding($latin1), ['Windows-1252', 'ISO-8859-1', 'ISO-8859-15']);
        $this->assertSame("G\u{00DC}NTER", StringHelper::convertToUtf8("G\xDCNTER", (string) StringHelper::detectLegacyEncoding($latin1)));

        $this->assertSame('CP850', StringHelper::detectLegacyEncoding("\x9Aberweisung G\x9ANTER M\x81ller"), 'CP850-Umlaute bleiben CP850');
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
     * Die Latin-Kodierungen sind auf den Buchstaben gleich: Umlaute duerfen das
     * Euro-Zeichen an 0xA4 nicht ueberstimmen (bisher Windows-1252, das Zeichen
     * wurde U+00A4). Bytes aus 0x80-0x9F bleiben Windows-1252, ein 0xA4 ohne
     * Betrag bleibt, was es war; gueltiges UTF-8 ("ae" als C3 A4 vor einer
     * Ziffer) ist kein Euro-Zeichen.
     */
    public function test_detect_legacy_encoding_euro_sign_with_umlauts_is_iso_8859_15(): void {
        $latin9 = "?00\xDCberweisung?20M\xFCller Gr\xFC\xDFe K\xF6ln?212,00\xA4Anzahl: 1 USt. 19,0%";
        $this->assertSame('ISO-8859-15', StringHelper::detectLegacyEncoding($latin9));
        $this->assertSame("2,00\u{20AC}Anzahl", StringHelper::convertToUtf8("2,00\xA4Anzahl", (string) StringHelper::detectLegacyEncoding($latin9)));

        $this->assertSame('Windows-1252', StringHelper::refineLatinEncoding("M\xFCller \x93Zitat\x94 2,00\xA4", 'Windows-1252'), 'typografische Zeichen: Windows-1252');
        $this->assertSame('Windows-1252', StringHelper::detectLegacyEncoding("M\xFCller Gr\xFC\xDFe \xA4 Zeichen"), 'ohne Betrag kein Euro-Zeichen');

        $this->assertSame('Windows-1252', StringHelper::refineLatinEncoding("\xC3\xA41 M\xFCller", 'Windows-1252'), 'UTF-8-Folge vor einer Ziffer');
        $this->assertSame('ISO-8859-15', StringHelper::refineLatinEncoding("12,00 \xA4", 'ISO-8859-1'));
        $this->assertSame('CP850', StringHelper::refineLatinEncoding("12,00 \xA4", 'CP850'), 'nur die Latin-Familie');
    }

    /**
     * Die Euro-Regel aus verorderten Merkmalen der Zeilen entscheidet wie ueber
     * den ganzen Text (zeilenweises Lesen, ckonverter N6 Punkt 4): Euro-Zeichen
     * in einer Zeile, ein Windows-1252-Zeichen (0x80-0x9F) in einer anderen.
     */
    public function test_latin_encoding_evidence_of_lines_decides_like_the_whole_text(): void {
        $cases = [
            "M\xFCller Gr\xFC\xDFe\nEntgelt 2,00\xA4\nK\xF6ln",
            "\xDDstanbul\n\xF6deme ba\xFEar\xFDl\xFD 2,00\xA4",
            "M\xFCller \x93Zitat\x94\nEntgelt 2,00\xA4",
            "M\xFCller\nZeichen \xA4 ohne Betrag",
            "\xC3\xA41 M\xFCller\nnur ASCII",
            "12,00 \xA4",
            "ohne Sonderzeichen",
        ];
        foreach ($cases as $text) {
            $evidence = StringHelper::latinEncodingEvidence('');
            foreach (explode("\n", $text) as $line) {
                $evidence = StringHelper::mergeLatinEncodingEvidence($evidence, StringHelper::latinEncodingEvidence($line));
            }
            $this->assertSame(StringHelper::latinEncodingEvidence($text), $evidence, bin2hex($text));
            foreach (['Windows-1252', 'ISO-8859-1', 'CP850', 'ISO-8859-9', 'Windows-1254'] as $encoding) {
                $this->assertSame(
                    StringHelper::refineLatinEncoding($text, $encoding),
                    StringHelper::refineLatinEncodingFromEvidence($encoding, $evidence),
                    bin2hex($text) . ' ' . $encoding
                );
            }
        }
        $this->assertSame('ISO-8859-15', StringHelper::refineLatinEncodingFromEvidence('Windows-1252', ['a4' => true, 'euroAtAmount' => true, 'c1' => false]));
        $this->assertSame('Windows-1252', StringHelper::refineLatinEncodingFromEvidence('Windows-1252', ['a4' => true, 'euroAtAmount' => true, 'c1' => true]));
    }

    /**
     * chardet meldet fuer deutsche Latin-1-Texte oft eine Latin-Variante
     * (ISO-8859-9 Tuerkisch, ckonverter 23.10.2026): die Euro-Regel galt nur
     * fuer Windows-1252/ISO-8859-1, das Euro-Zeichen an 0xA4 blieb U+00A4.
     * Fehlen dem Text die Bytes, in denen sich die Variante von ihrer
     * Grundform unterscheidet, und traegt er deutsche Umlaute neben
     * Buchstaben, gilt die Grundform (ISO-8859-x -> ISO-8859-1, Windows-125x
     * -> Windows-1252), danach die Euro-Regel. Echtes Tuerkisch (0xDD, 0xFD,
     * 0xFE) und Polnisch bleiben, was chardet meldet.
     */
    public function test_latin_variant_without_its_bytes_is_its_base_encoding(): void {
        $german = "Empf\xE4nger;M\xFCller Gr\xFC\xDFe;Stra\xDFe K\xF6ln;\xDCberweisung";

        $this->assertSame('ISO-8859-1', StringHelper::refineLatinEncoding($german, 'ISO-8859-9'));
        $this->assertSame('ISO-8859-15', StringHelper::refineLatinEncoding($german . ";Geb\xFChr 2,00\xA4", 'ISO-8859-9'), 'danach die Euro-Regel');
        $this->assertSame('Windows-1252', StringHelper::refineLatinEncoding($german, 'WINDOWS-1254'));
        $this->assertSame('Windows-1252', StringHelper::refineLatinEncoding($german . " \x93Zitat\x94 2,00\xA4", 'Windows-1254'), 'Windows-1252-Zeichen: keine Euro-Regel');
        $this->assertSame('ISO-8859-1', StringHelper::refineLatinEncoding($german, 'ISO-8859-2'));
        $this->assertSame('ISO-8859-1', StringHelper::refineLatinEncoding($german, 'iso8859-13'));
        $this->assertSame('Windows-1252', StringHelper::refineLatinEncoding($german, 'CP1250'));
        $this->assertSame(
            StringHelper::convertToUtf8($german, 'ISO-8859-9'),
            StringHelper::convertToUtf8($german, StringHelper::refineLatinEncoding($german, 'ISO-8859-9')),
            'ohne die unterscheidenden Bytes lesen Variante und Grundform gleich'
        );

        // DOS-Umlaute (CP850) unter einer Windows-Variante: die Grundform, die
        // Heuristik entscheidet dann zwischen Windows-1252 und CP850
        $dos = "Kontoauszug gem\x84\xE1 Ziffer 11;Konto\x81bersicht;Gesch\x84ftsbedingungen";
        $this->assertSame('Windows-1252', StringHelper::refineLatinEncoding($dos, 'Windows-1254'));
        $this->assertSame('CP850', StringHelper::detectLegacyEncoding($dos, 'Windows-1252'));

        $turkish = "\xDDstanbul \xF6deme ba\xFEar\xFDl\xFD;G\xFCnl\xFCk \xFCcret 2,00\xA4";
        $this->assertSame('ISO-8859-9', StringHelper::refineLatinEncoding($turkish, 'ISO-8859-9'), 'Tuerkisch bleibt');
        $this->assertSame('Windows-1254', StringHelper::refineLatinEncoding($turkish, 'Windows-1254'), 'Tuerkisch bleibt');
        $this->assertSame('ISO-8859-2', StringHelper::refineLatinEncoding("Pozna\xF1 \xA3\xF3d\xBC M\xFCller", 'ISO-8859-2'), 'Polnisch bleibt');
        $this->assertSame('ISO-8859-9', StringHelper::refineLatinEncoding("Entgelt 2,00\xA4", 'ISO-8859-9'), 'ohne Umlaut kein Beleg fuer Deutsch');
        $this->assertSame('ISO-8859-9', StringHelper::refineLatinEncoding("Summe \xE4 1", 'ISO-8859-9'), 'Umlaut ohne Buchstaben daneben');
        $this->assertSame('ISO-8859-15', StringHelper::refineLatinEncoding($german, 'ISO-8859-15'), 'ISO-8859-15 ist keine Variante');
        $this->assertSame('KOI8-R', StringHelper::refineLatinEncoding($german, 'KOI8-R'), 'nur die Latin-Familie');

        $this->assertSame("\xD0\xDD\xDE\xF0\xFD\xFE", StringHelper::latinVariantBytes('ISO-8859-9'), 'aus den Zeichentabellen');
        $this->assertSame("\xD0\xDD\xDE\xF0\xFD\xFE", StringHelper::latinVariantBytes('Windows-1254'), 'unbelegte Stellen zaehlen nicht');
        $this->assertSame('Windows-1252', StringHelper::latinVariantBase('windows-1257'));
        $this->assertNull(StringHelper::latinVariantBase('ISO-8859-15'));
        $this->assertNull(StringHelper::latinVariantBytes('Windows-1252'));
    }

    /**
     * chardet meldet echten tuerkischen Text als ISO-8859-1 (ckonverter P2):
     * "Ýþlem" statt "İşlem", "baþarýlý" statt "başarılı". Tuerkisch ist ein
     * Text mit punktlosem i (0xFD) zwischen zwei Buchstaben und ohne Buchstaben
     * ausserhalb des tuerkischen Alphabets - Windows-1252 wird Windows-1254,
     * ISO-8859-1 wird ISO-8859-9. Islaendisch (dieselben Bytes als Y mit Akut,
     * Thorn, Eth) traegt immer Akut-Vokale oder ae, Deutsch ae/Ae/ss: beides
     * bleibt Latin-1.
     */
    public function test_turkish_text_reported_as_latin1_is_turkish(): void {
        $turkish = (string) iconv('UTF-8', 'ISO-8859-9', "İşlem Tarihi;Açıklama;Tutar\r\n02.07.2025;Kira ödemesi Şubat;-1.250,00\r\n"
            . "03.07.2025;Maaş ödemesi Yılmaz Gıda Ltd. Şti.;8.500,00\r\n07.07.2025;Havale işlemi başarılı;-300,00\r\n08.07.2025;Müşteri iadesi Çiçek Mağazası;45,00\r\n");

        $this->assertSame('ISO-8859-9', StringHelper::refineLatinEncoding($turkish, 'ISO-8859-1'));
        $this->assertSame('Windows-1254', StringHelper::refineLatinEncoding($turkish, 'Windows-1252'));
        $this->assertSame('Windows-1254', StringHelper::refineLatinEncoding($turkish . "09.07.2025;\x93Fatura\x94 ödemesi;-89,90\r\n", 'Windows-1252'), 'Windows-1252-Satzzeichen');
        $utf8 = StringHelper::convertToUtf8($turkish, StringHelper::refineLatinEncoding($turkish, 'ISO-8859-1'));
        $this->assertStringContainsString('İşlem Tarihi;Açıklama', $utf8);
        $this->assertStringContainsString('Havale işlemi başarılı', $utf8);
        $this->assertStringContainsString('Çiçek Mağazası', $utf8);
        $this->assertTrue(StringHelper::latinEncodingEvidence($turkish)['turkish']);
        $this->assertSame('ISO-8859-9', StringHelper::refineLatinEncoding($turkish, 'ISO-8859-9'), 'chardets Tuerkisch bleibt');

        $icelandic = (string) iconv('UTF-8', 'ISO-8859-1', "Dagsetning;Skýring;Upphæð;Staða\r\n02.07.2025;Millifærsla frá Þórði Guðmundssyni;-12.500\r\n"
            . "03.07.2025;Launagreiðsla júlí;850.000\r\n06.07.2025;Þjónustugjald Íslandsbanki;-899\r\n");
        $this->assertTrue(StringHelper::latinEncodingEvidence($icelandic)['turkish'], 'Y mit Akut im Wort');
        $this->assertSame('ISO-8859-1', StringHelper::refineLatinEncoding($icelandic, 'ISO-8859-1'), 'Islaendisch: Akut-Vokale und ae');
        $this->assertSame('ISO-8859-1', StringHelper::refineLatinEncoding((string) iconv('UTF-8', 'ISO-8859-1', 'Greiðsla til Alþingis; Guðmundur maður'), 'ISO-8859-1'), 'Eth und Thorn ohne Y mit Akut');
        $this->assertSame('Windows-1252', StringHelper::refineLatinEncoding("\xDCberweisung an Y\xFDlmaz Stra\xDFe;Geb\xFChr M\xE4rz", 'Windows-1252'), 'Deutsch mit ae und ss bleibt');
        $this->assertSame('Windows-1254', StringHelper::refineLatinEncoding("\xDCberweisung an Y\xFDlmaz G\xFDda;Geb\xFChr f\xFCr R\xFCckbuchung", 'Windows-1252'), 'tuerkische Namen in deutschem Text ohne ae/ss');
        $this->assertSame('ISO-8859-1', StringHelper::refineLatinEncoding((string) iconv('UTF-8', 'ISO-8859-9', 'Şubat ödemesi Ayşe'), 'ISO-8859-1'), 'ohne punktloses i kein Beleg');
        $this->assertSame('ISO-8859-1', StringHelper::refineLatinEncoding("Fl\xE4che 12 m\xFD", 'ISO-8859-1'), '0xFD am Wortende');

        $this->assertSame('ISO-8859-9', StringHelper::refineLatinEncodingFromEvidence('ISO-8859-1', StringHelper::latinEncodingEvidence($turkish)));
        $this->assertFalse(StringHelper::isTurkishEvidence(StringHelper::latinEncodingEvidence($icelandic)));
    }

    /**
     * Auch der chardet-Weg fuer Zeichenketten (ShellChardet) gibt eine
     * Latin-Variante ohne ihre Bytes als Grundform zurueck - chardet liest den
     * kurzen deutschen Text als ISO-8859-9.
     */
    public function test_detect_encoding_resolves_a_latin_variant_without_its_bytes(): void {
        $detected = (string) StringHelper::detectEncoding("03.07.2024;Buchung M\xFCller Gartenbau Rechnung Gr\xFC\xDFe aus K\xF6ln 2,00\xA4;-2,00\r\n");

        $this->assertNotSame('ISO-8859-9', $detected);
        $this->assertNull(StringHelper::latinVariantBase($detected), $detected);
    }

    /**
     * chardet meldet fuer deutsche DOS-Texte (CP850) CJK-Mehrbyte-Kodierungen
     * (Shift_JIS, CP949, Johab; ckonverter 23.10.2026): jedes DOS-Umlaut-Byte
     * bildet mit dem folgenden ASCII-Buchstaben ein Zeichen, aus "Buecher" wurde
     * "B" + Kanji + "her" - der Buchstabe dahinter ging verloren. Ein CJK-Dekodat,
     * dessen Zeichen ueberwiegend einzeln an ASCII-Buchstaben stehen, ist bei
     * deutschen Umlauten eine Fehlerkennung: die Latin-Heuristik entscheidet.
     * Echtes Japanisch und Koreanisch bleibt, auch mit Umlaut-Bytes als Leitbyte
     * (0x81 + "A" ist in Shift_JIS ein Komma).
     */
    public function test_cjk_reading_of_a_german_dos_text_is_a_latin_encoding(): void {
        $dos = "\"BW B\x81cherWege Verlag\";\"Umsatz B\x81cherWege KW 11\";731,00\r\n"
            . "\"Schubert F\x81m GmbH\";\"R\x81ckzahlung: Zinsen\";12,00\r\n\"Sparkasse\";\"Hinweise auf der R\x81ckseite\";0,00\r\n";
        $box = $dos . '"' . str_repeat("\xC4", 32) . "\";0,00\r\n\"G\x81lten T\x81rk\";\"Geldw\x84schepr\x84vention\";50,00\r\n";

        $this->assertSame('CP850', StringHelper::refineMultibyteEncoding($dos, 'SHIFT_JIS'));
        $this->assertContains(StringHelper::refineMultibyteEncoding($box, 'CP949'), ['CP850', 'CP437'], 'Rahmenlinie als Hangul-Lauf; CP437 liest Umlaute und Rahmen wie CP850');
        $this->assertSame('CP850', StringHelper::refineMultibyteEncoding($dos, 'Johab'));
        $this->assertContains(StringHelper::refineMultibyteEncoding("Kaffeer\xF6sterei M\xFCller;Bonus f\xFCr Debitkarte;Gr\xFC\xDFe aus K\xF6ln", 'EUC-KR'), ['Windows-1252', 'ISO-8859-1']);

        // laengengleich: jedes Byte ein Zeichen, kein ASCII-Buchstabe verschluckt
        $utf8 = StringHelper::convertToUtf8($dos, StringHelper::refineMultibyteEncoding($dos, 'SHIFT_JIS'));
        $this->assertSame(strlen($dos), mb_strlen($utf8));
        $this->assertStringContainsString("B\u{00FC}cherWege", $utf8);
        $this->assertLessThan(strlen($dos), mb_strlen(StringHelper::convertToUtf8($dos, 'SHIFT_JIS')), 'das CJK-Dekodat verschluckt Buchstaben');

        $japanese = (string) iconv('UTF-8', 'SHIFT_JIS', "\u{53D6}\u{5F15}\u{65E5};\u{6458}\u{8981};\u{91D1}\u{984D}\r\n2024/03/01;\u{304A}\u{632F}\u{8FBC}\u{307F}\u{3001}\u{6771}\u{4EAC}\u{90FD}\u{4E2D}\u{592E}\u{533A};-85,000\r\n2024/03/02;\u{624B}\u{6570}\u{6599}\u{306E}\u{3054}\u{6848}\u{5185}\u{3002};-330\r\n2024/03/03;\u{30AB}\u{30FC}\u{30C9}\u{5229}\u{7528} \u{30B3}\u{30F3}\u{30D3}\u{30CB}\u{30A8}\u{30F3}\u{30B9}\u{30B9}\u{30C8}\u{30A2};-1,200\r\n");
        $this->assertTrue(StringHelper::latinEncodingEvidence($japanese)['german'], 'Shift_JIS traegt Umlaut-Bytes neben Buchstaben');
        $this->assertSame('SHIFT_JIS', StringHelper::refineMultibyteEncoding($japanese, 'SHIFT_JIS'), 'echtes Japanisch bleibt');
        $korean = "\u{AC70}\u{B798}\u{C77C};\u{C801}\u{C694};\u{AE08}\u{C561}\r\n2024-03-01;\u{C774}\u{CCB4} \u{C11C}\u{C6B8}\u{D2B9}\u{BCC4}\u{C2DC} \u{C911}\u{AD6C} PC\u{BC29};-85,000\r\n2024-03-02;\u{C218}\u{C218}\u{B8CC} \u{C548}\u{B0B4};-330\r\n";
        $this->assertSame('CP949', StringHelper::refineMultibyteEncoding((string) iconv('UTF-8', 'CP949', $korean), 'CP949'), 'echtes Koreanisch bleibt');
        $this->assertSame('EUC-KR', StringHelper::refineMultibyteEncoding((string) iconv('UTF-8', 'EUC-KR', $korean), 'EUC-KR'));

        $this->assertSame('SHIFT_JIS', StringHelper::refineMultibyteEncoding("Summe 12,00 \x81\x40 Ende", 'SHIFT_JIS'), 'ohne deutschen Umlaut bleibt chardets Wert');
        $this->assertSame('Windows-1252', StringHelper::refineMultibyteEncoding($dos, 'Windows-1252'), 'nur CJK-Kodierungen');
        foreach (['Shift_JIS', 'SJIS', 'CP932', 'EUC-JP', 'EUC-KR', 'CP949', 'Johab', 'GB2312', 'GBK', 'GB18030', 'Big5', 'EUC-TW'] as $cjk) {
            $this->assertTrue(StringHelper::isCjkMultibyteEncoding($cjk), $cjk);
        }
        foreach (['Windows-1252', 'CP850', 'UTF-8', 'ISO-8859-1', 'ISO-2022-JP'] as $other) {
            $this->assertFalse(StringHelper::isCjkMultibyteEncoding($other), $other);
        }
    }

    /**
     * Ein Legacy-Rest unter einer CJK-Kodierung (SumUp 0141, ckonverter
     * 23.10.2026): v1 schrieb je Zeile einmal "BankA" + 0xAC + "berweisung",
     * chardet meldete Johab (0,99) - 0xAC + "b" ist dort eine Hangul-Silbe.
     * Ohne Umlaut griff die CJK-Regel nicht. Wenige einzelne Hochbytes in
     * ASCII, deren CJK-Dekodat einzeln an ASCII-Buchstaben steht, sind kein
     * CJK: wie jeder Latin-Rest Windows-1252, mit DOS-Beleg CP850. Katakana in
     * Shift_JIS (ASCII-Folgebytes) und echtes Japanisch bleiben.
     */
    public function test_cjk_reading_of_a_sparse_legacy_rest_is_a_latin_encoding(): void {
        $row = "DRESDEFF100;DE91100800000012345678;;;;08.08.2025;1329,41;\"Booking.com B.V.\";;;;\"Eingehende BankA\xACberweisung\";;EUR\r\n";
        $ascii = "DRESDEFF100;DE91100800000012345678;;;;04.08.2025;46,35;\"SUMUP PID1270662 PAYOUT\";;;;\"SumUp Einzahlung\";;EUR\r\n";
        $sumup = $ascii . str_repeat($row . $ascii, 4);

        $this->assertSame('Windows-1252', StringHelper::refineMultibyteEncoding($sumup, 'JOHAB'));
        $this->assertSame('Windows-1252', StringHelper::refineMultibyteEncoding($sumup, 'CP949'));
        $this->assertSame('Windows-1252', StringHelper::refineMultibyteEncoding($sumup, 'SHIFT_JIS'));
        $this->assertSame('CP850', StringHelper::refineMultibyteEncoding($sumup . "\"M\x81ller Gartenbau\";12,00\r\n", 'JOHAB'), 'mit DOS-Beleg CP850');
        $utf8 = StringHelper::convertToUtf8($sumup, StringHelper::refineMultibyteEncoding($sumup, 'JOHAB'));
        $this->assertSame(strlen($sumup), mb_strlen($utf8), 'laengengleich: kein ASCII-Buchstabe verschluckt');
        $this->assertStringContainsString("BankA\u{00AC}berweisung", $utf8);

        $padding = str_repeat("2024-03-01;Item 4711 Standard price;100\r\n", 30);
        $katakana = (string) iconv('UTF-8', 'SHIFT_JIS', "\u{30A2}\u{30A4}\u{30B9}");
        $this->assertSame("\x83A\x83C\x83X", $katakana, 'Katakana: ASCII-Folgebytes, jedes Hochbyte allein');
        $this->assertSame('SHIFT_JIS', StringHelper::refineMultibyteEncoding($padding . "2024-03-02;Item $katakana price;100\r\n" . $padding, 'SHIFT_JIS'), 'ein Katakana-Wort ist ein CJK-Lauf');
        $this->assertSame('SHIFT_JIS', StringHelper::refineMultibyteEncoding($padding . "Summe 12,00 \x81\x40 Ende\r\n" . $padding, 'SHIFT_JIS'), 'ideografisches Leerzeichen');
        $this->assertSame('JOHAB', StringHelper::refineMultibyteEncoding(str_repeat("BankA\xACberweisung ", 3), 'JOHAB'), 'mehr als jedes hundertste Byte: kein Rest');
        $this->assertSame('JOHAB', StringHelper::refineMultibyteEncoding($padding . "Bank \xAC\xACber\r\n" . $padding, 'JOHAB'), 'zwei Hochbytes in Folge: kein Rest');
    }

    /**
     * Gemischte Texte (v1-Ausgaben: Kopf oder Textspalte als UTF-8, der Rest
     * CP850 oder Latin-1; ckonverter 23.10.2026) sind UTF-8 mit Legacy-Bytes:
     * ein UTF-8-kodierter Latin-1-Buchstabe neben einem ASCII-Buchstaben belegt
     * die UTF-8-Teile. Bisher wurde die ganze Datei mit einer Kodierung gewandelt
     * ("Ue" als C3 9C wurde in CP850 Rahmen + Pfund, in CP852 "A" mit Breve + "s"
     * mit Akut). Der Reparaturweg laesst gueltiges UTF-8 stehen und liest nur die
     * ungueltigen Bytes nach ihrer eigenen Heuristik.
     */
    public function test_utf8_letters_with_legacy_bytes_are_utf8_with_legacy_bytes(): void {
        $dos = "\"\xC3\x9Cberweisungsgutschrift\";\"M\x81ller Gartenbau\";\"Geb\x81hr f\x81r R\x81ckbuchung\"";
        $latin1 = "Zahlungsempf\xC3\xA4nger;Kaffeer\xF6sterei M\xFCller;Bonus f\xFCr Debitkarte";

        $this->assertTrue(StringHelper::isUtf8WithLegacyBytes($dos));
        $this->assertTrue(StringHelper::isUtf8WithLegacyBytes($latin1));
        $this->assertTrue(StringHelper::latinEncodingEvidence($dos)['utf8']);
        $this->assertFalse(StringHelper::isUtf8WithLegacyBytes("\xC3\x9Cberweisung M\xC3\xBCller"), 'gueltiges UTF-8');
        $this->assertFalse(StringHelper::isUtf8WithLegacyBytes("M\x81ller Gesch\x84ft"), 'reines CP850');
        $this->assertFalse(StringHelper::isUtf8WithLegacyBytes("\xDA\xC4\xC4\xBF M\x81ller \xC0\xC4\xC4\xB4"), 'CP850-Rahmen ohne Buchstaben daneben');
        $this->assertFalse(StringHelper::isUtf8WithLegacyBytes("a\xC3\x97b M\x81ller"), 'Malzeichen ist kein Buchstabe');

        $this->assertSame(
            "\"\u{00DC}berweisungsgutschrift\";\"M\u{00FC}ller Gartenbau\";\"Geb\u{00FC}hr f\u{00FC}r R\u{00FC}ckbuchung\"",
            StringHelper::repairInvalidUtf8($dos, StringHelper::legacyEncodingOfInvalidBytes($dos))
        );
        $this->assertSame(
            "Zahlungsempf\u{00E4}nger;Kaffeer\u{00F6}sterei M\u{00FC}ller;Bonus f\u{00FC}r Debitkarte",
            StringHelper::repairInvalidUtf8($latin1, StringHelper::legacyEncodingOfInvalidBytes($latin1))
        );
        $this->assertSame('UTF-8', StringHelper::detectEncoding($dos . "\r\n" . str_repeat("02.07.2025;Abschlag Strom Vertrag 4711;-85,00\r\n", 5)), 'auch der chardet-Weg fuer Zeichenketten');
    }

    /**
     * Doppelt kodierter DOS-Text in einer CP850-Datei (v1-Ausgabe brbgruppe,
     * ckonverter P2): CP850 als Windows-1252 gelesen und als UTF-8 geschrieben
     * ("ANNUIT" + U+017D + "T", "F" + U+0081 + "r", "Gesch" + U+201E + "ft"),
     * daneben der Kopf in CP850. Ohne UTF-8-kodierten Latin-1-Buchstaben galt
     * die Datei als CP850, jedes UTF-8-Zeichen wurde zwei Rahmenzeichen. Belegt
     * sind UTF-8-Teile auch durch Latin-Buchstaben bis U+017F, C1-Steuerzeichen
     * und typografische Zeichen der Windows-1252-Lesart am Buchstaben.
     */
    public function test_utf8_dos_mojibake_with_legacy_bytes_is_utf8_with_legacy_bytes(): void {
        $brb = "Gesch\x84ftvorgang;W\x84hrung;Geb\x81hr\r\n\"EINZUG RATE/ANNUIT\xC5\xBDT\";\"Hamburg F\xC2\x81r 01.\"\r\n\"EINZEL\xC5\xA1BERWEISUNG\"\r\n";

        $this->assertTrue(StringHelper::isUtf8WithLegacyBytes($brb));
        $this->assertTrue(StringHelper::latinEncodingEvidence($brb)['utf8']);
        $this->assertTrue(StringHelper::containsUtf8CharInWord("ANNUIT\xC5\xBDT"), 'Z mit Hacek');
        $this->assertTrue(StringHelper::containsUtf8CharInWord("F\xC2\x81r"), 'C1-Steuerzeichen');
        $this->assertTrue(StringHelper::containsUtf8CharInWord("Gesch\xE2\x80\x9Eft"), 'Anfuehrungszeichen unten');
        $this->assertTrue(StringHelper::containsUtf8CharInWord("K\xE2\x80\x9Dln M\xE2\x84\xA2BEL"), 'Anfuehrungszeichen oben, Markenzeichen');
        $this->assertTrue(StringHelper::containsUtf8LatinLetterInWord("ANNUIT\xC5\xBDT"), 'alter Name, derselbe Beleg');
        $this->assertFalse(StringHelper::containsUtf8CharInWord("Preis 12,00 \xE2\x82\xAC"), 'Euro an einer Zahl');
        $this->assertFalse(StringHelper::isUtf8WithLegacyBytes("Gesch\x84ftvorgang;W\x84hrung;Geb\x81hr;\xC4\xC4\xC4 Summe"), 'reines CP850');

        $this->assertSame(
            "Gesch\u{00E4}ftvorgang;W\u{00E4}hrung;Geb\u{00FC}hr\r\n\"EINZUG RATE/ANNUIT\u{017D}T\";\"Hamburg F\u{0081}r 01.\"\r\n\"EINZEL\u{0161}BERWEISUNG\"\r\n",
            StringHelper::repairInvalidUtf8($brb, StringHelper::legacyEncodingOfInvalidBytes($brb)),
            'UTF-8 bleibt, der Kopf ist CP850 - die Fehlkodierung repariert repairMojibake()'
        );
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function mojibakeRepairedProvider(): iterable {
        // Klasse 1: UTF-8 als Windows-1252 gelesen (SumUp 0141 MDCLRN2T, ckonverter C3)
        yield 'SumUp-Buchungsart' => ['Ausgehende BankÃ¼berweisung', 'Ausgehende Banküberweisung'];
        yield 'Umlaute und Eszett' => ['GrÃ¶ÃŸe Ã„rger Ã–l Ãœbel StraÃŸe Ã¤', 'Größe Ärger Öl Übel Straße ä'];
        yield 'Euro (drei Bytes)' => ['Preis 12,00 â‚¬', 'Preis 12,00 €'];
        yield 'polnisch' => ['WysÅ‚ane z SumUp', 'Wysłane z SumUp'];
        yield 'neben korrekt kodierten Zeichen' => ['Müller BankÃ¼berweisung', 'Müller Banküberweisung'];
        yield 'Satzzeichen in einer reparierten Zeile' => ['MÃ¼ller â€“ RÃ¼ckzahlung', 'Müller – Rückzahlung'];
        // Klasse 2: DOS-Text als Windows-1252/ISO-8859-1 gelesen (v1-Ausgabe brbgruppe, ckonverter P2)
        yield 'brbgruppe: Steuerzeichen belegt die Zeile' => ["\"EINZUG RATE/ANNUIT\u{017D}T Hambur\";\"r Str. 367, Hamburg F\u{0081}r 01.\"", "\"EINZUG RATE/ANNUITÄT Hambur\";\"r Str. 367, Hamburg Für 01.\""];
        yield 'brbgruppe: Kleinbuchstabe vor Grossbuchstaben' => ['"GUTSCHRIFT šBERWEISUNG DAUE"', '"GUTSCHRIFT ÜBERWEISUNG DAUE"'];
        yield 'brbgruppe: im Grossbuchstabenwort' => ['"EINZELšBERWEISUNG Finanzamt"', '"EINZELÜBERWEISUNG Finanzamt"'];
        yield 'Satzzeichen im Wort' => ["Gesch\u{201E}ftvorgang;W\u{201E}hrung;K\u{201D}ln;M\u{2122}BEL", 'Geschäftvorgang;Währung;Köln;MÖBEL'];
        yield 'Nachbarn in der reparierten Lesart' => ["Gr\u{0081}\u{00E1}e aus K\u{201D}ln", 'Grüße aus Köln'];
        yield 'Eszett im Grossbuchstabenwort' => ['STRAáE 5', 'STRAßE 5'];
        yield 'Gross-Umlaut am Wortanfang in belegter Zeile' => ["\u{017D}nderung der Gesch\u{201E}ftsbedingungen", 'Änderung der Geschäftsbedingungen'];
        yield 'ISO-8859-1-Lesart (Steuerzeichen)' => ["Gesch\u{0084}ft \u{009A}bertrag K\u{0094}ln \u{008E}nderung M\u{0099}BEL", 'Geschäft Übertrag Köln Änderung MÖBEL'];
    }

    /** Doppelt kodierter Text wird je physischer Zeile repariert (repairMojibake). */
    #[DataProvider('mojibakeRepairedProvider')]
    public function test_repair_mojibake_repairs_double_encoded_text(string $input, string $expected): void {
        $this->assertSame($expected, StringHelper::repairMojibake($input));
        $this->assertSame($expected, StringHelper::repairMojibakeLine($input));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function mojibakeUnchangedProvider(): iterable {
        yield 'reines UTF-8' => ['Müller Straße, Gebühr 1,50 €, São Paulo, Ærø, Øre'];
        yield 'portugiesisch ohne Folgezeichen' => ['JOÃO SILVA, SÃO PAULO, ÃGUA'];
        yield 'nur ASCII' => ['Kartenzahlung REWE 12.50'];
        yield 'unvollstaendige Folge' => ['Ã( und Ã'];
        yield 'nur Satzzeichen, kein typisches Paar' => ['Temperatur 20Â° â€“ ok'];
        yield 'echter Name mit a Akut (Revolut 0184)' => ['Money added from Gorzolla Jácomo'];
        yield 'deutsche Anfuehrungszeichen vor dem Wort (camt-Beispiel)' => ['„S“ (SEPA-Clearer) bzw. „E“ (Scheckabwicklung)'];
        yield 'englische Anfuehrungszeichen hinter dem Wort' => ['The “Payouts overview” table'];
        yield 'Markenzeichen hinter dem Wort' => ['Brand™ Name'];
        yield 'slawische Namen ohne Beleg' => ['Košice Žilina BOŽENA Dvořák'];
        yield 'Z mit Hacek im Grossbuchstabenwort ohne Beleg' => ['EINZUG RATE/ANNUITŽT Hambur'];
        yield 'ungueltiges UTF-8' => ["Gesch\x84ft F\xC2\x81r"];
        yield 'Windows-1252-Anfuehrungszeichen als ISO-8859-1 gelesen' => ["Er sagte \u{0084}Zitat\u{0094} und ging"];
        yield 'Markenzeichen als ISO-8859-1 gelesen' => ["Brand\u{0099} Name"];
    }

    #[DataProvider('mojibakeUnchangedProvider')]
    public function test_repair_mojibake_keeps_real_text(string $input): void {
        $this->assertSame($input, StringHelper::repairMojibake($input));
    }

    /** Je physischer Zeile: Zeilenenden und Zeilenzahl bleiben, der Beleg gilt nur fuer seine Zeile. */
    public function test_repair_mojibake_works_per_line(): void {
        $this->assertSame(
            "Kopf;Wert\r\nBanküberweisung;1\r\nJOÃO;2\nä;3\n",
            StringHelper::repairMojibake("Kopf;Wert\r\nBankÃ¼berweisung;1\r\nJOÃO;2\nÃ¤;3\n"),
        );
        $this->assertSame("ä\n20Â°", StringHelper::repairMojibake("Ã¤\n20Â°"), 'Klasse 1: nur Zeilen mit typischem Paar');
        $this->assertSame('ä 20°', StringHelper::repairMojibake('Ã¤ 20Â°'));
        $this->assertSame(
            "ANNUITÄT Für\r\nANNUITŽT\r\nÜBERWEISUNG\r\n",
            StringHelper::repairMojibake("ANNUITŽT F\u{0081}r\r\nANNUITŽT\r\nšBERWEISUNG\r\n"),
            'Klasse 2: nur belegte Zeilen'
        );
        $this->assertSame('Ausgehende Banküberweisung Košice', StringHelper::repairMojibake('Ausgehende BankÃ¼berweisung Košice'), 'eine Fehlkodierung je Zeile');
    }

    /**
     * Latin-1 mit typografischen Zeichen (Guillemet 0xAB, Grad 0xB0) und
     * Umlauten (Revolut-MT940, ckonverter 23.10.2026): 0xAB zaehlte als
     * CP852-Buchstabe und kippte jedes 0xE4 im Wort zu CP852 ("Europ" + "n" mit
     * Akut + "ischen"), der Grad zwischen Ziffern als CP850-Rahmenzeichen.
     * Typografische Latin-1-Zeichen ausserhalb eines Worts stehen fuer Latin-1,
     * CP852 braucht seine eigenen Bytes im Buchstabenkontext. Polnisch in CP852
     * und DOS-Rahmen bleiben, was sie waren.
     */
    public function test_latin1_typography_does_not_make_umlauts_cp852(): void {
        $revolut = ":86:177?00SEPA-Zahlung?20Ebay 0\xB002-14730-79155?21Amazon\xAB 20255 Luxemburg?22der Europ\xE4ischen Zentralbank\r\n"
            . ":86:177?00SEPA-Zahlung?20Ebay 0\xB002-14730-79166?21Ebay 0\xB002-14730-79177?22Europ\xE4ischen Zahlung\r\n";
        $detected = StringHelper::detectLegacyEncoding($revolut);
        $this->assertContains($detected, ['Windows-1252', 'ISO-8859-1']);
        $this->assertStringContainsString("Europ\u{00E4}ischen", StringHelper::convertToUtf8($revolut, (string) $detected));
        $this->assertStringContainsString("Amazon\u{00AB} 20255", StringHelper::convertToUtf8($revolut, (string) $detected));
        $this->assertContains(StringHelper::detectLegacyEncoding("Paragraf \xA7 3, 20\xB0C, \xABZitat\xBB, Gr\xFC\xDFe aus M\xE4rz"), ['Windows-1252', 'ISO-8859-1']);

        $polish = (string) iconv('UTF-8', 'CP852', "Zażółć gęślą jaźń; Łódź Poznań; Wrocław Gdańsk Kraków; właśnie będzie");
        $this->assertSame('CP852', StringHelper::detectLegacyEncoding($polish), 'Polnisch bleibt CP852');
        $this->assertSame('CP850', StringHelper::detectLegacyEncoding("M\x81ller Gesch\x84ft \xB0\xB1\xB2\xB0 Gr\x81\xE1e"), 'DOS-Schattierung bleibt CP850');
    }

    /**
     * MacRoman ist wandelbar: chardet meldet "MacRoman", normalisiert
     * "MACROMAN" - weder mbstring noch iconv kennen den Namen (iconv: MACINTOSH,
     * MAC), die Wandlung gab die Rohbytes zurueck (ungueltiges UTF-8).
     */
    public function test_mac_roman_is_convertible(): void {
        foreach (['MacRoman', 'MACROMAN', 'mac-roman', 'x-mac-roman', 'MACINTOSH', 'MAC', 'csMacintosh'] as $name) {
            $this->assertSame('MacRoman', StringHelper::normalizeEncodingName($name), $name);
        }
        $this->assertSame("\u{00C4}pfel M\u{00FC}ller \u{00DF}", StringHelper::convertToUtf8("\x80pfel M\x9Fller \xA7", 'MACROMAN'));
        $this->assertSame("\u{00C4}pfel", StringHelper::convertToUtf8("\x80pfel", 'MacRoman'));
        $this->assertSame("\u{00C4}pfel", StringHelper::convertEncoding("\x80pfel", 'MacRoman', 'UTF-8'));
        $this->assertSame("\x80pfel", StringHelper::convertEncoding("\u{00C4}pfel", 'UTF-8', 'MACROMAN'));
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

    public function test_trim_to_null_keeps_text_and_drops_blanks(): void {
        $this->assertSame('Text', StringHelper::trimToNull("  Text \n"));
        $this->assertSame('5', StringHelper::trimToNull(5));
        $this->assertSame('0', StringHelper::trimToNull('0'));
        $this->assertSame('a b', StringHelper::trimToNull(new class implements \Stringable {
            public function __toString(): string {
                return ' a b ';
            }
        }));

        foreach (['', "  \t", null, [], new \stdClass] as $missing) {
            $this->assertNull(StringHelper::trimToNull($missing));
        }
    }
}
