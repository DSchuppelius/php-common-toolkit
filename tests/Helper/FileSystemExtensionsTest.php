<?php
/*
 * Created on   : Fri Sep 18 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileSystemExtensionsTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Builders\XLSXDocumentBuilder;
use CommonToolkit\Exceptions\Parsers\DocumentLimitExceededException;
use CommonToolkit\Generators\XLSX\XLSXGenerator;
use CommonToolkit\Helper\FileSystem\{File, Folder};
use CommonToolkit\Helper\FileSystem\FileTypes\ZipFile;
use CommonToolkit\Helper\Shell;
use CommonToolkit\Parsers\XLSXDocumentParser;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use InvalidArgumentException;
use RuntimeException;
use Tests\Contracts\BaseTestCase;
use ZipArchive;

/**
 * Erweiterungen aus dem Toolkit-Audit 2026-09 (workDiary): Rechte beim
 * Schreiben, Temp-Dateien, Pfad-Eingrenzung, argv-Prozesse, gemischte ZIPs.
 */
class FileSystemExtensionsTest extends BaseTestCase {
    private string $dir;

    protected function setUp(): void {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/fs_ext_test_' . uniqid();
        mkdir($this->dir, 0755, true);
    }

    protected function tearDown(): void {
        exec('rm -rf ' . escapeshellarg($this->dir));
        parent::tearDown();
    }

    // --- File::write ---------------------------------------------------------

    public function test_write_with_permissions_creates_the_file_restricted(): void {
        $file = $this->dir . '/key.env';

        File::write($file, "SECRET=1\n", permissions: 0600);

        $this->assertSame("SECRET=1\n", file_get_contents($file));
        $this->assertSame('0600', sprintf('%04o', fileperms($file) & 0777));
    }

    public function test_write_with_permissions_tightens_an_existing_file(): void {
        $file = $this->dir . '/key.env';
        file_put_contents($file, 'alt');
        chmod($file, 0644);

        File::write($file, 'neu', permissions: 0600, lock: true);

        $this->assertSame('neu', file_get_contents($file));
        $this->assertSame('0600', sprintf('%04o', fileperms($file) & 0777));
    }

    public function test_atomic_write_replaces_content_and_keeps_permissions(): void {
        $file = $this->dir . '/config.json';
        file_put_contents($file, '{"a":1}');
        chmod($file, 0640);

        File::write($file, '{"a":2}', atomic: true);

        $this->assertSame('{"a":2}', file_get_contents($file));
        $this->assertSame('0640', sprintf('%04o', fileperms($file) & 0777));
        $this->assertSame(['config.json'], array_values(array_diff(scandir($this->dir), ['.', '..'])));
    }

    public function test_atomic_write_with_permissions_on_a_new_file(): void {
        $file = $this->dir . '/new.key';

        File::write($file, 'x', permissions: 0600, atomic: true);

        $this->assertSame('0600', sprintf('%04o', fileperms($file) & 0777));
    }

    // --- Temp-Dateien --------------------------------------------------------

    public function test_create_temp_has_extension_content_and_private_permissions(): void {
        $path = File::createTemp('inhalt', 'test_', 'pdf', directory: $this->dir);

        $this->assertStringStartsWith($this->dir . DIRECTORY_SEPARATOR . 'test_', $path);
        $this->assertStringEndsWith('.pdf', $path);
        $this->assertSame('inhalt', file_get_contents($path));
        $this->assertSame('0600', sprintf('%04o', fileperms($path) & 0777));
    }

    public function test_with_temp_returns_callback_result_and_cleans_up(): void {
        $seen = null;
        $result = File::withTemp('abc', function (string $path) use (&$seen): int {
            $seen = $path;
            return strlen((string) file_get_contents($path));
        }, extension: 'txt', directory: $this->dir);

        $this->assertSame(3, $result);
        $this->assertFileDoesNotExist((string) $seen);
    }

    public function test_with_temp_cleans_up_when_the_callback_throws(): void {
        $seen = null;
        $this->expectException(RuntimeException::class);
        try {
            File::withTemp('abc', function (string $path) use (&$seen): void {
                $seen = $path;
                throw new RuntimeException('boom');
            }, directory: $this->dir);
        } finally {
            $this->assertFileDoesNotExist((string) $seen);
        }
    }

    // --- isFile / isLink / Namen / MIME -------------------------------------

    public function test_is_file_distinguishes_files_from_directories(): void {
        file_put_contents($this->dir . '/a.txt', 'x');

        $this->assertTrue(File::isFile($this->dir . '/a.txt'));
        $this->assertFalse(File::isFile($this->dir));
        $this->assertTrue(File::exists($this->dir), 'exists() bleibt für Ordner wahr');
        $this->assertFalse(File::isFile($this->dir . '/fehlt.txt'));
    }

    public function test_is_link(): void {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Symlinks brauchen unter Windows erhöhte Rechte.');
        }
        file_put_contents($this->dir . '/a.txt', 'x');
        symlink($this->dir . '/a.txt', $this->dir . '/link');
        symlink($this->dir . '/weg', $this->dir . '/dangling');

