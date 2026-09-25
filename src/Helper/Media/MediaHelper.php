<?php
/*
 * Created on   : Mon Jun 22 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MediaHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Media;

use CommonToolkit\Contracts\Abstracts\ConfiguredHelperAbstract;
use CommonToolkit\Helper\FileSystem\{File, Folder};
use CommonToolkit\Helper\Shell;

/**
 * Generischer Medien-Helper auf Basis von FFmpeg (Audio-/Video-Konvertierung,
 * Metadaten), OpenAI Whisper (Speech-to-Text) und Piper/eSpeak-NG (Text-to-Speech).
 *
 * Die Executables werden über media_executables.json aufgelöst (Pfad + Verfügbarkeit).
 * Die FFmpeg-/Whisper-Kommandozeilen sind von Natur aus dynamisch (Codec-Argumente
 * vor der Ausgabedatei, optionale Sprache) und werden deshalb hier escaped
 * zusammengesetzt – nicht als statische Argument-Templates.
 *
 * Deployment-spezifische Pfade (Whisper-/Piper-Modellverzeichnisse) werden als
 * Parameter übergeben; dieser Helper kennt keine anwendungsspezifischen Pfade.
 */
final class MediaHelper extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../../../config/media_executables.json';

    /** Invariante FFmpeg-Flags für Konvertierungen. */
    private const FFMPEG_BASE_FLAGS = ['-hide_banner', '-loglevel', 'error'];

    /**
     * Whisper-Ausgabeformate, die genau eine Datei erzeugen.
     *
     * @var list<string>
     */
    public const WHISPER_FORMATS = ['txt', 'vtt', 'srt', 'tsv', 'json'];

    public static function isFfmpegAvailable(): bool {
        return self::isExecutableAvailable('ffmpeg');
    }

    public static function isWhisperAvailable(): bool {
        return self::isExecutableAvailable('whisper');
    }

    /**
     * Prüft, ob ein konfiguriertes Medien-Executable verfügbar ist
     * (z.B. 'ffmpeg', 'whisper', 'piper-tts', 'espeak-ng').
     */
    public static function isAvailable(string $name): bool {
        return self::isExecutableAvailable($name);
    }

    /**
     * Konvertiert eine Mediendatei mit FFmpeg.
     *
     * Baut `ffmpeg -hide_banner -loglevel error -i <input> [-vn] <codecArgs…> <output>`.
     * Die Codec-Argumente (z.B. ['-c:a','libmp3lame','-q:a','2'] bzw. ['-c:v','libx264',…])
     * werden einzeln escaped; der Aufrufer ist für deren inhaltliche Gültigkeit zuständig.
     *
     * @param string[] $codecArgs Codec-/Filter-Argumente in der Reihenfolge für FFmpeg
     * @param list<string> $output Referenz: Shell-Ausgabe (stdout+stderr)
     * @param bool $stripVideo true entfernt den Videostream (-vn, Audio-Konvertierung);
     *                         false behält ihn (Video-Konvertierung)
     * @param float|null $timeout Sekunden bis zum Abbruch; null = unbegrenzt. Bei
     *                            Ueberschreitung false, $output endet mit "Zeitgrenze N s ueberschritten".
     * @return bool true bei Erfolg (Exit 0 und Ausgabedatei vorhanden)
     */
    public static function convert(string $input, string $outputFile, array $codecArgs, array &$output = [], int &$returnCode = 0, bool $stripVideo = true, ?float $timeout = null): bool {
        $path = self::getExecutablePath('ffmpeg');
        if ($path === null) {
            return self::logErrorAndReturn(false, 'FFmpeg ist nicht verfügbar (media_executables.json).');
        }

        $argv = [$path, ...self::FFMPEG_BASE_FLAGS, '-i', $input];
        if ($stripVideo) {
            $argv[] = '-vn';
        }
        foreach ($codecArgs as $arg) {
            $argv[] = (string) $arg;
        }
        $argv[] = $outputFile;

        if (!Shell::execute($argv, $output, $returnCode, $timeout)) {
            return self::logErrorAndReturn(false, 'FFmpeg-Konvertierung fehlgeschlagen: ' . implode("\n", $output));
        }

        return File::exists($outputFile);
    }

    /**
     * Führt eine FFmpeg-Probe aus und liefert die (stderr-)Ausgabe als String.
     *
     * @param float|null $timeout Sekunden bis zum Abbruch; null = unbegrenzt. Bei Ueberschreitung null.
     */
    private static function probe(string $file, ?float $timeout = null): ?string {
        if (!File::exists($file) || !self::isFfmpegAvailable()) {
            return null;
        }

        $argv = self::getConfiguredArgv('ffmpeg-info', ['[INPUT]' => $file]);
        if ($argv === null) {
            return null;
        }

        $output = [];
        $returnCode = 0;
        // FFmpeg gibt Metadaten auf stderr aus und beendet sich ohne Ausgabedatei mit Exit 1;
        // Shell::execute() sammelt stdout und stderr gemeinsam, der Exit-Code ist hier unerheblich.
        Shell::execute($argv, $output, $returnCode, $timeout);
        if ($returnCode === Shell::EXIT_TIMEOUT) {
            return self::logErrorAndReturn(null, 'FFmpeg-Probe abgebrochen: ' . Shell::timeoutMessage((float) $timeout));
        }

        return implode("\n", $output);
    }

    /**
     * Liest Audio-Metadaten via FFmpeg aus.
     *
     * @param float|null $timeout Sekunden bis zum Abbruch der Probe; null = unbegrenzt. Bei Ueberschreitung null.
     * @return array{duration?: float, sample_rate?: int, channels?: int, codec?: string, bitrate?: int}|null
     */
    public static function getAudioInfo(string $file, ?float $timeout = null): ?array {
        $outputStr = self::probe($file, $timeout);
        if ($outputStr === null) {
            return null;
        }

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
     * Liest Video-Metadaten via FFmpeg aus.
     *
     * @param float|null $timeout Sekunden bis zum Abbruch der Probe; null = unbegrenzt. Bei Ueberschreitung null.
     * @return array{duration?: float, width?: int, height?: int, codec?: string, fps?: float}|null
     */
    public static function getVideoInfo(string $file, ?float $timeout = null): ?array {
        $outputStr = self::probe($file, $timeout);
        if ($outputStr === null) {
            return null;
        }

        $info = [];

        if (preg_match('/Duration:\s*(\d+):(\d+):(\d+(?:\.\d+)?)/', $outputStr, $matches)) {
            $info['duration'] = (float) $matches[1] * 3600 + (float) $matches[2] * 60 + (float) $matches[3];
        }
        // Auflösung: \d{2,5}-Grenzen überspringen den Codec-FourCC-Hex (z.B. "0x31637661")
        // und treffen die echte Auflösung ("1920x1080").
        if (preg_match('/Video:.*?\b(\d{2,5})x(\d{2,5})\b/', $outputStr, $matches)) {
            $info['width'] = (int) $matches[1];
            $info['height'] = (int) $matches[2];
        }
        if (preg_match('/Stream.*Video:\s*(\w+)/', $outputStr, $matches)) {
            $info['codec'] = $matches[1];
        }
        if (preg_match('/(\d+(?:\.\d+)?)\s*fps/', $outputStr, $matches)) {
            $info['fps'] = (float) $matches[1];
        }

        return !empty($info) ? $info : null;
    }

    /**
     * Transkribiert eine Audiodatei mit OpenAI Whisper.
     *
     * Das Ausgabeformat ist wählbar, weil derselbe Lauf je nach Zweck etwas
     * anderes liefern muss: Fließtext zum Weiterverarbeiten, WebVTT/SRT als
     * Untertitelspur mit Zeitmarken. Whispers 'all' ist bewusst nicht
     * zugelassen – es schreibt mehrere Dateien, für die es keinen einzelnen
     * Rückgabewert gibt.
     *
     * @param string $modelDir Verzeichnis mit den Whisper-Modellen (deployment-spezifisch)
     * @param string $language Sprachcode oder 'auto' für automatische Erkennung
     * @param string $task 'transcribe' oder 'translate'
     * @param string $outputFormat Eines aus WHISPER_FORMATS ('txt', 'vtt', 'srt', 'tsv', 'json')
     * @param float|null $timeout Sekunden bis zum Abbruch; null = unbegrenzt. Bei Ueberschreitung null
     *                            mit Fehlerlog "Zeitgrenze N s ueberschritten".
     * @return string|null Inhalt der Ausgabedatei oder null bei Fehler
     */
    public static function transcribeWhisper(
        string $input,
        string $outputDir,
        string $model = 'base',
        string $modelDir = '',
        string $language = 'auto',
        string $task = 'transcribe',
        string $device = 'cpu',
        string $outputFormat = 'txt',
        ?float $timeout = null
    ): ?string {
        $outputFormat = strtolower(trim($outputFormat));
        if (!in_array($outputFormat, self::WHISPER_FORMATS, true)) {
            return self::logErrorAndReturn(null, sprintf(
                'Unbekanntes Whisper-Ausgabeformat "%s"; erlaubt: %s.',
                $outputFormat,
                implode(', ', self::WHISPER_FORMATS)
            ));
        }

        $path = self::getExecutablePath('whisper');
        if ($path === null) {
            return self::logErrorAndReturn(null, 'Whisper ist nicht verfügbar (media_executables.json).');
        }

        Folder::create($outputDir);

        $argv = [$path, $input, '--model', $model];
        if ($modelDir !== '') {
            $argv[] = '--model_dir';
            $argv[] = $modelDir;
        }
        array_push($argv, '--device', $device, '--output_dir', $outputDir, '--output_format', $outputFormat, '--task', $task);
        if ($language !== 'auto') {
            $argv[] = '--language';
            $argv[] = $language;
        }

        $output = [];
        $returnCode = 0;
        if (!Shell::execute($argv, $output, $returnCode, $timeout)) {
            return self::logErrorAndReturn(null, sprintf('Whisper-Transkription fehlgeschlagen (Code %d): %s', $returnCode, implode("\n", $output)));
        }

        // Whisper benennt die Ausgabe nach der Eingabedatei.
        $basename = pathinfo($input, PATHINFO_FILENAME);
        $outputFile = $outputDir . '/' . $basename . '.' . $outputFormat;
        if (!File::exists($outputFile)) {
            $files = glob($outputDir . '/*.' . $outputFormat) ?: [];
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
     * @param list<string> $output Referenz: Shell-Ausgabe (stdout+stderr).
     * @param float|null $timeout Sekunden bis zum Abbruch; null = unbegrenzt. Bei
     *                            Ueberschreitung false, $output endet mit "Zeitgrenze N s ueberschritten".
     * @return bool true bei Erfolg (Ausgabedatei vorhanden)
     */
    public static function synthesizePiper(string $text, string $outputWav, string $modelPath, array &$output = [], int &$returnCode = 0, ?float $timeout = null): bool {
        $path = self::getExecutablePath('piper-tts');
        if ($path === null) {
            return self::logErrorAndReturn(false, 'Piper ist nicht verfügbar (media_executables.json).');
        }

        // Piper liest den Text von stdin (frueher per `echo | piper`, jetzt direkt als Standardeingabe).
        $argv = [$path, '--model', $modelPath, '--output_file', $outputWav];

        if (!Shell::execute($argv, $output, $returnCode, $timeout, $text . "\n")) {
            return self::logErrorAndReturn(false, 'Piper TTS fehlgeschlagen: ' . implode("\n", $output));
        }

        return File::exists($outputWav);
    }

    /**
     * Synthetisiert Sprache mit eSpeak-NG (Fallback-TTS).
     *
     * @param string $textPath Pfad zur Textdatei mit dem zu sprechenden Inhalt
     * @param list<string> $output Referenz: Shell-Ausgabe (stdout+stderr).
     * @param float|null $timeout Sekunden bis zum Abbruch; null = unbegrenzt. Bei
     *                            Ueberschreitung false, $output endet mit "Zeitgrenze N s ueberschritten".
     * @return bool true bei Erfolg (Ausgabedatei vorhanden)
     */
    public static function synthesizeEspeak(string $textPath, string $outputWav, string $voice = 'de', string $speed = '150', array &$output = [], int &$returnCode = 0, ?float $timeout = null): bool {
        $path = self::getExecutablePath('espeak-ng');
        if ($path === null) {
            return self::logErrorAndReturn(false, 'eSpeak-NG ist nicht verfügbar (media_executables.json).');
        }

        $argv = [$path, '-v', $voice, '-s', $speed, '-w', $outputWav, '-f', $textPath];

        if (!Shell::execute($argv, $output, $returnCode, $timeout)) {
            return self::logErrorAndReturn(false, 'eSpeak-NG TTS fehlgeschlagen: ' . implode("\n", $output));
        }

        return File::exists($outputWav);
    }
}
