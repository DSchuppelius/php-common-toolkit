<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Helper\Data\StringHelper;
use CommonToolkit\Helper\FileSystem\File;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

class FileTest extends BaseTestCase {
    private string $testFile;

    protected function setUp(): void {
        $this->testFile = tempnam(sys_get_temp_dir(), 'testfile');
        file_put_contents($this->testFile, 'This is a test file.');
    }

    protected function tearDown(): void {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    public function test_mime_type(): void {
        $mimeType = File::mimeType($this->testFile);
        $this->assertEquals('text/plain', $mimeType);
    }

    public function test_mime_encoding(): void {
        $encoding = File::mimeEncoding($this->testFile);
        $this->assertIsString($encoding);
        $this->assertNotEmpty($encoding);
    }

    public function test_chardet(): void {
        $encoding = File::chardet($this->testFile);
        $this->assertIsString($encoding);
        $this->assertNotEmpty($encoding);
    }

    public function test_mime_type_failure(): void {
        $invalidFile = '/path/to/nonexistent/file';
        $this->assertFalse(File::mimeType($invalidFile));
    }

    public function test_mime_encoding_failure(): void {
        $invalidFile = '/path/to/nonexistent/file';
        $this->assertFalse(File::mimeEncoding($invalidFile));
    }

    public function test_chardet_failure(): void {
        $invalidFile = '/path/to/nonexistent/file';
        $this->assertFalse(File::chardet($invalidFile));
    }

    public function test_file_exists_returns_true_for_existing_file(): void {
        $testFile = tempnam(sys_get_temp_dir(), 'test');
        $this->assertTrue(File::exists($testFile));
        unlink($testFile);
    }

    public function test_file_exists_returns_false_for_non_existing_file(): void {
        $nonExistingFile = '/path/to/non/existing/file.txt';
        $this->assertFalse(File::exists($nonExistingFile));
    }

    public function test_read_returns_content(): void {
        $content = File::read($this->testFile);
        $this->assertEquals('This is a test file.', $content);
    }

    public function test_write_overwrites_file(): void {
        File::write($this->testFile, 'Updated content');
        $this->assertEquals('Updated content', file_get_contents($this->testFile));
    }

    public function test_delete_removes_file(): void {
        File::delete($this->testFile);
        $this->assertFileDoesNotExist($this->testFile);
    }

    public function test_size_returns_correct_file_size(): void {
        $size = File::size($this->testFile);
        $this->assertEquals(strlen('This is a test file.'), $size);
    }

    public function test_is_readable_returns_true(): void {
        $this->assertTrue(File::isReadable($this->testFile));
    }

    public function test_is_ready_returns_true(): void {
        $this->assertTrue(File::isReady($this->testFile));
    }

    public function test_wait4_ready_returns_true_immediately(): void {
        $this->assertTrue(File::wait4Ready($this->testFile));
    }

    public function test_create_new_file(): void {
        $newFile = sys_get_temp_dir() . '/created_test_file.txt';
        File::create($newFile, 0644, 'Hello world');
        $this->assertFileExists($newFile);
        $this->assertEquals('Hello world', file_get_contents($newFile));
        unlink($newFile);
    }

    public function test_rename_file(): void {
        $newName = $this->testFile . '_renamed';
        File::rename($this->testFile, $newName);
        $this->assertFileExists($newName);
        $this->assertFileDoesNotExist($this->testFile);
        unlink($newName);
    }

    public function test_copy_file(): void {
        $copyTarget = $this->testFile . '_copy';
        File::copy($this->testFile, $copyTarget);
        $this->assertFileExists($copyTarget);
        $this->assertEquals(file_get_contents($this->testFile), file_get_contents($copyTarget));
        unlink($copyTarget);
    }

    public function test_move_file(): void {
        $moveTarget = $this->testFile . '_moved';
        File::move($this->testFile, sys_get_temp_dir(), basename($moveTarget));
        $this->assertFileExists($moveTarget);
        $this->assertFileDoesNotExist($this->testFile);
        unlink($moveTarget);
    }

    public function test_contains_keyword_finds_match(): void {
        $this->assertTrue(File::containsKeyword($this->testFile, 'test'));
    }

    public function test_contains_keyword_returns_false_on_no_match(): void {
        $this->assertFalse(File::containsKeyword($this->testFile, 'xyz123'));
    }

    public function test_line_count(): void {
        file_put_contents($this->testFile, "line1\nline2\nline3\n");
        $this->assertEquals(4, File::lineCount($this->testFile));
    }

    public function test_char_count(): void {
        file_put_contents($this->testFile, "abc123");
        $this->assertEquals(6, File::charCount($this->testFile));
    }

    public function test_is_windows_reserved_name_detects_nul(): void {
        $this->assertTrue(File::isWindowsReservedName('/path/to/NUL'));
        $this->assertTrue(File::isWindowsReservedName('/path/to/nul'));
        $this->assertTrue(File::isWindowsReservedName('/path/to/NUL.txt'));
        $this->assertTrue(File::isWindowsReservedName('NUL'));
    }

    public function test_is_windows_reserved_name_detects_all_reserved_names(): void {
        foreach (File::WINDOWS_RESERVED_NAMES as $name) {
            $this->assertTrue(File::isWindowsReservedName("/path/to/$name"), "Failed for $name");
            $this->assertTrue(File::isWindowsReservedName("/path/to/$name.txt"), "Failed for $name.txt");
        }
    }

    public function test_is_windows_reserved_name_returns_false_for_normal_files(): void {
        $this->assertFalse(File::isWindowsReservedName('/path/to/normal.txt'));
        $this->assertFalse(File::isWindowsReservedName('/path/to/file'));
        $this->assertFalse(File::isWindowsReservedName('/path/to/NULLABLE.txt'));
        $this->assertFalse(File::isWindowsReservedName('/path/to/connect.log'));
    }

    public function test_exists_returns_false_for_windows_reserved_names(): void {
        $this->assertFalse(File::exists('/tmp/NUL'));
        $this->assertFalse(File::exists('/tmp/CON'));
        $this->assertFalse(File::exists('/tmp/PRN'));
    }

    public function test_write_throws_exception_for_windows_reserved_name(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Windows-reservierter Gerätename');
        File::write('/tmp/NUL', 'test');
    }

    public function test_create_throws_exception_for_windows_reserved_name(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Windows-reservierter Gerätename');
        File::create('/tmp/NUL.txt');
    }

    public function test_rename_throws_exception_for_windows_reserved_name(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Windows-reservierter Gerätename');
        File::rename($this->testFile, '/tmp/NUL');
    }

    public function test_read_as_utf8_with_ansi_csv(): void {
        $ansiFile = __DIR__ . '/../../.samples/ansi.csv';
        $this->assertFileExists($ansiFile, 'ansi.csv Testdatei muss existieren');

        // Encoding der Originaldatei prüfen (sollte ISO-8859-x oder Windows-1252 sein)
        $encoding = File::chardet($ansiFile);
        $this->assertNotFalse($encoding, 'Encoding muss erkannt werden');
        $this->assertTrue(
            str_contains($encoding, 'ISO-8859') || str_contains($encoding, 'Windows-1252'),
            'Datei sollte als ISO-8859 oder Windows-1252 erkannt werden, erkannt wurde: ' . $encoding
        );

        // Als UTF-8 lesen
        $content = File::readAsUtf8($ansiFile);

        // Prüfen, dass der Inhalt jetzt gültiges UTF-8 ist
        $this->assertTrue(mb_check_encoding($content, 'UTF-8'), 'Inhalt muss gültiges UTF-8 sein');

        // Prüfen, dass Umlaute korrekt konvertiert wurden
        $this->assertStringContainsString('München', $content, 'München muss korrekt konvertiert sein');
        $this->assertStringContainsString('Köln', $content, 'Köln muss korrekt konvertiert sein');
        $this->assertStringContainsString('Straße', $content, 'Straße muss korrekt konvertiert sein');
    }

    public function test_read_as_utf8_repairs_latin1_byte_behind_the_encoding_sample(): void {
        // chardet prueft nur die ersten 4 KB: ein Latin-1-Umlaut dahinter liess
        // die Datei als ASCII gelten, das rohe Byte wurde spaeter zum "?"
        $file = tempnam(sys_get_temp_dir(), 'latin1late');
        file_put_contents($file, str_repeat("Zeile ohne Sonderzeichen;1,00\r\n", 200) . "\"G\xDCNTER STEINDORF\";400,00\r\n");
        File::clearChardetCache();

        try {
            $content = File::readAsUtf8($file);
            $this->assertTrue(mb_check_encoding($content, 'UTF-8'));
            $this->assertStringContainsString("G\u{00DC}NTER STEINDORF", $content);

            $lines = iterator_to_array(File::readLinesAsUtf8($file), false);
            $this->assertSame("\"G\u{00DC}NTER STEINDORF\";400,00", end($lines));
        } finally {
            unlink($file);
            File::clearChardetCache();
        }
    }

    /**
     * ISO-8859-15 mit Umlauten im Dateianfang und dem Euro-Zeichen (0xA4)
     * dahinter: chardet und seine Heuristik sahen nur die ersten 4 KB und
     * lasen Windows-1252 - aus "2,00" + Euro wurde "2,00" + U+00A4 (MT940-Ziel,
     * ckonverter 23.10.2026). Dasselbe hinter einem reinen ASCII-Anfang.
     */
    public function test_read_as_utf8_reads_euro_sign_behind_the_sample_as_iso_8859_15(): void {
        $head = ":20:STARTUMS\r\n:25:25190001/0976643000\r\n:28C:11/1\r\n:60F:C231031EUR1000,00\r\n";
        $block = ":61:2311021102D45,00NDDTNONREF\r\n:86:105?00SEPA-Basislastschrift?20Beitrag f\xFCr November?21Mitgliedsnummer 4711?32Verein f\xFCr Gartenfreunde\r\n"
            . ":61:2311031103C120,00NTRFNONREF\r\n:86:166?00\xDCberweisung?20Rechnung 2023-11 Danke?32M\xFCller Gartenbau GmbH\r\n";
        $asciiBlock = ":61:2311031103C120,00NTRFNONREF\r\n:86:166?00Ueberweisung?20Rechnung 2023-11 Danke?32Mueller Gartenbau GmbH\r\n";
        $tail = ":61:2311231123D2,38NCHGNONREF\r\n:86:808?00Entgelt/Auslagen?20M\xFCller Datum Lieferung: 23.11.2023?222,00\xA4Anzahl: 1 USt. 19,0% 0,?2338\xA4Re.Nr. 3421300423\r\n"
            . ":62F:C231130EUR500,00\r\n-\r\n";
        $cases = [
            'umlaut-head' => $head . str_repeat($block, 30) . $tail,
            'ascii-head' => $head . str_repeat($asciiBlock, 60) . $tail,
        ];
        foreach ($cases as $label => $bytes) {
            $this->assertGreaterThan(4096, strpos($bytes, "\xA4"), $label);
            $file = tempnam(sys_get_temp_dir(), 'latin9late');
            file_put_contents($file, $bytes);
            File::clearChardetCache();

            try {
                $content = File::readAsUtf8($file);
                $this->assertStringContainsString("?222,00\u{20AC}Anzahl", $content, $label);
                $this->assertStringContainsString("M\u{00FC}ller", $content, $label);
                $this->assertStringNotContainsString("\u{00A4}", $content, $label);
            } finally {
                unlink($file);
                File::clearChardetCache();
            }
        }
    }

    public function test_read_as_utf8_reads_cp850_behind_an_ascii_sample_as_cp850(): void {
        // v1-Bankumsaetze (DATEV-ASCII) sind CP850; mit reinem ASCII-Anfang galt
        // jedes spaetere Byte als Windows-1252 (0x84 = Anfuehrungszeichen statt "ae")
        $file = tempnam(sys_get_temp_dir(), 'cp850late');
        file_put_contents($file, str_repeat("Zeile ohne Sonderzeichen;1,00\r\n", 200) . "\"Umbuchung Gesch\x84ft\";\"Gr\x81\xE1e\";400,00\r\n");
        File::clearChardetCache();

        try {
            $this->assertStringContainsString("Umbuchung Gesch\u{00E4}ft", File::readAsUtf8($file));

            $lines = iterator_to_array(File::readLinesAsUtf8($file), false);
            $this->assertSame("\"Umbuchung Gesch\u{00E4}ft\";\"Gr\u{00FC}\u{00DF}e\";400,00", end($lines));
        } finally {
            unlink($file);
            File::clearChardetCache();
        }
    }

    /**
     * Der Zeilenleser entscheidet den Zeichensatz wie readAsUtf8 ueber die ganze
     * Datei (ckonverter N6 Punkt 4): bisher bestimmte die erste Zeile mit einem
     * Legacy-Byte allein das Encoding hinter einem ASCII-Anfang - "Se" + 0xA4 +
     * "or" (CP850 "Senor" mit Tilde) machte die Datei zu ISO-8859-1, die CP850-
     * Umlaute danach wurden Steuerzeichen ("Gesch" + U+0084 + "ft").
     */
    public function test_read_lines_as_utf8_decides_cp850_behind_an_ascii_sample_from_the_whole_file(): void {
        $file = tempnam(sys_get_temp_dir(), 'cp850mix');
        file_put_contents($file, str_repeat("Zeile ohne Sonderzeichen;1,00\r\n", 200)
            . "\"Se\xA4or Garcia\";1,00\r\n\"Caf\x82 am Markt\";2,00\r\n\"Umbuchung Gesch\x84ft\";\"Gr\x81\xE1e\";400,00\r\n");
        File::clearChardetCache();

        try {
            $whole = explode("\r\n", rtrim(File::readAsUtf8($file), "\r\n"));
            File::clearChardetCache();
            $lines = iterator_to_array(File::readLinesAsUtf8($file), false);

            $this->assertSame($whole, $lines);
            $this->assertSame(["\"Se\u{00F1}or Garcia\";1,00", "\"Caf\u{00E9} am Markt\";2,00", "\"Umbuchung Gesch\u{00E4}ft\";\"Gr\u{00FC}\u{00DF}e\";400,00"], array_slice($lines, -3));
        } finally {
            unlink($file);
            File::clearChardetCache();
        }
    }

    /**
     * ISO-8859-15 mit Umlauten im Dateianfang und dem Euro-Zeichen (0xA4)
     * dahinter, zeilenweise gelesen (CSV-Streaming): chardet sah Windows-1252,
     * jede Zeile wurde so gewandelt und das Euro-Zeichen wurde U+00A4. Die
     * Euro-Regel gilt wie in readAsUtf8 fuer die ganze Datei (ckonverter N6).
     */
    public function test_read_lines_as_utf8_reads_euro_sign_behind_the_sample_as_iso_8859_15(): void {
        $head = ":20:STARTUMS\r\n:25:25190001/0976643000\r\n:28C:11/1\r\n:60F:C231031EUR1000,00\r\n";
        $block = ":61:2311021102D45,00NDDTNONREF\r\n:86:105?00SEPA-Basislastschrift?20Beitrag f\xFCr November?21Mitgliedsnummer 4711?32Verein f\xFCr Gartenfreunde\r\n"
            . ":61:2311031103C120,00NTRFNONREF\r\n:86:166?00\xDCberweisung?20Rechnung 2023-11 Danke?32M\xFCller Gartenbau GmbH\r\n";
        $tail = ":61:2311231123D2,38NCHGNONREF\r\n:86:808?00Entgelt/Auslagen?20M\xFCller Datum Lieferung: 23.11.2023?222,00\xA4Anzahl: 1 USt. 19,0% 0,?2338\xA4Re.Nr. 3421300423\r\n"
            . ":62F:C231130EUR500,00\r\n-\r\n";
        // klein: die Datei wird fuer die Entscheidung ganz gelesen; gross (> 256 KB): zeilenweise
        foreach (['klein' => 30, 'gross' => 1500] as $label => $repeat) {
            $bytes = $head . str_repeat($block, $repeat) . $tail;
            $this->assertGreaterThan(4096, strpos($bytes, "\xA4"), $label);
            $file = tempnam(sys_get_temp_dir(), 'latin9lines');
            file_put_contents($file, $bytes);
            File::clearChardetCache();

            try {
                $lines = iterator_to_array(File::readLinesAsUtf8($file), false);
                $euro = array_values(array_filter($lines, static fn (string $line): bool => str_starts_with($line, ':86:808')));
                $this->assertSame([":86:808?00Entgelt/Auslagen?20M\u{00FC}ller Datum Lieferung: 23.11.2023?222,00\u{20AC}Anzahl: 1 USt. 19,0% 0,?2338\u{20AC}Re.Nr. 3421300423"], $euro, $label);
                $this->assertStringNotContainsString("\u{00A4}", implode("\n", $lines), $label);
                File::clearChardetCache();
                $this->assertSame(explode("\r\n", rtrim(File::readAsUtf8($file), "\r\n")), $lines, $label . ': wie readAsUtf8');
            } finally {
                unlink($file);
                File::clearChardetCache();
            }
        }
    }

    /**
     * Deutsche Dateien, die chardet als Latin-Variante liest: Latin-1 mit
     * Euro-Zeichen als ISO-8859-9 (Tuerkisch), DOS-Text (CP850) als Windows-1254.
     *
     * @return array<string, array{string, string}>
     */
    public static function latinVariantFileProvider(): array {
        return [
            'latin1-als-iso-8859-9' => [
                "Buchungstag;Verwendungszweck;Betrag\r\n" . str_repeat("02.07.2024;Abschlag Strom Vertrag 4711;-85,00\r\n", 10)
                    . "03.07.2024;Buchung M\xFCller Gartenbau Rechnung Gr\xFC\xDFe aus K\xF6ln 2,00\xA4;-2,00\r\n",
                "03.07.2024;Buchung M\u{00FC}ller Gartenbau Rechnung Gr\u{00FC}\u{00DF}e aus K\u{00F6}ln 2,00\u{20AC};-2,00",
            ],
            'cp850-als-windows-1254' => [
                "Buchungstag;Text;Betrag\r\n15.03.2024;\"Rechnungsabschluss (Konto\x81bersicht) Wir bitten um Pruefung\";0,00\r\n"
                    . "15.03.2024;\"Kontoauszug gem\x84\xE1 Ziffer 11 AGB, Gesch\x84ftsbedingungen, Vollst\x84ndigkeit\";0,00\r\n",
                "15.03.2024;\"Kontoauszug gem\u{00E4}\u{00DF} Ziffer 11 AGB, Gesch\u{00E4}ftsbedingungen, Vollst\u{00E4}ndigkeit\";0,00",
            ],
        ];
    }

    /**
     * chardet meldet fuer kurze deutsche Latin-1-Texte ISO-8859-9 (Tuerkisch)
     * und fuer DOS-Text (CP850) Windows-1254 (ckonverter 23.10.2026). Die
     * Variante blieb: die Euro-Regel griff nicht ("2,00" + U+00A4), die
     * Heuristik fuer DOS-Codepages lief nicht ("Konto?bersicht"). Ohne die
     * Bytes der Variante gilt ihre Grundform - im chardet-Pfad, im Ganzleser
     * und im Zeilenleser gleich.
     */
    #[DataProvider('latinVariantFileProvider')]
    public function test_read_as_utf8_reads_a_latin_variant_without_its_bytes_as_its_base(string $bytes, string $lastLine): void {
        $file = tempnam(sys_get_temp_dir(), 'latinvariant');
        file_put_contents($file, $bytes);
        File::clearChardetCache();

        try {
            $whole = explode("\r\n", rtrim(File::readAsUtf8($file), "\r\n"));
            $this->assertSame($lastLine, end($whole));
            $this->assertStringNotContainsString("\u{00A4}", implode("\n", $whole));

            File::clearChardetCache();
            $this->assertSame($whole, iterator_to_array(File::readLinesAsUtf8($file), false), 'Zeilenleser wie readAsUtf8');

            File::clearChardetCache();
            $encoding = File::chardet($file);
            $this->assertIsString($encoding);
            $this->assertNull(StringHelper::latinVariantBase($encoding), $encoding);
        } finally {
            unlink($file);
            File::clearChardetCache();
        }
    }

    /**
     * Die Entscheidung ueber eine Latin-Variante gilt fuer die ganze Datei,
     * auch wenn sie zu gross ist, um sie ganz zu laden (zeilenweise Merkmale):
     * ein tuerkisches Zeichen (0xFD) hinter 256 KB haelt ISO-8859-9, ein
     * Euro-Zeichen dahinter macht aus der Grundform ISO-8859-15.
     */
    public function test_latin_variant_of_a_large_file_is_decided_over_the_whole_file(): void {
        $refine = new \ReflectionMethod(File::class, 'refineLatinEncodingOfFile');
        $head = "Buchungstag;Verwendungszweck;Betrag\r\n03.07.2024;Rechnung M\xFCller Gr\xFC\xDFe aus K\xF6ln;-2,00\r\n";
        $body = str_repeat("02.07.2024;Abschlag Strom Vertrag 4711;-85,00\r\n", 7000);
        $cases = [
            'deutsch' => [$head . $body . "31.07.2024;Kontof\xFChrung;-2,00\r\n", 'ISO-8859-9', 'ISO-8859-1'],
            'euro' => [$head . $body . "31.07.2024;Entgelt 2,00\xA4;-2,00\r\n", 'ISO-8859-9', 'ISO-8859-15'],
            'tuerkisch' => [$head . $body . "31.07.2024;Kira \xF6demesi Ba\xFEar\xFDl\xFD;-2,00\r\n", 'ISO-8859-9', 'ISO-8859-9'],
            'windows' => [$head . $body . "31.07.2024;\x93Abschlag\x94;-2,00\r\n", 'Windows-1254', 'Windows-1252'],
        ];
        foreach ($cases as $label => [$bytes, $encoding, $expected]) {
            $this->assertGreaterThan(262144, strlen($bytes), $label);
            $file = tempnam(sys_get_temp_dir(), 'latinvariantlarge');
            file_put_contents($file, $bytes);

            try {
                $this->assertSame($expected, $refine->invoke(null, $file, $encoding), $label);
                $this->assertSame($expected, StringHelper::refineLatinEncoding($bytes, $encoding), "$label: wie der ganze Text");
            } finally {
                unlink($file);
            }
        }
    }

    /**
     * Deutsche DOS-Dateien, die chardet als CJK-Mehrbyte-Kodierung liest
     * (Shift_JIS, CP949; ckonverter 23.10.2026).
     *
     * @return array<string, array{string, string}>
     */
    public static function cjkMisdetectedFileProvider(): array {
        $head = "Buchungstag;Valuta;Empfaenger;Verwendungszweck;Betrag;Waehrung\r\n";
        $ascii = '';
        for ($i = 1; $i <= 12; $i++) {
            $ascii .= sprintf("%02d.03.2024;%02d.03.2024;\"Schubert GmbH\";\"Abschlag Strom Vertrag 47%02d\";-85,00;EUR\r\n", $i, $i, $i);
        }
        $umlauts = "15.03.2024;15.03.2024;\"BW B\x81cherWege Verlag\";\"Umsatz B\x81cherWege KW 11\";731,00;EUR\r\n"
            . "16.03.2024;16.03.2024;\"Schubert F\x81m GmbH\";\"R\x81ckzahlung: Zinsen\";12,00;EUR\r\n"
            . "17.03.2024;17.03.2024;\"Sparkasse\";\"Hinweise auf der R\x81ckseite beachten\";0,00;EUR\r\n";
        $line = '"' . str_repeat("\xC4", 32) . '";"' . str_repeat("\xC4", 32) . "\";0,00;EUR\r\n";

        return [
            'shift-jis' => [$head . $ascii . $umlauts, "Umsatz B\u{00FC}cherWege KW 11"],
            'cp949' => [$head . substr($ascii, 0, 600) . '10.07.2025;10.07.2025;' . $line
                . "11.07.2025;11.07.2025;\"G\x81lten T\x81rkUmbuchung\";\"G\x81lten T\x81rk\";50,00;EUR\r\n"
                . "12.07.2025;12.07.2025;\"Geldw\x84schepr\x84vention\";\"R\x81ckgabe/Widerruf\";-50,00;EUR\r\n"
                . '13.07.2025;13.07.2025;' . $line, "Geldw\u{00E4}schepr\u{00E4}vention"],
        ];
    }

    /**
     * chardet meldet fuer deutsche DOS-Dateien Shift_JIS oder CP949: jedes
     * Umlaut-Byte bildet mit dem folgenden Buchstaben ein Kanji/Hangul, der
     * Buchstabe ging verloren. Das CJK-Dekodat ohne CJK-Dichte (einzelne
     * Zeichen in ASCII-Woertern) ist eine Fehlerkennung - die Latin-Heuristik
     * entscheidet, Ganzleser und Zeilenleser lesen DOS-Text, Byte fuer Zeichen.
     */
    #[DataProvider('cjkMisdetectedFileProvider')]
    public function test_read_as_utf8_reads_a_german_dos_file_that_chardet_calls_cjk_as_cp850(string $bytes, string $expected): void {
        $file = tempnam(sys_get_temp_dir(), 'cjkdos');
        file_put_contents($file, $bytes);
        File::clearChardetCache();

        try {
            $this->assertContains(File::chardet($file), ['CP850', 'CP437'], 'eine DOS-Codepage (gleich auf Umlauten und Rahmen)');
            File::clearChardetCache();
            $whole = File::readAsUtf8($file);
            $this->assertStringContainsString($expected, $whole);
            $this->assertSame(strlen($bytes), mb_strlen($whole), 'laengengleich: jedes Byte ein Zeichen');
            File::clearChardetCache();
            $this->assertSame(explode("\r\n", rtrim($whole, "\r\n")), iterator_to_array(File::readLinesAsUtf8($file), false), 'Zeilenleser wie readAsUtf8');
        } finally {
            unlink($file);
            File::clearChardetCache();
        }
    }

    /** Echtes Japanisch in Shift_JIS bleibt Shift_JIS (Gegenprobe zur CJK-Regel). */
    public function test_read_as_utf8_keeps_a_real_shift_jis_file(): void {
        $text = "\u{53D6}\u{5F15}\u{65E5};\u{6458}\u{8981};\u{91D1}\u{984D}\r\n"
            . "2024/03/01;\u{304A}\u{632F}\u{8FBC}\u{307F}\u{3001}\u{6771}\u{4EAC}\u{90FD}\u{4E2D}\u{592E}\u{533A};-85,000\r\n"
            . "2024/03/02;\u{624B}\u{6570}\u{6599}\u{306E}\u{3054}\u{6848}\u{5185}\u{3002};-330\r\n"
            . "2024/03/03;\u{30AB}\u{30FC}\u{30C9}\u{5229}\u{7528} \u{30B3}\u{30F3}\u{30D3}\u{30CB}\u{30A8}\u{30F3}\u{30B9}\u{30B9}\u{30C8}\u{30A2};-1,200\r\n";
        $file = tempnam(sys_get_temp_dir(), 'sjis');
        file_put_contents($file, (string) iconv('UTF-8', 'SHIFT_JIS', $text));
        File::clearChardetCache();

        try {
            $this->assertSame('SHIFT_JIS', File::chardet($file));
            File::clearChardetCache();
            $this->assertSame($text, File::readAsUtf8($file));
        } finally {
            unlink($file);
            File::clearChardetCache();
        }
    }

    /**
     * Echter tuerkischer Text, den chardet als ISO-8859-1 meldet (ckonverter
     * P2): Ganz- und Zeilenleser lesen ihn als Tuerkisch, auch wenn er hinter
     * einer ASCII-Stichprobe steht. Islaendisch bleibt Latin-1.
     */
    public function test_read_as_utf8_reads_turkish_text_that_chardet_calls_latin1_as_turkish(): void {
        $turkish = "İşlem Tarihi;Açıklama;Tutar;Bakiye\r\n02.07.2025;Kira ödemesi Şubat;-1.250,00;3.420,15\r\n"
            . "03.07.2025;Maaş ödemesi Yılmaz Gıda Ltd. Şti.;8.500,00;11.920,15\r\n04.07.2025;Kredi kartı ödemesi;-640,30;11.279,85\r\n"
            . "07.07.2025;Havale işlemi başarılı;-300,00;11.039,95\r\n08.07.2025;Müşteri iadesi Çiçek Mağazası;45,00;11.084,95\r\n";
        $icelandic = "Dagsetning;Skýring;Upphæð;Staða\r\n02.07.2025;Millifærsla frá Þórði Guðmundssyni;-12.500;342.015\r\n"
            . "03.07.2025;Launagreiðsla júlí;850.000;1.192.015\r\n06.07.2025;Þjónustugjald Íslandsbanki;-899;1.199.713\r\n";
        $padding = str_repeat("01.07.2025;Kartenzahlung REWE Markt;-12,50;100,00\r\n", 120);
        $cases = [
            'tuerkisch' => [(string) iconv('UTF-8', 'ISO-8859-9', $turkish), $turkish, ['ISO-8859-9', 'Windows-1254']],
            'tuerkisch hinter der Stichprobe' => [$padding . (string) iconv('UTF-8', 'Windows-1254', $turkish), $padding . $turkish, ['ASCII']],
            'islaendisch' => [(string) iconv('UTF-8', 'ISO-8859-1', $icelandic), $icelandic, ['ISO-8859-1', 'Windows-1252']],
        ];

        foreach ($cases as $name => [$bytes, $expected, $chardet]) {
            $file = tempnam(sys_get_temp_dir(), 'turkish');
            file_put_contents($file, $bytes);
            File::clearChardetCache();
            try {
                $this->assertContains(File::chardet($file), $chardet, $name);
                File::clearChardetCache();
                $this->assertSame($expected, File::readAsUtf8($file), $name);
                File::clearChardetCache();
                $this->assertSame(explode("\r\n", rtrim($expected, "\r\n")), iterator_to_array(File::readLinesAsUtf8($file), false), $name . ': Zeilenleser');
            } finally {
                unlink($file);
                File::clearChardetCache();
            }
        }
    }

    /**
     * SumUp 0141 (ckonverter 23.10.2026): je Zeile ein einzelnes 0xAC
     * ("BankA" + 0xAC + "berweisung", v1-Quellmuell), chardet meldet Johab. Der
     * Legacy-Rest ist kein CJK: Windows-1252, Byte fuer Zeichen, im Ganz- wie
     * im Zeilenleser.
     */
    public function test_read_as_utf8_reads_a_sparse_legacy_rest_that_chardet_calls_johab(): void {
        $ascii = "DRESDEFF100;DE91100800000012345678;;;;04.08.2025;46,35;\"SUMUP PID1270662 PAYOUT\";;;;\"SumUp Einzahlung\";\"Ref 2025 Nr 4711\";;;EUR;;;;;\r\n";
        $bytes = $ascii;
        foreach (['Eingehende', 'Ausgehende', 'Ausgehende', 'Ausgehende', 'Eingehende', 'Ausgehende'] as $i => $direction) {
            $bytes .= "DRESDEFF100;DE91100800000012345678;;;;1$i.08.2025;-48,90;\"Lieferant GmbH\";;;DE06562500301000585260;\"$direction BankAberweisung \";"
                . "\"Lieferant GmbH DE0656250030\";\"1000585260 Rechnung 2025-4711\";\"COP54LP2LR                 \";;EUR;\"$direction BankA\xACberweisung\";;;;;;;;;;;;;;;;\r\n";
        }
        $file = tempnam(sys_get_temp_dir(), 'sumup');
        file_put_contents($file, $bytes);
        File::clearChardetCache();

        try {
            $this->assertSame('Windows-1252', File::chardet($file));
            File::clearChardetCache();
            $whole = File::readAsUtf8($file);
            $this->assertStringContainsString("Ausgehende BankA\u{00AC}berweisung", $whole);
            $this->assertSame(strlen($bytes), mb_strlen($whole), 'laengengleich: jedes Byte ein Zeichen');
            File::clearChardetCache();
            $this->assertSame(explode("\r\n", rtrim($whole, "\r\n")), iterator_to_array(File::readLinesAsUtf8($file), false), 'Zeilenleser wie readAsUtf8');
        } finally {
            unlink($file);
            File::clearChardetCache();
        }
    }

    /**
     * Gemischte Dateien (v1-Ausgaben, ckonverter 23.10.2026): die Textspalte
     * oder der Kopf als UTF-8, der Rest CP850 bzw. Latin-1. chardet meldete eine
     * Einzelkodierung (Windows-1254, MacRoman, ISO-8859-1), die ganze Datei wurde
     * damit gewandelt - "Ue" als C3 9C wurde Rahmen + Pfund oder "A" mit Breve +
     * "s" mit Akut, "ae" als C3 A4 wurde "A" mit Tilde + Waehrungszeichen.
     * Traegt eine Datei UTF-8-kodierte Buchstaben in Woertern, ist sie UTF-8 mit
     * Legacy-Bytes: der Reparaturweg vor jeder Ganzdatei-Heuristik, auch wenn
     * die UTF-8-Zeilen hinter 256 KB stehen.
     *
     * @return array<string, array{string, list<string>}>
     */
    public static function mixedUtf8FileProvider(): array {
        $head = "Buchungstag;Valuta;Text;Empfaenger;Verwendungszweck;Betrag;Waehrung\r\n";
        $ascii = "02.07.2025;02.07.2025;Lastschrift;\"Stadtwerke Musterstadt\";\"Abschlag Strom Vertrag 4711\";-85,00;EUR\r\n";
        $mixed = "03.07.2025;03.07.2025;\"\xC3\x9Cberweisungsgutschrift\";\"M\x81ller Gartenbau\";\"Geb\x81hr f\x81r R\x81ckbuchung\";12,00;EUR\r\n";
        $dos = '';
        for ($i = 0; $i < 30; $i++) {
            $dos .= $i % 3 === 0 ? $mixed : $ascii;
        }
        $latin1Head = "Buchungstag;Zahlungsempf\xC3\xA4nger;Empf\xC3\xA4nger-IBAN;Verwendungszweck;Betrag\r\n";
        $latin1Row = "03.07.2025;\"Kaffeer\xF6sterei M\xFCller\";DE02120300000000202051;\"Bonus f\xFCr Debitkarte\";1,00\r\n";

        return [
            'utf8-und-cp850' => [$head . $dos, ["03.07.2025;03.07.2025;\"\u{00DC}berweisungsgutschrift\";\"M\u{00FC}ller Gartenbau\";\"Geb\u{00FC}hr f\u{00FC}r R\u{00FC}ckbuchung\";12,00;EUR"]],
            'utf8-kopf-und-latin1' => [$latin1Head . str_repeat($latin1Row, 20), [
                "Buchungstag;Zahlungsempf\u{00E4}nger;Empf\u{00E4}nger-IBAN;Verwendungszweck;Betrag",
                "03.07.2025;\"Kaffeer\u{00F6}sterei M\u{00FC}ller\";DE02120300000000202051;\"Bonus f\u{00FC}r Debitkarte\";1,00",
            ]],
            'cp850-mit-doppelt-kodiertem-dos-text' => [
                "BLZ;Konto;Umsatz;Verwendungszweck 1;Verwendungszweck 2;Gesch\x84ftvorgang;W\x84hrung;\x8Equivalenzbetrag;Geb\x81hr\r\n"
                . str_repeat("HASPDEHHXXX;DE34200505501050805173;-529,19;\"EINZUG RATE/ANNUIT\xC5\xBDT Hambur\";\"r Str. 367, Hamburg F\xC2\x81r 01.\";;EUR;;\r\n"
                    . "HASPDEHHXXX;DE34200505501050805173;2261,00;\"GUTSCHRIFT \xC5\xA1BERWEISUNG DAUE\";\" H.J. Mordhorst Holzhandel \";;EUR;;\r\n", 6),
                [
                    "BLZ;Konto;Umsatz;Verwendungszweck 1;Verwendungszweck 2;Gesch\u{00E4}ftvorgang;W\u{00E4}hrung;\u{00C4}quivalenzbetrag;Geb\u{00FC}hr",
                    "HASPDEHHXXX;DE34200505501050805173;-529,19;\"EINZUG RATE/ANNUIT\u{017D}T Hambur\";\"r Str. 367, Hamburg F\u{0081}r 01.\";;EUR;;",
                    "HASPDEHHXXX;DE34200505501050805173;2261,00;\"GUTSCHRIFT \u{0161}BERWEISUNG DAUE\";\" H.J. Mordhorst Holzhandel \";;EUR;;",
                ],
            ],
            'cp850-mit-doppelt-kodiertem-ae' => [
                "Buchungstag;Text;Betrag\r\n" . str_repeat("02.07.2025;\"Geb\x81hr f\x81r R\x81ckbuchung\";-1,00\r\n03.07.2025;\"Gesch\xE2\x80\x9Eftsanteil\";2,00\r\n", 5),
                ["02.07.2025;\"Geb\u{00FC}hr f\u{00FC}r R\u{00FC}ckbuchung\";-1,00", "03.07.2025;\"Gesch\u{201E}ftsanteil\";2,00"],
            ],
            'gross-utf8-zeile-hinten' => [str_repeat($latin1Row, 3500) . "31.07.2025;\"Stra\xC3\x9Fenbau M\xC3\xBCller\";DE02120300000000202051;\"Abschlag\";1,00\r\n", [
                "03.07.2025;\"Kaffeer\u{00F6}sterei M\u{00FC}ller\";DE02120300000000202051;\"Bonus f\u{00FC}r Debitkarte\";1,00",
                "31.07.2025;\"Stra\u{00DF}enbau M\u{00FC}ller\";DE02120300000000202051;\"Abschlag\";1,00",
            ]],
        ];
    }

    /** @param list<string> $expectedLines */
    #[DataProvider('mixedUtf8FileProvider')]
    public function test_read_as_utf8_repairs_a_mixed_utf8_and_legacy_file(string $bytes, array $expectedLines): void {
        $file = tempnam(sys_get_temp_dir(), 'mixedutf8');
        file_put_contents($file, $bytes);
        File::clearChardetCache();

        try {
            $this->assertSame('UTF-8', File::chardet($file));
            File::clearChardetCache();
            $whole = explode("\r\n", rtrim(File::readAsUtf8($file), "\r\n"));
            foreach ($expectedLines as $expected) {
                $this->assertContains($expected, $whole);
            }
            File::clearChardetCache();
            $this->assertSame($whole, iterator_to_array(File::readLinesAsUtf8($file), false), 'Zeilenleser wie readAsUtf8');
            File::clearChardetCache();
            $this->assertSame(array_slice($whole, -2), File::tailAsUtf8($file, 2, true), 'tailAsUtf8 repariert wie readAsUtf8');
        } finally {
            unlink($file);
            File::clearChardetCache();
        }
    }

    /**
     * Latin-1-MT940 mit Guillemet und Grad (Revolut, ckonverter 23.10.2026):
     * die Heuristik waehlte CP852, aus "Europaeischen" wurde "Europ" + "n" mit
     * Akut + "ischen".
     */
    public function test_read_as_utf8_reads_latin1_with_typography_as_latin1(): void {
        $bytes = ":20:STARTUMS\r\n:25:DE50100101788784724252\r\n:28C:0\r\n:60F:C260601EUR100,00\r\n"
            . ":61:2606020602D12,00NMSCNONREF\r\n:86:177?00SEPA-Zahlung?20Ebay 0\xB002-14730-79155?21Amazon\xAB 20255 Luxemburg?22der Europ\xE4ischen Zentralbank\r\n"
            . ":61:2606030603D2,00NMSCNONREF\r\n:86:177?00SEPA-Zahlung?20Ebay 0\xB002-14730-79166?21Ebay 0\xB002-14730-79177?22Europ\xE4ischen Zahlung\r\n"
            . ":62F:C260630EUR86,00\r\n-\r\n";
        $file = tempnam(sys_get_temp_dir(), 'latin1typo');
        file_put_contents($file, $bytes);
        File::clearChardetCache();

        try {
            $whole = File::readAsUtf8($file);
            $this->assertStringContainsString("der Europ\u{00E4}ischen Zentralbank", $whole);
            $this->assertStringContainsString("Ebay 0\u{00B0}02-14730-79155?21Amazon\u{00AB} 20255", $whole);
            File::clearChardetCache();
            $this->assertSame(explode("\r\n", rtrim($whole, "\r\n")), iterator_to_array(File::readLinesAsUtf8($file), false));
            File::clearChardetCache();
            $this->assertContains(File::chardet($file), ['Windows-1252', 'ISO-8859-1']);
        } finally {
            unlink($file);
            File::clearChardetCache();
        }
    }

    /**
     * chardet meldet MacRoman (ckonverter 23.10.2026): die Heuristik sah nur die
     * 4-KB-Stichprobe, deren einziges Sonderzeichen (CP850 0x90 in "CAFE" mit
     * Akut) nichts entschied - die CP850-Umlaute dahinter zaehlten nicht, und
     * "MACROMAN" kannten weder mbstring noch iconv: readAsUtf8 gab ungueltiges
     * UTF-8 zurueck, tailAsUtf8 warf einen ValueError. Entscheidet die
     * Stichprobe nicht, entscheiden die Legacy-Bytes der ganzen Datei; bleibt
     * es MacRoman, wird MacRoman gewandelt.
     */
    public function test_mac_roman_file_is_decided_over_the_whole_file_and_convertible(): void {
        $head = "Buchungstag;Valuta;Text;Empfaenger;Verwendungszweck;Betrag;Waehrung\r\n";
        $ascii = "02.07.2025;02.07.2025;Lastschrift;\"Stadtwerke Musterstadt\";\"Abschlag Strom Vertrag 4711\";-85,00;EUR\r\n";
        $dos = $head . $ascii . $ascii . "05.06.2025;05.06.2025;Lastschrift;\"GRAND CAF\x90 BETRIEBSGESELLSCHAFT\";\"Rechnung 4711\";-245,00;EUR\r\n"
            . str_repeat($ascii, 57) . str_repeat("17.07.2025;17.07.2025;Entgelt;\"DKB AG\";\"2,2% f\x81r W\x84hrungsumrechnung\";-0,38;EUR\r\n", 5);
        $undecided = "Buchungstag;Valuta;Text;Empfaenger;Verwendungszweck;Betrag;Waehrung\r\n"
            . str_repeat("02.09.2025;02.09.2025;Lastschrift;\"JPMORGAN CHASE BANK\";\"BANK REFD 135 9929 80131\";-85,00;EUR\r\n", 20)
            . "03.09.2025;03.09.2025;Gutschrift;\"9929 80131I \x83GMBH AND CO.\xA0 \";\"KG 0.00\";100,00;EUR\r\n";
        $file = tempnam(sys_get_temp_dir(), 'macroman');

        try {
            file_put_contents($file, $dos);
            File::clearChardetCache();
            $this->assertSame('CP850', File::chardet($file));
            File::clearChardetCache();
            $whole = File::readAsUtf8($file);
            $this->assertStringContainsString("\"GRAND CAF\u{00C9} BETRIEBSGESELLSCHAFT\"", $whole);
            $this->assertStringContainsString("\"2,2% f\u{00FC}r W\u{00E4}hrungsumrechnung\"", $whole);

            file_put_contents($file, $undecided);
            File::clearChardetCache();
            $this->assertSame('MacRoman', File::chardet($file), 'ohne Beleg bleibt chardets MacRoman');
            File::clearChardetCache();
            $whole = File::readAsUtf8($file);
            $this->assertTrue(mb_check_encoding($whole, 'UTF-8'));
            $this->assertStringContainsString("80131I \u{00C9}GMBH AND CO.\u{2020} ", $whole);
            File::clearChardetCache();
            $this->assertSame(explode("\r\n", rtrim($whole, "\r\n")), iterator_to_array(File::readLinesAsUtf8($file), false));
            File::clearChardetCache();
            $this->assertSame(array_slice(explode("\r\n", rtrim($whole, "\r\n")), -1), File::tailAsUtf8($file, 1, true));
        } finally {
            unlink($file);
            File::clearChardetCache();
        }
    }

    public function test_read_lines_as_utf8_with_ansi_csv(): void {
        $ansiFile = __DIR__ . '/../../.samples/ansi.csv';
        $this->assertFileExists($ansiFile, 'ansi.csv Testdatei muss existieren');

        $lines = iterator_to_array(File::readLinesAsUtf8($ansiFile));

        $this->assertGreaterThan(0, count($lines), 'Mindestens eine Zeile erwartet');

        // Alle Zeilen müssen gültiges UTF-8 sein
        foreach ($lines as $i => $line) {
            $this->assertTrue(mb_check_encoding($line, 'UTF-8'), "Zeile $i muss gültiges UTF-8 sein");
        }

        // Umlaute in den Zeilen prüfen
        $allContent = implode("\n", $lines);
        $this->assertStringContainsString('München', $allContent, 'München muss korrekt konvertiert sein');
        $this->assertStringContainsString('Straße', $allContent, 'Straße muss im Header korrekt konvertiert sein');
    }

    public function test_read_lines_as_utf8_with_skip_empty(): void {
        $ansiFile = __DIR__ . '/../../.samples/ansi.csv';

        $linesAll = iterator_to_array(File::readLinesAsUtf8($ansiFile, false));
        $linesNoEmpty = iterator_to_array(File::readLinesAsUtf8($ansiFile, true));

        // Bei skipEmpty=true sollten keine leeren Zeilen enthalten sein
        foreach ($linesNoEmpty as $line) {
            $this->assertNotEmpty(trim($line), 'Keine leeren Zeilen bei skipEmpty=true');
        }
    }

    public function test_read_lines_as_utf8_with_max_lines(): void {
        $ansiFile = __DIR__ . '/../../.samples/ansi.csv';

        $lines = iterator_to_array(File::readLinesAsUtf8($ansiFile, false, 2));

        $this->assertCount(2, $lines, 'Genau 2 Zeilen erwartet bei maxLines=2');
        $this->assertTrue(mb_check_encoding($lines[0], 'UTF-8'), 'Erste Zeile muss gültiges UTF-8 sein');
        $this->assertTrue(mb_check_encoding($lines[1], 'UTF-8'), 'Zweite Zeile muss gültiges UTF-8 sein');
    }

    public function test_read_lines_as_utf8_with_start_line(): void {
        $ansiFile = __DIR__ . '/../../.samples/ansi.csv';

        // Ab Zeile 2 lesen (überspringt Header)
        $lines = iterator_to_array(File::readLinesAsUtf8($ansiFile, false, null, 2));
        $allLines = iterator_to_array(File::readLinesAsUtf8($ansiFile));

        $this->assertCount(count($allLines) - 1, $lines, 'Eine Zeile weniger bei startLine=2');

        // Erste Zeile sollte Daten enthalten, nicht den Header
        $this->assertStringNotContainsString('Hausnummer', $lines[0], 'Header sollte übersprungen sein');
    }

    public function test_read_lines_as_array_utf8(): void {
        $ansiFile = __DIR__ . '/../../.samples/ansi.csv';

        $lines = File::readLinesAsArrayUtf8($ansiFile);

        $this->assertGreaterThan(0, count($lines));

        // Alle Zeilen müssen gültiges UTF-8 sein
        foreach ($lines as $line) {
            $this->assertTrue(mb_check_encoding($line, 'UTF-8'), 'Zeile muss gültiges UTF-8 sein');
        }

        $allContent = implode("\n", $lines);
        $this->assertStringContainsString('München', $allContent);
        $this->assertStringContainsString('Köln', $allContent);
    }

    public function test_tail_returns_last_lines(): void {
        // Erstelle Testdatei mit mehreren Zeilen
        $testFile = tempnam(sys_get_temp_dir(), 'tail_test');
        $content = "Zeile 1\nZeile 2\nZeile 3\nZeile 4\nZeile 5\nZeile 6\nZeile 7\nZeile 8\nZeile 9\nZeile 10";
        file_put_contents($testFile, $content);

        try {
            // Lese die letzten 3 Zeilen
            $lines = File::tail($testFile, 3);
            $this->assertCount(3, $lines);
            $this->assertEquals('Zeile 8', $lines[0]);
            $this->assertEquals('Zeile 9', $lines[1]);
            $this->assertEquals('Zeile 10', $lines[2]);
        } finally {
            unlink($testFile);
        }
    }

    public function test_tail_with_default_lines(): void {
        // Erstelle Testdatei mit mehr als 10 Zeilen
        $testFile = tempnam(sys_get_temp_dir(), 'tail_test');
        $lines = [];
        for ($i = 1; $i <= 15; $i++) {
            $lines[] = "Zeile $i";
        }
        file_put_contents($testFile, implode("\n", $lines));

        try {
            // Standard: 10 Zeilen
            $result = File::tail($testFile);
            $this->assertCount(10, $result);
            $this->assertEquals('Zeile 6', $result[0]);
            $this->assertEquals('Zeile 15', $result[9]);
        } finally {
            unlink($testFile);
        }
    }

    public function test_tail_with_skip_empty(): void {
        $testFile = tempnam(sys_get_temp_dir(), 'tail_test');
        $content = "Zeile 1\n\nZeile 2\n\n\nZeile 3\nZeile 4\n\nZeile 5";
        file_put_contents($testFile, $content);

        try {
            // Ohne skipEmpty
            $lines = File::tail($testFile, 5, false);
            $this->assertContains('', $lines);

            // Mit skipEmpty
            $linesSkipped = File::tail($testFile, 5, true);
            $this->assertNotContains('', $linesSkipped);
            $this->assertCount(5, $linesSkipped);
        } finally {
            unlink($testFile);
        }
    }

    public function test_tail_with_empty_file(): void {
        $testFile = tempnam(sys_get_temp_dir(), 'tail_test');
        file_put_contents($testFile, '');

        try {
            $lines = File::tail($testFile, 10);
            $this->assertCount(0, $lines);
        } finally {
            unlink($testFile);
        }
    }

    public function test_tail_with_fewer_lines_than_requested(): void {
        $testFile = tempnam(sys_get_temp_dir(), 'tail_test');
        $content = "Zeile 1\nZeile 2\nZeile 3";
        file_put_contents($testFile, $content);

        try {
            // Fordere 10 Zeilen an, aber nur 3 vorhanden
            $lines = File::tail($testFile, 10);
            $this->assertCount(3, $lines);
            $this->assertEquals('Zeile 1', $lines[0]);
            $this->assertEquals('Zeile 2', $lines[1]);
            $this->assertEquals('Zeile 3', $lines[2]);
        } finally {
            unlink($testFile);
        }
    }

    public function test_tail_throws_exception_for_invalid_lines(): void {
        $this->expectException(\InvalidArgumentException::class);
        File::tail($this->testFile, 0);
    }

    public function test_tail_throws_exception_for_non_existing_file(): void {
        $this->expectException(\ERRORToolkit\Exceptions\FileSystem\FileNotFoundException::class);
        File::tail('/path/to/nonexistent/file.txt', 10);
    }

    public function test_tail_as_utf8(): void {
        $ansiFile = __DIR__ . '/../../.samples/ansi.csv';

        $lines = File::tailAsUtf8($ansiFile, 5);

        $this->assertLessThanOrEqual(5, count($lines));

        // Alle Zeilen müssen gültiges UTF-8 sein
        foreach ($lines as $line) {
            $this->assertTrue(mb_check_encoding($line, 'UTF-8'), 'Zeile muss gültiges UTF-8 sein');
        }
    }

    public function test_tail_with_windows_line_endings(): void {
        $testFile = tempnam(sys_get_temp_dir(), 'tail_test');
        $content = "Zeile 1\r\nZeile 2\r\nZeile 3\r\nZeile 4\r\nZeile 5";
        file_put_contents($testFile, $content);

        try {
            $lines = File::tail($testFile, 3);
            $this->assertCount(3, $lines);
            // Prüfe, dass \r entfernt wurde
            $this->assertEquals('Zeile 3', $lines[0]);
            $this->assertEquals('Zeile 4', $lines[1]);
            $this->assertEquals('Zeile 5', $lines[2]);
        } finally {
            unlink($testFile);
        }
    }

    public function test_is_blocked_by_open_basedir_with_no_restriction(): void {
        // Wenn open_basedir nicht gesetzt ist, sollte nichts blockiert sein
        $currentOpenBasedir = ini_get('open_basedir');

        if (empty($currentOpenBasedir)) {
            $this->assertFalse(File::isBlockedByOpenBasedir('/some/random/path'));
            $this->assertFalse(File::isBlockedByOpenBasedir($this->testFile));
        } else {
            // Wenn open_basedir gesetzt ist, sollte die temp-Datei nicht blockiert sein
            $this->assertFalse(File::isBlockedByOpenBasedir($this->testFile));
        }
    }

    public function test_is_blocked_by_open_basedir_with_allowed_path(): void {
        // Test mit temporärem Verzeichnis, das normalerweise erlaubt ist
        $tempDir = sys_get_temp_dir();
        $testFile = tempnam($tempDir, 'basedir_test');

        try {
            // Die Datei im temp-Verzeichnis sollte nicht blockiert sein
            $this->assertFalse(File::isBlockedByOpenBasedir($testFile));
        } finally {
            unlink($testFile);
        }
    }

    public function test_exists_returns_false_for_blocked_path(): void {
        // Dieser Test prüft indirekt die open_basedir-Integration in exists()
        // Bei einem blockierten Pfad sollte exists() false zurückgeben
        $currentOpenBasedir = ini_get('open_basedir');

        if (!empty($currentOpenBasedir)) {
            // Prüfe einen Pfad, der wahrscheinlich nicht in open_basedir ist
            $blockedPath = '/root/somefile.txt';
            if (File::isBlockedByOpenBasedir($blockedPath)) {
                $this->assertFalse(File::exists($blockedPath));
            }
        }

        // Standard-Test: Nicht-existierende Datei
        $this->assertFalse(File::exists('/nonexistent/path/file.txt'));
    }

    public function test_sanitize_filename_basic(): void {
        $this->assertSame('mein-dokument', File::sanitizeFilename('mein-dokument.csv'));
        $this->assertSame('export-2026', File::sanitizeFilename('export-2026.xlsx'));
        $this->assertSame('Kontoauszug_2026', File::sanitizeFilename('Kontoauszug 2026.pdf'));
    }

    public function test_sanitize_filename_with_path(): void {
        $this->assertSame('report', File::sanitizeFilename('/home/user/downloads/report.csv'));
        // Windows-Pfade: basename() auf Linux erkennt \ nicht als Trenner
        $this->assertSame('data', File::sanitizeFilename('C:/Users/test/data.txt'));
    }

    public function test_sanitize_filename_special_chars(): void {
        $this->assertSame('Umsatz_bersicht_M_rz', File::sanitizeFilename('Umsatzübersicht März.csv'));
        $this->assertSame('file_with_many_spaces', File::sanitizeFilename('file   with   many   spaces.txt'));
    }

    public function test_sanitize_filename_empty(): void {
        $this->assertSame('', File::sanitizeFilename(''));
        $this->assertSame('', File::sanitizeFilename('.csv'));
    }

    public function test_sanitize_filename_max_length(): void {
        $longName = str_repeat('a', 300) . '.txt';
        $result = File::sanitizeFilename($longName);
        $this->assertLessThanOrEqual(255, strlen($result));

        $result10 = File::sanitizeFilename($longName, false, 10);
        $this->assertLessThanOrEqual(10, strlen($result10));
    }

    public function test_sanitize_filename_windows_reserved(): void {
        $this->assertSame('_CON', File::sanitizeFilename('CON.txt'));
        $this->assertSame('_NUL', File::sanitizeFilename('NUL.csv'));
    }

    public function test_sanitize_filename_keep_extension(): void {
        $this->assertSame('mein-dokument.csv', File::sanitizeFilename('mein-dokument.csv', true));
        $this->assertSame('Kontoauszug_2026.pdf', File::sanitizeFilename('Kontoauszug 2026.pdf', true));
        $this->assertSame('report.csv', File::sanitizeFilename('/home/user/downloads/report.csv', true));
    }

    public function test_sanitize_filename_keep_extension_special_chars(): void {
        // Extension mit Sonderzeichen wird bereinigt ($ wird entfernt)
        $this->assertSame('test.cv', File::sanitizeFilename('test.c$v', true));
        // Ohne Extension bleibt ohne Extension
        $this->assertSame('', File::sanitizeFilename('.csv', true));
    }

    public function test_sanitize_filename_keep_extension_windows_reserved(): void {
        $this->assertSame('_CON.txt', File::sanitizeFilename('CON.txt', true));
        $this->assertSame('_NUL.csv', File::sanitizeFilename('NUL.csv', true));
    }

    // ==================== readChunks ====================

    public function test_read_chunks_roundtrip(): void {
        $content = str_repeat('ABCDEFGH', 1000); // 8000 Bytes
        file_put_contents($this->testFile, $content);

        $chunks = iterator_to_array(File::readChunks($this->testFile, 256), false);

        $this->assertGreaterThan(1, count($chunks));
        $this->assertSame($content, implode('', $chunks));
    }

    public function test_read_chunks_strict_roundtrip(): void {
        $content = 'Strict gelesener Inhalt';
        file_put_contents($this->testFile, $content);

        $chunks = iterator_to_array(File::readChunks($this->testFile, 8, null, true), false);

        $this->assertSame($content, implode('', $chunks));
    }

    public function test_read_chunks_strict_unreadable_source_throws(): void {
        // fread === false ist von außen kaum erzwingbar — mindestens der
        // Guard-Pfad (nicht lesbare Quelle) muss im strict-Modus werfen.
        $generator = File::readChunks('/path/to/nonexistent/file', 8192, null, true);

        $this->expectException(FileNotFoundException::class);
        $generator->current(); // Generator läuft erst beim Konsum an
    }

    public function test_read_lines_as_utf8_streams_utf16_files_line_by_line(): void {
        $lines = ['Buchungsdatum;Partnername;Betrag', '02.01.2024;Müller & Söhne;-12,50', '', '03.01.2024;Straße "Zitat";1.000,00'];
        $utf8 = implode("\r\n", $lines) . "\r\n";
        $file = tempnam(sys_get_temp_dir(), 'utf16');
        file_put_contents($file, "\xFF\xFE" . mb_convert_encoding($utf8, 'UTF-16LE', 'UTF-8'));

        try {
            $this->assertSame($lines, iterator_to_array(File::readLinesAsUtf8($file), false));
            $this->assertSame(array_values(array_filter($lines, fn ($l) => $l !== '')), iterator_to_array(File::readLinesAsUtf8($file, true), false));
            $this->assertSame($lines[1], iterator_to_array(File::readLinesAsUtf8($file, false, 1, 2), false)[0]);
            // Gleicher Text wie ueber die Ganzdatei-Konvertierung
            $this->assertSame(preg_split('/\r\n/', rtrim(File::readAsUtf8($file), "\r\n")), iterator_to_array(File::readLinesAsUtf8($file), false));
        } finally {
            unlink($file);
        }
    }

    /**
     * Byte-basierte MIME-Erkennung: liefert für Inhalte ohne Datei im Dateisystem
     * dieselbe Semantik wie File::mimeType() (string|false).
     *
     * @return array<string, array{string, string}>
     */
    public static function mimeContentProvider(): array {
        return [
            'pdf' => ["%PDF-1.4\n1 0 obj\n<< >>\nendobj\ntrailer\n%%EOF\n", 'application/pdf'],
            'png' => ["\x89PNG\r\n\x1a\n" . str_repeat("\x00", 40), 'image/png'],
            'jpeg' => ["\xFF\xD8\xFF\xE0\x00\x10JFIF\x00" . str_repeat("\x00", 32), 'image/jpeg'],
            'gif87a' => ['GIF87a' . str_repeat("\x00", 32), 'image/gif'],
            'gif89a' => ['GIF89a' . str_repeat("\x00", 32), 'image/gif'],
            'zip' => ["PK\x03\x04" . str_repeat("\x00", 40), 'application/zip'],
            'xml' => ['<?xml version="1.0" encoding="UTF-8"?><Invoice><ID>1</ID></Invoice>', 'text/xml'],
            // finfo meldet XML ohne Prolog als text/plain — UBL/CII dürfen ohne Prolog kommen.
            'xml_without_declaration' => ['<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"><ID>1</ID></Invoice>', 'text/xml'],
            'xml_bom_whitespace' => ["\xEF\xBB\xBF\n  <rsm:CrossIndustryInvoice xmlns:rsm=\"urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100\"/>", 'text/xml'],
            'json' => ['{"invoice": {"id": 1, "lines": [1, 2, 3]}}', 'application/json'],
            'html' => ['<!DOCTYPE html><html><body>Text</body></html>', 'text/html'],
            'text' => ["Dies ist reiner Text mit Umlauten: äöüß.\nZeile zwei.\n", 'text/plain'],
            'binary' => ["\x00\x01\x02\x03\xFF\xFE" . str_repeat("\x7F", 20), 'application/octet-stream'],
        ];
    }

    #[DataProvider('mimeContentProvider')]
    public function test_mime_type_from_content(string $bytes, string $expected): void {
        $this->assertSame($expected, File::mimeTypeFromContent($bytes));
    }

    #[DataProvider('mimeContentProvider')]
    public function test_mime_type_from_magic_bytes_matches_without_finfo(string $bytes, string $expected): void {
        // Der Fallback muss ohne ext-fileinfo dieselben Typen liefern.
        $this->assertSame($expected, File::mimeTypeFromMagicBytes($bytes));
    }

    public function test_mime_type_from_content_detects_further_signatures(): void {
        $this->assertSame('image/tiff', File::mimeTypeFromMagicBytes("II*\x00" . str_repeat("\x00", 30)));
        $this->assertSame('image/tiff', File::mimeTypeFromMagicBytes("MM\x00*" . str_repeat("\x00", 30)));
        $this->assertSame('application/gzip', File::mimeTypeFromMagicBytes("\x1F\x8B\x08\x00" . str_repeat("\x00", 30)));
        $this->assertSame('image/webp', File::mimeTypeFromMagicBytes("RIFF\x24\x00\x00\x00WEBPVP8 " . str_repeat("\x00", 20)));
        $this->assertSame('image/bmp', File::mimeTypeFromMagicBytes("BM\x46\x00\x00\x00\x00\x00\x00\x00\x36\x00\x00\x00"));
        // "BM" allein ist Text, kein Bitmap-Header.
        $this->assertSame('text/plain', File::mimeTypeFromMagicBytes('BMW Rechnung 2026'));
    }

    public function test_mime_type_from_content_reads_xml_without_declaration(): void {
        $this->assertSame('text/xml', File::mimeTypeFromMagicBytes('<Invoice xmlns="urn:test"><ID>1</ID></Invoice>'));
        $this->assertSame('text/plain', File::mimeTypeFromMagicBytes('kein < Markup, nur Text'));
    }

    public function test_mime_type_from_content_returns_false_for_empty_input(): void {
        $this->assertFalse(File::mimeTypeFromContent(''));
        $this->assertFalse(File::mimeTypeFromMagicBytes(''));
    }

    public function test_mime_type_from_content_matches_file_based_detection(): void {
        $bytes = File::read($this->testFile);
        $this->assertSame(File::mimeType($this->testFile), File::mimeTypeFromContent($bytes));
    }

    public function test_mime_encoding_from_content(): void {
        $this->assertSame('us-ascii', File::mimeEncodingFromContent('Plain ASCII content'));
        $this->assertSame('utf-8', File::mimeEncodingFromContent('Umlaute äöü und ein Euro €'));
        $this->assertSame('binary', File::mimeEncodingFromContent("\x00\x01\x02\xFF\xFE\xFD" . str_repeat("\x80", 16)));
        $this->assertFalse(File::mimeEncodingFromContent(''));
    }

    public function test_mime_encoding_from_content_detects_bom(): void {
        $utf16 = "\xFF\xFE" . mb_convert_encoding('Hallo Welt', 'UTF-16LE', 'UTF-8');
        $this->assertSame('utf-16le', File::mimeEncodingFromContent($utf16));
        $this->assertSame('text/plain', File::mimeTypeFromMagicBytes($utf16));
    }
}
