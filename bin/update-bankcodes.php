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
 *   php bin/update-bankcodes.php --pruefen            # nur berichten, nichts schreiben
 *
 * Scheitert ein Schritt, bleibt eine vorhandene Tabelle unverändert und der
 * Rückgabewert ist 1. Ohne die Tabelle verhält sich BankHelper::bicFromIBAN()
 * für LU wie zuvor: es liefert null statt einer geratenen Angabe.
 */

declare(strict_types=1);

/** Publikationsseite mit dem Downloadlink; die Dateinummer wechselt je Ausgabe. */
const ABBL_SEITE = 'https://www.abbl.lu/publications/abbl-luxembourg-register-of-iban-bic-codes/';

/** Ohne Kennung antworten manche Vorschaltdienste gar nicht. */
const KENNUNG = 'Mozilla/5.0 (compatible; php-common-toolkit update-bankcodes)';

const ZEITLIMIT = 30;

$ziel = dirname(__DIR__) . '/data/iban-bankcode-bic.local.csv';
$luDatei = null;
$nurPruefen = false;
// Zieht die ABBL die Publikationsseite um, soll das ohne Codeänderung zu
// überbrücken sein — und macht den Fehlerpfad prüfbar.
$seiteUrl = ABBL_SEITE;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--lu=')) {
        $luDatei = substr($arg, 5);
    } elseif (str_starts_with($arg, '--seite=')) {
        $seiteUrl = substr($arg, 8);
    } elseif ($arg === '--pruefen' || $arg === '--dry-run') {
        $nurPruefen = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Verwendung:\n"
            . "  php bin/update-bankcodes.php                    Register selbst holen\n"
            . "  php bin/update-bankcodes.php --lu=<datei.xlsx>  bereits vorliegendes Register\n"
            . "  php bin/update-bankcodes.php --pruefen          nur berichten, nichts schreiben\n"
            . "  php bin/update-bankcodes.php --seite=<url>      abweichende Publikationsseite\n";
        exit(0);
    }
}

/**
 * Bricht mit einer Meldung ab — und sagt ausdrücklich, dass nichts verändert
 * wurde. Eine halb geschriebene Tabelle wäre schlimmer als eine veraltete.
 */
function abbruch(string $grund, string ...$hinweise): never {
    global $ziel;

    fwrite(STDERR, "  FEHLER: $grund\n");
    foreach ($hinweise as $hinweis) {
        fwrite(STDERR, "          $hinweis\n");
    }
    fwrite(STDERR, is_file($ziel)
        ? '          Bestehende Tabelle bleibt unverändert: ' . basename($ziel) . "\n"
        : "          Es gibt noch keine Tabelle; bicFromIBAN() liefert für LU weiterhin null.\n");

    exit(1);
}

/**
 * Holt eine URL — erst über den Stream-Wrapper, sonst über cURL.
 *
 * Beide Wege, weil auf Servern regelmäßig einer fehlt: `allow_url_fopen` ist
 * bei vielen Hostern abgeschaltet, cURL ist dafür fast immer da. Ohne diesen
 * Rückfall scheiterte der Abruf und die Meldung behauptete, es gäbe keinen
 * Downloadlink — der Unterschied ist für die Fehlersuche entscheidend.
 *
 * @return array{0: string|null, 1: string} Inhalt und, wenn null, der Grund
 */
function hole(string $url): array {
    $fehler = [];

    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return [null, "keine gültige URL: $url"];
    }

    if (ini_get('allow_url_fopen')) {
        $kontext = stream_context_create(['http' => [
            'header' => 'User-Agent: ' . KENNUNG . "\r\nAccept: */*\r\n",
            'timeout' => ZEITLIMIT,
            'follow_location' => 1,
            'max_redirects' => 5,
            'ignore_errors' => true,
        ]]);
        $inhalt = @file_get_contents($url, false, $kontext);
        $status = statusAus($http_response_header ?? []);

        if ($inhalt !== false && $inhalt !== '' && $status !== null && $status < 400) {
            return [$inhalt, ''];
        }
        $fehler[] = $inhalt === false
            ? 'Stream-Wrapper: kein Verbindungsaufbau'
            : ($status !== null && $status >= 400 ? "Stream-Wrapper: HTTP $status" : 'Stream-Wrapper: leere Antwort');
    } else {
        $fehler[] = 'allow_url_fopen ist abgeschaltet';
    }

    if (!extension_loaded('curl')) {
        $fehler[] = 'cURL nicht verfügbar';

        return [null, implode('; ', $fehler)];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => ZEITLIMIT,
        CURLOPT_USERAGENT => KENNUNG,
    ]);
    $inhalt = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curlFehler = curl_error($ch);
    curl_close($ch);

    if (!is_string($inhalt) || $inhalt === '') {
        $fehler[] = 'cURL: ' . ($curlFehler !== '' ? $curlFehler : 'leere Antwort');

        return [null, implode('; ', $fehler)];
    }
    if ($status >= 400) {
        $fehler[] = "cURL: HTTP $status";

        return [null, implode('; ', $fehler)];
    }

    return [$inhalt, ''];
}

