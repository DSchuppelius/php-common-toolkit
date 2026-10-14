<?php
/*
 * Created on   : Wed Oct 14 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MediaHelperWhisperInputTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper\Media;

use CommonToolkit\Helper\FileSystem\Folder;
use CommonToolkit\Helper\Media\MediaHelper;
use ReflectionMethod;
use Tests\Contracts\BaseTestCase;

/**
 * Whisper bekommt nie die Rohdatei, sondern eine WAV, die das eigene ffmpeg
 * mit Protokollgrenze erzeugt hat - Whispers internes ffmpeg kennt diese
 * Grenze nicht (Scan 13.10.2026, S4-07).
 */
class MediaHelperWhisperInputTest extends BaseTestCase {
    private string $dir;

    protected function setUp(): void {
        parent::setUp();
        if (!MediaHelper::isFfmpegAvailable()) {
            $this->markTestSkipped('FFmpeg ist nicht installiert.');
        }
        $this->dir = sys_get_temp_dir() . '/whisper-input-' . bin2hex(random_bytes(4));
        Folder::create($this->dir);
    }

    protected function tearDown(): void {
        if (isset($this->dir) && is_dir($this->dir)) {
            foreach (glob($this->dir . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->dir);
        }
        parent::tearDown();
    }

    public function test_quelle_wird_zur_wav_mit_16_khz_mono(): void {
        $source = $this->dir . '/Ansage.ogg';
        $argv = ['ffmpeg', '-hide_banner', '-loglevel', 'error', '-f', 'lavfi', '-i', 'sine=frequency=440:duration=1', '-ac', '2', '-ar', '44100', $source];
        exec(implode(' ', array_map('escapeshellarg', $argv)), $out, $code);
        $this->assertSame(0, $code, 'Testton liess sich nicht erzeugen');

        $wav = $this->whisperInput($source, $this->dir);

        $this->assertNotNull($wav);
        $this->assertStringEndsWith('/Ansage.wav', $wav);
        $header = (string) file_get_contents($wav, false, null, 0, 44);
        $this->assertSame('RIFF', substr($header, 0, 4));
        $channels = unpack('v', substr($header, 22, 2));
        $rate = unpack('V', substr($header, 24, 4));
        $this->assertIsArray($channels);
        $this->assertIsArray($rate);
        $this->assertSame(1, $channels[1], 'mono');
        $this->assertSame(16000, $rate[1], '16 kHz');
    }

    /** Eine Playlist mit Netzverweis scheitert an der Protokollgrenze, statt nach aussen zu greifen. */
    public function test_playlist_mit_netzverweis_wird_nicht_gewandelt(): void {
        $playlist = $this->dir . '/liste.m3u8';
        file_put_contents($playlist, "#EXTM3U\n#EXT-X-TARGETDURATION:1\n#EXTINF:1,\nhttp://127.0.0.1:9/ton.wav\n#EXT-X-ENDLIST\n");

        $this->assertNull($this->whisperInput($playlist, $this->dir));
        $this->assertFileDoesNotExist($this->dir . '/liste.wav');
    }

    private function whisperInput(string $input, string $outputDir): ?string {
        $method = new ReflectionMethod(MediaHelper::class, 'whisperInput');

        return $method->invoke(null, $input, $outputDir);
    }
}
