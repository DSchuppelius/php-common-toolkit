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
 */
final class OfficeHelper extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../../../config/office_executables.json';

    /** Praefix der Profilverzeichnisse: <tmp>/<PROFILE_PREFIX>-lo-<zufall>. */
    public const PROFILE_PREFIX = 'commontoolkit';

    /** Platzhalter in office_executables.json, den der Helper mit der file-URI des Profils fuellt. */
    private const PROFILE_PLACEHOLDER = '[PROFILE_URI]';

    /** LibreOffice-Option, die das Benutzerprofil festlegt. */
    private const PROFILE_OPTION = '-env:UserInstallation=';

    /** Datei, an der ein initialisiertes Profil erkennbar ist. */
    private const PROFILE_MARKER = 'user/registrymodifications.xcu';

    /** Zeitgrenze fuer die einmalige Initialisierung der Profilvorlage. */
    private const TEMPLATE_INIT_TIMEOUT = 120.0;

    /** Pfad der Profilvorlage, sobald sie in diesem Prozess einmal bestaetigt wurde. */
    private static ?string $templateDir = null;

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

        $profileDir = self::createProfileDir();
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

            $argv = self::withProfile($argv, $profileUri);

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
     * Legt das Profilverzeichnis dieses Aufrufs an - aus der Vorlage kopiert,
     * wenn eine existiert, sonst leer (LibreOffice initialisiert es dann selbst).
     */
    private static function createProfileDir(): string {
        $dir = self::newTempDir('lo');

        $template = self::profileTemplate();
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

        return $dir;
    }

    /**
     * Pfad der initialisierten Profilvorlage `<tmp>/<PROFILE_PREFIX>-lo-template-<benutzer>`
     * oder null, wenn sie nicht angelegt werden kann (dann laeuft jeder Aufruf
     * mit leerem Profil, nur langsamer).
     *
     * Die Initialisierung ist gegen gleichzeitige Prozesse abgesichert: jeder
     * initialisiert in ein eigenes Verzeichnis und benennt es atomar auf den
     * Vorlagenpfad um; der Verlierer verwirft seines und nutzt das des Gewinners.
     */
    private static function profileTemplate(): ?string {
        if (self::$templateDir !== null && is_file(self::$templateDir . DIRECTORY_SEPARATOR . self::PROFILE_MARKER)) {
            return self::$templateDir;
        }
        if (self::$templateUnavailable) {
            return null;
        }

        $template = self::tempBase() . '-lo-template-' . self::userTag();
        if (is_file($template . DIRECTORY_SEPARATOR . self::PROFILE_MARKER)) {
            return self::$templateDir = $template;
        }

        $initDir = self::newTempDir('lo-init');
        $initialized = self::initializeProfile($initDir);

        if ($initialized && @rename($initDir, $template)) {
            self::logDebug("LibreOffice-Profilvorlage angelegt: $template");
            return self::$templateDir = $template;
        }

        self::removeDir($initDir);

        // Ein anderer Prozess war schneller: dessen Vorlage nutzen.
        if (is_file($template . DIRECTORY_SEPARATOR . self::PROFILE_MARKER)) {
            return self::$templateDir = $template;
        }

        self::$templateUnavailable = true;

        return self::logWarningAndReturn(null, 'LibreOffice-Profilvorlage konnte nicht angelegt werden; jeder Aufruf startet mit leerem Profil.');
    }

    /**
     * Laesst LibreOffice ein Profil im Verzeichnis anlegen (--terminate_after_init).
     */
    private static function initializeProfile(string $profileDir): bool {
        $path = self::getExecutablePath('libreoffice');
        if ($path === null) {
            return false;
        }

        $output = [];
        $returnCode = 0;
        $argv = [$path, '--headless', self::PROFILE_OPTION . self::fileUri($profileDir), '--terminate_after_init'];
        if (!Shell::execute($argv, $output, $returnCode, self::TEMPLATE_INIT_TIMEOUT)) {
            self::logDebug(sprintf('Profil-Initialisierung mit Exit %d beendet: %s', $returnCode, implode("\n", $output)));
        }

        return is_file($profileDir . DIRECTORY_SEPARATOR . self::PROFILE_MARKER);
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
