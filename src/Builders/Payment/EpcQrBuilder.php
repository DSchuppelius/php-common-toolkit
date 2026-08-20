<?php
/*
 * Created on   : Thu Aug 20 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EpcQrBuilder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Builders\Payment;

use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Helper\Data\BankHelper;
use InvalidArgumentException;

/**
 * Nutzlast eines EPC-QR-Codes („Girocode", EPC069-12 v3.1) — der Textinhalt,
 * den ein Banking-App-Scanner erwartet.
 *
 * Bewusst NUR die Nutzlast, kein Bild: Die Grafik erzeugt jede QR-Bibliothek
 * aus diesem String, und ein Renderer würde dem Toolkit eine Abhängigkeit
 * aufzwingen, die die meisten Aufrufer schon mitbringen.
 *
 * Aufbau (feste Zeilenfolge, LF-getrennt):
 *
 * ```
 *  1 BCD                 Service-Tag
 *  2 002                 Version (002 = BIC optional)
 *  3 1|2                 Zeichensatz (1 = UTF-8, 2 = ISO-8859-1)
 *  4 SCT                 Identifikation (SEPA Credit Transfer)
 *  5 <BIC>               optional ab Version 002
 *  6 <Empfängername>     max. 70 Zeichen
 *  7 <IBAN>
 *  8 EUR<Betrag>         optional; Punkt als Dezimaltrenner, 0.01–999999999.99
 *  9 <Zweck-Code>        optional (Purpose, 4 Zeichen)
 * 10 <Strukturierte Referenz>  optional  ─┐ nur EINES von beiden
 * 11 <Verwendungszweck>         optional ─┘
 * 12 <Hinweis für den Zahler>   optional, max. 70 Zeichen
 * ```
 *
 * Harte Grenzen der Spezifikation, die hier geprüft werden: 331 Byte
 * Gesamtlänge, Euro-only, und der Ausschluss von Referenz *und* freiem
 * Verwendungszweck — ein Code, der beides trägt, wird von Scannern
 * abgelehnt, und zwar erst beim Nutzer.
 */
final class EpcQrBuilder {
    /** Maximale Nutzlast laut EPC069-12 (Byte, nicht Zeichen). */
    public const MAX_BYTES = 331;

    private string $bic = '';

    private string $purpose = '';

    private string $reference = '';

    private string $remittance = '';

    private string $note = '';

    private ?string $amount = null;

    private bool $utf8 = true;

    private function __construct(
        private readonly string $name,
        private readonly string $iban,
    ) {}

    /**
     * @param string $name Empfängername (max. 70 Zeichen)
     * @param string $iban IBAN des Empfängers
     */
    public static function to(string $name, string $iban): self {
        $trimmedName = trim($name);
        if ($trimmedName === '' || mb_strlen($trimmedName) > 70) {
            throw new InvalidArgumentException('EPC-QR: Empfängername fehlt oder ist länger als 70 Zeichen.');
        }

        $normalized = BankHelper::normalizeIBAN($iban);
        if ($normalized === null || !BankHelper::validateIBAN($normalized, true)) {
            throw new InvalidArgumentException('EPC-QR: ungültige IBAN.');
        }

        return new self($trimmedName, $normalized);
    }

    public function bic(?string $bic): self {
        $value = strtoupper(trim((string) $bic));
        if ($value !== '' && !BankHelper::isBIC($value)) {
            throw new InvalidArgumentException('EPC-QR: ungültige BIC.');
        }
        $this->bic = $value;

        return $this;
    }

    /**
     * Betrag in Euro. Der EPC-QR kennt ausschließlich EUR — eine andere
     * Währung ist kein Formatierungsdetail, sondern ein anderer Anwendungsfall.
     */
    public function amount(float|string $amount, CurrencyCode $currency = CurrencyCode::Euro): self {
        if ($currency !== CurrencyCode::Euro) {
            throw new InvalidArgumentException('EPC-QR: nur EUR ist zulässig (EPC069-12).');
        }

        $value = round((float) $amount, 2);
        if ($value < 0.01 || $value > 999999999.99) {
            throw new InvalidArgumentException('EPC-QR: Betrag muss zwischen 0.01 und 999999999.99 EUR liegen.');
        }

        $this->amount = number_format($value, 2, '.', '');

        return $this;
    }

