<?php
/*
 * Created on   : Mon Oct 05 2026
 * Author       : Daniel Joerg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MediaHelperProtocolLimitTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper\Media;

use CommonToolkit\Helper\Media\MediaHelper;
use CommonToolkit\Helper\{Platform, Shell};
use ReflectionMethod;
use Symfony\Component\Process\{ExecutableFinder, Process};
use Tests\Contracts\BaseTestCase;

/**
 * FFmpeg oeffnet fuer eine Eingabe nur lokale Dateien: jede Kommandozeile, die
 * eine vom Aufrufer gelieferte Datei liest, traegt `-protocol_whitelist file,pipe`
 * vor `-i`. Eine praeparierte Eingabe (Playlist, Container mit Verweisen) oder
 * eine Netzadresse als Eingabe loest damit keinen Abruf aus; Konvertierung,
 * Probe und Einzelbild-Ausgabe lokaler Dateien laufen unveraendert.
 */
class MediaHelperProtocolLimitTest extends BaseTestCase {
    private const CONFIG_FILE = __DIR__ . '/../../../config/media_executables.json';

    private string $dir;

    protected function setUp(): void {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/ctk-media-limit-' . bin2hex(random_bytes(4));
        mkdir($this->dir, 0700, true);
    }

    protected function tearDown(): void {
        $this->removeDir($this->dir);
        parent::tearDown();
    }

    public function test_probe_kommando_der_konfiguration_begrenzt_die_protokolle_vor_der_eingabe(): void {
        $arguments = $this->configuredArguments('ffmpeg-info');

        $limit = array_search('-protocol_whitelist', $arguments, true);
        $input = array_search('-i', $arguments, true);

        $this->assertIsInt($limit, 'ffmpeg-info braucht -protocol_whitelist');
        $this->assertIsInt($input);
        $this->assertLessThan($input, $limit, 'Die Grenze ist eine Eingabe-Option und steht vor -i.');
        $this->assertSame('file,pipe', $arguments[$limit + 1]);
        $this->assertSame('[INPUT]', $arguments[$input + 1]);
    }

    public function test_konfiguration_enthaelt_keine_shell_operatoren(): void {
        // Der CommandBuilder behandelt Argumente mit > < | als Shell-Operatoren (kein Escaping).
        $config = json_decode($this->readFile(self::CONFIG_FILE), true);
        $this->assertIsArray($config);

        foreach ($config['shellExecutables'] as $name => $executable) {
            foreach ($executable['arguments'] ?? [] as $argument) {
                $this->assertDoesNotMatchRegularExpression('/[<>|]/', (string) $argument, "Shell-Operator in $name");
            }
        }
    }

    public function test_kommando_ohne_grenze_bekommt_sie_vor_der_eingabe(): void {
        $withLimit = new ReflectionMethod(MediaHelper::class, 'withInputProtocolLimit');

        // Ueberschreibende Konfiguration ohne die Vorgabe: der Helper setzt sie vor -i.
        $this->assertSame(
            ['ffmpeg', '-hide_banner', '-protocol_whitelist', 'file,pipe', '-i', '/tmp/a.mp4'],
            $withLimit->invoke(null, ['ffmpeg', '-hide_banner', '-i', '/tmp/a.mp4']),
        );

        // Eine eigene Grenze der Konfiguration bleibt stehen.
        $own = ['ffmpeg', '-protocol_whitelist', 'file', '-i', '/tmp/a.mp4'];
        $this->assertSame($own, $withLimit->invoke(null, $own));

        // Ohne Eingabe gibt es nichts zu begrenzen.
        $this->assertSame(['ffmpeg', '-version'], $withLimit->invoke(null, ['ffmpeg', '-version']));
    }

