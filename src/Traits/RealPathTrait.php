<?php
/*
 * Created on   : Mon Dec 30 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : RealPathTrait.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Traits;

use CommonToolkit\Helper\FileSystem\File;
use InvalidArgumentException;

/**
 * Trait für die Auflösung von realen Pfaden.
 * Wird von File und Folder verwendet.
 * 
 * Erfordert, dass die verwendende Klasse folgende Methoden implementiert:
 * - public static function exists(string $path): bool
 * - protected static function logDebug(string $message): void
 * - protected static function logError(string $message): void
 */
trait RealPathTrait {
    /**
     * Gibt den realen Pfad zurück.
     *
     * @param string $path Der Pfad zur Datei oder zum Verzeichnis.
     * @return string Der reale Pfad.
     */
    public static function getRealPath(string $path): string {
        // Windows-reservierte Gerätenamen nicht auflösen (auch auf Linux für Samba-Kompatibilität)
        if (File::isWindowsReservedName($path)) {
            self::logDebug("Windows-reservierter Gerätename, Pfad nicht aufgelöst: $path");
            return $path;
        }

        if (self::exists($path)) {
            $realPath = realpath($path);
            if ($realPath === false) {
                self::logDebug("Konnte Pfad nicht auflösen: $path");
                return $path;
            }
            if ($realPath !== $path) {
                self::logDebug("Pfad wurde normalisiert: $path -> $realPath");
            }
            return $realPath;
        }
        self::logDebug("Pfad existiert nicht, unverändert zurückgeben: $path");
        return $path;
    }

    /**
     * Validiert, dass der Pfad kein Windows-reservierter Gerätename ist.
     * Wirft eine Exception wenn der Name reserviert ist.
     *
     * @param string $path Der zu validierende Pfad.
     * @throws InvalidArgumentException Wenn der Name reserviert ist.
     */
    protected static function validateNotReservedName(string $path): void {
        if (File::isWindowsReservedName($path)) {
            self::logError("Ungültiger Pfadname (Windows-reservierter Name): $path");
            throw new InvalidArgumentException(
                "Ungültiger Name: " . basename($path) . " ist ein Windows-reservierter Gerätename"
            );
        }
    }
}
