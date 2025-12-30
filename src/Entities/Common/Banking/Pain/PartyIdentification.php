<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PartyIdentification.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain;

use CommonToolkit\Enums\CountryCode;

/**
 * Party Identification für pain-Nachrichten.
 * 
 * Repräsentiert eine Partei (Debtor, Creditor, Initiating Party) 
 * gemäß ISO 20022 PartyIdentification-Schema.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain
 */
final readonly class PartyIdentification {
    public function __construct(
        private ?string $name = null,
        private ?PostalAddress $postalAddress = null,
        private ?string $organisationId = null,
        private ?string $privateId = null,
        private ?string $bic = null,
        private ?string $lei = null,
        private ?CountryCode $countryOfResidence = null
    ) {
    }

    /**
     * Gibt den Namen zurück (Nm).
     */
    public function getName(): ?string {
        return $this->name;
    }

    /**
     * Gibt die Postadresse zurück (PstlAdr).
     */
    public function getPostalAddress(): ?PostalAddress {
        return $this->postalAddress;
    }

    /**
     * Gibt die Organisations-ID zurück (OrgId).
     */
    public function getOrganisationId(): ?string {
        return $this->organisationId;
    }

    /**
     * Gibt die Privat-ID zurück (PrvtId).
     */
    public function getPrivateId(): ?string {
        return $this->privateId;
    }

    /**
     * Gibt den BIC zurück (AnyBIC).
     */
    public function getBic(): ?string {
        return $this->bic;
    }

    /**
     * Gibt die LEI zurück (Legal Entity Identifier).
     */
    public function getLei(): ?string {
        return $this->lei;
    }

    /**
     * Gibt das Wohnsitzland zurück (CtryOfRes).
     */
    public function getCountryOfResidence(): ?CountryCode {
        return $this->countryOfResidence;
    }

    /**
     * Prüft ob die Partei gültig ist (mindestens Name oder ID).
     */
    public function isValid(): bool {
        return $this->name !== null
            || $this->organisationId !== null
            || $this->privateId !== null;
    }

    /**
     * Erstellt eine einfache Party mit nur Name.
     */
    public static function fromName(string $name): self {
        return new self(name: $name);
    }

    /**
     * Erstellt eine Party mit Name und BIC.
     */
    public static function fromNameAndBic(string $name, string $bic): self {
        return new self(name: $name, bic: $bic);
    }
}
