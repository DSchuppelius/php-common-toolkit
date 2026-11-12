<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EpcQrPayment.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Payment;

use CommonToolkit\Enums\Payment\EpcQrCharset;

/**
 * Inhalt eines gelesenen EPC-QR-Codes (Girocode, EPC069-12), erzeugt von
 * {@see \CommonToolkit\Parsers\Payment\EpcQrParser}. Unveränderlich; alle Texte
 * sind UTF-8, leere optionale Felder sind null.
 *
 * Die IBAN wird mit gültigem Aufbau, aber auch mit falscher Prüfziffer
 * geliefert — ob sie gültig ist, sagt {@see isIbanValid()}. Ein Aufrufer, der
 * eine Zahlung vorbereitet, muss diesen Wert prüfen; der Leser verschweigt
 * eine kaputte IBAN nicht, verwirft den Code deswegen aber auch nicht.
 *
 * SENSIBLE WERTE: IBAN und Name sind Kontodaten. Die Klasse implementiert
 * deshalb weder `Stringable` noch `JsonSerializable`.
 */
final class EpcQrPayment {
    /**
     * @param string      $version     "001" oder "002"
     * @param string      $iban        Normalisiert (ohne Leerraum, Großschreibung)
     * @param bool        $ibanValid   Aufbau, Länderlänge und Prüfziffer gültig
     * @param string|null $amount      Betrag in EUR mit zwei Nachkommastellen ("1234.50")
     */
    public function __construct(
        private readonly string $version,
        private readonly EpcQrCharset $charset,
        private readonly ?string $bic,
        private readonly string $name,
        private readonly string $iban,
        private readonly bool $ibanValid,
        private readonly ?string $amount,
        private readonly ?string $purpose,
        private readonly ?string $reference,
        private readonly ?string $remittance,
        private readonly ?string $note,
    ) {}

    /** "001" (BIC Pflicht) oder "002" (BIC optional). */
    public function getVersion(): string {
        return $this->version;
    }

    /** Zeichensatz, in dem die Nutzlast kodiert war; die Texte hier sind immer UTF-8. */
    public function getCharset(): EpcQrCharset {
        return $this->charset;
    }

    /** BIC in Großschreibung, null wenn nicht angegeben (nur ab Version 002 erlaubt). */
    public function getBic(): ?string {
        return $this->bic;
    }

    /** Name des Empfängers. */
    public function getName(): string {
        return $this->name;
    }

    /** IBAN des Empfängers, normalisiert; Gültigkeit über {@see isIbanValid()}. */
    public function getIban(): string {
        return $this->iban;
    }

    /** Aufbau, Länderlänge und Prüfziffer der IBAN sind gültig (MOD 97-10). */
    public function isIbanValid(): bool {
        return $this->ibanValid;
    }

    /**
     * Betrag in EUR als Dezimal-String mit genau zwei Nachkommastellen
     * ("1234.50"), null wenn der Code keinen Betrag vorgibt. Bewusst kein
     * float: der Wert geht verlustfrei an `Decimal::of()` oder `Money::of()`.
     */
    public function getAmount(): ?string {
        return $this->amount;
    }

    /** Purpose-Code (vier Zeichen, z. B. "GDDS"). */
    public function getPurpose(): ?string {
        return $this->purpose;
    }

    /** Strukturierte Referenz (z. B. RF-Creditor-Reference); schließt {@see getRemittance()} aus. */
    public function getReference(): ?string {
        return $this->reference;
    }

    /** Unstrukturierter Verwendungszweck; schließt {@see getReference()} aus. */
    public function getRemittance(): ?string {
        return $this->remittance;
    }

    /** Hinweis an den Zahler (wird nicht mit der Überweisung übertragen). */
    public function getNote(): ?string {
        return $this->note;
    }
}
