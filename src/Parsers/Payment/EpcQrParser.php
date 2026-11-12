<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EpcQrParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers\Payment;

use CommonToolkit\Builders\Payment\EpcQrBuilder;
use CommonToolkit\Entities\Payment\EpcQrPayment;
use CommonToolkit\Enums\Payment\EpcQrCharset;
use CommonToolkit\Exceptions\Parsers\InvalidEpcQrPayloadException as Invalid;
use CommonToolkit\Helper\Data\{BankHelper, NumberHelper, StringHelper};
use ERRORToolkit\Traits\ErrorLog;

/**
 * Liest die Nutzlast eines EPC-QR-Codes („Girocode", EPC069-12 v3.1) —
 * Gegenstück zu {@see EpcQrBuilder}. Das Bild zu dekodieren ist Sache der
 * QR-Bibliothek des Aufrufers; hier kommt der gelesene Text an.
 *
 * Geprüft wird gegen dieselben harten Grenzen wie beim Erzeugen (Konstanten
 * von {@see EpcQrBuilder}): Service-Tag `BCD`, Version `001`/`002`,
 * Zeichensatz 1–8, Identifikation `SCT`, 7 bis 12 Zeilen, höchstens 331 Byte,
 * Feldlängen, Betrag `EUR0.01`–`EUR999999999.99` mit Punkt und höchstens zwei
 * Nachkommastellen, Purpose-Code, Referenz ODER Verwendungszweck.
 *
 * Toleriert werden: Zeilenenden LF und CRLF (auch gemischt), ein einzelner
 * Zeilenumbruch am Ende (viele Scanner hängen ihn an) und Leerzeichen am
 * Rand eines Feldes. Version 001 verlangt eine BIC, Version 002 nicht.
 *
 * Die IBAN muss wie eine IBAN aufgebaut sein (Land, zwei Prüfziffern,
 * höchstens 34 Zeichen); ob Länderlänge und Prüfziffer stimmen, steht im
 * Ergebnis ({@see EpcQrPayment::isIbanValid()}) — der Code wird deswegen
 * nicht verworfen, der Aufrufer entscheidet.
 *
 * @example
 * ```php
 * $payment = EpcQrParser::parse($scannedText);
 * if (!$payment->isIbanValid()) { ... }
 * $payment->getAmount();      // "99.99"
 * EpcQrParser::tryParse('https://example.org'); // null
 * ```
 */
final class EpcQrParser {
    use ErrorLog;

    /** Allgemeiner IBAN-Aufbau (ISO 13616): Land, zwei Prüfziffern, bis zu 30 Zeichen BBAN. */
    private const IBAN_SHAPE = '/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/';

    /** "EUR" + bis zu neun Vorkommastellen + optional ein bis zwei Nachkommastellen. */
    private const AMOUNT_SHAPE = '/^EUR([0-9]{1,9})(?:\.([0-9]{1,2}))?$/';

    /** Steuerzeichen außer Tabulator; LF/CR trennen Zeilen und bleiben nie im Feld. */
    private const CONTROL_CHARACTERS = '/[\x00-\x08\x0A-\x1F\x7F]/';

    /**
     * Zerlegt eine EPC-QR-Nutzlast.
     *
     * @throws Invalid Bei ungültiger Nutzlast; {@see Invalid::getField()} nennt das Feld.
     */
    public static function parse(string $payload): EpcQrPayment {
        try {
            return self::decode($payload);
        } catch (Invalid $exception) {
            self::logError('EPC-QR-Nutzlast ungültig (' . $exception->getField() . '): ' . $exception->getMessage());

            throw $exception;
        }
    }

    /**
     * Wie {@see parse()}, liefert aber bei `null`, leerer oder ungültiger
     * Nutzlast `null` — für Scanner, die beliebige QR-Codes sehen.
     */
    public static function tryParse(?string $payload): ?EpcQrPayment {
        if ($payload === null || $payload === '') {
            return null;
        }

        try {
            return self::decode($payload);
        } catch (Invalid) {
            return null;
        }
    }

