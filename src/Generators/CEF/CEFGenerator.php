<?php
/*
 * Created on   : Mon Sep 28 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CEFGenerator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Generators\CEF;

use InvalidArgumentException;

/**
 * Zeilen im Common Event Format (CEF, ArcSight) für SIEM-Systeme:
 *
 *   CEF:0|Vendor|Product|Version|SignatureId|Name|Severity|key=value key=value
 *
 * Kopffelder maskieren `\` und `|`, Erweiterungswerte `\`, `=` und
 * Zeilenumbrüche; Zeilenumbrüche im Kopf werden zu Leerzeichen.
 */
final class CEFGenerator {
    public const VERSION = 0;

    /**
     * @param int $severity 0 (niedrig) bis 10 (sehr hoch); Werte außerhalb werden begrenzt
     * @param array<string, scalar|null> $extensions leere Werte und null entfallen
     * @throws InvalidArgumentException bei Schlüsseln außer Buchstaben, Ziffern und `_`
     */
    public static function line(string $vendor, string $product, string $version, string $signatureId, string $name, int $severity, array $extensions = []): string {
        $header = array_map(self::escapeHeader(...), [$vendor, $product, $version, $signatureId, $name]);
        $line = 'CEF:' . self::VERSION . '|' . implode('|', $header) . '|' . max(0, min(10, $severity)) . '|';

        $pairs = [];
        foreach ($extensions as $key => $value) {
            if (preg_match('/^[A-Za-z0-9_]+$/', (string) $key) !== 1) {
                throw new InvalidArgumentException("CEF: ungültiger Erweiterungsschlüssel '{$key}'.");
            }
            if ($value === null || $value === '') {
                continue;
            }
            $pairs[] = $key . '=' . self::escapeExtension(is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
        }

        return $line . implode(' ', $pairs);
    }

    private static function escapeHeader(string $value): string {
        return str_replace(['\\', '|', "\r\n", "\r", "\n"], ['\\\\', '\\|', ' ', ' ', ' '], $value);
    }

    private static function escapeExtension(string $value): string {
        return str_replace(['\\', '=', "\r\n", "\r", "\n"], ['\\\\', '\\=', '\\n', '\\r', '\\n'], $value);
    }
}
