<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BasicHeader.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Swift;

/**
 * SWIFT FIN Basic Header Block (Block 1)
 * 
 * Format: {1:F01BANKBEBB2222123456}
 * - Application ID (F = FIN, A = GPA, L = GPA Delayed)
 * - Service ID (01 = FIN/GPA, 21 = ACK/NAK)
 * - Logical Terminal Address (BIC + Terminal Code)
 * - Session Number (4 digits)
 * - Sequence Number (6 digits)
 */
final class BasicHeader {
    public function __construct(
        private readonly string $applicationId,
        private readonly string $serviceId,
        private readonly string $logicalTerminalAddress,
        private readonly ?string $sessionNumber = null,
        private readonly ?string $sequenceNumber = null
    ) {
    }

    public function getApplicationId(): string {
        return $this->applicationId;
    }

    public function getServiceId(): string {
        return $this->serviceId;
    }

    public function getLogicalTerminalAddress(): string {
        return $this->logicalTerminalAddress;
    }

    /**
     * Extrahiert den BIC aus der Logical Terminal Address
     */
    public function getBic(): string {
        return substr($this->logicalTerminalAddress, 0, 8);
    }

    /**
     * Extrahiert den Terminal Code aus der Logical Terminal Address
     */
    public function getTerminalCode(): string {
        return substr($this->logicalTerminalAddress, 8, 1);
    }

    /**
     * Extrahiert die Branch aus der Logical Terminal Address (letzte 3 Zeichen)
     */
    public function getBranch(): string {
        return substr($this->logicalTerminalAddress, 9, 3);
    }

    public function getSessionNumber(): ?string {
        return $this->sessionNumber;
    }

    public function getSequenceNumber(): ?string {
        return $this->sequenceNumber;
    }

    /**
     * Prüft ob es sich um eine FIN-Nachricht handelt
     */
    public function isFin(): bool {
        return $this->applicationId === 'F';
    }

    /**
     * Prüft ob es sich um eine GPA-Nachricht handelt
     */
    public function isGpa(): bool {
        return $this->applicationId === 'A';
    }

    /**
     * Gibt den vollständigen Block 1 String zurück
     */
    public function __toString(): string {
        $result = $this->applicationId . $this->serviceId . $this->logicalTerminalAddress;
        if ($this->sessionNumber !== null) {
            $result .= $this->sessionNumber;
        }
        if ($this->sequenceNumber !== null) {
            $result .= $this->sequenceNumber;
        }
        return '{1:' . $result . '}';
    }

    /**
     * Parst einen Block 1 String
     * 
     * @param string $raw Roher Block-Inhalt (ohne {1: und })
     */
    public static function parse(string $raw): self {
        // Format: F01BANKBEBB2222123456
        // F = AppID (1), 01 = ServiceID (2), BANKBEBB2222 = LT Address (12), 1234 = Session (4), 56 = Sequence (6)
        $appId = substr($raw, 0, 1);
        $serviceId = substr($raw, 1, 2);
        $ltAddress = substr($raw, 3, 12);

        $sessionNumber = null;
        $sequenceNumber = null;

        if (strlen($raw) > 15) {
            $sessionNumber = substr($raw, 15, 4);
        }
        if (strlen($raw) > 19) {
            $sequenceNumber = substr($raw, 19);
        }

        return new self(
            applicationId: $appId,
            serviceId: $serviceId,
            logicalTerminalAddress: $ltAddress,
            sessionNumber: $sessionNumber,
            sequenceNumber: $sequenceNumber
        );
    }
}