    /** Purpose-Code (4 Zeichen, z. B. „GDDS"). */
    public function purpose(?string $purpose): self {
        $value = strtoupper(trim((string) $purpose));
        if ($value !== '' && !preg_match('/^[A-Z0-9]{4}$/', $value)) {
            throw new InvalidArgumentException('EPC-QR: Purpose-Code muss vier alphanumerische Zeichen haben.');
        }
        $this->purpose = $value;

        return $this;
    }

    /**
     * Strukturierte Referenz (z. B. RF-Creditor-Reference), max. 35 Zeichen.
     * Schließt {@see remittance()} aus.
     */
    public function reference(?string $reference): self {
        $value = trim((string) $reference);
        if (mb_strlen($value) > 35) {
            throw new InvalidArgumentException('EPC-QR: strukturierte Referenz darf höchstens 35 Zeichen haben.');
        }
        if ($value !== '' && $this->remittance !== '') {
            throw new InvalidArgumentException('EPC-QR: Referenz und Verwendungszweck schließen sich aus.');
        }
        $this->reference = $value;

        return $this;
    }

    /** Freier Verwendungszweck, max. 140 Zeichen. Schließt {@see reference()} aus. */
    public function remittance(?string $text): self {
        $value = trim((string) $text);
        if (mb_strlen($value) > 140) {
            throw new InvalidArgumentException('EPC-QR: Verwendungszweck darf höchstens 140 Zeichen haben.');
        }
        if ($value !== '' && $this->reference !== '') {
            throw new InvalidArgumentException('EPC-QR: Referenz und Verwendungszweck schließen sich aus.');
        }
        $this->remittance = $value;

        return $this;
    }

    /** Hinweis für den Zahler (wird nicht übertragen), max. 70 Zeichen. */
    public function note(?string $note): self {
        $value = trim((string) $note);
        if (mb_strlen($value) > 70) {
            throw new InvalidArgumentException('EPC-QR: Hinweis darf höchstens 70 Zeichen haben.');
        }
        $this->note = $value;

        return $this;
    }

    /**
     * Zeichensatz: UTF-8 (Default) oder ISO-8859-1. Letzteres nur für alte
     * Scanner — Umlaute gehen dabei nicht verloren, aber alles außerhalb von
     * Latin-1 schon.
     */
    public function charset(bool $utf8): self {
        $this->utf8 = $utf8;

        return $this;
    }

    /** Fertige Nutzlast; wirft, wenn die 331-Byte-Grenze überschritten wird. */
    public function build(): string {
        $lines = [
            'BCD',
            '002',
            $this->utf8 ? '1' : '2',
            'SCT',
            $this->bic,
            $this->name,
            $this->iban,
            $this->amount !== null ? 'EUR' . $this->amount : '',
            $this->purpose,
            $this->reference,
            $this->remittance,
            $this->note,
        ];

        // Leere Felder am Ende weglassen: die Spezifikation erlaubt das
        // Abschneiden ab dem letzten belegten Feld, und jedes gesparte Byte
        // zählt gegen die 331er-Grenze.
        while ($lines !== [] && end($lines) === '') {
            array_pop($lines);
        }

        $payload = implode("\n", $lines);
        if (!$this->utf8) {
            $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $payload);
            $payload = $converted === false ? $payload : $converted;
        }

        if (strlen($payload) > self::MAX_BYTES) {
            throw new InvalidArgumentException(sprintf(
                'EPC-QR: Nutzlast ist %d Byte lang, erlaubt sind %d.',
                strlen($payload),
                self::MAX_BYTES,
            ));
        }

        return $payload;
    }

    public function __toString(): string {
        return $this->build();
    }
}
