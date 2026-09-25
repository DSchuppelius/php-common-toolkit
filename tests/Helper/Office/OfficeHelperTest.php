<?php
/*
 * Created on   : Fri Sep 25 2026
 * Author       : Daniel Joerg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : OfficeHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper\Office;

use CommonToolkit\Helper\Office\OfficeHelper;
use CommonToolkit\Helper\Shell;
use ReflectionMethod;
use Symfony\Component\Process\{ExecutableFinder, Process};
use Tests\Contracts\{BaseTestCase, TimeoutProbeTrait};

class OfficeHelperTest extends BaseTestCase {
    use TimeoutProbeTrait;

    private string $dir;

    protected function setUp(): void {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/ctk-office-' . bin2hex(random_bytes(4));
        mkdir($this->dir, 0700, true);
    }

    protected function tearDown(): void {
        $this->removeProbeDir($this->dir);
        parent::tearDown();
    }

    public function test_zeitgrenze_bricht_langsame_konvertierung_ab(): void {
        $probe = $this->runTimeoutProbe('officeConvert', 0.5);

        $this->assertFalse($probe['result']);
        $this->assertSame(Shell::EXIT_TIMEOUT, $probe['returnCode']);
        $this->assertContains('Zeitgrenze 0.5 s ueberschritten', $probe['output']);
        $this->assertLessThan(4.0, $probe['duration']);
    }

    public function test_fremdes_profil_in_der_konfiguration_wird_ersetzt(): void {
        // Ein Konsument kann den Eintrag 'libreoffice' mit einem festen Profil ueberschreiben
        // (ConfigLoader merged Sektionen); der Helper setzt trotzdem sein eigenes Profil durch.
        $withProfile = new ReflectionMethod(OfficeHelper::class, 'withProfile');
        $uri = 'file:///tmp/commontoolkit-lo-abc';

        $fixed = ['libreoffice', '--headless', '-env:UserInstallation=file:///tmp/libreoffice-user', '--convert-to', 'pdf'];
        $this->assertSame(
            ['libreoffice', '--headless', '-env:UserInstallation=' . $uri, '--convert-to', 'pdf'],
            $withProfile->invoke(null, $fixed, $uri),
        );

        $missing = ['libreoffice', '--headless', '--convert-to', 'pdf'];
        $this->assertSame(
            ['libreoffice', '-env:UserInstallation=' . $uri, '--headless', '--convert-to', 'pdf'],
            $withProfile->invoke(null, $missing, $uri),
        );

        $own = ['libreoffice', '--headless', '-env:UserInstallation=' . $uri, '--convert-to', 'pdf'];
        $this->assertSame($own, $withProfile->invoke(null, $own, $uri));
    }

    public function test_zwei_gleichzeitige_konvertierungen_gelingen_beide(): void {
        $this->requireSoffice();

        $inputs = [];
        foreach (['eins', 'zwei'] as $name) {
            $inputs[$name] = $this->dir . '/' . $name . '.txt';
            file_put_contents($inputs[$name], "Parallele LibreOffice-Konvertierung: $name\n");
        }

        // Einmal vorab, damit die Profilvorlage steht und beide Laeufe wirklich gleichzeitig konvertieren.
        $warmup = [];
        $warmupCode = 0;
        $this->assertTrue(OfficeHelper::convert($inputs['eins'], 'pdf', $this->dir, $warmup, $warmupCode, 120.0), implode("\n", $warmup));
        @unlink($this->dir . '/eins.pdf');

        $processes = [];
        foreach ($inputs as $name => $input) {
            $processes[$name] = new Process([PHP_BINARY, '-r', $this->conversionScript(), $input, $this->dir], null, null, null, 180.0);
            $processes[$name]->start();
        }

        foreach ($processes as $name => $process) {
            $process->wait();
            $decoded = json_decode(trim($process->getOutput()), true);
            $this->assertIsArray($decoded, "Kindprozess $name ohne Ergebnis: " . $process->getOutput() . $process->getErrorOutput());
            $this->assertTrue($decoded['result'], "Konvertierung $name fehlgeschlagen: " . implode("\n", $decoded['output']));
            $this->assertSame(0, $decoded['returnCode'], "Konvertierung $name endete mit rc {$decoded['returnCode']}");
            $this->assertFileExists($this->dir . '/' . $name . '.pdf');
        }
    }

    public function test_profilverzeichnis_wird_nach_dem_aufruf_geloescht(): void {
        $this->requireSoffice();

        $input = $this->dir . '/aufraeumen.txt';
        file_put_contents($input, "Profil weg nach Aufruf\n");
        $before = $this->profileDirs();

        $converted = OfficeHelper::convertToFile($input, 'pdf', $this->dir, 120.0);

        $this->assertSame($this->dir . '/aufraeumen.pdf', $converted);
        $this->assertSame($before, $this->profileDirs(), 'Es darf kein Profilverzeichnis dieses Aufrufs uebrig bleiben.');
    }

    private function requireSoffice(): void {
        $finder = new ExecutableFinder;
        if ($finder->find('soffice') === null && $finder->find('libreoffice') === null) {
            $this->markTestSkipped('LibreOffice (soffice/libreoffice) ist nicht installiert.');
        }
        if (!OfficeHelper::isAvailable()) {
            $this->markTestSkipped('LibreOffice ist laut office_executables.json nicht verfuegbar.');
        }
    }

    /** @return list<string> Profilverzeichnisse einzelner Aufrufe (ohne die Vorlage). */
    private function profileDirs(): array {
        $dirs = glob(sys_get_temp_dir() . '/' . OfficeHelper::PROFILE_PREFIX . '-lo-*') ?: [];
        sort($dirs);

        return array_values(array_filter($dirs, static fn (string $dir): bool => !str_contains(basename($dir), '-lo-template-')));
    }

    private function conversionScript(): string {
        return sprintf(
            'require %s; $o = []; $c = 0; $r = CommonToolkit\Helper\Office\OfficeHelper::convert($argv[1], "pdf", $argv[2], $o, $c, 180.0); echo json_encode(["result" => $r, "returnCode" => $c, "output" => $o]);',
            var_export(dirname(__DIR__, 3) . '/vendor/autoload.php', true),
        );
    }
}
