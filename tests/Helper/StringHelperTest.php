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
use Tests\Contracts\BaseTestCase;

class StringHelperTest extends BaseTestCase {
    public function test_utf8_to_iso8859_1(): void {
        $utf8 = "Grüße";
        $iso = StringHelper::utf8ToIso8859_1($utf8);
        $this->assertNotSame($utf8, $iso);
        $this->assertIsString($iso);
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

        // mbstring-fremde Encodings (DOS-Codepages, chardet-Fehlerkennungen) → iconv-Pfad
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
}
