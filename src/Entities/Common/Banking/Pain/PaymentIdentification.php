<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentIdentification.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain;

/**
 * Payment Identification für pain-Nachrichten.
 * 
 * Enthält die Referenzen zur Identifikation einer Zahlung:
 * - InstrId: Instruction Identification (optional, intern)
 * - EndToEndId: End-to-End Identification (Pflicht, durchgängig)
 * - UETR: Unique End-to-End Transaction Reference (optional, UUID)
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain
 */
final readonly class PaymentIdentification {
    public function __construct(
        private string $endToEndId,
        private ?string $instructionId = null,
        private ?string $uetr = null
    ) {
    }

    /**
     * Gibt die End-to-End-ID zurück (EndToEndId).
     * Dies ist die durchgängige Referenz, die bis zum Empfänger transportiert wird.
     */
    public function getEndToEndId(): string {
        return $this->endToEndId;
    }

    /**
     * Gibt die Instruction-ID zurück (InstrId).
     * Dies ist die interne Referenz zwischen Auftraggeber und Bank.
     */
    public function getInstructionId(): ?string {
        return $this->instructionId;
    }

    /**
     * Gibt die UETR zurück (Unique End-to-End Transaction Reference).
     * UUID v4 Format für gpi-Tracking.
     */
    public function getUetr(): ?string {
        return $this->uetr;
    }

    /**
     * Generiert eine neue UETR (UUID v4).
     */
    public static function generateUetr(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Erstellt eine PaymentIdentification mit nur EndToEndId.
     */
    public static function fromEndToEndId(string $endToEndId): self {
        return new self(endToEndId: $endToEndId);
    }

    /**
     * Erstellt eine PaymentIdentification mit EndToEndId und InstructionId.
     */
    public static function create(string $endToEndId, ?string $instructionId = null): self {
        return new self(
            endToEndId: $endToEndId,
            instructionId: $instructionId
        );
    }

    /**
     * Erstellt eine PaymentIdentification mit generierter UETR.
     */
    public static function withUetr(string $endToEndId, ?string $instructionId = null): self {
        return new self(
            endToEndId: $endToEndId,
            instructionId: $instructionId,
            uetr: self::generateUetr()
        );
    }
}
