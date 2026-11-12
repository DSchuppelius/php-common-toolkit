<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CompoundFileReaderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Parsers;

use CommonToolkit\Entities\CompoundFile\DirectoryEntry;
use CommonToolkit\Exceptions\Parsers\{DocumentLimitExceededException, MalformedDocumentException};
use CommonToolkit\Parsers\CompoundFileReader;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;
use Tests\Support\CompoundFileWriter;

/**
 * Compound File Binary (MS-CFB) lesen — mit synthetischen Dateien aus dem
 * {@see CompoundFileWriter}, auch beschädigten.
 */
class CompoundFileReaderTest extends BaseTestCase {
    /** Groß genug für mehr als 109 FAT-Sektoren (DIFAT-Kette) bei 512-Byte-Sektoren. */
    private const DIFAT_STREAM_BYTES = 7_500_000;

    /** @var array{0: CompoundFileWriter, 1: string}|null */
    private static ?array $difatFile = null;

    private static function sample(int $majorVersion = 3): CompoundFileWriter {
        return (new CompoundFileWriter($majorVersion))
            ->addStream('Ordner/klein', 'Hallo Welt')
            ->addStream('Ordner/mini-grenze', str_repeat('m', 4095))
            ->addStream('Ordner/Unterordner/groß', self::pattern(4096))
            ->addStream('Ordner/Unterordner/leer', '')
            ->addStream('mehrere-mini', self::pattern(200))
            ->addStream('regulär', self::pattern(5000))
            ->addStream('regulär-2', self::pattern(5000, 7))
            ->addStorage('Ärger');
    }

    private static function pattern(int $length, int $seed = 3): string {
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= chr(($i * $seed + intdiv($i, 251)) % 256);
        }

