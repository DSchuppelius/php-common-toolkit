<?php
/*
 * Created on   : Tue Apr 01 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Platform.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper;

use ERRORToolkit\Helper\OsHelper;

/**
 * Platform Helper - Wrapper um den OsHelper aus dem ERRORToolkit.
 * Bietet plattformspezifische Funktionen und stellt Kompatibilität mit der ursprünglichen API sicher.
 */
class Platform {
    /**
     * Überprüft, ob das aktuelle Betriebssystem Windows ist.
     *
     * @return bool True, wenn das Betriebssystem Windows ist, andernfalls false.
     */
    public static function isWindows(): bool {
        return OsHelper::isWindows();
    }

    /**
     * Überprüft, ob das aktuelle Betriebssystem Linux ist.
     *
     * @return bool True, wenn das Betriebssystem Linux ist, andernfalls false.
     */
    public static function isLinux(): bool {
        return OsHelper::isLinux();
    }

    /**
     * Überprüft, ob das aktuelle Betriebssystem macOS ist.
     *
     * @return bool True, wenn das Betriebssystem macOS ist, andernfalls false.
     */
    public static function isMac(): bool {
        return OsHelper::isMacOS();
    }

    /**
     * Überprüft, ob das aktuelle Betriebssystem Unix-artig ist (Linux oder macOS).
     *
     * @return bool True, wenn das System Unix-artig ist, andernfalls false.
     */
    public static function isUnix(): bool {
        return OsHelper::isUnix();
    }

    /**
     * Gibt den Namen des Betriebssystems zurück.
     * Für Rückwärtskompatibilität wird das Format angepasst.
     *
     * @return string Der Name des Betriebssystems in Großbuchstaben (z. B. 'WINDOWS', 'LINUX', 'DARWIN').
     */
    public static function getOsName(): string {
        $osName = OsHelper::getOsName();
        return match ($osName) {
            'Windows' => 'WINDOWS',
            'Linux' => 'LINUX',
            'macOS' => 'DARWIN',
            default => strtoupper($osName)
        };
    }

    /**
     * Gibt den Shell-Befehl-Präfix zurück, der für die aktuelle Plattform geeignet ist.
     *
     * @param bool $usePowerShell Ob PowerShell verwendet werden soll (nur für Windows).
     * @return string Der Shell-Befehl-Präfix.
     */
    public static function getShellCommandPrefix(bool $usePowerShell = false): string {
        if (self::isWindows()) {
            return $usePowerShell ? 'powershell -ExecutionPolicy Bypass -Command' : 'cmd /c';
        }

        return $usePowerShell ? 'pwsh -Command' : '';
    }

    /**
     * Gibt die Dateiendung für ausführbare Dateien zurück.
     *
     * @return string Die Dateiendung ('.exe' für Windows, leer für Unix).
     */
    public static function getExecutableExtension(): string {
        return self::isWindows() ? '.exe' : '';
    }

    /**
     * Gibt den Pfad an, der für die aktuelle Plattform angepasst wurde.
     *
     * @param string $path Der Pfad, der angepasst werden soll.
     * @return string Der angepasste Pfad.
     */
    public static function adjustPath(string $path): string {
        return self::isWindows() ? str_replace('/', '\\', $path) : $path;
    }

    // === Erweiterte Funktionen aus dem OsHelper ===

    /**
     * Gibt das Home-Verzeichnis des aktuellen Benutzers zurück.
     *
     * @return string Das Home-Verzeichnis.
     */
    public static function getHomeDirectory(): string {
        return OsHelper::getHomeDirectory();
    }

    /**
     * Gibt das System-Temp-Verzeichnis zurück.
     *
     * @return string Das Temp-Verzeichnis.
     */
    public static function getTempDirectory(): string {
        return OsHelper::getTempDirectory();
    }

    /**
     * Gibt den korrekten Pfad-Separator für das aktuelle System zurück.
     *
     * @return string Der Pfad-Separator ('\\' für Windows, '/' für Unix).
     */
    public static function getPathSeparator(): string {
        return OsHelper::getPathSeparator();
    }

    /**
     * Gibt den korrekten PATH-Separator für Umgebungsvariablen zurück.
     *
     * @return string Der PATH-Separator (';' für Windows, ':' für Unix).
     */
    public static function getEnvPathSeparator(): string {
        return OsHelper::getEnvPathSeparator();
    }

    /**
     * Prüft, ob eine Datei ausführbar ist.
     *
     * @param string $path Der Pfad zur Datei.
     * @return bool True, wenn die Datei ausführbar ist.
     */
    public static function isExecutable(string $path): bool {
        return OsHelper::isExecutable($path);
    }

    /**
     * Sucht nach einem Executable in den PATH-Verzeichnissen.
     *
     * @param string $name Der Name des Executables.
     * @return string|null Der vollständige Pfad oder null, wenn nicht gefunden.
     */
    public static function findExecutable(string $name): ?string {
        return OsHelper::findExecutable($name);
    }

    /**
     * Gibt den aktuellen Benutzernamen zurück.
     *
     * @return string Der Benutzername.
     */
    public static function getCurrentUsername(): string {
        return OsHelper::getCurrentUsername();
    }

    /**
     * Prüft, ob der aktuelle Benutzer Root/Administrator-Rechte hat.
     *
     * @return bool True, wenn der Benutzer privilegiert ist.
     */
    public static function isPrivilegedUser(): bool {
        return OsHelper::isPrivilegedUser();
    }

    /**
     * Gibt eine Umgebungsvariable zurück.
     *
     * @param string $name Der Name der Umgebungsvariable.
     * @param string|null $default Der Default-Wert, falls die Variable nicht existiert.
     * @return string|null Der Wert der Umgebungsvariable oder der Default-Wert.
     */
    public static function getEnv(string $name, ?string $default = null): ?string {
        return OsHelper::getEnv($name, $default);
    }

    /**
     * Setzt eine Umgebungsvariable.
     *
     * @param string $name Der Name der Umgebungsvariable.
     * @param string $value Der Wert der Umgebungsvariable.
     * @return bool True bei Erfolg.
     */
    public static function setEnv(string $name, string $value): bool {
        return OsHelper::setEnv($name, $value);
    }

    /**
     * Gibt die Anzahl der verfügbaren CPU-Kerne zurück.
     *
     * @return int Die Anzahl der CPU-Kerne.
     */
    public static function getCpuCoreCount(): int {
        return OsHelper::getCpuCoreCount();
    }

    /**
     * Gibt die System-Architektur zurück.
     *
     * @return string Die Architektur (z.B. 'x86_64', 'aarch64').
     */
    public static function getArchitecture(): string {
        return OsHelper::getArchitecture();
    }

    /**
     * Gibt die Kernel-Version zurück.
     *
     * @return string Die Kernel-Version.
     */
    public static function getKernelVersion(): string {
        return OsHelper::getKernelVersion();
    }

    /**
     * Gibt detaillierte System-Informationen zurück.
     *
     * @return array Umfassende System-Informationen.
     */
    public static function getSystemInfo(): array {
        return OsHelper::getSystemInfo();
    }

    /**
     * Gibt die aktuelle Benutzer-ID zurück (nur Unix).
     *
     * @return int|null Die Benutzer-ID oder null auf Windows.
     */
    public static function getCurrentUserId(): ?int {
        return OsHelper::getCurrentUserId();
    }

    /**
     * Gibt die aktuelle Gruppen-ID zurück (nur Unix).
     *
     * @return int|null Die Gruppen-ID oder null auf Windows.
     */
    public static function getCurrentGroupId(): ?int {
        return OsHelper::getCurrentGroupId();
    }
}