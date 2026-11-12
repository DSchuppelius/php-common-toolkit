<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Recipient.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Mail;

use CommonToolkit\Enums\Mail\RecipientType;

/**
 * Empfänger einer Nachricht: Anzeigename, Adresse und Art (An/Cc/Bcc).
 * Für die Adresse gilt dasselbe wie bei {@see Address}: wie gelesen, bevorzugt
 * SMTP, nicht validiert.
 */
final class Recipient {
    public function __construct(
        private readonly string $name,
        private readonly string $address,
        private readonly RecipientType $type,
    ) {}

    /** Anzeigename, '' wenn keiner angegeben ist. */
    public function getName(): string {
        return $this->name;
    }

    /** Adresse (bevorzugt SMTP), '' wenn keine bekannt ist. */
    public function getAddress(): string {
        return $this->address;
    }

    public function getType(): RecipientType {
        return $this->type;
    }
}
