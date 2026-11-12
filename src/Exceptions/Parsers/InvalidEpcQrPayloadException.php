<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : InvalidEpcQrPayloadException.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Exceptions\Parsers;

/**
 * Die Nutzlast eines EPC-QR-Codes (Girocode, EPC069-12) ist ungültig.
 *
 * {@see getField()} nennt das betroffene Element als feste Kennung (eine der
 * FIELD_*-Konstanten), damit Aufrufer eine übersetzte Meldung bilden können,
 * ohne den Meldungstext zu vergleichen. Die Meldung selbst enthält nie den
 * Feldinhalt (IBAN, Name, Verwendungszweck).
 */
class InvalidEpcQrPayloadException extends MalformedDocumentException {
    /** Die Nutzlast als Ganzes: leer, zu lang, zu viele/zu wenige Zeilen. */
    public const FIELD_PAYLOAD = 'payload';

    public const FIELD_SERVICE_TAG = 'serviceTag';

    public const FIELD_VERSION = 'version';

    /** Zeichensatz-Kennung oder Bytes, die im angegebenen Zeichensatz ungültig sind. */
    public const FIELD_CHARSET = 'charset';

    public const FIELD_IDENTIFICATION = 'identification';

    public const FIELD_BIC = 'bic';

    public const FIELD_NAME = 'name';

    public const FIELD_IBAN = 'iban';

    public const FIELD_AMOUNT = 'amount';

    public const FIELD_PURPOSE = 'purpose';

    public const FIELD_REFERENCE = 'reference';

    public const FIELD_REMITTANCE = 'remittance';

    public const FIELD_NOTE = 'note';

    public function __construct(string $message, private readonly string $field) {
        parent::__construct($message);
    }

    /** Betroffenes Element, eine der FIELD_*-Konstanten. */
    public function getField(): string {
        return $this->field;
    }
}
