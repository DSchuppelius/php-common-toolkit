<?php
/*
 * Created on   : Tue Apr 01 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PlatformHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper;

class PlatformHelper {
    public static function isWindows(): bool {
        return self::getOsName() === 'WINDOWS';
    }

    public static function isLinux(): bool {
        return self::getOsName() === 'LINUX';
    }

    public static function isMac(): bool {
        return self::getOsName() === 'DARWIN';
    }

    public static function getOsName(): string {
        return strtoupper(PHP_OS_FAMILY); // z. B. 'WINDOWS', 'LINUX', 'DARWIN'
    }

    public static function getShellCommandPrefix(bool $usePowerShell = false): string {
        if (self::isWindows()) {
            return $usePowerShell ? 'powershell -ExecutionPolicy Bypass -Command' : 'cmd /c';
        }

        return $usePowerShell ? 'pwsh -Command' : '';
    }

    public static function getExecutableExtension(): string {
        return self::isWindows() ? '.exe' : '';
    }

    public static function adjustPath(string $path): string {
        // Beispiel: Auf Windows evtl. Backslashes normalisieren
        return self::isWindows() ? str_replace('/', '\\', $path) : $path;
    }
}