    /** @throws Invalid */
    private static function decode(string $payload): EpcQrPayment {
        if (str_ends_with($payload, "\r\n")) {
            $payload = substr($payload, 0, -2);
        } elseif (str_ends_with($payload, "\n")) {
            $payload = substr($payload, 0, -1);
        }

        $bytes = strlen($payload);
        if ($bytes === 0) {
            throw new Invalid('Nutzlast ist leer.', Invalid::FIELD_PAYLOAD);
        }
        if ($bytes > EpcQrBuilder::MAX_BYTES) {
            throw new Invalid(sprintf('Nutzlast ist %d Byte lang, erlaubt sind %d.', $bytes, EpcQrBuilder::MAX_BYTES), Invalid::FIELD_PAYLOAD);
        }

        $lines = preg_split('/\r?\n/', $payload) ?: [];
        if (count($lines) > EpcQrBuilder::MAX_LINES) {
            throw new Invalid(sprintf('Nutzlast hat %d Zeilen, erlaubt sind %d.', count($lines), EpcQrBuilder::MAX_LINES), Invalid::FIELD_PAYLOAD);
        }
        $lines = array_map(static fn (string $line): string => trim($line, ' '), $lines);

        if ($lines[0] !== EpcQrBuilder::SERVICE_TAG) {
            throw new Invalid('Service-Tag ist nicht "BCD".', Invalid::FIELD_SERVICE_TAG);
        }
        $version = $lines[1] ?? '';
        if ($version !== EpcQrBuilder::VERSION && $version !== EpcQrBuilder::VERSION_BIC_REQUIRED) {
            throw new Invalid('Version ist weder "001" noch "002".', Invalid::FIELD_VERSION);
        }
        $charsetDigit = $lines[2] ?? '';
        $charset = preg_match('/^[1-8]$/', $charsetDigit) === 1 ? EpcQrCharset::tryFrom((int) $charsetDigit) : null;
        if ($charset === null) {
            throw new Invalid('Zeichensatz-Kennung ist keine Ziffer von 1 bis 8.', Invalid::FIELD_CHARSET);
        }
        if (($lines[3] ?? '') !== EpcQrBuilder::IDENTIFICATION) {
            throw new Invalid('Identifikation ist nicht "SCT".', Invalid::FIELD_IDENTIFICATION);
        }
        if (count($lines) < EpcQrBuilder::MIN_LINES) {
            throw new Invalid('Nutzlast endet vor der IBAN.', Invalid::FIELD_PAYLOAD);
        }
        if ($charset === EpcQrCharset::Utf8 && !mb_check_encoding($payload, 'UTF-8')) {
            throw new Invalid('Nutzlast ist als UTF-8 gekennzeichnet, enthält aber ungültige Bytes.', Invalid::FIELD_CHARSET);
        }

        // Felder 5–12; fehlende Zeilen am Ende sind leere Felder.
        $fieldNames = [
            Invalid::FIELD_BIC, Invalid::FIELD_NAME, Invalid::FIELD_IBAN, Invalid::FIELD_AMOUNT,
            Invalid::FIELD_PURPOSE, Invalid::FIELD_REFERENCE, Invalid::FIELD_REMITTANCE, Invalid::FIELD_NOTE,
        ];
        $fields = [];
        foreach ($fieldNames as $index => $fieldName) {
            $fields[$fieldName] = self::decodeField($lines[$index + 4] ?? '', $charset, $fieldName);
        }

        $bic = self::bic($fields[Invalid::FIELD_BIC], $version);
        $name = self::text($fields[Invalid::FIELD_NAME], EpcQrBuilder::MAX_NAME_LENGTH, Invalid::FIELD_NAME, 'Empfängername')
            ?? throw new Invalid('Empfängername fehlt.', Invalid::FIELD_NAME);
        $iban = self::iban($fields[Invalid::FIELD_IBAN]);
        $amount = self::amount($fields[Invalid::FIELD_AMOUNT]);
        $purpose = self::purpose($fields[Invalid::FIELD_PURPOSE]);
        $reference = self::text($fields[Invalid::FIELD_REFERENCE], EpcQrBuilder::MAX_REFERENCE_LENGTH, Invalid::FIELD_REFERENCE, 'Strukturierte Referenz');
        $remittance = self::text($fields[Invalid::FIELD_REMITTANCE], EpcQrBuilder::MAX_REMITTANCE_LENGTH, Invalid::FIELD_REMITTANCE, 'Verwendungszweck');
        if ($reference !== null && $remittance !== null) {
            throw new Invalid('Strukturierte Referenz und Verwendungszweck schließen sich aus.', Invalid::FIELD_REMITTANCE);
        }
        $note = self::text($fields[Invalid::FIELD_NOTE], EpcQrBuilder::MAX_NOTE_LENGTH, Invalid::FIELD_NOTE, 'Hinweis');

        return new EpcQrPayment(
            version: $version,
            charset: $charset,
            bic: $bic,
            name: $name,
            iban: $iban,
            ibanValid: BankHelper::validateIBAN($iban, true),
            amount: $amount,
            purpose: $purpose,
            reference: $reference,
            remittance: $remittance,
            note: $note,
        );
    }