    public function test_konvertierung_und_probe_starten_ffmpeg_mit_der_grenze(): void {
        if (Platform::isWindows()) {
            $this->markTestSkipped('Die Attrappe ist ein sh-Skript; der Test laeuft nur unter Unix.');
        }

        // Attrappe, die ihre Argumente protokolliert; in einem frischen Prozess loest
        // media_executables.json "ffmpeg" ueber PATH auf sie auf.
        $binDir = $this->dir . '/bin';
        mkdir($binDir, 0700);
        $log = $this->dir . '/argv.log';
        file_put_contents($binDir . '/ffmpeg', "#!/bin/sh\nfor a in \"\$@\"; do printf '%s\\n' \"\$a\" >> \"\$CTK_ARGV_LOG\"; done\nprintf '%s\\n' '--' >> \"\$CTK_ARGV_LOG\"\nexit 0\n");
        chmod($binDir . '/ffmpeg', 0700);

        $input = $this->dir . '/eingabe.mp4';
        file_put_contents($input, 'kein Medium');

        $script = sprintf(
            'require %s; $o = []; $c = 0; '
            . 'CommonToolkit\Helper\Media\MediaHelper::convert($argv[1], $argv[2] . "/a.mp3", ["-c:a", "libmp3lame"], $o, $c, true, 10.0); '
            . 'CommonToolkit\Helper\Media\MediaHelper::convert($argv[1], $argv[2] . "/f-%%04d.png", ["-vf", "fps=1"], $o, $c, false, 10.0); '
            . 'CommonToolkit\Helper\Media\MediaHelper::getAudioInfo($argv[1], 10.0); '
            . 'CommonToolkit\Helper\Media\MediaHelper::getVideoInfo($argv[1], 10.0);',
            var_export(dirname(__DIR__, 3) . '/vendor/autoload.php', true),
        );
        $process = new Process(
            [PHP_BINARY, '-r', $script, $input, $this->dir],
            null,
            ['PATH' => $binDir . PATH_SEPARATOR . (getenv('PATH') ?: '/usr/bin:/bin'), 'CTK_ARGV_LOG' => $log],
            null,
            60.0,
        );
        $process->run();

        $this->assertFileExists($log, 'Die Attrappe wurde nicht gestartet: ' . $process->getOutput() . $process->getErrorOutput());
        $calls = array_values(array_filter(array_map(
            static fn (string $call): array => array_values(array_filter(explode("\n", $call), static fn (string $arg): bool => $arg !== '')),
            explode("\n--\n", $this->readFile($log)),
        )));

        $this->assertCount(4, $calls, 'Zwei Konvertierungen und zwei Proben.');
        foreach ($calls as $index => $argv) {
            $inputOption = array_search('-i', $argv, true);
            $this->assertIsInt($inputOption, "Aufruf $index ohne -i: " . implode(' ', $argv));
            $this->assertSame($input, $argv[$inputOption + 1]);
            $this->assertGreaterThanOrEqual(2, $inputOption);
            $this->assertSame(
                ['-protocol_whitelist', 'file,pipe'],
                [$argv[$inputOption - 2], $argv[$inputOption - 1]],
                "Aufruf $index ohne Protokollgrenze unmittelbar vor -i: " . implode(' ', $argv),
            );
        }
    }

    public function test_lokale_dateien_laufen_mit_der_grenze_unveraendert(): void {
        $this->requireFfmpeg();

        // Synthetische Medien: Sinuston und Testbild mit Ton.
        $wav = $this->dir . '/ton.wav';
        $video = $this->dir . '/bild.mkv';
        $this->generate(['-f', 'lavfi', '-i', 'sine=frequency=440:duration=1', $wav]);
        $this->generate(['-f', 'lavfi', '-i', 'testsrc=duration=1:size=160x120:rate=5', '-f', 'lavfi', '-i', 'sine=frequency=440:duration=1', '-c:v', 'mpeg4', '-c:a', 'pcm_s16le', '-shortest', $video]);

        $audioInfo = MediaHelper::getAudioInfo($wav, 30.0);
        $this->assertIsArray($audioInfo);
        $this->assertSame(44100, $audioInfo['sample_rate'] ?? null);
        $this->assertSame('pcm_s16le', $audioInfo['codec'] ?? null);

        $videoInfo = MediaHelper::getVideoInfo($video, 30.0);
        $this->assertIsArray($videoInfo);
        $this->assertSame(160, $videoInfo['width'] ?? null);
        $this->assertSame(120, $videoInfo['height'] ?? null);

        $output = [];
        $returnCode = 0;
        $this->assertTrue(MediaHelper::convert($video, $this->dir . '/nur-ton.wav', ['-c:a', 'pcm_s16le'], $output, $returnCode, true, 30.0), implode("\n", $output));
        $this->assertGreaterThan(1000, filesize($this->dir . '/nur-ton.wav'));

        // Einzelbilder: Ausgabemuster statt einer Datei, convert() meldet deshalb false - es zaehlen die Bilder.
        MediaHelper::convert($video, $this->dir . '/bild-%04d.png', ['-vf', 'fps=2'], $output, $returnCode, false, 30.0);
        $this->assertSame(0, $returnCode, implode("\n", $output));
        $this->assertCount(2, glob($this->dir . '/bild-*.png') ?: []);
    }

