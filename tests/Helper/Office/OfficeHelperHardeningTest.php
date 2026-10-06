<?php
/*
 * Created on   : Mon Oct 05 2026
 * Author       : Daniel Joerg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : OfficeHelperHardeningTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper\Office;

use CommonToolkit\Helper\Office\OfficeHelper;
use CommonToolkit\Helper\Platform;
use DOMDocument;
use DOMElement;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Process\{ExecutableFinder, Process};
use Tests\Contracts\BaseTestCase;
use ZipArchive;

/**
 * LibreOffice wandelt Dateien des Aufrufers - verknuepfte Inhalte darin duerfen
 * weder eine lokale Datei in die Ausgabe ziehen noch eine ausgehende Anfrage
 * ausloesen. Jedes Profil eines Aufrufs traegt dafuer die Eintraege aus
 * config/libreoffice/registrymodifications.xcu.
 *
 * Die Dokumente entstehen hier synthetisch; Ziel der Verknuepfungen sind eine
 * Bilddatei im Testverzeichnis und ein Lauscher auf 127.0.0.1, den der Test
 * selbst oeffnet. Ein eingebettetes Bild dient als Gegenprobe: es MUSS im PDF
 * ankommen, sonst waere die Pruefung blind.
 */
class OfficeHelperHardeningTest extends BaseTestCase {
    private const HARDENING_FILE = __DIR__ . '/../../../config/libreoffice/registrymodifications.xcu';

    private const CONFIG_FILE = __DIR__ . '/../../../config/office_executables.json';

    private const SCRIPTING = '/org.openoffice.Office.Common/Security/Scripting';

    private const PROVIDERS = '/org.openoffice.ucb.Configuration/ContentProviders/Local/SecondaryKeys/Office/ProviderData';

    /** Kennung eines Bildobjekts im PDF-Export von LibreOffice. */
    private const PDF_IMAGE = '/Subtype/Image';