/** @param list<string> $kopfzeilen */
function statusAus(array $kopfzeilen): ?int {
    $status = null;
    foreach ($kopfzeilen as $zeile) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $zeile, $m) === 1) {
            $status = (int) $m[1]; // letzte Zeile gewinnt: nach Umleitungen
        }
    }

    return $status;
}

/**
 * Sucht den Downloadlink in drei Stufen, von genau nach großzügig.
 *
 * Eine einzige Regel war zu wenig: Sobald die ABBL die Datei anders benennt,
 * steht das Werkzeug. Welche Stufe gegriffen hat, wird gemeldet — greift die
 * unterste, lohnt ein Blick, ob es noch die richtige Datei ist.
 *
 * @return array{0: string|null, 1: string, 2: int} Link, Stufe, Zahl aller XLSX-Links
 */
function findeLink(string $seite): array {
    $alle = [];
    preg_match_all('#https?://[^"\'\s<>]+\.xlsx#i', $seite, $m);
    foreach ($m[0] as $url) {
        $alle[html_entity_decode($url, ENT_QUOTES, 'UTF-8')] = true;
    }
    $alle = array_keys($alle);

    foreach ([
        'Name wie bisher' => '#RegisterofIBANBICCodes\d*\.xlsx$#i',
        'Name nennt IBAN und BIC' => '#iban.*bic|bic.*iban#i',
        'einzige XLSX-Datei der Seite' => null,
    ] as $stufe => $muster) {
        if ($muster === null) {
            if (count($alle) === 1) {
                return [$alle[0], $stufe, count($alle)];
            }
            continue;
        }
        foreach ($alle as $url) {
            if (preg_match($muster, $url) === 1) {
                return [$url, $stufe, count($alle)];
            }
        }
    }

    return [null, '', count($alle)];
}

/** @var array<string, string> "<Land>;<Code>" -> Datenzeile */
$eintraege = [];

// --- LU: ABBL-Register ------------------------------------------------------
$temporaer = null;
if ($luDatei === null) {
    fwrite(STDERR, 'LU: suche den Downloadlink auf ' . $seiteUrl . " …\n");
    [$seite, $grund] = hole($seiteUrl);
    if ($seite === null) {
        abbruch(
            "Publikationsseite nicht abrufbar ($grund)",
            'Das Register lässt sich im Browser laden und mit --lu=<datei.xlsx> übergeben.'
        );
    }

    [$link, $stufe, $anzahl] = findeLink($seite);
    if ($link === null) {
        abbruch(
            $anzahl === 0
                ? 'kein einziger XLSX-Link auf der Seite — Aufbau geändert oder Inhalt wird nachgeladen'
                : "kein passender von $anzahl XLSX-Links auf der Seite",
            'Register im Browser laden und mit --lu=<datei.xlsx> übergeben.'
        );
    }
    fwrite(STDERR, '  ' . basename(parse_url($link, PHP_URL_PATH) ?: $link) . " ($stufe)\n");

    [$inhalt, $grund] = hole($link);
    if ($inhalt === null) {
        abbruch("Download fehlgeschlagen ($grund)", 'Link: ' . $link);
    }

    $temporaer = tempnam(sys_get_temp_dir(), 'abbl') . '.xlsx';
    file_put_contents($temporaer, $inhalt);
    $luDatei = $temporaer;
}

fwrite(STDERR, "LU: lese Register $luDatei …\n");
if (!is_readable($luDatei)) {
    abbruch("Datei nicht lesbar: $luDatei");
}

$zip = new ZipArchive;
if ($zip->open($luDatei) !== true) {
    abbruch(
        'keine lesbare XLSX-Datei: ' . basename($luDatei),
        'Bei einem Download: möglicherweise eine Anmeldeseite statt der Tabelle.'
    );
}

$texte = [];
preg_match_all('#<si>(.*?)</si>#s', (string) $zip->getFromName('xl/sharedStrings.xml'), $m);
foreach ($m[1] as $si) {
    preg_match_all('#<t[^>]*>(.*?)</t>#s', $si, $t);
    $texte[] = html_entity_decode(implode('', $t[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
}

// Das Blatt heißt nicht zwingend sheet1.xml — das erste vorhandene nehmen.
$blatt = null;
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = (string) $zip->getNameIndex($i);
    if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name) === 1) {
        $blatt = $name;
        break;
    }
}
if ($blatt === null) {
    abbruch('kein Tabellenblatt in der XLSX-Datei gefunden');
}

