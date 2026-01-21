<?php
/*
 * Created on   : Sun Dec 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : JsonHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use JsonException;

/**
 * Helper-Klasse für JSON-Verarbeitung und Validierung.
 *
 * Bietet Funktionen für:
 * - JSON-Validierung und -Parsing
 * - Schema-Validierung (JSON Schema Draft 7)
 * - Pretty-Print und Minify
 * - JSONPath-ähnliche Pfad-Extraktion
 * - Sichere JSON-Dekodierung mit Fehlererkennung
 */
class JsonHelper extends HelperAbstract {
    use ErrorLog;

    /**
     * Validiert einen JSON-String auf syntaktische Korrektheit.
     *
     * @param string $json Der zu validierende JSON-String
     * @return bool True wenn gültig, false andernfalls
     */
    public static function isValid(string $json): bool {
        try {
            json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return true;
        } catch (JsonException $e) {
            return self::logErrorAndReturn(false, "JSON-Validierung fehlgeschlagen: " . $e->getMessage());
        }
    }

    /**
     * Dekodiert JSON sicher mit ausführlicher Fehlerbehandlung.
     *
     * @param string $json Der JSON-String
     * @param bool $associative Ob Objekte als assoziative Arrays zurückgegeben werden sollen
     * @param int $depth Maximale Verschachtelungstiefe
     * @return mixed Die dekodierte JSON-Struktur
     * @throws InvalidArgumentException Bei ungültigem JSON
     */
    public static function decode(string $json, bool $associative = true, int $depth = 512): mixed {
        try {
            return json_decode($json, $associative, $depth, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            self::logErrorAndThrow(InvalidArgumentException::class, "JSON-Dekodierung fehlgeschlagen: " . $e->getMessage());
        }
    }

    /**
     * Kodiert Daten zu JSON mit sicherer Fehlerbehandlung.
     *
     * @param mixed $data Die zu kodierenden Daten
     * @param int $flags JSON-Encoding-Flags
     * @param int $depth Maximale Verschachtelungstiefe
     * @return string Der JSON-String
     * @throws InvalidArgumentException Bei Encoding-Fehlern
     */
    public static function encode(mixed $data, int $flags = 0, int $depth = 512): string {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR | $flags, $depth);
        } catch (JsonException $e) {
            self::logErrorAndThrow(InvalidArgumentException::class, "JSON-Kodierung fehlgeschlagen: " . $e->getMessage());
        }
    }

    /**
     * Formatiert JSON für bessere Lesbarkeit (Pretty-Print).
     *
     * @param string $json Der JSON-String
     * @return string Der formatierte JSON-String
     * @throws InvalidArgumentException Bei ungültigem JSON
     */
    public static function prettyPrint(string $json): string {
        $data = self::decode($json);
        return self::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Minimiert JSON durch Entfernen überflüssiger Leerzeichen.
     *
     * @param string $json Der JSON-String
     * @return string Der minimierte JSON-String
     * @throws InvalidArgumentException Bei ungültigem JSON
     */
    public static function minify(string $json): string {
        $data = self::decode($json);
        return self::encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Extrahiert einen Wert aus JSON anhand eines Pfades (vereinfachtes JSONPath).
     *
     * @param string|array $json JSON-String oder bereits dekodierte Daten
     * @param string $path Pfad im Format 'data.transactions[0].amount'
     * @return mixed Der extrahierte Wert oder null wenn nicht gefunden
     */
    public static function extractPath(string|array $json, string $path): mixed {
        if (is_string($json)) {
            try {
                $data = self::decode($json);
            } catch (InvalidArgumentException) {
                return null;
            }
        } else {
            $data = $json;
        }

        $parts = self::parsePath($path);
        $current = $data;

        foreach ($parts as $part) {
            if (is_array($current)) {
                if ($part['type'] === 'property' && array_key_exists($part['key'], $current)) {
                    $current = $current[$part['key']];
                } elseif ($part['type'] === 'index' && isset($current[$part['index']])) {
                    $current = $current[$part['index']];
                } else {
                    return null;
                }
            } elseif (is_object($current) && $part['type'] === 'property' && property_exists($current, $part['key'])) {
                $current = $current->{$part['key']};
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Parst einen JSONPath-ähnlichen Pfad in einzelne Komponenten.
     *
     * @param string $path Der zu parsende Pfad
     * @return array<array{type: string, key?: string, index?: int}> Array von Pfad-Komponenten
     */
    private static function parsePath(string $path): array {
        $parts = [];
        $segments = explode('.', $path);

        foreach ($segments as $segment) {
            if (preg_match('/^(.+)\[(\d+)\]$/', $segment, $matches)) {
                // Property mit Array-Index: "transactions[0]"
                if (!empty($matches[1])) {
                    $parts[] = ['type' => 'property', 'key' => $matches[1]];
                }
                $parts[] = ['type' => 'index', 'index' => (int)$matches[2]];
            } else {
                // Einfache Property: "data"
                $parts[] = ['type' => 'property', 'key' => $segment];
            }
        }

        return $parts;
    }

    /**
     * Validiert JSON gegen ein JSON Schema (vereinfachte Implementierung).
     *
     * @param string $json Der zu validierende JSON-String
     * @param array $schema Das JSON Schema als Array
     * @return array{valid: bool, errors: string[]} Validierungsergebnis
     */
    public static function validateSchema(string $json, array $schema): array {
        try {
            $data = self::decode($json);
        } catch (InvalidArgumentException $e) {
            return [
                'valid' => false,
                'errors' => ['Ungültiger JSON: ' . $e->getMessage()]
            ];
        }

        $errors = [];
        self::validateValue($data, $schema, '', $errors);

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validiert einen Wert rekursiv gegen ein Schema.
     *
     * @param mixed $value Der zu validierende Wert
     * @param array $schema Das Schema
     * @param string $path Der aktuelle Pfad für Fehlermeldungen
     * @param array &$errors Array für gesammelte Fehler
     */
    private static function validateValue(mixed $value, array $schema, string $path, array &$errors): void {
        // Type-Validierung
        if (isset($schema['type'])) {
            $expectedType = $schema['type'];
            $actualType = self::getJsonType($value);

            if ($actualType !== $expectedType) {
                $errors[] = "Pfad '{$path}': Erwartet '{$expectedType}', erhalten '{$actualType}'";
                return;
            }
        }

        // Required Properties (für Objects)
        if ($expectedType === 'object' && isset($schema['required'])) {
            foreach ($schema['required'] as $requiredField) {
                if (!is_array($value) || !array_key_exists($requiredField, $value)) {
                    $errors[] = "Pfad '{$path}': Pflichtfeld '{$requiredField}' fehlt";
                }
            }
        }

        // Properties Validation (für Objects)
        if ($expectedType === 'object' && isset($schema['properties']) && is_array($value)) {
            foreach ($schema['properties'] as $prop => $propSchema) {
                if (array_key_exists($prop, $value)) {
                    $propPath = $path ? "{$path}.{$prop}" : $prop;
                    self::validateValue($value[$prop], $propSchema, $propPath, $errors);
                }
            }
        }

        // Items Validation (für Arrays)
        if ($expectedType === 'array' && isset($schema['items']) && is_array($value)) {
            foreach ($value as $index => $item) {
                $itemPath = "{$path}[{$index}]";
                self::validateValue($item, $schema['items'], $itemPath, $errors);
            }
        }
    }

    /**
     * Ermittelt den JSON-Typ eines PHP-Wertes.
     *
     * @param mixed $value Der Wert
     * @return string Der JSON-Typ
     */
    private static function getJsonType(mixed $value): string {
        return match (true) {
            is_null($value) => 'null',
            is_bool($value) => 'boolean',
            is_int($value), is_float($value) => 'number',
            is_string($value) => 'string',
            is_array($value) && array_is_list($value) => 'array',
            is_array($value) => 'object',
            is_object($value) => 'object',
            default => 'unknown'
        };
    }

    /**
     * Merged zwei JSON-Objekte rekursiv.
     *
     * @param string $json1 Erstes JSON-Objekt
     * @param string $json2 Zweites JSON-Objekt
     * @return string Das gemergete JSON-Objekt
     * @throws InvalidArgumentException Bei ungültigem JSON
     */
    public static function merge(string $json1, string $json2): string {
        $data1 = self::decode($json1);
        $data2 = self::decode($json2);

        if (!is_array($data1) || !is_array($data2)) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Beide JSON-Strings müssen Objekte repräsentieren');
        }

        $merged = array_replace_recursive($data1, $data2);
        return self::encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Filtert sensible Daten aus JSON für Logging/Debug-Zwecke.
     *
     * @param string $json Der JSON-String
     * @param array $sensitiveFields Array von Feldnamen die maskiert werden sollen
     * @param string $mask Der Maskierungsstring
     * @return string JSON mit maskierten sensitiven Feldern
     */
    public static function maskSensitiveData(string $json, array $sensitiveFields = ['password', 'pin', 'cvv', 'token'], string $mask = '***'): string {
        try {
            $data = self::decode($json);
            self::maskRecursive($data, $sensitiveFields, $mask);
            return self::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (InvalidArgumentException $e) {
            return self::logErrorAndReturn($json, "Fehler beim Maskieren sensitiver Daten: " . $e->getMessage());
        }
    }

    /**
     * Maskiert sensitive Felder rekursiv.
     *
     * @param mixed &$data Die Daten (by reference)
     * @param array $sensitiveFields Array von Feldnamen
     * @param string $mask Der Maskierungsstring
     */
    private static function maskRecursive(mixed &$data, array $sensitiveFields, string $mask): void {
        if (is_array($data)) {
            foreach ($data as $key => &$value) {
                if (in_array(strtolower((string)$key), array_map('strtolower', $sensitiveFields), true)) {
                    $value = $mask;
                } else {
                    self::maskRecursive($value, $sensitiveFields, $mask);
                }
            }
        }
    }
}
