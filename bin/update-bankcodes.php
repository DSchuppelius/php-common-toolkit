#!/usr/bin/env php
<?php
/*
 * Created on   : Mon Sep 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : update-bankcodes.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 *
 * Liest das ABBL-Register (Luxemburg) ein und schreibt es nach
 * data/iban-bankcode-bic.local.csv.
 *
 * Warum eigenes Werkzeug: Die übrigen Länder brauchen keines.
 *   AT (OeNB, CC BY 4.0) und BE (NBB, Weitergabe mit Quellenangabe) liegen in
 *   data/iban-bankcode-bic.csv bei; CH holt BankHelper selbst über die in
 *   config/helper.json hinterlegte SIX-URL — wie die Bundesbank-Dateien.
 *
 * Für LU braucht es zwei Schritte, die loadDataFile() nicht abbilden kann: Der
 * Downloadlink steht erst auf der Publikationsseite (mit wechselnder Versionsnummer),
 * und das Register ist eine XLSX-Datei. Die ABBL erlaubt die Nutzung zudem nur
 * "under the condition that it is not modified in any way" — die daraus erzeugte
 * Tabelle bleibt deshalb lokal (.gitignore) und wird nie ausgeliefert.
 *
 * Verwendung:
 *   php bin/update-bankcodes.php                      # Register selbst holen
 *   php bin/update-bankcodes.php --lu=<datei.xlsx>    # bereits vorliegendes Register
 *
 * Ohne die Tabelle verhält sich BankHelper::bicFromIBAN() für LU wie zuvor:
 * es liefert null statt einer geratenen Angabe.
 */

declare(strict_types=1);

/** Publikationsseite mit dem Downloadlink; die Dateinummer wechselt je Ausgabe. */
const ABBL_SEITE = 'https://www.abbl.lu/publications/abbl-luxembourg-register-of-iban-bic-codes/';

$ziel = dirname(__DIR__) . '/data/iban-bankcode-bic.local.csv';
$luDatei = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--lu=')) {
        $luDatei = substr($arg, 5);
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Verwendung: php bin/update-bankcodes.php --lu=<ABBL-Register.xlsx>\n";
        exit(0);
    }
}

/** @var array<string, string> "<Land>;<Code>" → Datenzeile */
$eintraege = [];

// --- LU: ABBL-Register, nur aus lokaler Datei (kein offener Abruf) -----------
$temporaer = null;
if ($luDatei === null) {
    fwrite(STDERR, 'LU: suche den Downloadlink auf ' . ABBL_SEITE . " …\n");
    $seite = @file_get_contents(ABBL_SEITE);
    if ($seite === false || preg_match('#https://[^"\']+RegisterofIBANBICCodes\d*\.xlsx#i', $seite, $m) !== 1) {
        fwrite(STDERR, "  FEHLER: kein Link gefunden — Register manuell laden und mit --lu=<datei.xlsx> übergeben\n");
        exit(1);
    }
    fwrite(STDERR, '  ' . basename($m[0]) . "\n");
    $inhalt = @file_get_contents($m[0]);
    if ($inhalt === false) {
        fwrite(STDERR, "  FEHLER: Download fehlgeschlagen\n");
        exit(1);
    }
    $temporaer = tempnam(sys_get_temp_dir(), 'abbl') . '.xlsx';
    file_put_contents($temporaer, $inhalt);
    $luDatei = $temporaer;
}

fwrite(STDERR, "LU: lese Register $luDatei …\n");
$zip = new ZipArchive;
if (!is_readable($luDatei) || $zip->open($luDatei) !== true) {
    fwrite(STDERR, "  FEHLER: Datei nicht lesbar\n");
    exit(1);
}

$texte = [];
preg_match_all('#<si>(.*?)</si>#s', (string) $zip->getFromName('xl/sharedStrings.xml'), $m);
foreach ($m[1] as $si) {
    preg_match_all('#<t[^>]*>(.*?)</t>#s', $si, $t);
    $texte[] = html_entity_decode(implode('', $t[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
}

preg_match_all('#<row[^>]*>(.*?)</row>#s', (string) $zip->getFromName('xl/worksheets/sheet1.xml'), $rows);
foreach ($rows[1] as $row) {
    preg_match_all('#<c\s+r="([A-Z]+)\d+"([^>]*)>(?:<v>([^<]*)</v>)?</c>#', $row, $cs, PREG_SET_ORDER);
    $zellen = [];
    foreach ($cs as $c) {
        $zellen[$c[1]] = str_contains($c[2], 't="s"') ? ($texte[(int) ($c[3] ?? 0)] ?? '') : ($c[3] ?? '');
    }
    $code = ltrim(trim((string) ($zellen['B'] ?? '')), '0');
    $bic = strtoupper(str_replace(' ', '', trim((string) ($zellen['C'] ?? ''))));
    $name = trim((string) ($zellen['A'] ?? ''));
    if ($code !== '' && preg_match('/^\d+$/', $code) === 1 && preg_match('/^[A-Z]{6}[A-Z0-9]{2,5}$/', $bic) === 1) {
        $eintraege['LU;' . $code] ??= "LU;$code;$bic;$name";
    }
}
fwrite(STDERR, '  ' . count($eintraege) . " Einträge\n");

if ($eintraege === []) {
    fwrite(STDERR, "Keine Einträge gefunden — bestehende Datei bleibt unverändert.\n");
    exit(1);
}

ksort($eintraege);
$kopf = "# Lokal erzeugt von bin/update-bankcodes.php am " . date('d.m.Y') . " — NICHT im Repository.\n"
    . "# LU: ABBL — Luxembourg Register of IBAN/BIC Codes (nur unverändert nutzbar).\n"
    . "# CH holt BankHelper selbst über die in config/helper.json hinterlegte SIX-URL.\n"
    . "# Format: Land;Bankcode;BIC;Bankname   — Bankcode ohne führende Nullen.\n";

file_put_contents($ziel, $kopf . implode("\n", array_values($eintraege)) . "\n");
if ($temporaer !== null) {
    @unlink($temporaer);
}
fwrite(STDERR, 'Geschrieben: ' . $ziel . ' (' . count($eintraege) . " Einträge)\n");