/** @return array<string, string> Spaltenbuchstabe -> Wert */
$zellenAus = static function (string $row) use ($texte): array {
    preg_match_all('#<c\s+r="([A-Z]+)\d+"([^>]*)>(?:<v>([^<]*)</v>)?</c>#', $row, $cs, PREG_SET_ORDER);
    $zellen = [];
    foreach ($cs as $c) {
        $zellen[$c[1]] = str_contains($c[2], 't="s"') ? ($texte[(int) ($c[3] ?? 0)] ?? '') : ($c[3] ?? '');
    }

    return $zellen;
};

preg_match_all('#<row[^>]*>(.*?)</row>#s', (string) $zip->getFromName($blatt), $rows);

// Spalten über die Überschrift finden statt A/B/C anzunehmen: Wird das Register
// umsortiert, entstünde sonst stillschweigend eine leere oder falsche Tabelle.
$spalteName = 'A';
$spalteCode = 'B';
$spalteBic = 'C';
foreach ($rows[1] as $row) {
    $zellen = $zellenAus($row);
    $gefunden = [];
    foreach ($zellen as $spalte => $wert) {
        $wert = strtolower(trim($wert));
        if ($wert === '') {
            continue;
        }
        // Reihenfolge und `elseif` sind wesentlich: Die Überschrift heißt
        // "BICCode" — mit \bbic\b griff nichts, und auf /code/ geprüft hätte
        // sie die Codespalte an sich gerissen.
        if (preg_match('/bic|swift/', $wert) === 1) {
            $gefunden['bic'] ??= $spalte;
        } elseif (preg_match('/code|clearing|sort/', $wert) === 1) {
            $gefunden['code'] ??= $spalte;
        } elseif (preg_match('/name|institut|bank|établissement|etablissement/', $wert) === 1) {
            $gefunden['name'] ??= $spalte;
        }
    }
    if (isset($gefunden['bic'], $gefunden['code']) && $gefunden['bic'] !== $gefunden['code']) {
        $spalteBic = $gefunden['bic'];
        $spalteCode = $gefunden['code'];
        $spalteName = $gefunden['name'] ?? $spalteName;
        fwrite(STDERR, "  Spalten aus der Überschrift: Code=$spalteCode BIC=$spalteBic Name=$spalteName\n");
        break;
    }
}

foreach ($rows[1] as $row) {
    $zellen = $zellenAus($row);
    $code = ltrim(trim((string) ($zellen[$spalteCode] ?? '')), '0');
    $bic = strtoupper(str_replace(' ', '', trim((string) ($zellen[$spalteBic] ?? ''))));
    $name = trim((string) ($zellen[$spalteName] ?? ''));
    if ($code !== '' && preg_match('/^\d+$/', $code) === 1 && preg_match('/^[A-Z]{6}[A-Z0-9]{2,5}$/', $bic) === 1) {
        $eintraege['LU;' . $code] ??= "LU;$code;$bic;$name";
    }
}
fwrite(STDERR, '  ' . count($eintraege) . " Einträge\n");

if ($temporaer !== null) {
    @unlink($temporaer);
}

if ($eintraege === []) {
    abbruch(
        'keine verwertbaren Zeilen im Register',
        'Spaltenaufbau vermutlich geändert — Datei mit --lu= prüfen.'
    );
}

// Ein Register, das plötzlich einen Bruchteil enthält, ist verdächtiger als
// eines, das gar nichts enthält: Letzteres fällt auf, Ersteres nicht.
$bisher = is_file($ziel)
    ? count(array_filter(file($ziel) ?: [], static fn (string $z): bool => $z !== '' && $z[0] !== '#'))
    : 0;
if ($bisher > 0 && count($eintraege) < (int) ($bisher * 0.8)) {
    abbruch(
        sprintf('nur %d Einträge gegenüber bisher %d — das sieht nach einem Teilabzug aus', count($eintraege), $bisher),
        'Mit --lu= gegen die heruntergeladene Datei prüfen; danach ggf. die Datei direkt übergeben.'
    );
}

if ($nurPruefen) {
    fwrite(STDERR, sprintf("Prüflauf: %d Einträge gelesen, nichts geschrieben.\n", count($eintraege)));
    exit(0);
}

ksort($eintraege);
$kopf = '# Lokal erzeugt von bin/update-bankcodes.php am ' . date('d.m.Y') . " — NICHT im Repository.\n"
    . "# LU: ABBL — Luxembourg Register of IBAN/BIC Codes (nur unverändert nutzbar).\n"
    . "# CH holt BankHelper selbst über die in config/helper.json hinterlegte SIX-URL.\n"
    . "# Format: Land;Bankcode;BIC;Bankname   — Bankcode ohne führende Nullen.\n";

if (file_put_contents($ziel, $kopf . implode("\n", array_values($eintraege)) . "\n") === false) {
    abbruch('Zieldatei nicht schreibbar: ' . $ziel);
}
fwrite(STDERR, 'Geschrieben: ' . $ziel . ' (' . count($eintraege) . " Einträge)\n");
