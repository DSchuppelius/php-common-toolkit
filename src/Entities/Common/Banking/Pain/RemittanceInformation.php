<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : RemittanceInformation.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain;

/**
 * Remittance Information für pain-Nachrichten.
 * 
 * Verwendungszweck einer Zahlung. Kann entweder unstrukturiert (Freitext)
 * oder strukturiert (Referenznummern, Rechnungsdaten) sein.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain
 */
final readonly class RemittanceInformation {
    /**
     * @param string[] $unstructured Unstrukturierte Verwendungszweckzeilen
     */
    public function __construct(
        private array $unstructured = [],
        private ?string $creditorReference = null,
        private ?string $creditorReferenceType = null
    ) {
    }

    /**
     * Gibt die unstrukturierten Verwendungszweckzeilen zurück (Ustrd).
     * @return string[]
     */
    public function getUnstructured(): array {
        return $this->unstructured;
    }

    /**
     * Gibt den Verwendungszweck als einzelnen String zurück.
     */
    public function getUnstructuredString(): string {
        return implode(' ', $this->unstructured);
    }

    /**
     * Gibt die strukturierte Gläubigerreferenz zurück (CdtrRef/Ref).
     * Z.B. ISO 11649 Referenz (RF-Referenz).
     */
    public function getCreditorReference(): ?string {
        return $this->creditorReference;
    }

    /**
     * Gibt den Typ der Gläubigerreferenz zurück (CdtrRef/Tp).
     * Z.B. "SCOR" für ISO 11649.
     */
    public function getCreditorReferenceType(): ?string {
        return $this->creditorReferenceType;
    }

    /**
     * Prüft ob strukturierte Informationen vorhanden sind.
     */
    public function hasStructured(): bool {
        return $this->creditorReference !== null;
    }

    /**
     * Erstellt aus unstrukturiertem Text.
     */
    public static function fromText(string $text): self {
        // Splitten in max. 140 Zeichen pro Zeile (pain.001 Limit)
        $lines = str_split($text, 140);
        return new self(unstructured: $lines);
    }

    /**
     * Erstellt mit ISO 11649 Gläubigerreferenz (RF-Referenz).
     */
    public static function fromCreditorReference(string $reference): self {
        return new self(
            creditorReference: $reference,
            creditorReferenceType: 'SCOR'
        );
    }

    /**
     * Erstellt mit Text und Referenz kombiniert.
     */
    public static function create(string $text, ?string $creditorReference = null): self {
        $lines = str_split($text, 140);
        return new self(
            unstructured: $lines,
            creditorReference: $creditorReference,
            creditorReferenceType: $creditorReference !== null ? 'SCOR' : null
        );
    }
}
