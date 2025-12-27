<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Reference.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Mt9;

use RuntimeException;

/**
 * Gemeinsame Referenz-Klasse für alle MT9xx-Nachrichtentypen.
 * 
 * Repräsentiert die Referenz in Feld :61: einer SWIFT MT-Nachricht.
 * Format: [N][Transaktionscode 3 Zeichen][Referenz max. 16 Zeichen]//[Bankreferenz]
 * 
 * @package CommonToolkit\Entities\Common\Banking\Mt9
 */
class Reference {
    private string $transactionCode;
    private string $reference;
    private ?string $bankReference;

    /**
     * @param string $transactionCode 3-stelliger Transaktionscode (z.B. TRF, CHK)
     * @param string $reference Kundenreferenz (max. 16 Zeichen inkl. Code)
     * @param string|null $bankReference Bankreferenz nach //
     */
    public function __construct(string $transactionCode, string $reference, ?string $bankReference = null) {
        $combined = $transactionCode . $reference;
        if (strlen($combined) > 16) {
            throw new RuntimeException("MT9xx-Referenzüberschreitung: max. 16 Zeichen erlaubt, übergeben: " . $combined);
        }

        $this->transactionCode = $transactionCode;
        $this->reference = $reference;
        $this->bankReference = $bankReference;
    }

    /**
     * Gibt den Transaktionscode zurück (z.B. TRF, CHK, BOE).
     */
    public function getTransactionCode(): string {
        return $this->transactionCode;
    }

    /**
     * Gibt die Kundenreferenz zurück.
     */
    public function getReference(): string {
        return $this->reference;
    }

    /**
     * Gibt die Bankreferenz zurück (nach //).
     */
    public function getBankReference(): ?string {
        return $this->bankReference;
    }

    /**
     * Serialisiert im SWIFT MT-Format.
     * Format: N[Code][Referenz]//[Bankreferenz]
     */
    public function __toString(): string {
        $result = 'N' . $this->transactionCode . $this->reference;
        if ($this->bankReference !== null) {
            $result .= '//' . $this->bankReference;
        }
        return $result;
    }

    /**
     * Parst eine Referenz aus einem SWIFT MT :61: Feld.
     */
    public static function fromSwiftField(string $field): self {
        $bankReference = null;

        // Bankreferenz nach // extrahieren
        if (str_contains($field, '//')) {
            [$field, $bankReference] = explode('//', $field, 2);
        }

        // N + 3-stelliger Code + Referenz
        if (preg_match('/^N?([A-Z]{3})(.*)$/', $field, $matches)) {
            return new self($matches[1], $matches[2], $bankReference);
        }

        // Fallback: Alles als Referenz
        return new self('TRF', $field, $bankReference);
    }
}