    /** 1x1-PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private string $dir;

    protected function setUp(): void {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/ctk-office-haertung-' . bin2hex(random_bytes(4));
        mkdir($this->dir, 0700, true);
    }

    protected function tearDown(): void {
        (new ReflectionProperty(OfficeHelper::class, 'templateUnavailable'))->setValue(null, false);
        $this->removeDir($this->dir);
        parent::tearDown();
    }

    public function test_haertung_sperrt_verknuepfungen_makros_und_netz_anbieter(): void {
        $items = $this->registryItems($this->readFile(self::HARDENING_FILE));

        $this->assertSame('true', $items[self::SCRIPTING . '#BlockUntrustedRefererLinks'] ?? null, 'Verknuepfungen aus fremden Dokumenten muessen gesperrt sein');
        $this->assertSame('', $items[self::SCRIPTING . '#SecureURL'] ?? null, 'Es darf keinen vertrauenswuerdigen Ort geben');
        $this->assertSame('0', $items['/org.openoffice.Office.Writer/Content/Update#Link'] ?? null, 'Writer: Verknuepfungen nie aktualisieren');
        $this->assertSame('1', $items['/org.openoffice.Office.Calc/Content/Update#Link'] ?? null, 'Calc: Verknuepfungen nie aktualisieren');
        $this->assertSame('true', $items[self::SCRIPTING . '#DisableMacrosExecution'] ?? null);
        $this->assertSame('3', $items[self::SCRIPTING . '#MacroSecurityLevel'] ?? null);
        $this->assertSame('true', $items[self::SCRIPTING . '#DisableActiveContent'] ?? null);

        // http, WebDAV, https, CMIS, GIO
        foreach (['Provider3', 'Provider4', 'Provider12', 'Provider43', 'Provider999'] as $provider) {
            $this->assertSame('remove', $items[self::PROVIDERS . '#' . $provider] ?? null, "Inhaltsanbieter $provider muss entfernt sein");
        }
    }

    public function test_helper_uebernimmt_alle_eintraege_ohne_kommentare(): void {
        $hardening = $this->hardening();

        $this->assertStringNotContainsString('<!--', $hardening);
        $this->assertSame(
            $this->registryItems($this->readFile(self::HARDENING_FILE)),
            $this->registryItems($this->wrap($hardening)),
            'Der Helper muss genau die Eintraege der mitgelieferten Datei anhaengen.',
        );
    }

    public function test_kommando_traegt_die_startschalter_und_keine_shell_operatoren(): void {
        $config = json_decode($this->readFile(self::CONFIG_FILE), true);
        $this->assertIsArray($config);
        $arguments = $config['shellExecutables']['libreoffice']['arguments'] ?? null;
        $this->assertIsArray($arguments);

        foreach (['--headless', '--norestore', '--nolockcheck', '--nodefault', '-env:UserInstallation=[PROFILE_URI]'] as $expected) {
            $this->assertContains($expected, $arguments);
        }
        foreach ($arguments as $argument) {
            // Der CommandBuilder behandelt Argumente mit > < | als Shell-Operatoren (kein Escaping).
            $this->assertDoesNotMatchRegularExpression('/[<>|]/', (string) $argument);
        }
    }

    public function test_fehlende_startschalter_werden_ergaenzt(): void {
        $withStartSwitches = new ReflectionMethod(OfficeHelper::class, 'withStartSwitches');

        // Ueberschreibende Konfiguration ohne die Schalter: der Helper setzt sie hinter das Programm.
        $this->assertSame(
            ['libreoffice', '--norestore', '--nolockcheck', '--nodefault', '--headless', '--convert-to', 'pdf'],
            $withStartSwitches->invoke(null, ['libreoffice', '--headless', '--convert-to', 'pdf']),
        );
        $this->assertSame(
            ['libreoffice', '--nolockcheck', '--headless', '--norestore', '--nodefault'],
            $withStartSwitches->invoke(null, ['libreoffice', '--headless', '--norestore', '--nodefault']),
        );

        $complete = ['libreoffice', '--headless', '--norestore', '--nolockcheck', '--nodefault', '--convert-to', 'pdf'];
        $this->assertSame($complete, $withStartSwitches->invoke(null, $complete));
    }

    public function test_haertung_steht_am_ende_einer_vorhandenen_registry(): void {
        $registry = $this->dir . '/user/registrymodifications.xcu';
        mkdir(dirname($registry), 0700);
        // Ein Profil (etwa eine veraenderte Vorlage), das Verknuepfungen ausdruecklich erlaubt.
        file_put_contents($registry, $this->wrap(
            '<item oor:path="/org.openoffice.Setup/Office"><prop oor:name="ooSetupInstCompleted" oor:op="fuse"><value>true</value></prop></item>' . "\n"
            . '<item oor:path="' . self::SCRIPTING . '"><prop oor:name="BlockUntrustedRefererLinks" oor:op="fuse"><value>false</value></prop></item>',
        ));

        (new ReflectionMethod(OfficeHelper::class, 'hardenProfile'))->invoke(null, $this->dir, $this->hardening());

        // registryItems() liefert je Eintrag den letzten Wert - so liest auch LibreOffice die Datei.
        $items = $this->registryItems($this->readFile($registry));
        $this->assertSame('true', $items[self::SCRIPTING . '#BlockUntrustedRefererLinks'] ?? null, 'Die Haertung muss den frueheren Eintrag ueberstimmen.');
        $this->assertSame('true', $items['/org.openoffice.Setup/Office#ooSetupInstCompleted'] ?? null, 'Die uebrigen Eintraege des Profils bleiben erhalten.');
        $this->assertSame('true', $items[self::SCRIPTING . '#DisableMacrosExecution'] ?? null);
    }

    public function test_profil_ohne_registry_bekommt_die_haertung(): void {
        (new ReflectionMethod(OfficeHelper::class, 'hardenProfile'))->invoke(null, $this->dir, $this->hardening());

        $registry = $this->dir . '/user/registrymodifications.xcu';
        $this->assertFileExists($registry);
        $this->assertSame($this->registryItems($this->readFile(self::HARDENING_FILE)), $this->registryItems($this->readFile($registry)));
    }

    public function test_abgeschnittene_registry_wird_durch_die_haertung_ersetzt(): void {
        // Eine Registry ohne Schlusselement (Kopie der Vorlage brach ab) wird ersetzt, nicht fortgeschrieben.
        $registry = $this->dir . '/user/registrymodifications.xcu';
        mkdir(dirname($registry), 0700);
        file_put_contents($registry, '<?xml version="1.0" encoding="UTF-8"?><oor:items xmlns:oor="http://openoffice.org/2001/registry"><item oor:path="/x">');

        (new ReflectionMethod(OfficeHelper::class, 'hardenProfile'))->invoke(null, $this->dir, $this->hardening());

        $this->assertSame($this->registryItems($this->readFile(self::HARDENING_FILE)), $this->registryItems($this->readFile($registry)));
    }

    public function test_vorlagenpfad_haengt_am_inhalt_der_haertung(): void {
        $templatePath = new ReflectionMethod(OfficeHelper::class, 'templatePath');
        $hardening = $this->hardening();

        $current = $templatePath->invoke(null, $hardening);
        $this->assertIsString($current);
        $this->assertMatchesRegularExpression('/-lo-template-[0-9a-f]+-[0-9a-f]{12}$/', $current, 'Benutzerkennung und Fingerabdruck der Haertung');
        $this->assertSame($current, $templatePath->invoke(null, $hardening));
        $this->assertNotSame($current, $templatePath->invoke(null, $hardening . "\n<item/>"), 'Eine geaenderte Haertung darf keine aeltere Vorlage weiterbenutzen.');
    }

    public function test_aufruf_startet_mit_schaltern_und_gehaertetem_profil(): void {
        if (Platform::isWindows()) {
            $this->markTestSkipped('Die Attrappe ist ein sh-Skript; der Test laeuft nur unter Unix.');
        }

        // Attrappe: beendet die Profil-Initialisierung ohne Profil (es gibt dann keine Vorlage), protokolliert
        // beim eigentlichen Aufruf ihre Argumente und sichert die Registry des uebergebenen Profils.
        $binDir = $this->dir . '/bin';
        mkdir($binDir, 0700);
        $script = <<<'SH'
            #!/bin/sh
            case "$*" in *--terminate_after_init*) exit 0;; esac
            for a in "$@"; do
                printf '%s\n' "$a" >> "$CTK_ARGV_LOG"
                case "$a" in -env:UserInstallation=file://*) cp "${a#-env:UserInstallation=file://}/user/registrymodifications.xcu" "$CTK_REGISTRY_COPY";; esac
            done
            exit 0
            SH;
        file_put_contents($binDir . '/libreoffice', $script . "\n");
        chmod($binDir . '/libreoffice', 0700);

        $input = $this->dir . '/eingabe.txt';
        file_put_contents($input, "Text\n");
        $log = $this->dir . '/argv.log';
        $registryCopy = $this->dir . '/registry-des-aufrufs.xcu';
        // Eigener Temp-Ordner: dort liegt keine Vorlage eines frueheren, echten Laufs.
        $tmp = $this->dir . '/tmp';
        mkdir($tmp, 0700);

        $process = new Process(
            [PHP_BINARY, '-d', 'sys_temp_dir=' . $tmp, '-r', sprintf(
                'require %s; $o = []; $c = 0; var_export(CommonToolkit\Helper\Office\OfficeHelper::convert($argv[1], "pdf", $argv[2], $o, $c, 20.0));',
                var_export(dirname(__DIR__, 3) . '/vendor/autoload.php', true),
            ), $input, $this->dir],
            null,
            ['PATH' => $binDir . PATH_SEPARATOR . (getenv('PATH') ?: '/usr/bin:/bin'), 'CTK_ARGV_LOG' => $log, 'CTK_REGISTRY_COPY' => $registryCopy],
            null,
            60.0,
        );
        $process->run();

        $this->assertFileExists($log, 'Die Attrappe wurde nicht gestartet: ' . $process->getOutput() . $process->getErrorOutput());
        $argv = array_values(array_filter(explode("\n", $this->readFile($log)), static fn (string $arg): bool => $arg !== ''));
        foreach (['--headless', '--norestore', '--nolockcheck', '--nodefault', '--convert-to', $input] as $expected) {
            $this->assertContains($expected, $argv);
        }

        $this->assertFileExists($registryCopy, 'Das Profil des Aufrufs muss beim Start eine Registry tragen.');
        $items = $this->registryItems($this->readFile($registryCopy));
        $this->assertSame('true', $items[self::SCRIPTING . '#BlockUntrustedRefererLinks'] ?? null);
        $this->assertSame('true', $items[self::SCRIPTING . '#DisableMacrosExecution'] ?? null);
        $this->assertSame([], glob($tmp . '/' . OfficeHelper::PROFILE_PREFIX . '-lo-*') ?: [], 'Das Profil des Aufrufs wird wieder geloescht.');
    }

    public function test_verknuepftes_bild_wird_nicht_gelesen_eingebettetes_bleibt(): void {
        $this->requireSoffice();

        $linkedImage = $this->dir . '/verknuepft.png';
        file_put_contents($linkedImage, (string) base64_decode(self::PNG, true));
        $linked = $this->writeOdt('verknuepft.odt', 'file://' . $linkedImage);
        $embedded = $this->writeOdt('eingebettet.odt', 'Pictures/bild.png', true);

        $embeddedPdf = OfficeHelper::convertToFile($embedded, 'pdf', $this->dir, 180.0);
        $this->assertIsString($embeddedPdf);
        $this->assertTrue(str_contains($this->readFile($embeddedPdf), self::PDF_IMAGE), 'Gegenprobe: das eingebettete Bild muss im PDF stehen.');

        $linkedPdf = OfficeHelper::convertToFile($linked, 'pdf', $this->dir, 180.0);
        $this->assertIsString($linkedPdf);
        $this->assertFalse(str_contains($this->readFile($linkedPdf), self::PDF_IMAGE), 'Das verknuepfte Bild darf nicht geladen werden.');

        // Die Vorlage, aus der die Profile kopiert werden, traegt die Haertung ebenfalls.
        $template = (new ReflectionMethod(OfficeHelper::class, 'templatePath'))->invoke(null, $this->hardening());
        $this->assertIsString($template);
        $this->assertFileExists($template . '/user/registrymodifications.xcu');
        $items = $this->registryItems($this->readFile($template . '/user/registrymodifications.xcu'));
        $this->assertSame('true', $items[self::SCRIPTING . '#BlockUntrustedRefererLinks'] ?? null);
    }

    public function test_verknuepftes_bild_wird_auch_ohne_vorlage_nicht_gelesen(): void {
        $this->requireSoffice();

        // Ohne Vorlage traegt das Profil nur die Haertung; den Rest initialisiert LibreOffice selbst.
        (new ReflectionProperty(OfficeHelper::class, 'templateUnavailable'))->setValue(null, true);
        $templatePath = new ReflectionMethod(OfficeHelper::class, 'templatePath');
        $profileTemplate = new ReflectionMethod(OfficeHelper::class, 'profileTemplate');
        $template = $templatePath->invoke(null, $this->hardening());
        $this->assertIsString($template);
        if (is_dir($template)) {
            // Die Vorlage eines frueheren Laufs gilt weiter - der Fall ohne Vorlage laesst sich dann nicht stellen.
            $this->assertSame($template, $profileTemplate->invoke(null, $this->hardening()));
            $this->markTestSkipped('Es gibt bereits eine Profilvorlage; der Fall ohne Vorlage ist ueber die Attrappe abgedeckt.');
        }

        $linkedImage = $this->dir . '/verknuepft.png';
        file_put_contents($linkedImage, (string) base64_decode(self::PNG, true));
        $pdf = OfficeHelper::convertToFile($this->writeOdt('verknuepft.odt', 'file://' . $linkedImage), 'pdf', $this->dir, 180.0);

        $this->assertIsString($pdf);
        $this->assertFalse(str_contains($this->readFile($pdf), self::PDF_IMAGE), 'Das verknuepfte Bild darf nicht geladen werden.');
    }

    public function test_verknuepfung_auf_netzadresse_loest_keinen_abruf_aus(): void {
        $this->requireSoffice();

        // Eigener Lauscher auf einem freien lokalen Port; jede Verbindung waere ein Abruf aus dem Dokument.
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        $this->assertIsResource($server, "Lauscher nicht anlegbar: $error");
        $address = (string) stream_socket_get_name($server, false);

        try {
            $pdf = OfficeHelper::convertToFile($this->writeOdt('netz.odt', "http://$address/bild.png"), 'pdf', $this->dir, 180.0);

            $this->assertIsString($pdf);
            $this->assertFalse(@stream_socket_accept($server, 0.0), 'Am Lauscher darf keine Verbindung ankommen.');
        } finally {
            fclose($server);
        }
    }

    /**
     * Die Eintraege, wie der Helper sie anhaengt.
     */
    private function hardening(): string {
        $hardening = (new ReflectionMethod(OfficeHelper::class, 'hardeningItems'))->invoke(null);
        $this->assertIsString($hardening, 'Die mitgelieferte Haertung fehlt oder ist leer.');

        return $hardening;
    }

