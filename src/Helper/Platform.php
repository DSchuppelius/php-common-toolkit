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

class Platform {
    /**
     * Überprüft, ob das aktuelle Betriebssystem Windows ist.
     *
     * @return bool True, wenn das Betriebssystem Windows ist, andernfalls false.
     */
    public static function isWindows(): bool {
        return self::getOsName() === 'WINDOWS';
    }

    /**
     * Überprüft, ob das aktuelle Betriebssystem Linux ist.
     *
     * @return bool True, wenn das Betriebssystem Linux ist, andernfalls false.
     */
    public static function isLinux(): bool {
        return self::getOsName() === 'LINUX';
    }

    /**
     * Überprüft, ob das aktuelle Betriebssystem macOS ist.
     *
     * @return bool True, wenn das Betriebssystem macOS ist, andernfalls false.
     */
    public static function isMac(): bool {
        return self::getOsName() === 'DARWIN';
    }

    /**
     * Gibt den Namen des Betriebssystems zurück.
     *
     * @return string Der Name des Betriebssystems in Großbuchstaben (z. B. 'WINDOWS', 'LINUX', 'DARWIN').
     */
    public static function getOsName(): string {
        return strtoupper(PHP_OS_FAMILY); // z. B. 'WINDOWS', 'LINUX', 'DARWIN'
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
     * Gibt den Dateinamen mit der richtigen Erweiterung für die aktuelle Plattform zurück.
     *
     * @param string $filename Der Dateiname ohne Erweiterung.
     * @return string Der Dateiname mit der richtigen Erweiterung.
     */
    public static function getExecutableExtension(): string {
        return self::isWindows() ? '.exe' : '';
    }

    /**
     * Gibt den Pfad an, der für die aktuelle Plattform geeignet ist.
     *
     * @param string $path Der Pfad, der angepasst werden soll.
     * @return string Der angepasste Pfad.
     */
    public static function adjustPath(string $path): string {
        // Beispiel: Auf Windows evtl. Backslashes normalisieren
        return self::isWindows() ? str_replace('/', '\\', $path) : $path;
    }
}