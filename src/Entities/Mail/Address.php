<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Address.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Mail;

/**
 * Absender einer Nachricht: Anzeigename und Adresse, beide wie gelesen.
 *
 * Bewusst kein {@see \CommonToolkit\ValueObjects\EmailAddress}: die Adresse
 * ist nicht validiert und kann leer sein oder — wenn die Quelle keine
 * SMTP-Adresse hergibt — eine andere Adressform tragen (z. B. eine
 * Exchange-Adresse "/O=…/CN=…"). Wer eine geprüfte Adresse braucht, nimmt
 * `EmailAddress::tryFrom($address->getAddress())`.
 *
 * SENSIBLE WERTE: Name und Adresse sind personenbezogen; die Klasse
 * implementiert deshalb weder `Stringable` noch `JsonSerializable`.
 */
final class Address {
    public function __construct(
        private readonly string $name,
        private readonly string $address,
    ) {}

    /** Anzeigename, '' wenn keiner angegeben ist. */
    public function getName(): string {
        return $this->name;
    }

    /** Adresse (bevorzugt SMTP), '' wenn keine bekannt ist. */
    public function getAddress(): string {
        return $this->address;
    }
}
