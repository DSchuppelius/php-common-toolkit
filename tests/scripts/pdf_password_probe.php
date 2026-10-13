<?php
/**
 * Subprozess-Probe fuer die Passwortwege von PdfFile.
 *
 * Der Aufrufer legt Attrappen von qpdf und pdfinfo in ein Verzeichnis und
 * stellt es in PATH voran; sie protokollieren ihre Argumente (und den Inhalt
 * von @Argumentdateien) in die Datei aus CKT_QPDF_LOG. Die
 * Executable-Konfiguration loest die Namen in diesem frischen Prozess ueber
 * PATH auf, deshalb laeuft die Probe getrennt vom PHPUnit-Prozess.
 *
 * Aufruf: php pdf_password_probe.php <fall> <eingabe> <ausgabe> <passwort> [besitzerpasswort]
 * Ausgabe: eine JSON-Zeile mit result und metadata.
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use CommonToolkit\Helper\FileSystem\FileTypes\PdfFile;

// Ab PHP 8.5 ist $argv nicht in jeder Konfiguration gesetzt (register_argc_argv)
$args = is_array($_SERVER['argv'] ?? null) ? array_values($_SERVER['argv']) : [];
[, $case, $input, $outputFile, $password] = $args + [null, '', '', '', ''];
$owner = $args[5] ?? null;

$metadata = $case === 'metadata' ? PdfFile::getMetaData($input, $password) : null;
$result = match ($case) {
    'decrypt' => PdfFile::decrypt($input, $outputFile, $password),
    'encrypt' => PdfFile::encrypt($input, $outputFile, $password, $owner),
    'metadata' => true,
    default => throw new InvalidArgumentException("Unbekannter Probe-Fall: $case"),
};

echo json_encode(['result' => $result, 'metadata' => $metadata], JSON_THROW_ON_ERROR), "\n";
