<?php
/*
 * Created on   : Tue Oct 13 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PdfFilePasswordTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use Symfony\Component\Process\Process;
use Tests\Contracts\BaseTestCase;

/**
 * PDF-Passwoerter stehen nie auf einer Befehlszeile (Prozessliste), sondern
 * nur in einer Argumentdatei mit Rechten 0600, die danach wieder verschwindet.
 * Geprueft mit Attrappen von qpdf und pdfinfo, die ihre Argumente, die
 * Befehlszeile ihres Elternprozesses und den Inhalt der Argumentdatei
 * protokollieren.
 */
final class PdfFilePasswordTest extends BaseTestCase {
    private const PASSWORD = 'Geh eim;$(id)';
    private const OWNER = 'Besitzer pw';

    private string $workDir = '';

    protected function setUp(): void {
        parent::setUp();
        if (PHP_OS_FAMILY !== 'Linux') {
            $this->markTestSkipped('Die Attrappen sind sh-Skripte und lesen /proc.');
        }

        $this->workDir = sys_get_temp_dir() . '/ctk-pdfpw-' . bin2hex(random_bytes(4));
        mkdir($this->workDir . '/bin', 0700, true);
        mkdir($this->workDir . '/tmp', 0700);

        $qpdf = <<<'SH'
            #!/bin/sh
            log="$CKT_QPDF_LOG"
            printf 'PARENT %s\n' "$(tr '\0' ' ' < /proc/$PPID/cmdline)" >> "$log"
            for a in "$@"; do
              printf 'ARG %s\n' "$a" >> "$log"
              case "$a" in
                @*) f=${a#@}
                    printf 'MODE %s\n' "$(stat -c %a "$f")" >> "$log"
                    while IFS= read -r l || [ -n "$l" ]; do printf 'FILE %s\n' "$l" >> "$log"; done < "$f";;
              esac
            done
            for a in "$@"; do vorletzt=$letzt; letzt=$a; done
            sed 's/GESCHUETZT/OFFEN/' "$vorletzt" > "$letzt"
            SH;
        $pdfinfo = <<<'SH'
            #!/bin/sh
            for a in "$@"; do printf 'PDFINFO %s\n' "$a" >> "$CKT_QPDF_LOG"; letzt=$a; done
            if grep -q GESCHUETZT "$letzt"; then echo 'Command Line Error: Incorrect password'; exit 1; fi
            printf 'Title:          Probe\nPages:          2\nEncrypted:      no\n'
            SH;
        file_put_contents($this->workDir . '/bin/qpdf', $qpdf . "\n");
        file_put_contents($this->workDir . '/bin/pdfinfo', $pdfinfo . "\n");
        chmod($this->workDir . '/bin/qpdf', 0700);
        chmod($this->workDir . '/bin/pdfinfo', 0700);

        file_put_contents($this->workDir . '/geschuetzt.pdf', "%PDF-1.4\n% GESCHUETZT\n");
    }

    protected function tearDown(): void {
        if ($this->workDir !== '') {
            (new Process(['rm', '-rf', $this->workDir]))->run();
        }
        parent::tearDown();
    }

    public function test_decrypt_gibt_das_passwort_nur_ueber_die_argumentdatei(): void {
        $probe = $this->runProbe('decrypt', self::PASSWORD);

        $this->assertTrue($probe['result']);
        $this->assertPasswordNeverOnCommandLine($probe['log'], self::PASSWORD);
        $this->assertContains('FILE --password=' . self::PASSWORD, $probe['log']);
        $this->assertContains('MODE 600', $probe['log']);
        $this->assertContains('ARG --decrypt', $probe['log']);
        $this->assertSame([], $probe['tempLeft'], 'Argumentdatei und Temp-Ordner muessen wieder weg sein.');
    }

    public function test_encrypt_gibt_beide_passwoerter_nur_ueber_die_argumentdatei(): void {
        $probe = $this->runProbe('encrypt', self::PASSWORD, self::OWNER);

        $this->assertTrue($probe['result']);
        $this->assertPasswordNeverOnCommandLine($probe['log'], self::PASSWORD);
        $this->assertPasswordNeverOnCommandLine($probe['log'], self::OWNER);
        $file = array_values(array_filter($probe['log'], static fn (string $line): bool => str_starts_with($line, 'FILE ')));
        $this->assertSame(['FILE --encrypt', 'FILE ' . self::PASSWORD, 'FILE ' . self::OWNER, 'FILE 256', 'FILE --'], $file);
        $this->assertContains('MODE 600', $probe['log']);
        $this->assertSame([], $probe['tempLeft']);
    }

    public function test_metadaten_geschuetzter_datei_ohne_passwort_auf_der_befehlszeile(): void {
        $probe = $this->runProbe('metadata', self::PASSWORD);

        $this->assertTrue($probe['result']);
        $this->assertSame('Probe', $probe['metadata']['Title'] ?? null);
        $this->assertSame('yes', $probe['metadata']['Encrypted'] ?? null, 'Die Kopie ist entschluesselt, die Quelle nicht.');
        $this->assertPasswordNeverOnCommandLine($probe['log'], self::PASSWORD);
        $this->assertNotContains('PDFINFO -opw', $probe['log']);
        $this->assertSame([], $probe['tempLeft'], 'Die entschluesselte Kopie darf nicht liegen bleiben.');
    }

    /**
     * @param list<string> $log
     */
    private function assertPasswordNeverOnCommandLine(array $log, string $password): void {
        foreach ($log as $line) {
            if (str_starts_with($line, 'ARG ') || str_starts_with($line, 'PARENT ') || str_starts_with($line, 'PDFINFO ')) {
                $this->assertStringNotContainsString($password, $line);
            }
        }
    }

    /**
     * @return array{result: bool, metadata: array<string, string>|null, log: list<string>, tempLeft: list<string>}
     */
    private function runProbe(string $case, string $password, ?string $owner = null): array {
        $logFile = $this->workDir . '/qpdf.log';
        $arguments = [PHP_BINARY, dirname(__DIR__) . '/scripts/pdf_password_probe.php', $case, $this->workDir . '/geschuetzt.pdf', $this->workDir . '/ausgabe.pdf', $password];
        if ($owner !== null) {
            $arguments[] = $owner;
        }

        $process = new Process($arguments, null, [
            'PATH' => $this->workDir . '/bin' . PATH_SEPARATOR . (getenv('PATH') ?: '/usr/bin:/bin'),
            'TMPDIR' => $this->workDir . '/tmp',
            'CKT_QPDF_LOG' => $logFile,
        ], null, 60.0);
        $process->run();

        $lines = preg_split('/\R/', trim($process->getOutput())) ?: [];
        $decoded = json_decode((string) end($lines), true);
        $this->assertIsArray($decoded, "Probe ohne JSON-Ergebnis (Exit {$process->getExitCode()}): " . $process->getOutput() . $process->getErrorOutput());

        $log = is_file($logFile) ? (file($logFile, FILE_IGNORE_NEW_LINES) ?: []) : [];

        return [
            'result' => (bool) ($decoded['result'] ?? false),
            'metadata' => is_array($decoded['metadata'] ?? null) ? $decoded['metadata'] : null,
            'log' => $log,
            'tempLeft' => glob($this->workDir . '/tmp/pdffile_*') ?: [],
        ];
    }
}
