<?php
/*
 * Created on   : Mon Sep 28 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Property.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\ICalendar;

/**
 * Content line of an iCalendar component (RFC 5545 §3.1): name, parameters and
 * the raw value. Text values are unescaped on access via {@see getText()}.
 */
final class Property {
    /** @param array<string, string> $parameters upper-case parameter names */
    public function __construct(
        private readonly string $name,
        private readonly string $value,
        private readonly array $parameters = [],
    ) {}

    public function getName(): string {
        return $this->name;
    }

    /** Raw value as written in the file (escapes preserved). */
    public function getValue(): string {
        return $this->value;
    }

    /** @return array<string, string> */
    public function getParameters(): array {
        return $this->parameters;
    }

    public function getParameter(string $name): ?string {
        return $this->parameters[strtoupper($name)] ?? null;
    }

    /** TEXT value with RFC 5545 escapes resolved (\n, \, \; \\). */
    public function getText(): string {
        return self::unescape($this->value);
    }

    /**
     * Value split at unescaped commas (multi-value properties such as
     * CATEGORIES or EXDATE), each part unescaped and trimmed.
     *
     * @return list<string>
     */
    public function getParts(): array {
        $parts = preg_split('/(?<!\\\\),/', $this->value) ?: [];

        return array_values(array_filter(array_map(static fn (string $part): string => trim(self::unescape($part)), $parts), static fn (string $part): bool => $part !== ''));
    }

    private static function unescape(string $value): string {
        return strtr($value, ['\\n' => "\n", '\\N' => "\n", '\\,' => ',', '\;' => ';', '\\\\' => '\\']);
    }
}
