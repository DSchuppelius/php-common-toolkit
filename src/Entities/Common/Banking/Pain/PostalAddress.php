<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PostalAddress.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain;

use CommonToolkit\Enums\CountryCode;

/**
 * Postal Address für pain-Nachrichten.
 * 
 * Repräsentiert eine Postadresse gemäß ISO 20022 PostalAddress-Schema.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain
 */
final readonly class PostalAddress {
    /**
     * @param string[] $addressLines
     */
    public function __construct(
        private ?string $streetName = null,
        private ?string $buildingNumber = null,
        private ?string $postCode = null,
        private ?string $townName = null,
        private ?CountryCode $country = null,
        private array $addressLines = [],
        private ?string $department = null,
        private ?string $subDepartment = null
    ) {
    }

    public function getStreetName(): ?string {
        return $this->streetName;
    }

    public function getBuildingNumber(): ?string {
        return $this->buildingNumber;
    }

    public function getPostCode(): ?string {
        return $this->postCode;
    }

    public function getTownName(): ?string {
        return $this->townName;
    }

    public function getCountry(): ?CountryCode {
        return $this->country;
    }

    /**
     * @return string[]
     */
    public function getAddressLines(): array {
        return $this->addressLines;
    }

    public function getDepartment(): ?string {
        return $this->department;
    }

    public function getSubDepartment(): ?string {
        return $this->subDepartment;
    }

    /**
     * Gibt die formatierte Adresse als String zurück.
     */
    public function format(): string {
        $lines = [];

        if ($this->department !== null) {
            $lines[] = $this->department;
        }

        if ($this->streetName !== null) {
            $street = $this->streetName;
            if ($this->buildingNumber !== null) {
                $street .= ' ' . $this->buildingNumber;
            }
            $lines[] = $street;
        }

        if (!empty($this->addressLines)) {
            $lines = array_merge($lines, $this->addressLines);
        }

        if ($this->postCode !== null || $this->townName !== null) {
            $cityLine = trim(($this->postCode ?? '') . ' ' . ($this->townName ?? ''));
            $lines[] = $cityLine;
        }

        if ($this->country !== null) {
            $lines[] = $this->country->value;
        }

        return implode("\n", $lines);
    }

    /**
     * Erstellt eine einfache Adresse.
     */
    public static function simple(
        string $streetName,
        string $buildingNumber,
        string $postCode,
        string $townName,
        CountryCode $country
    ): self {
        return new self(
            streetName: $streetName,
            buildingNumber: $buildingNumber,
            postCode: $postCode,
            townName: $townName,
            country: $country
        );
    }
}
