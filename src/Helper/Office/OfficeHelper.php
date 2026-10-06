<?php
/*
 * Created on   : Mon Jun 22 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : OfficeHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Office;

use CommonToolkit\Contracts\Abstracts\ConfiguredHelperAbstract;
use CommonToolkit\Helper\FileSystem\{File, Folder};
use CommonToolkit\Helper\Shell;
use RuntimeException;
use Throwable;

/**
 * Dokument-Konvertierung via LibreOffice (headless, --convert-to).
 *
 * Generisch für beliebige von LibreOffice unterstützte Zielformate
 * (z.B. txt->docx/odt, docx->pdf, odt->pdf, …). Das Executable wird über
 * office_executables.json aufgelöst.
 *
 * LibreOffice laesst je Benutzerprofil nur eine Instanz zu; ein zweiter
 * gleichzeitiger Aufruf auf demselben Profil endet still mit Exit 1 und ohne
 * Ausgabedatei. Jeder Aufruf bekommt deshalb ein eigenes Profilverzeichnis
 * `<tmp>/<PROFILE_PREFIX>-lo-<zufall>`, das im finally wieder geloescht wird.
 * Damit nicht jeder Aufruf die Erstinitialisierung des Profils (~3 s) bezahlt,
 * wird es aus einer einmal initialisierten Vorlage kopiert.
 *
 * Haertung: Die Eingabe kann ein fremdes (hochgeladenes) Dokument sein. Ein
 * leeres Profil laedt verknuepfte Inhalte (Bild mit file:- oder http:-Verweis
 * in ODT/DOCX) beim Oeffnen nach - der Rechner liest dann eine eigene Datei in
 * die Ausgabe oder stellt eine ausgehende Anfrage. Jedes Profil eines Aufrufs
 * traegt deshalb die Eintraege aus config/libreoffice/registrymodifications.xcu
 * (Verknuepfungen aus fremden Dokumenten gesperrt, keine vertrauenswuerdigen
 * Orte, Verknuepfungen nie aktualisieren, Makros aus, keine Netz-
 * Inhaltsanbieter); ohne diese Datei startet kein Aufruf. Die Vorlage haengt
 * am Inhalt der Eintraege, eine geaenderte Haertung benutzt also nie ein
 * aelteres Profil weiter. Verknuepfte (nicht eingebettete) Inhalte fehlen
 * damit in der Ausgabe.
 */