    /**
     * Ein Feld nach UTF-8, ohne Steuerzeichen.
     *
     * @throws Invalid
     */
    private static function decodeField(string $raw, EpcQrCharset $charset, string $field): string {
        $value = $charset === EpcQrCharset::Utf8 ? $raw : StringHelper::convertToUtf8($raw, $charset->encoding());
        if (preg_match(self::CONTROL_CHARACTERS, $value) === 1) {
            throw new Invalid('Feld enthält Steuerzeichen.', $field);
        }

        return $value;
    }

    /** @throws Invalid */
    private static function bic(string $value, string $version): ?string {
        $bic = BankHelper::normalizeBIC($value);
        if ($bic === null) {
            if ($version === EpcQrBuilder::VERSION_BIC_REQUIRED) {
                throw new Invalid('Version 001 verlangt eine BIC.', Invalid::FIELD_BIC);
            }

            return null;
        }
        if (!BankHelper::isBIC($bic)) {
            throw new Invalid('BIC ist ungültig.', Invalid::FIELD_BIC);
        }

        return $bic;
    }

    /** @throws Invalid */
    private static function iban(string $value): string {
        $iban = BankHelper::normalizeIBAN($value);
        if ($iban === null) {
            throw new Invalid('IBAN fehlt.', Invalid::FIELD_IBAN);
        }
        if (preg_match(self::IBAN_SHAPE, $iban) !== 1) {
            throw new Invalid('IBAN hat keinen IBAN-Aufbau (Land, Prüfziffern, höchstens 34 Zeichen).', Invalid::FIELD_IBAN);
        }

        return $iban;
    }

    /**
     * Betrag als Dezimal-String mit zwei Nachkommastellen — ohne float.
     *
     * @throws Invalid
     */
    private static function amount(string $value): ?string {
        if ($value === '') {
            return null;
        }
        if (preg_match(self::AMOUNT_SHAPE, $value, $match) !== 1) {
            throw new Invalid('Betrag hat nicht die Form "EUR" + Betrag mit Punkt und höchstens zwei Nachkommastellen.', Invalid::FIELD_AMOUNT);
        }

        $integer = ltrim($match[1], '0');
        $amount = ($integer === '' ? '0' : $integer) . '.' . str_pad($match[2] ?? '', 2, '0');
        if (
            !is_numeric($amount)
            || NumberHelper::comparePrecise($amount, EpcQrBuilder::MIN_AMOUNT, 2) < 0
            || NumberHelper::comparePrecise($amount, EpcQrBuilder::MAX_AMOUNT, 2) > 0
        ) {
            throw new Invalid('Betrag muss zwischen 0.01 und 999999999.99 EUR liegen.', Invalid::FIELD_AMOUNT);
        }

        return $amount;
    }

    /** @throws Invalid */
    private static function purpose(string $value): ?string {
        $purpose = strtoupper($value);
        if ($purpose === '') {
            return null;
        }
        if (preg_match(EpcQrBuilder::PURPOSE_PATTERN, $purpose) !== 1) {
            throw new Invalid('Purpose-Code muss vier alphanumerische Zeichen haben.', Invalid::FIELD_PURPOSE);
        }

        return $purpose;
    }

    /** @throws Invalid */
    private static function text(string $value, int $maxLength, string $field, string $label): ?string {
        if ($value === '') {
            return null;
        }
        if (mb_strlen($value, 'UTF-8') > $maxLength) {
            throw new Invalid("$label ist länger als $maxLength Zeichen.", $field);
        }

        return $value;
    }
}