        return $out;
    }

    /** @return array{0: CompoundFileWriter, 1: string} */
    private static function difatFile(): array {
        if (self::$difatFile === null) {
            $writer = (new CompoundFileWriter(3))
                ->addStream('riesig', str_repeat(self::pattern(4096), intdiv(self::DIFAT_STREAM_BYTES, 4096)) . 'Ende')
                ->addStream('klein', 'neben dem Riesen');
            self::$difatFile = [$writer, $writer->build()];
        }

        return self::$difatFile;
    }

    // ------------------------------------------------------------------
    // Lesen
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{int, int}>
     */
    public static function versionProvider(): array {
        return [
            'Version 3, 512-Byte-Sektoren' => [3, 512],
            'Version 4, 4096-Byte-Sektoren' => [4, 4096],
        ];
    }

    #[DataProvider('versionProvider')]
    public function test_reads_mini_and_regular_streams(int $majorVersion, int $sectorSize): void {
        $reader = CompoundFileReader::fromString(self::sample($majorVersion)->build(), 'probe.cfb');

        $this->assertSame($majorVersion, $reader->getMajorVersion());
        $this->assertSame($sectorSize, $reader->getSectorSize());
        $this->assertTrue($reader->getRoot()->isRoot());

        $this->assertSame('Hallo Welt', $this->read($reader, 'Ordner/klein'));
        $this->assertSame(str_repeat('m', 4095), $this->read($reader, 'Ordner/mini-grenze'));
        $this->assertSame(self::pattern(4096), $this->read($reader, 'Ordner/Unterordner/groß'));
        $this->assertSame('', $this->read($reader, 'Ordner/Unterordner/leer'));
        $this->assertSame(self::pattern(200), $this->read($reader, 'mehrere-mini'));
        $this->assertSame(self::pattern(5000), $this->read($reader, 'regulär'));
        $this->assertSame(self::pattern(5000, 7), $this->read($reader, 'regulär-2'));
    }

    public function test_navigates_the_directory_tree(): void {
        $reader = CompoundFileReader::fromString(self::sample()->build());

        $names = array_map(static fn (DirectoryEntry $entry): string => $entry->getName(), $reader->getChildren($reader->getRoot()));
        sort($names);
        $this->assertSame(['Ordner', 'mehrere-mini', 'regulär', 'regulär-2', 'Ärger'], $names);

        $folder = $reader->getChild($reader->getRoot(), 'ORDNER');
        $this->assertNotNull($folder);
        $this->assertTrue($folder->isStorage());
        $this->assertFalse($folder->isStream());
        $this->assertSame(DirectoryEntry::TYPE_STORAGE, $folder->getType());
        $this->assertCount(3, $reader->getChildren($folder));

        $this->assertNotNull($reader->getChild($reader->getRoot(), 'ärger'), 'Groß-/Kleinschreibung auch jenseits von ASCII egal');
        $this->assertNull($reader->getChild($reader->getRoot(), 'fehlt'));
        $this->assertNull($reader->getEntryByPath('Ordner/fehlt/klein'));

        $stream = $reader->getEntryByPath('/ordner/unterordner/GROSS');
        $this->assertNull($stream, 'Einfache Großschreibung wie MS-CFB: ß bleibt ß, "GROSS" ist ein anderer Name');
        $stream = $reader->getEntryByPath('/Ordner/Unterordner/groß');
        $this->assertNotNull($stream);
        $this->assertSame(4096, $stream->getSize());
        $this->assertSame([], $reader->getChildren($stream));
    }

    public function test_reads_a_difat_chain_beyond_109_fat_sectors(): void {
        [$writer, $bytes] = self::difatFile();
        $this->assertNotEmpty($writer->layout()['difatSectors'], 'Testdatei muss DIFAT-Sektoren haben.');
        $this->assertGreaterThan(109, count($writer->layout()['fatSectors']));

        $reader = CompoundFileReader::fromString($bytes);
        $content = $this->read($reader, 'riesig');

        $this->assertSame(intdiv(self::DIFAT_STREAM_BYTES, 4096) * 4096 + 4, strlen($content));
        $this->assertStringEndsWith('Ende', $content);
        $this->assertSame(self::pattern(4096), substr($content, 4096 * 1000, 4096));
        $this->assertSame('neben dem Riesen', $this->read($reader, 'klein'));
    }

    public function test_reads_from_file(): void {
        $path = sys_get_temp_dir() . '/cfb-test-' . bin2hex(random_bytes(6)) . '.cfb';
        file_put_contents($path, self::sample()->build());
        try {
            $reader = CompoundFileReader::fromFile($path);
            $this->assertSame('Hallo Welt', $this->read($reader, 'Ordner/klein'));

            try {
                CompoundFileReader::fromFile($path, 1024);
                $this->fail('Grenze erwartet.');
            } catch (DocumentLimitExceededException $exception) {
                $this->assertSame(DocumentLimitExceededException::KIND_BYTES, $exception->getKind());
                $this->assertSame(basename($path), $exception->getDocument());
            }
        } finally {
            unlink($path);
        }
    }

    public function test_recognizes_the_signature(): void {
        $this->assertTrue(CompoundFileReader::isCompoundFile(self::sample()->build()));
        $this->assertFalse(CompoundFileReader::isCompoundFile("PK\x03\x04"));
        $this->assertFalse(CompoundFileReader::isCompoundFile(''));
    }

    // ------------------------------------------------------------------
    // Grenzen und Fehlbedienung
    // ------------------------------------------------------------------

    public function test_size_limits(): void {
        $bytes = self::sample()->build();

        try {
            CompoundFileReader::fromString($bytes, 'probe.cfb', 1000);
            $this->fail('Grenze erwartet.');
        } catch (DocumentLimitExceededException $exception) {
            $this->assertSame(1000, $exception->getLimit());
            $this->assertSame(strlen($bytes), $exception->getActual());
            $this->assertSame('probe.cfb', $exception->getDocument());
        }

        $reader = CompoundFileReader::fromString($bytes, null, null);
        $stream = $reader->getEntryByPath('regulär');
        $this->assertNotNull($stream);
        $this->expectException(DocumentLimitExceededException::class);
        $reader->readStream($stream, 4999);
    }

    public function test_non_positive_limit_is_refused(): void {
        $this->expectException(InvalidArgumentException::class);

        CompoundFileReader::fromString(self::sample()->build(), null, 0);
    }

    public function test_reading_a_storage_is_refused(): void {
        $reader = CompoundFileReader::fromString(self::sample()->build());

        $this->expectException(InvalidArgumentException::class);
        $reader->readStream($reader->getRoot());
    }

    public function test_unknown_entry_id_is_refused(): void {
        $reader = CompoundFileReader::fromString(self::sample()->build());

        $this->expectException(InvalidArgumentException::class);
        $reader->getEntry(9999);
    }

    // ------------------------------------------------------------------
    // Beschädigte Dateien
    // ------------------------------------------------------------------

    public function test_wrong_signature(): void {
        $bytes = self::sample()->build();

        $this->assertMalformed('PK' . substr($bytes, 2), 'Signatur');
        $this->assertMalformed("From: max@example.com\r\nSubject: keine CFB\r\n\r\n" . str_repeat('x', 600), 'Signatur');
    }

    public function test_too_short_for_a_header(): void {
        $this->assertMalformed(substr(self::sample()->build(), 0, 300), 'Kopf');
        $this->assertMalformed('', 'Kopf');
    }

    public function test_truncated_file(): void {
        $bytes = self::sample()->build();

        $this->assertMalformed(substr($bytes, 0, intdiv(strlen($bytes), 2)));
        $this->assertMalformed(substr($bytes, 0, strlen($bytes) - 100), 'abgeschnitten');
    }

    public function test_bad_header_fields(): void {
        $bytes = self::sample()->build();

        $this->assertMalformed(substr_replace($bytes, pack('v', 0xFEFF), 0x1C, 2), 'Byte-Reihenfolge');
        $this->assertMalformed(substr_replace($bytes, pack('v', 5), 0x1A, 2), 'Hauptversion');
        $this->assertMalformed(substr_replace($bytes, pack('v', 12), 0x1E, 2), 'Sektorgröße');
        $this->assertMalformed(substr_replace($bytes, pack('v', 7), 0x20, 2), 'Mini-Sektorgröße');
        $this->assertMalformed(substr_replace($bytes, pack('V', 8192), 0x38, 4), 'Mini-Stream');
        $this->assertMalformed(substr_replace($bytes, pack('V', 0xFFFFFF), 0x2C, 4), 'FAT-Sektoren');
        $this->assertMalformed(substr_replace($bytes, pack('V', 0), 0x2C, 4), 'FAT-Sektoren');
    }

    public function test_cycle_in_a_sector_chain(): void {
        $writer = self::sample();
        $bytes = $writer->build();
        $start = $writer->layout()['starts']['regulär'];

        $broken = $writer->patchFat($bytes, $start + 1, $start);

        $this->assertStreamMalformed($broken, 'regulär', 'Zyklus');
    }

    public function test_self_referencing_sector(): void {
        $writer = self::sample();
        $bytes = $writer->build();
        $start = $writer->layout()['starts']['regulär'];

        $this->assertStreamMalformed($writer->patchFat($bytes, $start, $start), 'regulär', 'Zyklus');
    }

    public function test_sector_chain_pointing_outside_the_file(): void {
        $writer = self::sample();
        $bytes = $writer->build();
        $start = $writer->layout()['starts']['regulär'];

        $this->assertStreamMalformed($writer->patchFat($bytes, $start, 999_999), 'regulär', 'Sektor 999999');
        $this->assertStreamMalformed($writer->patchFat($bytes, $start, CompoundFileWriter::FREE_SECTOR), 'regulär', 'FREESECT');
    }

    public function test_sector_chain_shorter_than_the_stream(): void {
        $writer = self::sample();
        $bytes = $writer->build();
        $start = $writer->layout()['starts']['regulär'];

        $this->assertStreamMalformed($writer->patchFat($bytes, $start, CompoundFileWriter::END_OF_CHAIN), 'regulär', 'endet nach 1 von 10');
    }

    public function test_cycle_in_the_mini_fat(): void {
        $writer = self::sample();
        $bytes = $writer->build();
        $start = $writer->layout()['starts']['mehrere-mini'];

        $this->assertStreamMalformed($writer->patchMiniFat($bytes, $start + 2, $start), 'mehrere-mini', 'Zyklus');
    }

    public function test_stream_size_beyond_the_file(): void {
        $writer = self::sample();
        $bytes = $writer->build();

        $this->assertStreamMalformed($writer->patchEntry($bytes, 'regulär', CompoundFileWriter::ENTRY_SIZE, 0x7FFFFFF0), 'regulär', 'die Datei hat nur');
    }

    public function test_stream_on_the_sectors_of_another_structure(): void {
        $writer = self::sample();
        $bytes = $writer->build();
        $layout = $writer->layout();

        // Zwei Streams auf denselben Sektoren: der zweite Zugriff fällt auf.
        $shared = $writer->patchEntry($bytes, 'regulär-2', CompoundFileWriter::ENTRY_START, $layout['starts']['regulär']);
        $reader = CompoundFileReader::fromString($shared);
        $this->read($reader, 'regulär');
        $this->assertStreamMalformed($shared, 'regulär-2', 'zwei Strukturen', $reader);

        // Ein Stream auf dem Verzeichnis
        $onDirectory = $writer->patchEntry($bytes, 'regulär', CompoundFileWriter::ENTRY_START, $layout['directorySectors'][0]);
        $this->assertStreamMalformed($onDirectory, 'regulär', 'zwei Strukturen');
    }

    public function test_mini_streams_sharing_mini_sectors(): void {
        $writer = self::sample();
        $bytes = $writer->build();
        $layout = $writer->layout();

        $shared = $writer->patchEntry($bytes, 'Ordner/klein', CompoundFileWriter::ENTRY_START, $layout['starts']['mehrere-mini']);
        $reader = CompoundFileReader::fromString($shared);
        $this->read($reader, 'mehrere-mini');
        $this->assertStreamMalformed($shared, 'Ordner/klein', 'Mini-Sektor', $reader);
    }

    public function test_cycle_in_the_directory_tree(): void {
        $writer = self::sample();
        $bytes = $writer->build();
        $folderId = $writer->layout()['ids']['Ordner'];

        // Ein Kind des Ordners verweist als Geschwister zurück auf den Ordner.
        $broken = $writer->patchEntry($bytes, 'Ordner/klein', CompoundFileWriter::ENTRY_LEFT, $folderId);
        $this->assertMalformed($broken, 'mehrfach verknüpft');

        // Der Ordner nennt die Wurzel als Kind.
        $this->assertMalformed($writer->patchEntry($bytes, 'Ordner', CompoundFileWriter::ENTRY_CHILD, 0));
    }

    public function test_shared_subtree_in_the_directory(): void {
        $writer = self::sample();
        $bytes = $writer->build();
        $subfolderId = $writer->layout()['ids']['Ordner/Unterordner'];

        $this->assertMalformed($writer->patchEntry($bytes, 'Ärger', CompoundFileWriter::ENTRY_CHILD, $subfolderId), 'mehrfach verknüpft');
    }

    public function test_directory_reference_outside_the_directory(): void {
        $writer = self::sample();
        $bytes = $writer->build();

        $this->assertMalformed($writer->patchEntry($bytes, 'Ordner', CompoundFileWriter::ENTRY_CHILD, 5000), 'Verzeichniseintrag 5000');
    }

    public function test_cycle_in_the_difat_chain(): void {
        [$writer, $bytes] = self::difatFile();
        $difatSector = $writer->layout()['difatSectors'][0];
        $sectorStart = ($difatSector + 1) * 512;

        // Mehr FAT-Sektoren ankündigen, als die eine DIFAT-Seite trägt, und ihren
        // Folgeverweis auf sich selbst richten.
        $broken = substr_replace($bytes, pack('V', 300), 0x2C, 4);
        $broken = substr_replace($broken, pack('V', 5), 0x48, 4);
        $broken = substr_replace($broken, pack('V', $difatSector), $sectorStart + 508, 4);

        $this->assertMalformed($broken, 'DIFAT: Zyklus');
    }

    public function test_exception_carries_the_document_name(): void {
        try {
            CompoundFileReader::fromString('kaputt', 'eingang.msg');
            $this->fail('Exception erwartet.');
        } catch (MalformedDocumentException $exception) {
            $this->assertSame('eingang.msg', $exception->getDocument());
            $this->assertStringContainsString('eingang.msg', $exception->getMessage());
        }
    }

    private function read(CompoundFileReader $reader, string $path): string {
        $entry = $reader->getEntryByPath($path);
        $this->assertNotNull($entry, "Eintrag $path fehlt");

        return $reader->readStream($entry);
    }

    private function assertMalformed(string $bytes, ?string $messagePart = null): void {
        try {
            CompoundFileReader::fromString($bytes);
            $this->fail('MalformedDocumentException erwartet.');
        } catch (MalformedDocumentException $exception) {
            if ($messagePart !== null) {
                $this->assertStringContainsString($messagePart, $exception->getMessage());
            }
        }
    }

    private function assertStreamMalformed(string $bytes, string $path, string $messagePart, ?CompoundFileReader $reader = null): void {
        $reader ??= CompoundFileReader::fromString($bytes);
        try {
            $this->read($reader, $path);
            $this->fail('MalformedDocumentException erwartet.');
        } catch (MalformedDocumentException $exception) {
            $this->assertStringContainsString($messagePart, $exception->getMessage());
        }
    }
}