    private function wrap(string $items): string {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<oor:items xmlns:oor="http://openoffice.org/2001/registry" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' . "\n"
            . $items . "\n</oor:items>\n";
    }

    /**
     * Liest eine Registry-Datei: "<pfad>#<name>" => Wert einer Eigenschaft bzw.
     * Operation eines Knotens. Spaetere Eintraege ueberschreiben fruehere.
     *
     * @return array<string, string>
     */
    private function registryItems(string $xml): array {
        $document = new DOMDocument;
        $this->assertTrue($document->loadXML($xml), 'Die Registry-Datei muss wohlgeformtes XML sein.');

        $namespace = 'http://openoffice.org/2001/registry';
        $items = [];
        foreach ($document->getElementsByTagName('item') as $item) {
            $path = $item->getAttributeNS($namespace, 'path');
            foreach ($item->childNodes as $child) {
                if (!$child instanceof DOMElement) {
                    continue;
                }
                $key = $path . '#' . $child->getAttributeNS($namespace, 'name');
                $items[$key] = $child->localName === 'node' ? $child->getAttributeNS($namespace, 'op') : trim($child->textContent);
            }
        }

        return $items;
    }

    /**
     * Schreibt ein ODT mit einem Bildrahmen, dessen Bild auf $href zeigt; mit
     * $embed liegt das Bild unter diesem Namen im Paket.
     */
    private function writeOdt(string $name, string $href, bool $embed = false): string {
        $path = $this->dir . '/' . $name;
        $content = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            . ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"'
            . ' xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"'
            . ' xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"'
            . ' xmlns:xlink="http://www.w3.org/1999/xlink" office:version="1.3">'
            . '<office:body><office:text><text:p>Probe</text:p><text:p>'
            . '<draw:frame draw:name="Bild1" text:anchor-type="as-char" svg:width="4cm" svg:height="3cm">'
            . '<draw:image xlink:href="' . htmlspecialchars($href, ENT_XML1 | ENT_QUOTES) . '" xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad"/>'
            . '</draw:frame></text:p></office:text></office:body></office:document-content>';
        $entries = '<manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.text"/>'
            . '<manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>'
            . ($embed ? '<manifest:file-entry manifest:full-path="' . $href . '" manifest:media-type="image/png"/>' : '');
        $manifest = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0" manifest:version="1.3">' . $entries . '</manifest:manifest>';

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
        $zip->setCompressionName('mimetype', ZipArchive::CM_STORE);
        $zip->addFromString('content.xml', $content);
        $zip->addFromString('META-INF/manifest.xml', $manifest);
        if ($embed) {
            $zip->addFromString($href, (string) base64_decode(self::PNG, true));
        }
        $this->assertTrue($zip->close());

        return $path;
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
