<?php
/*
 * Created on   : Tue Oct 28 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVField.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Entities\Common\CSV;

class CSVField {
    public const DEFAULT_ENCLOSURE = '"';

    private string $value;
    private bool $quoted = false;
    private int $enclosureRepeat = 0;
    private ?string $raw;

    public function __construct(string $raw, string $enclosure = self::DEFAULT_ENCLOSURE) {
        $this->raw = $raw;
        $this->analyze($raw, $enclosure);
    }

    private function analyze(string $raw, string $enclosure): void {
        $enc = preg_quote($enclosure, '/');
        $trimmed = trim($raw);

        // Frühzeitiger Sonderfall: reines Quote-Feld
        // Beispiele: "", """", """""", usw.
        if (preg_match('/^' . $enc . '+$/', $trimmed)) {
            $this->quoted = true;
            $this->value = '';
            $this->enclosureRepeat = intdiv(strlen($trimmed), 2);
            return;
        }

        $matches = [];

        if (preg_match('/^(' . $enc . '+)(.*?)(?:' . $enc . '+)$/s', $trimmed, $matches)) {
            $this->quoted = true;

            $startRun = strlen($matches[1]);
            $endRun = 0;
            if (preg_match('/(' . $enc . '+)$/', $trimmed, $endMatch)) {
                $endRun = strlen($endMatch[1]);
            }

            // Leeres Feld mit symmetrischen Quotes → intdiv
            if (trim($matches[2]) === '' && $startRun === $endRun) {
                $this->enclosureRepeat = intdiv($startRun, 2);
                $this->value = '';
                return;
            }

            $this->enclosureRepeat = min($startRun, $endRun);

            $inner = $matches[2];

            // Asymmetrische Quote-Runs ausgleichen
            if ($startRun > $endRun) {
                $inner = str_repeat($enclosure, $startRun - $endRun) . $inner;
            } elseif ($endRun > $startRun) {
                $inner = $inner . str_repeat($enclosure, $endRun - $startRun);
            }

            // Bei einfachem Quote-Level doppelte Quotes im Inneren entescapen
            if ($this->enclosureRepeat === 1) {
                $inner = str_replace($enclosure . $enclosure, $enclosure, $inner);
            }

            $this->value = $inner;
        } else {
            // Unquoted Field
            $this->quoted = false;
            $this->enclosureRepeat = 0;
            $this->value = trim($raw);
        }
    }

    public function isQuoted(): bool {
        return $this->quoted;
    }

    public function getEnclosureRepeat(): int {
        return $this->enclosureRepeat;
    }

    public function getValue(): string {
        return $this->value;
    }

    public function getRaw(): ?string {
        return $this->raw;
    }

    public function toString(string $enclosure = self::DEFAULT_ENCLOSURE): string {
        $quoteLevel = max(1, $this->enclosureRepeat);

        if ($this->quoted) {
            $enc = str_repeat($enclosure, $quoteLevel);
            $value = $this->value;

            // Nur bei einfachem Quote-Level innerhalb des Werts escapen
            if ($this->enclosureRepeat === 1) {
                $value = str_replace($enclosure, $enclosure . $enclosure, $value);
            }

            return $enc . $value . $enc;
        }

        return $this->value;
    }
}