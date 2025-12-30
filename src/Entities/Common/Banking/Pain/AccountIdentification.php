<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AccountIdentification.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain;

use CommonToolkit\Enums\CurrencyCode;

/**
 * Account Identification für pain-Nachrichten.
 * 
 * Repräsentiert eine Kontoidentifikation gemäß ISO 20022.
 * Unterstützt sowohl IBAN als auch proprietäre Kontonummern.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain
 */
final readonly class AccountIdentification {
    public function __construct(
        private ?string $iban = null,
        private ?string $other = null,
        private ?CurrencyCode $currency = null,
        private ?string $name = null
    ) {
    }

    /**
     * Gibt die IBAN zurück.
     */
    public function getIban(): ?string {
        return $this->iban;
    }

    /**
     * Gibt die alternative Kontonummer zurück.
     */
    public function getOther(): ?string {
        return $this->other;
    }

    /**
     * Gibt die Währung zurück.
     */
    public function getCurrency(): ?CurrencyCode {
        return $this->currency;
    }

    /**
     * Gibt den Kontonamen zurück.
     */
    public function getName(): ?string {
        return $this->name;
    }

    /**
     * Gibt die Kontoidentifikation zurück (IBAN oder Other).
     */
    public function getIdentification(): ?string {
        return $this->iban ?? $this->other;
    }

    /**
     * Prüft ob es sich um eine IBAN handelt.
     */
    public function isIban(): bool {
        return $this->iban !== null;
    }

    /**
     * Erstellt eine Kontoidentifikation aus IBAN.
     */
    public static function fromIban(string $iban, ?CurrencyCode $currency = null): self {
        return new self(iban: $iban, currency: $currency);
    }

    /**
     * Erstellt eine Kontoidentifikation aus proprietärer Kontonummer.
     */
    public static function fromOther(string $other, ?CurrencyCode $currency = null): self {
        return new self(other: $other, currency: $currency);
    }
}
