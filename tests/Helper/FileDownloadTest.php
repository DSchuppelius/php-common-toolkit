<?php
/*
 * Created on   : Sat Jun 14 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileDownloadTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Helper\FileSystem\File;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für File::download().
 *
 * Diese Tests laufen vollständig offline: statt echter Netzwerkaufrufe wird der
 * 'http'-Stream-Wrapper temporär durch einen In-Memory-Wrapper ersetzt. Dadurch
 * geht KEIN echter Socket auf (funktioniert auch unter voller Netzwerk-Isolation).
 */
class FileDownloadTest extends BaseTestCase {
    private string $tmpDir;

    protected function setUp(): void {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/file-download-test-' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void {
        // Wrapper sicher zurücksetzen, falls ein Test ihn nicht selbst restauriert hat.
        @stream_wrapper_restore('http');

        foreach (glob($this->tmpDir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->tmpDir);
    }

    private function listDir(): array {
        $entries = scandir($this->tmpDir) ?: [];
        return array_values(array_filter($entries, fn($e) => $e !== '.' && $e !== '..'));
    }

    public function testSuccessfulDownloadWritesDestination(): void {
        InMemoryHttpStreamWrapper::$payload = "Zeile A\nZeile B\n";
        InMemoryHttpStreamWrapper::$failAfter = null;

        stream_wrapper_unregister('http');
        stream_wrapper_register('http', InMemoryHttpStreamWrapper::class);
        try {
            $destination = $this->tmpDir . '/result.txt';
            $bytes = File::download('http://example.test/data', $destination);
        } finally {
            stream_wrapper_restore('http');
        }

        $this->assertSame(strlen("Zeile A\nZeile B\n"), $bytes, 'Rückgabe muss Byte-Anzahl sein');
        $this->assertFileExists($destination);
        $this->assertSame("Zeile A\nZeile B\n", file_get_contents($destination));

        // Keine Temp-Reste nach Erfolg.
        $this->assertSame(['result.txt'], $this->listDir(), 'Es darf nur die Zieldatei verbleiben, keine .tmp-Datei');
    }

    public function testSuccessfulDownloadReplacesExistingDestination(): void {
        $destination = $this->tmpDir . '/result.txt';
        file_put_contents($destination, 'ALTER INHALT');

        InMemoryHttpStreamWrapper::$payload = "NEUER INHALT";
        InMemoryHttpStreamWrapper::$failAfter = null;

        stream_wrapper_unregister('http');
        stream_wrapper_register('http', InMemoryHttpStreamWrapper::class);
        try {
            $bytes = File::download('http://example.test/data', $destination);
        } finally {
            stream_wrapper_restore('http');
        }

        $this->assertSame(strlen('NEUER INHALT'), $bytes);
        $this->assertSame('NEUER INHALT', file_get_contents($destination));
        $this->assertSame(['result.txt'], $this->listDir(), 'Keine Temp-Reste nach Erfolg');
    }

    /**
     * KERN-REGRESSIONSTEST: Eine bereits vorhandene, gültige Zieldatei muss bei einem
     * abbrechenden Download UNVERÄNDERT bleiben und es dürfen keine .tmp-Reste übrig sein.
     */
    public function testAbortedDownloadLeavesExistingDestinationUntouched(): void {
        $destination = $this->tmpDir . '/result.txt';
        $original = "GUELTIGER BESTAND\nzeile2\n";
        file_put_contents($destination, $original);

        // Quelle öffnet erfolgreich, bricht aber nach einem Chunk mit einem Lesefehler ab.
        InMemoryHttpStreamWrapper::$payload = str_repeat('X', 100000);
        InMemoryHttpStreamWrapper::$failAfter = 1; // nach dem 1. read schlägt der nächste fehl

        stream_wrapper_unregister('http');
        stream_wrapper_register('http', InMemoryHttpStreamWrapper::class);
        try {
            $result = File::download('http://example.test/broken', $destination);
        } finally {
            stream_wrapper_restore('http');
        }

        $this->assertFalse($result, 'Abbrechender Download muss false liefern');
        $this->assertFileExists($destination, 'Zieldatei muss weiterhin existieren');
        $this->assertSame($original, file_get_contents($destination), 'Bestehende Zieldatei muss UNVERÄNDERT bleiben');
        $this->assertSame(['result.txt'], $this->listDir(), 'Keine .tmp-Reste im Verzeichnis');
    }

    public function testUnreachableSourceLeavesExistingDestinationUntouched(): void {
        $destination = $this->tmpDir . '/result.txt';
        $original = "GUELTIGER BESTAND";
        file_put_contents($destination, $original);

        // Quelle lässt sich gar nicht erst öffnen.
        InMemoryHttpStreamWrapper::$payload = '';
        InMemoryHttpStreamWrapper::$failOpen = true;

        stream_wrapper_unregister('http');
        stream_wrapper_register('http', InMemoryHttpStreamWrapper::class);
        try {
            $result = @File::download('http://example.test/unreachable', $destination);
        } finally {
            stream_wrapper_restore('http');
            InMemoryHttpStreamWrapper::$failOpen = false;
        }

        $this->assertFalse($result);
        $this->assertSame($original, file_get_contents($destination), 'Zieldatei bleibt unangetastet');
        $this->assertSame(['result.txt'], $this->listDir(), 'Keine .tmp-Reste im Verzeichnis');
    }

    public function testInvalidUrlSchemeReturnsFalse(): void {
        $destination = $this->tmpDir . '/result.txt';
        $this->assertFalse(File::download('file:///etc/hosts', $destination));
        $this->assertFileDoesNotExist($destination);
    }
}

/**
 * In-Memory-Ersatz für den 'http'-Stream-Wrapper. Liefert eine konfigurierbare
 * Nutzlast und kann optional das Öffnen oder einen Lesevorgang fehlschlagen lassen.
 */
class InMemoryHttpStreamWrapper {
    /** @var resource|null */
    public $context;

    public static string $payload = '';
    public static bool $failOpen = false;
    /** @var int|null Nach wievielen erfolgreichen Reads der nächste Read fehlschlägt (null = nie). */
    public static ?int $failAfter = null;

    private int $position = 0;
    private int $reads = 0;

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool {
        if (self::$failOpen) {
            return false;
        }
        $this->position = 0;
        $this->reads = 0;
        return true;
    }

    public function stream_read(int $count) {
        if (self::$failAfter !== null && $this->reads >= self::$failAfter) {
            return false; // simuliert abbrechende Quelle
        }
        $this->reads++;
        $chunk = substr(self::$payload, $this->position, $count);
        $this->position += strlen($chunk);
        return $chunk;
    }

    public function stream_eof(): bool {
        return $this->position >= strlen(self::$payload);
    }

    public function stream_stat() {
        return [];
    }

    public function stream_close(): void {
    }
}
