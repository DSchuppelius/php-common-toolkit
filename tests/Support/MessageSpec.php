<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MessageSpec.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;

/**
 * Test-Hilfe: MAPI-Objekt (Nachricht, Empfänger oder Anhang) für den
 * {@see OutlookMessageWriter}. Eigenschaften über ihre ID (z. B. 0x0037 =
 * PR_SUBJECT); der Typ ergibt sich aus der Methode.
 */
final class MessageSpec {
    /** @var array<int, string> ID => UTF-8-Text (geschrieben als 001F oder 001E) */
    public array $strings = [];

    /** @var array<int, string> ID => Bytes (0102) */
    public array $binaries = [];

    /** @var array<int, int> ID => Wert (0003) */
    public array $integers = [];

    /** @var array<int, bool> ID => Wert (000B) */
    public array $booleans = [];

    /** @var array<int, DateTimeImmutable> ID => Zeitpunkt (0040) */
    public array $times = [];

    /** @var list<MessageSpec> */
    public array $recipients = [];

    /** @var list<MessageSpec> */
    public array $attachments = [];

    /** Eingebettete Nachricht (nur an einem Anhang). */
    public ?MessageSpec $embedded = null;

    public static function create(): self {
        return new self;
    }

    public function string(int $id, string $value): self {
        $this->strings[$id] = $value;

        return $this;
    }

    public function binary(int $id, string $value): self {
        $this->binaries[$id] = $value;

        return $this;
    }

    public function integer(int $id, int $value): self {
        $this->integers[$id] = $value;

        return $this;
    }

    public function boolean(int $id, bool $value): self {
        $this->booleans[$id] = $value;

        return $this;
    }

    public function time(int $id, DateTimeImmutable $value): self {
        $this->times[$id] = $value;

        return $this;
    }

    public function recipient(self $recipient): self {
        $this->recipients[] = $recipient;

        return $this;
    }

    public function attachment(self $attachment): self {
        $this->attachments[] = $attachment;

        return $this;
    }

    public function embed(self $message): self {
        $this->embedded = $message;

        return $this;
    }
}
