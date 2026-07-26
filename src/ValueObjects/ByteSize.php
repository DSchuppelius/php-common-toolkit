<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ByteSize.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Helper\Data\NumberHelper;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;
use Throwable;

/**
 * Immutable, exakte Datenmenge in Bytes.
 *
 * Ersetzt rohe Integers mit unklaren Einheiten: Der Zustand ist eine
 * nicht-negative Ganzzahl Bytes, gerechnet wird ohne float-Zwischenschritte.
 * Formatierung und Parsen delegieren an {@see NumberHelper::formatBytes()}
 * bzw. {@see NumberHelper::parseByteString()} (1024er-Basis).
 *
 * Achtung Helper-Vertrag: {@see parse()} verlangt eine Einheit
 * ("1024" ohne Einheit wirft); {@see tryParse()} liefert dafür `null`.
 *
 * @example
 * ```php
 * $size = ByteSize::parse('1,5 GB');
 * $size->getBytes();                          // 1610612736
 * $size->plus(ByteSize::parse('1 MB'))->format(); // "1.5 GB"
 * ByteSize::ofBytes(1572864)->format();       // "1.5 MB"
 * ```
 */
final class ByteSize implements JsonSerializable, Stringable {
    use ErrorLog;

    /** Nicht-negative Anzahl Bytes. */
    private readonly int $bytes;

    private function __construct(int $bytes) {
        $this->bytes = $bytes;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt eine Datenmenge aus Bytes.
     *
     * @throws InvalidArgumentException Bei negativen Werten.
     */
    public static function ofBytes(int $bytes): self {
        if ($bytes < 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Datenmenge darf nicht negativ sein: $bytes");
        }

        return new self($bytes);
    }

    /**
     * Parst eine Größenangabe mit Einheit ("1.5 MB", "1,5 GB"; 1024er-Basis).
     * Delegiert an {@see NumberHelper::parseByteString()} — dessen
     * Exception-Verhalten bleibt sichtbar (Einheit ist Pflicht).
     *
     * @throws \RuntimeException Bei nicht deutbarem Format (Verhalten des Helpers).
     */
    public static function parse(string $input): self {
        return self::ofBytes(NumberHelper::parseByteString($input));
    }

    /**
     * Wie {@see parse()}, liefert aber bei `null`, leerer oder nicht
     * deutbarer Eingabe `null` statt einer Exception.
     */
    public static function tryParse(?string $input): ?self {
        if ($input === null || trim($input) === '') {
            return null;
        }

        try {
            return self::parse($input);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Leere Datenmenge (0 Bytes).
     */
    public static function zero(): self {
        return new self(0);
    }

    /**
     * Summiert Datenmengen (leere Liste → 0 Bytes).
     *
     * @param iterable<self> $sizes
     */
    public static function sum(iterable $sizes): self {
        $total = 0;
        foreach ($sizes as $size) {
            $total += $size->bytes;
        }

        return new self($total);
    }

    // ========================================================================
    // Arithmetik (liefert stets eine neue Instanz)
    // ========================================================================

    public function plus(self $other): self {
        return new self($this->bytes + $other->bytes);
    }

    /**
     * Subtraktion. Datenmengen sind nie negativ — ein Unterlauf wirft.
     *
     * @throws InvalidArgumentException Bei Ergebnis < 0.
     */
    public function minus(self $other): self {
        $result = $this->bytes - $other->bytes;
        if ($result < 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Datenmenge darf nicht negativ werden: {$this->bytes} - {$other->bytes}");
        }

        return new self($result);
    }

    /**
     * Vervielfachung mit einem nicht-negativen Faktor.
     *
     * @throws InvalidArgumentException Bei negativem Faktor.
     */
    public function times(int $factor): self {
        if ($factor < 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Faktor darf nicht negativ sein: $factor");
        }

        return new self($this->bytes * $factor);
    }

    // ========================================================================
    // Vergleich
    // ========================================================================

    /**
     * @return int -1, 0 oder 1.
     */
    public function compareTo(self $other): int {
        return $this->bytes <=> $other->bytes;
    }

    public function equals(self $other): bool {
        return $this->bytes === $other->bytes;
    }

    public function isZero(): bool {
        return $this->bytes === 0;
    }

    // ========================================================================
    // Zugriff / Formatierung / Serialisierung
    // ========================================================================

    public function getBytes(): int {
        return $this->bytes;
    }

    /**
     * Menschenlesbar formatiert (1024er-Basis, z.B. "1.5 MB"), delegiert an
     * {@see NumberHelper::formatBytes()}.
     */
    public function format(int $precision = 2): string {
        return NumberHelper::formatBytes($this->bytes, $precision);
    }

    /**
     * Maschinenlesbare Darstellung: "1572864 B".
     */
    public function __toString(): string {
        return $this->bytes . ' B';
    }

    /**
     * Bytes als Ganzzahl (verlustfrei).
     */
    public function jsonSerialize(): int {
        return $this->bytes;
    }
}
