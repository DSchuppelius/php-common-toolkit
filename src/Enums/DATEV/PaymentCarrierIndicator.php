<?php
/*
 * Created on   : Thu Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentCarrierIndicator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

/**
 * DATEV Zahlungsträger-Kennzeichen für Debitoren/Kreditoren (Feld 136).
 *
 * Leer bzw. 0 = keine Angaben, es gilt die Stammdaten-Schlüsselung
 * 5 = Einzelscheck
 * 6 = Sammelscheck
 * 7 = SEPA-/Auslandsüberweisung mit einer Rechnung
 * 8 = SEPA-/Auslandsüberweisung mit mehreren Rechnungen
 * 9 = keine Überweisungen, Schecks
 *
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/debitorskreditors
 */
enum PaymentCarrierIndicator: int {
    case NONE                  = 0; // keine Angaben, es gilt die Stammdaten-Schlüsselung
    case SINGLE_CHECK          = 5; // Einzelscheck
    case COLLECTIVE_CHECK      = 6; // Sammelscheck
    case SEPA_TRANSFER_SINGLE  = 7; // SEPA-/Auslandsüberweisung mit einer Rechnung
    case SEPA_TRANSFER_MULTI   = 8; // SEPA-/Auslandsüberweisung mit mehreren Rechnungen
    case DISABLED              = 9; // keine Überweisungen, Schecks

    /**
     * Deutsche Textbezeichnung für UI/Logging.
     */
    public function getLabel(): string {
        return match ($this) {
            self::NONE                 => 'keine Angabe (Stammdaten-Schlüsselung)',
            self::SINGLE_CHECK         => 'Einzelscheck',
            self::COLLECTIVE_CHECK     => 'Sammelscheck',
            self::SEPA_TRANSFER_SINGLE => 'SEPA-/Auslandsüberweisung (einzeln)',
            self::SEPA_TRANSFER_MULTI  => 'SEPA-/Auslandsüberweisung (Sammel)',
            self::DISABLED             => 'keine Überweisungen/Schecks',
        };
    }

    /**
     * Factory für CSV/DATEV-Import.
     */
    public static function fromInt(int $value): self {
        return match ($value) {
            0 => self::NONE,
            5 => self::SINGLE_CHECK,
            6 => self::COLLECTIVE_CHECK,
            7 => self::SEPA_TRANSFER_SINGLE,
            8 => self::SEPA_TRANSFER_MULTI,
            9 => self::DISABLED,
            default => throw new InvalidArgumentException("Ungültiges Zahlungsträgerkennzeichen: $value"),
        };
    }

    /**
     * Factory für String-Werte (quoted in DATEV-Format).
     */
    public static function tryFromString(string $value): ?self {
        $trimmed = trim($value, '" ');
        if ($trimmed === '') {
            return null;
        }
        return self::fromInt((int) $trimmed);
    }

    /**
     * Prüft, ob es sich um eine SEPA-Überweisung handelt.
     */
    public function isSepaTransfer(): bool {
        return $this === self::SEPA_TRANSFER_SINGLE || $this === self::SEPA_TRANSFER_MULTI;
    }

    /**
     * Prüft, ob es sich um einen Scheck handelt.
     */
    public function isCheck(): bool {
        return $this === self::SINGLE_CHECK || $this === self::COLLECTIVE_CHECK;
    }

    /**
     * Prüft, ob Zahlungsträger explizit deaktiviert ist.
     */
    public function isDisabled(): bool {
        return $this === self::DISABLED;
    }
}