final class OfficeHelper extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../../../config/office_executables.json';

    /** Praefix der Profilverzeichnisse: <tmp>/<PROFILE_PREFIX>-lo-<zufall>. */
    public const PROFILE_PREFIX = 'commontoolkit';

    /** Platzhalter in office_executables.json, den der Helper mit der file-URI des Profils fuellt. */
    private const PROFILE_PLACEHOLDER = '[PROFILE_URI]';

    /** LibreOffice-Option, die das Benutzerprofil festlegt. */
    private const PROFILE_OPTION = '-env:UserInstallation=';

    /** Registry-Datei des Benutzerprofils; an ihr ist zugleich ein initialisiertes Profil erkennbar. */
    private const PROFILE_MARKER = 'user/registrymodifications.xcu';

    /** Mitgelieferte gehaertete Registry-Eintraege, die jedes Profil eines Aufrufs traegt. */
    private const HARDENING_FILE = __DIR__ . '/../../../config/libreoffice/registrymodifications.xcu';

    /** Schlusselement der Registry-Datei; die Haertung kommt unmittelbar davor. */
    private const REGISTRY_END = '</oor:items>';

    /**
     * Startschalter jedes Aufrufs: keine Wiederherstellung nach einem Absturz,
     * keine Pruefung fremder Sperrdateien, kein leeres Startdokument.
     *
     * @var list<string>
     */
    private const START_SWITCHES = ['--norestore', '--nolockcheck', '--nodefault'];

    /** Zeitgrenze fuer die einmalige Initialisierung der Profilvorlage. */
    private const TEMPLATE_INIT_TIMEOUT = 120.0;

    /** true, wenn die Vorlage in diesem Prozess nicht angelegt werden konnte (kein erneuter Versuch). */
    private static bool $templateUnavailable = false;

    public static function isAvailable(): bool {
        return self::isExecutableAvailable('libreoffice');
    }

    /**
     * Konvertiert eine Datei mit LibreOffice in das Zielformat.
     *
     * Die Ausgabedatei landet im $outputDir mit gleichem Basisnamen und der
     * dem Zielformat entsprechenden Endung (LibreOffice-Verhalten).
     *
     * LibreOffice startet mit einem eigenen, gehaerteten Profil (siehe
     * Klassenkommentar): verknuepfte Inhalte der Eingabe werden nicht geladen,
     * Makros nicht ausgefuehrt. Fehlt die mitgelieferte Haertung, startet der
     * Aufruf nicht (false).
     *
     * @param string $inputFile    Pfad zur Eingabedatei
     * @param string $targetFormat LibreOffice-Zielformat (z.B. 'docx', 'odt', 'pdf')
     * @param string $outputDir    Ausgabeverzeichnis
     * @param list<string> $output Referenz: Shell-Ausgabe (stdout+stderr)
     * @param float|null $timeout  Sekunden bis zum Abbruch; null = unbegrenzt. Bei
     *                             Ueberschreitung false, $output endet mit "Zeitgrenze N s ueberschritten".
     * @return bool true bei Erfolg (Exit 0)
     */
    public static function convert(string $inputFile, string $targetFormat, string $outputDir, array &$output = [], int &$returnCode = 0, ?float $timeout = null): bool {
        if (!self::isAvailable()) {
            return self::logErrorAndReturn(false, 'LibreOffice ist nicht verfügbar (office_executables.json).');
        }

        $hardening = self::hardeningItems();
        if ($hardening === null) {
            return self::logErrorAndReturn(false, 'LibreOffice-Haertung fehlt oder ist leer (config/libreoffice/registrymodifications.xcu); ohne sie startet kein Aufruf.');
        }

        $profileDir = self::createProfileDir($hardening);
        try {
            $profileUri = self::fileUri($profileDir);
            $argv = self::getConfiguredArgv('libreoffice', [
                self::PROFILE_PLACEHOLDER => $profileUri,
                '[FORMAT]' => $targetFormat,
                '[OUTPUT_DIR]' => $outputDir,
                '[INPUT]' => $inputFile,
            ]);
            if ($argv === null) {
                return self::logErrorAndReturn(false, 'LibreOffice-Kommando nicht konfiguriert (office_executables.json).');
            }

            $argv = self::withStartSwitches(self::withProfile($argv, $profileUri));

            if (!Shell::execute($argv, $output, $returnCode, $timeout)) {
                return self::logErrorAndReturn(false, 'LibreOffice-Konvertierung fehlgeschlagen: ' . implode("\n", $output));
            }

            return true;
        } finally {
            self::removeDir($profileDir);
        }
    }

    /**
     * Konvertiert und liefert den vollständigen Pfad der erzeugten Datei zurück.
     *
     * @param float|null $timeout Sekunden bis zum Abbruch; null = unbegrenzt.
     * @return string|null Pfad zur Ausgabedatei oder null bei Fehler
     */
    public static function convertToFile(string $inputFile, string $targetFormat, string $outputDir, ?float $timeout = null): ?string {
        $output = [];
        $returnCode = 0;
        if (!self::convert($inputFile, $targetFormat, $outputDir, $output, $returnCode, $timeout)) {
            return null;
        }

        $basename = pathinfo($inputFile, PATHINFO_FILENAME);
        $outputFile = $outputDir . '/' . $basename . '.' . $targetFormat;

        return File::exists($outputFile) ? $outputFile : null;
    }

    /**
     * Setzt das Profil des Aufrufs durch: eine fremde `-env:UserInstallation=`
     * (z. B. aus einer ueberschreibenden Konfiguration ohne Platzhalter) wird
     * ersetzt, eine fehlende hinter dem Programm eingefuegt.
     *
     * @param list<string> $argv
     * @return list<string>
     */
    private static function withProfile(array $argv, string $profileUri): array {
        $option = self::PROFILE_OPTION . $profileUri;
        $result = [];
        $present = false;
        foreach ($argv as $index => $argument) {
            if ($index > 0 && str_starts_with($argument, self::PROFILE_OPTION)) {
                if ($argument !== $option) {
                    self::logDebug("Fremdes LibreOffice-Profil in der Konfiguration ersetzt: $argument");
                    $argument = $option;
                }
                if ($present) {
                    continue;
                }
                $present = true;
            }
            $result[] = $argument;
        }

        if (!$present) {
            array_splice($result, 1, 0, [$option]);
        }

        return $result;
    }

    /**
     * Setzt die Startschalter durch: fehlt einer (z. B. in einer ueberschreibenden
     * Konfiguration), kommt er hinter das Programm.
     *
     * @param list<string> $argv
     * @return list<string>
     */
    private static function withStartSwitches(array $argv): array {
        $missing = array_values(array_diff(self::START_SWITCHES, $argv));
        if ($missing !== []) {
            array_splice($argv, 1, 0, $missing);
        }

        return $argv;
    }

    /**
     * Legt das Profilverzeichnis dieses Aufrufs an - aus der Vorlage kopiert,
     * wenn eine existiert, sonst nur mit den gehaerteten Eintraegen (den Rest
     * initialisiert LibreOffice dann selbst).
     *
     * @param string $hardening Die Eintraege aus {@see hardeningItems()}.
     * @throws RuntimeException Wenn das Profil nicht angelegt oder nicht gehaertet werden kann.
     */
    private static function createProfileDir(string $hardening): string {
        $dir = self::newTempDir('lo');

        $template = self::profileTemplate($hardening);
        if ($template !== null) {
            try {
                Folder::copy($template, $dir, true);
                // Ein Lock der Vorlage darf die Kopie nicht als "belegt" markieren.
                $lock = $dir . DIRECTORY_SEPARATOR . '.lock';
                if (is_file($lock)) {
                    @unlink($lock);
                }
            } catch (Throwable $e) {
                self::logWarning('Profilvorlage nicht kopierbar, LibreOffice startet mit leerem Profil: ' . $e->getMessage());
            }
        }

        try {
            self::hardenProfile($dir, $hardening);
        } catch (Throwable $e) {
            self::removeDir($dir);
            self::logErrorAndThrow(RuntimeException::class, 'LibreOffice-Profil nicht haertbar, LibreOffice wird nicht gestartet: ' . $e->getMessage());
        }

        return $dir;
    }

    /**
     * Schreibt die gehaerteten Eintraege ans Ende der Registry-Datei des
     * Profils: spaetere Eintraege gewinnen, die Haertung gilt also auch dann,
     * wenn die kopierte Vorlage etwas anderes traegt. Ein Profil ohne (lesbare)
     * Registry-Datei bekommt genau die mitgelieferte Datei.
     *
     * @param string $hardening Die Eintraege aus {@see hardeningItems()}.
     */
    private static function hardenProfile(string $profileDir, string $hardening): void {
        $registry = $profileDir . DIRECTORY_SEPARATOR . self::PROFILE_MARKER;

        if (is_file($registry)) {
            $existing = File::read($registry);
            $end = strrpos($existing, self::REGISTRY_END);
            if ($end !== false) {
                File::write($registry, substr($existing, 0, $end) . $hardening . "\n" . self::REGISTRY_END . "\n");

                return;
            }
        }

        Folder::create(dirname($registry), 0700, true);
        File::copy(self::HARDENING_FILE, $registry);
    }

    /**
     * Die <item>-Eintraege der mitgelieferten Haertung, eine Zeile je Eintrag
     * (ohne Kommentare), oder null, wenn die Datei fehlt oder keinen Eintrag traegt.
     */
    private static function hardeningItems(): ?string {
        if (!is_file(self::HARDENING_FILE)) {
            return null;
        }

        $content = preg_replace('~<!--.*?-->~s', '', File::read(self::HARDENING_FILE)) ?? '';
        if ((int) preg_match_all('~<item\b.*?</item>~s', $content, $matches) < 1) {
            return null;
        }

        return implode("\n", $matches[0]);
    }

    /**
     * Pfad der initialisierten und gehaerteten Profilvorlage
     * `<tmp>/<PROFILE_PREFIX>-lo-template-<benutzer>-<fingerabdruck>` oder null,
     * wenn sie nicht angelegt werden kann (dann laeuft jeder Aufruf mit einem
     * Profil, das nur die Haertung traegt, nur langsamer).
     *
     * Der Fingerabdruck haengt am Inhalt der Haertung: aendert sie sich,
     * entsteht eine neue Vorlage, und eine aeltere (auch eine noch ungehaertete
     * `...-lo-template-<benutzer>`) wird nicht mehr kopiert.
     *
     * Die Initialisierung ist gegen gleichzeitige Prozesse abgesichert: jeder
     * initialisiert in ein eigenes Verzeichnis und benennt es atomar auf den
     * Vorlagenpfad um; der Verlierer verwirft seines und nutzt das des Gewinners.
     *
     * @param string $hardening Die Eintraege aus {@see hardeningItems()}.
     */
    private static function profileTemplate(string $hardening): ?string {
        $template = self::templatePath($hardening);
        if (is_file($template . DIRECTORY_SEPARATOR . self::PROFILE_MARKER)) {
            return $template;
        }
        if (self::$templateUnavailable) {
            return null;
        }

        $initDir = self::newTempDir('lo-init');
        $initialized = self::initializeProfile($initDir, $hardening);

        if ($initialized && @rename($initDir, $template)) {
            self::logDebug("LibreOffice-Profilvorlage angelegt: $template");
            return $template;
        }

        self::removeDir($initDir);

        // Ein anderer Prozess war schneller: dessen Vorlage nutzen.
        if (is_file($template . DIRECTORY_SEPARATOR . self::PROFILE_MARKER)) {
            return $template;
        }

        self::$templateUnavailable = true;

        return self::logWarningAndReturn(null, 'LibreOffice-Profilvorlage konnte nicht angelegt werden; jeder Aufruf startet mit einem Profil, das nur die Haertung traegt.');
    }

    /**
     * Pfad der Profilvorlage zu einer Haertung (siehe {@see profileTemplate()}).
     */
    private static function templatePath(string $hardening): string {
        return self::tempBase() . '-lo-template-' . self::userTag() . '-' . substr(sha1($hardening), 0, 12);
    }

    /**
     * Laesst LibreOffice ein Profil im Verzeichnis anlegen (--terminate_after_init)
     * und traegt danach die Haertung ein. Die Initialisierung selbst oeffnet kein Dokument.
     */
    private static function initializeProfile(string $profileDir, string $hardening): bool {
        $path = self::getExecutablePath('libreoffice');
        if ($path === null) {
            return false;
        }

        $output = [];
        $returnCode = 0;
        $argv = [$path, '--headless', ...self::START_SWITCHES, self::PROFILE_OPTION . self::fileUri($profileDir), '--terminate_after_init'];
        if (!Shell::execute($argv, $output, $returnCode, self::TEMPLATE_INIT_TIMEOUT)) {
            self::logDebug(sprintf('Profil-Initialisierung mit Exit %d beendet: %s', $returnCode, implode("\n", $output)));
        }

        if (!is_file($profileDir . DIRECTORY_SEPARATOR . self::PROFILE_MARKER)) {
            return false;
        }

        try {
            self::hardenProfile($profileDir, $hardening);
        } catch (Throwable $e) {
            return self::logWarningAndReturn(false, 'LibreOffice-Profilvorlage nicht haertbar: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Neues, leeres Verzeichnis `<tmp>/<PROFILE_PREFIX>-<kind>-<zufall>`.
     */
    private static function newTempDir(string $kind): string {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $dir = self::tempBase() . '-' . $kind . '-' . bin2hex(random_bytes(6));
            if (@mkdir($dir, 0700)) {
                return $dir;
            }
        }

        self::logErrorAndThrow(RuntimeException::class, 'Temporaeres Verzeichnis fuer das LibreOffice-Profil nicht anlegbar in ' . sys_get_temp_dir());
    }

    private static function tempBase(): string {
        return rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . self::PROFILE_PREFIX;
    }

    /**
     * Benutzerkennung im Vorlagenpfad: Profile sind 0700, ein anderer Benutzer
     * derselben Maschine braucht seine eigene Vorlage.
     */
    private static function userTag(): string {
        if (function_exists('posix_geteuid')) {
            return (string) posix_geteuid();
        }

        return substr(md5(get_current_user()), 0, 8);
    }

    /**
     * Loescht ein Profilverzeichnis; Fehler dabei duerfen eine erfolgreiche
     * Konvertierung nicht mehr kippen.
     */
    private static function removeDir(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        try {
            Folder::delete($dir, true);
        } catch (Throwable $e) {
            self::logWarning("LibreOffice-Profilverzeichnis nicht geloescht: $dir (" . $e->getMessage() . ')');
        }
    }

    /**
     * file-URI eines lokalen Pfads, wie LibreOffice sie fuer -env:UserInstallation erwartet.
     */
    private static function fileUri(string $path): string {
        $path = str_replace('\\', '/', $path);
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        $encoded = implode('/', array_map('rawurlencode', explode('/', $path)));

        return 'file://' . str_replace('%3A', ':', $encoded);
    }
}
