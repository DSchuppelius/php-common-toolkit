<?php
/*
 * Created on   : Mon Jun 22 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AudioHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Media;

use CommonToolkit\Contracts\Abstracts\ConfiguredHelperAbstract;
use CommonToolkit\Helper\FileSystem\{File, Folder};
use CommonToolkit\Helper\Shell;

/**
 * Generischer Audio-Helper auf Basis von FFmpeg (Konvertierung, Metadaten),
 * OpenAI Whisper (Speech-to-Text) und Piper/eSpeak-NG (Text-to-Speech).
 *
 * Die Executables werden über audio_executables.json aufgelöst (Pfad + Verfügbarkeit).
 * Die FFmpeg-/Whisper-Kommandozeilen sind von Natur aus dynamisch (Codec-Argumente
 * vor der Ausgabedatei, optionale Sprache) und werden deshalb hier escaped
 * zusammengesetzt – nicht als statische Argument-Templates.
 *
 * Deployment-spezifische Pfade (Whisper-/Piper-Modellverzeichnisse) werden als
 * Parameter übergeben; dieser Helper kennt keine anwendungsspezifischen Pfade.
 */
final class AudioHelper extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../../../config/audio_executables.json';

    /** Invariante FFmpeg-Flags für Audio-Konvertierungen. */
    private const FFMPEG_BASE_FLAGS = ['-hide_banner', '-loglevel', 'error'];

    public static function isFfmpegAvailable(): bool {
        return self::isExecutableAvailable('ffmpeg');
    }

    public static function isWhisperAvailable(): bool {
        return self::isExecutableAvailable('whisper');
    }

    /**
     * Prüft, ob ein konfiguriertes Audio-Executable verfügbar ist
     * (z.B. 'ffmpeg', 'whisper', 'piper-tts', 'espeak-ng').
     */
    public static function isAvailable(string $name): bool {
        return self::isExecutableAvailable($name);
    }

    /**
     * Konvertiert eine Audiodatei mit FFmpeg.
     *
     * Baut `ffmpeg -hide_banner -loglevel error -i <input> -vn <codecArgs…> <output>`.
     * Die Codec-Argumente (z.B. ['-c:a','libmp3lame','-q:a','2','-ac','1']) werden
     * einzeln escaped; der Aufrufer ist für deren inhaltliche Gültigkeit zuständig.
     *
     * @param string[] $codecArgs Codec-/Filter-Argumente in der Reihenfolge für FFmpeg
     * @param array $output Referenz: Shell-Ausgabe (stdout+stderr)
     * @return bool true bei Erfolg (Exit 0 und Ausgabedatei vorhanden)
     */
    public static function convert(string $input, string $outputFile, array $codecArgs, array &$output = [], int &$returnCode = 0): bool {
        $path = self::getExecutablePath('ffmpeg');
        if ($path === null) {
            return self::logErrorAndReturn(false, 'FFmpeg ist nicht verfügbar (audio_executables.json).');
        }

        $parts = [escapeshellarg($path)];
        foreach (self::FFMPEG_BASE_FLAGS as $flag) {
            $parts[] = $flag;
        }
        $parts[] = '-i';
        $parts[] = escapeshellarg($input);
        $parts[] = '-vn';
        foreach ($codecArgs as $arg) {
            $parts[] = escapeshellarg((string) $arg);
        }
        $parts[] = escapeshellarg($outputFile);

        $command = implode(' ', $parts);

        if (!Shell::executeShellCommand($command, $output, $returnCode)) {
            return self::logErrorAndReturn(false, 'FFmpeg-Konvertierung fehlgeschlagen: ' . implode("\n", $output));
        }

        return File::exists($outputFile);
    }

    /**
     * Liest Audio-Metadaten via FFmpeg aus.
     *
     * @return array{duration?: float, sample_rate?: int, channels?: int, codec?: string, bitrate?: int}|null
     */
    public static function getAudioInfo(string $file): ?array {
        if (!File::exists($file) || !self::isFfmpegAvailable()) {
            return null;
        }

        $command = self::getConfiguredCommand('ffmpeg-audio-info', ['[INPUT]' => $file]);
        if ($command === null) {
            return null;
        }

        $output = [];
        $returnCode = 0;
        // FFmpeg gibt Metadaten auf stderr aus; Shell leitet stderr standardmäßig nach stdout um.
        Shell::executeShellCommand($command, $output, $returnCode);
        $outputStr = implode("\n", $output);

        $info = [];

        if (preg_match('/Duration:\s*(\d+):(\d+):(\d+(?:\.\d+)?)/', $outputStr, $matches)) {
            $info['duration'] = (float) $matches[1] * 3600 + (float) $matches[2] * 60 + (float) $matches[3];
        }
        if (preg_match('/(\d+)\s*Hz/', $outputStr, $matches)) {
            $info['sample_rate'] = (int) $matches[1];
        }
        if (preg_match('/Audio:.*?(\d+)\s*channels?/i', $outputStr, $matches)) {
            $info['channels'] = (int) $matches[1];
        } elseif (preg_match('/stereo/i', $outputStr)) {
            $info['channels'] = 2;
        } elseif (preg_match('/mono/i', $outputStr)) {
            $info['channels'] = 1;
        }
        if (preg_match('/Audio:\s*(\w+)/', $outputStr, $matches)) {
            $info['codec'] = $matches[1];
        }
        if (preg_match('/Audio:.*?(\d+)\s*kb\/s/i', $outputStr, $matches)) {
            $info['bitrate'] = (int) $matches[1] * 1000;
        } elseif (preg_match('/bitrate:\s*(\d+)\s*kb\/s/i', $outputStr, $matches)) {
            $info['bitrate'] = (int) $matches[1] * 1000;
        }

        return !empty($info) ? $info : null;
    }

    /**
     * Transkribiert eine Audiodatei mit OpenAI Whisper.
     *
     * @param string $modelDir Verzeichnis mit den Whisper-Modellen (deployment-spezifisch)
     * @param string $language Sprachcode oder 'auto' für automatische Erkennung
     * @param string $task 'transcribe' oder 'translate'
     * @return string|null Transkribierter Text oder null bei Fehler
     */
    public static function transcribeWhisper(
        string $input,
        string $outputDir,
        string $model = 'base',
        string $modelDir = '',
        string $language = 'auto',
        string $task = 'transcribe',
        string $device = 'cpu'
    ): ?string {
        $path = self::getExecutablePath('whisper');
        if ($path === null) {
            return self::logErrorAndReturn(null, 'Whisper ist nicht verfügbar (audio_executables.json).');
        }

        Folder::create($outputDir);

        $parts = [
            escapeshellarg($path),
            escapeshellarg($input),
            '--model', escapeshellarg($model),
        ];
        if ($modelDir !== '') {
            $parts[] = '--model_dir';
            $parts[] = escapeshellarg($modelDir);
        }
        $parts[] = '--device';
        $parts[] = escapeshellarg($device);
        $parts[] = '--output_dir';
        $parts[] = escapeshellarg($outputDir);
        $parts[] = '--output_format';
        $parts[] = 'txt';
        $parts[] = '--task';
        $parts[] = escapeshellarg($task);
        if ($language !== 'auto') {
            $parts[] = '--language';
            $parts[] = escapeshellarg($language);
        }

        $command = implode(' ', $parts);
        $output = [];
        $returnCode = 0;
        if (!Shell::executeShellCommand($command, $output, $returnCode)) {
            return self::logErrorAndReturn(null, sprintf('Whisper-Transkription fehlgeschlagen (Code %d): %s', $returnCode, implode("\n", $output)));
        }

        // Whisper benennt die Ausgabe nach der Eingabedatei.
        $basename = pathinfo($input, PATHINFO_FILENAME);
        $outputFile = $outputDir . '/' . $basename . '.txt';
        if (!File::exists($outputFile)) {
            $files = glob($outputDir . '/*.txt') ?: [];
            if (empty($files)) {
                return self::logErrorAndReturn(null, 'Whisper hat keine Ausgabedatei erstellt: ' . implode("\n", $output));
            }
            $outputFile = $files[0];
        }

        return trim(File::read($outputFile));
    }

    /**
     * Synthetisiert Sprache mit Piper (Neural TTS).
     *
     * @param string $modelPath Vollständiger Pfad zur .onnx-Stimmdatei (deployment-spezifisch)
     * @return bool true bei Erfolg (Ausgabedatei vorhanden)
     */
    public static function synthesizePiper(string $text, string $outputWav, string $modelPath, array &$output = [], int &$returnCode = 0): bool {
        $path = self::getExecutablePath('piper-tts');
        if ($path === null) {
            return self::logErrorAndReturn(false, 'Piper ist nicht verfügbar (audio_executables.json).');
        }

        // Piper liest den Text von stdin.
        $command = sprintf(
            'echo %s | %s --model %s --output_file %s',
            escapeshellarg($text),
            escapeshellarg($path),
            escapeshellarg($modelPath),
            escapeshellarg($outputWav)
        );

        if (!Shell::executeShellCommand($command, $output, $returnCode)) {
            return self::logErrorAndReturn(false, 'Piper TTS fehlgeschlagen: ' . implode("\n", $output));
        }

        return File::exists($outputWav);
    }

    /**
     * Synthetisiert Sprache mit eSpeak-NG (Fallback-TTS).
     *
     * @param string $textPath Pfad zur Textdatei mit dem zu sprechenden Inhalt
     * @return bool true bei Erfolg (Ausgabedatei vorhanden)
     */
    public static function synthesizeEspeak(string $textPath, string $outputWav, string $voice = 'de', string $speed = '150', array &$output = [], int &$returnCode = 0): bool {
        $path = self::getExecutablePath('espeak-ng');
        if ($path === null) {
            return self::logErrorAndReturn(false, 'eSpeak-NG ist nicht verfügbar (audio_executables.json).');
        }

        $command = sprintf(
            '%s -v %s -s %s -w %s -f %s',
            escapeshellarg($path),
            escapeshellarg($voice),
            escapeshellarg($speed),
            escapeshellarg($outputWav),
            escapeshellarg($textPath)
        );

        if (!Shell::executeShellCommand($command, $output, $returnCode)) {
            return self::logErrorAndReturn(false, 'eSpeak-NG TTS fehlgeschlagen: ' . implode("\n", $output));
        }

        return File::exists($outputWav);
    }
}
