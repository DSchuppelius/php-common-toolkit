<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MtDocumentAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Abstracts\Common\Banking\Mt9;

use CommonToolkit\Enums\Common\Banking\MtType;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;

/**
 * Abstrakte Basisklasse für MT9xx-Dokumente (SWIFT Cash Management).
 * 
 * Gemeinsame Eigenschaften aller MT9-Nachrichtentypen:
 * - Account-Informationen
 * - Referenz-ID
 * - Auszugsnummer
 * - Währung
 * 
 * @package CommonToolkit\Contracts\Abstracts\Common\Banking\Mt9
 */
abstract class MtDocumentAbstract {
    protected string $accountId;
    protected string $referenceId;
    protected string $statementNumber;
    protected CurrencyCode $currency;
    protected DateTimeImmutable $creationDateTime;

    public function __construct(
        string $accountId,
        string $referenceId,
        string $statementNumber,
        CurrencyCode $currency,
        ?DateTimeImmutable $creationDateTime = null
    ) {
        $this->accountId = $accountId;
        $this->referenceId = $referenceId;
        $this->statementNumber = $statementNumber;
        $this->currency = $currency;
        $this->creationDateTime = $creationDateTime ?? new DateTimeImmutable();
    }

    /**
     * Gibt den MT-Nachrichtentyp zurück.
     */
    abstract public function getMtType(): MtType;

    /**
     * Gibt die Kontonummer/IBAN zurück.
     * Feld :25: in SWIFT-Notation.
     */
    public function getAccountId(): string {
        return $this->accountId;
    }

    /**
     * Gibt die Transaktionsreferenz zurück.
     * Feld :20: in SWIFT-Notation.
     */
    public function getReferenceId(): string {
        return $this->referenceId;
    }

    /**
     * Gibt die Auszugsnummer/Folgenummer zurück.
     * Feld :28C: in SWIFT-Notation.
     */
    public function getStatementNumber(): string {
        return $this->statementNumber;
    }

    /**
     * Gibt die Währung zurück.
     */
    public function getCurrency(): CurrencyCode {
        return $this->currency;
    }

    /**
     * Gibt das Erstellungsdatum zurück.
     */
    public function getCreationDateTime(): DateTimeImmutable {
        return $this->creationDateTime;
    }

    /**
     * Gibt die Anzahl der Einträge zurück.
     */
    abstract public function countEntries(): int;

    /**
     * Serialisiert das Dokument im SWIFT MT-Format.
     */
    abstract public function __toString(): string;
}