        $this->assertTrue(File::isLink($this->dir . '/link'));
        $this->assertTrue(File::isLink($this->dir . '/dangling'));
        $this->assertFalse(File::isLink($this->dir . '/a.txt'));
    }

    public function test_sanitize_display_name_keeps_unicode_and_strips_paths(): void {
        $this->assertSame('Übersicht März.pdf', File::sanitizeDisplayName('Übersicht März.pdf'));
        $this->assertSame('report.pdf', File::sanitizeDisplayName('C:\\Users\\x\\report.pdf'));
        $this->assertSame('passwd', File::sanitizeDisplayName('../../etc/passwd'));
        $this->assertSame('a_b.txt', File::sanitizeDisplayName("a\x01b.txt"));
        $this->assertSame('file', File::sanitizeDisplayName('..'));
        $this->assertSame('datei', File::sanitizeDisplayName('', fallback: 'datei'));
        $this->assertSame('äbc', File::sanitizeDisplayName('äbcdef', 3));
        $this->assertTrue(mb_check_encoding(File::sanitizeDisplayName("ok\xFF.txt"), 'UTF-8'));
    }

    public function test_mime_type_for_extension_reverses_the_extension_table(): void {
        $this->assertSame('application/pdf', File::mimeTypeForExtension('pdf'));
        $this->assertSame('image/jpeg', File::mimeTypeForExtension('.JPEG'));
        $this->assertSame('image/tiff', File::mimeTypeForExtension('tiff'));
        $this->assertSame('application/xml', File::mimeTypeForExtension('xml'));
        $this->assertNull(File::mimeTypeForExtension('unbekannt'));
        foreach (['pdf', 'png', 'csv', 'docx', 'zip'] as $ext) {
            $this->assertSame($ext, File::extensionForMimeType((string) File::mimeTypeForExtension($ext)));
        }
    }

    // --- Folder::resolveWithin ----------------------------------------------

    public function test_resolve_within_accepts_paths_inside_the_base(): void {
        mkdir($this->dir . '/base/sub', 0755, true);
        file_put_contents($this->dir . '/base/sub/a.txt', 'x');
        $base = realpath($this->dir . '/base');

        $this->assertSame($base . '/sub/a.txt', Folder::resolveWithin($this->dir . '/base', 'sub/a.txt'));
        $this->assertSame($base . '/sub/a.txt', Folder::resolveWithin($this->dir . '/base', $base . '/sub/a.txt'));
        $this->assertNull(Folder::resolveWithin($this->dir . '/base', '.'));
        $this->assertSame($base, Folder::resolveWithin($this->dir . '/base', '.', allowBase: true));
    }

    public function test_resolve_within_rejects_traversal_prefix_tricks_and_missing_paths(): void {
        mkdir($this->dir . '/base', 0755, true);
        mkdir($this->dir . '/base2', 0755, true);
        file_put_contents($this->dir . '/secret.txt', 'x');
        file_put_contents($this->dir . '/base2/b.txt', 'x');

        $this->assertNull(Folder::resolveWithin($this->dir . '/base', '../secret.txt'));
        $this->assertNull(Folder::resolveWithin($this->dir . '/base', '../base2/b.txt'));
        $this->assertNull(Folder::resolveWithin($this->dir . '/base', $this->dir . '/base2/b.txt'));
        $this->assertNull(Folder::resolveWithin($this->dir . '/base', 'fehlt.txt'));
        $this->assertNull(Folder::resolveWithin($this->dir . '/fehlt', 'a.txt'));
        $this->assertNull(Folder::resolveWithin($this->dir . '/base', ''));
    }

    public function test_resolve_within_rejects_symlink_escapes(): void {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Symlinks brauchen unter Windows erhöhte Rechte.');
        }
        mkdir($this->dir . '/base', 0755, true);
        file_put_contents($this->dir . '/secret.txt', 'x');
        symlink($this->dir . '/secret.txt', $this->dir . '/base/link.txt');

        $this->assertNull(Folder::resolveWithin($this->dir . '/base', 'link.txt'));
    }

    // --- Shell::run ----------------------------------------------------------

    public function test_run_captures_output_and_exit_code_without_a_shell(): void {
        $result = Shell::run([PHP_BINARY, '-r', 'echo $argv[1]; fwrite(STDERR, "err"); exit(3);', 'a b;c $(x)']);

        $this->assertSame(3, $result->exitCode);
        $this->assertSame('a b;c $(x)', $result->output, 'Argumente gehen ohne Shell unverändert durch');
        $this->assertSame('err', $result->errorOutput);
        $this->assertFalse($result->timedOut);
        $this->assertFalse($result->isSuccessful());
    }

    public function test_run_passes_environment_and_streams_output(): void {
        $chunks = [];
        $result = Shell::run(
            [PHP_BINARY, '-r', 'echo getenv("CTK_SECRET");'],
            env: ['CTK_SECRET' => 'geheim'],
            onOutput: function (string $buffer, bool $isError) use (&$chunks): void {
                $chunks[] = [$buffer, $isError];
            },
        );

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('', $result->output, 'gestreamte Ausgabe landet nicht im Ergebnis');
        $this->assertSame([['geheim', false]], $chunks);
    }

    public function test_run_reports_a_timeout_instead_of_throwing(): void {
        $result = Shell::run([PHP_BINARY, '-r', 'sleep(5);'], timeout: 0.5);

        $this->assertTrue($result->timedOut);
        $this->assertNull($result->exitCode);
        $this->assertFalse($result->isSuccessful());
        $this->assertLessThan(4.0, $result->duration);
    }

    public function test_run_requires_a_program(): void {
        $this->expectException(InvalidArgumentException::class);
        Shell::run([]);
    }

    // --- ZipFile -------------------------------------------------------------

    public function test_create_from_entries_mixes_files_and_strings(): void {
        file_put_contents($this->dir . '/doc.txt', 'aus Datei');
        $zipPath = $this->dir . '/out.zip';

        ZipFile::createFromEntries([
            ['archiveName' => 'files/doc.txt', 'path' => $this->dir . '/doc.txt'],
            ['archiveName' => 'manifest.json', 'content' => '{"ok":true}'],
        ], $zipPath);

        $this->assertSame(
            ['files/doc.txt' => 'aus Datei', 'manifest.json' => '{"ok":true}'],
            ZipFile::readEntriesFromFile($zipPath),
        );
    }

    public function test_create_from_entries_rejects_bad_entries_before_writing(): void {
        $zipPath = $this->dir . '/out.zip';
        foreach ([
            [['archiveName' => '../evil.txt', 'content' => 'x']],
            [['archiveName' => 'a.txt', 'content' => 'x', 'path' => '/etc/hostname']],
            [['archiveName' => 'a.txt']],
            [['archiveName' => 'a.txt', 'path' => $this->dir . '/fehlt.txt']],
            [],
        ] as $entries) {
            try {
                ZipFile::createFromEntries($entries, $zipPath);
                $this->fail('Exception erwartet für ' . json_encode($entries));
            } catch (InvalidArgumentException|FileNotFoundException) {
            }
            $this->assertFileDoesNotExist($zipPath);
        }
    }

    public function test_create_from_entries_encrypts_with_aes_when_a_password_is_given(): void {
        if (!ZipFile::supportsEncryption()) {
            $this->markTestSkipped('libzip ohne AES-Unterstützung.');
        }
        $zipPath = $this->dir . '/secret.zip';

        ZipFile::createFromEntries([['archiveName' => 'report.json', 'content' => '{"geheim":1}']], $zipPath, 'pa55');

        $zip = new ZipArchive;
        $zip->open($zipPath);
        $stat = $zip->statName('report.json');
        $this->assertIsArray($stat);
        $this->assertSame(ZipArchive::EM_AES_256, $stat['encryption_method']);
        $this->assertFalse($zip->getFromName('report.json'), 'ohne Passwort nicht lesbar');
        $zip->setPassword('pa55');
        $this->assertSame('{"geheim":1}', $zip->getFromName('report.json'));
        $zip->close();
    }

    public function test_read_entries_from_file_honours_limits_and_skip_filter(): void {
        $zipPath = $this->dir . '/in.zip';
        file_put_contents($zipPath, ZipFile::createFromStrings(['a.txt' => str_repeat('a', 10), 'b.exe' => 'MZ', 'c.txt' => str_repeat('c', 10)]));

        $this->assertSame(
            ['a.txt' => str_repeat('a', 10), 'c.txt' => str_repeat('c', 10)],
            ZipFile::readEntriesFromFile($zipPath, skipEntry: static fn (string $name): bool => str_ends_with($name, '.exe')),
        );

        $this->expectException(DocumentLimitExceededException::class);
        ZipFile::readEntriesFromFile($zipPath, maxBytes: 15);
    }

    public function test_read_entries_from_file_requires_an_existing_file(): void {
        $this->expectException(FileNotFoundException::class);
        ZipFile::readEntriesFromFile($this->dir . '/fehlt.zip');
    }

    // --- XLSXDocumentParser::fromString -------------------------------------

    public function test_xlsx_from_string_parses_like_from_file(): void {
        $doc = (new XLSXDocumentBuilder)
            ->sheet('Daten')
            ->setHeader(['Name', 'Menge'])
            ->addRow(['Schraube', 12])
            ->build();
        $path = $this->dir . '/in.xlsx';
        XLSXGenerator::toFile($doc, $path);

        $parsed = XLSXDocumentParser::fromString((string) file_get_contents($path));

        $sheet = $parsed->getFirstSheet();
        $this->assertNotNull($sheet);
        $this->assertSame('Daten', $sheet->getName());
        $this->assertSame(['Name', 'Menge'], $sheet->getHeaderNames());
        $this->assertCount(1, $sheet->getRows());
    }

    public function test_xlsx_from_string_rejects_empty_input(): void {
        $this->expectException(InvalidArgumentException::class);
        XLSXDocumentParser::fromString('');
    }
}