    public function test_netzadresse_als_eingabe_loest_keinen_abruf_aus(): void {
        $this->requireFfmpeg();

        // Eigener Lauscher auf einem freien lokalen Port; er antwortet nie. Ohne die Grenze
        // verbindet sich FFmpeg und wartet bis zur Zeitgrenze, mit ihr bricht es sofort ab.
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        $this->assertIsResource($server, "Lauscher nicht anlegbar: $error");
        $address = (string) stream_socket_get_name($server, false);

        try {
            $output = [];
            $returnCode = 0;
            $started = microtime(true);
            $converted = MediaHelper::convert("http://$address/eingabe.mp4", $this->dir . '/netz.wav', ['-c:a', 'pcm_s16le'], $output, $returnCode, true, 5.0);

            $this->assertFalse($converted);
            $this->assertNotSame(Shell::EXIT_TIMEOUT, $returnCode, 'FFmpeg darf nicht auf eine Antwort gewartet haben.');
            $this->assertLessThan(4.0, microtime(true) - $started);
            $this->assertStringContainsString('not on whitelist', implode("\n", $output));
            $this->assertFalse(@stream_socket_accept($server, 0.0), 'Am Lauscher darf keine Verbindung ankommen.');
        } finally {
            fclose($server);
        }
    }

    public function test_playlist_mit_netzverweis_loest_keinen_abruf_aus(): void {
        $this->requireFfmpeg();

        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        $this->assertIsResource($server, "Lauscher nicht anlegbar: $error");
        $address = (string) stream_socket_get_name($server, false);

        try {
            $playlist = $this->dir . '/liste.m3u8';
            file_put_contents($playlist, "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-TARGETDURATION:2\n#EXT-X-MEDIA-SEQUENCE:0\n#EXTINF:2.0,\nhttp://$address/seg0.ts\n#EXT-X-ENDLIST\n");

            $started = microtime(true);
            $this->assertNull(MediaHelper::getVideoInfo($playlist, 5.0));
            $output = [];
            $returnCode = 0;
            $this->assertFalse(MediaHelper::convert($playlist, $this->dir . '/liste.wav', ['-c:a', 'pcm_s16le'], $output, $returnCode, true, 5.0));

            $this->assertLessThan(8.0, microtime(true) - $started);
            $this->assertFalse(@stream_socket_accept($server, 0.0), 'Am Lauscher darf keine Verbindung ankommen.');
        } finally {
            fclose($server);
        }
    }

    /** @return list<string> */
    private function configuredArguments(string $name): array {
        $config = json_decode($this->readFile(self::CONFIG_FILE), true);
        $this->assertIsArray($config);
        $arguments = $config['shellExecutables'][$name]['arguments'] ?? null;
        $this->assertIsArray($arguments, "$name fehlt in media_executables.json");

        return array_map(static fn (mixed $argument): string => (string) $argument, array_values($arguments));
    }

    private function requireFfmpeg(): void {
        if ((new ExecutableFinder)->find('ffmpeg') === null || !MediaHelper::isFfmpegAvailable()) {
            $this->markTestSkipped('FFmpeg ist nicht installiert.');
        }
    }

    /**
     * Erzeugt eine synthetische Mediendatei mit dem echten FFmpeg (lavfi-Quellen).
     *
     * @param list<string> $arguments
     */
    private function generate(array $arguments): void {
        $output = [];
        $returnCode = 0;
        $ok = Shell::execute(['ffmpeg', '-hide_banner', '-loglevel', 'error', '-y', ...$arguments], $output, $returnCode, 60.0);
        if (!$ok) {
            $this->markTestSkipped('FFmpeg kann die Testmedien nicht erzeugen: ' . implode("\n", $output));
        }
    }

    private function removeDir(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $entry) {
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
