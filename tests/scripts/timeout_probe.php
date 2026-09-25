<?php
/**
 * Subprozess-Probe fuer die Zeitgrenzen von MediaHelper und OfficeHelper.
 *
 * Der Aufrufer legt Attrappen der Executables (ffmpeg, whisper, piper,
 * espeak-ng, libreoffice) in ein Verzeichnis und stellt es in PATH voran;
 * jede Attrappe schlaeft laenger als die uebergebene Zeitgrenze. Die
 * Executable-Konfiguration loest die Namen in diesem frischen Prozess ueber
 * PATH auf, deshalb laeuft die Probe getrennt vom PHPUnit-Prozess.
 *
 * Aufruf: php timeout_probe.php <fall> <zeitgrenze> <arbeitsverzeichnis>
 * Ausgabe: eine JSON-Zeile mit result, output, returnCode und duration.
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use CommonToolkit\Helper\Media\MediaHelper;
use CommonToolkit\Helper\Office\OfficeHelper;

$case = $argv[1] ?? '';
$timeout = (float) ($argv[2] ?? 0.5);
$workDir = rtrim($argv[3] ?? sys_get_temp_dir(), '/');

$output = [];
$returnCode = 0;
$started = microtime(true);

$result = match ($case) {
    'convert' => MediaHelper::convert(__FILE__, $workDir . '/out.mp3', ['-c:a', 'libmp3lame'], $output, $returnCode, true, $timeout),
    'getAudioInfo' => MediaHelper::getAudioInfo(__FILE__, $timeout),
    'getVideoInfo' => MediaHelper::getVideoInfo(__FILE__, $timeout),
    'transcribeWhisper' => MediaHelper::transcribeWhisper(__FILE__, $workDir . '/whisper', 'base', '', 'auto', 'transcribe', 'cpu', 'txt', $timeout),
    'synthesizePiper' => MediaHelper::synthesizePiper('Hallo', $workDir . '/piper.wav', $workDir . '/voice.onnx', $output, $returnCode, $timeout),
    'synthesizeEspeak' => MediaHelper::synthesizeEspeak(__FILE__, $workDir . '/espeak.wav', 'de', '150', $output, $returnCode, $timeout),
    'officeConvert' => OfficeHelper::convert(__FILE__, 'pdf', $workDir, $output, $returnCode, $timeout),
    default => throw new InvalidArgumentException("Unbekannter Probe-Fall: $case"),
};

echo json_encode([
    'result' => $result,
    'output' => $output,
    'returnCode' => $returnCode,
    'duration' => round(microtime(true) - $started, 2),
], JSON_THROW_ON_ERROR), "\n";
