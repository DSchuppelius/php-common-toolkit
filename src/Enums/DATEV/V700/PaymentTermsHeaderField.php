<?php
/*
 * Created on   : Sun Dec 15 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentTermsHeaderField.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV\V700;

use CommonToolkit\Contracts\Interfaces\DATEV\FieldHeaderInterface;

/**
 * DATEV Zahlungsbedingungen (Payment Terms) - Feldheader V700.
 * Vollständige Implementierung aller 23 DATEV-Felder für Zahlungsbedingungen
 * basierend auf der offiziellen DATEV-Spezifikation.
 * 
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/payment-terms
 */
enum PaymentTermsHeaderField: string implements FieldHeaderInterface {
    // Spalten 1-10: Grunddaten der Zahlungsbedingung
    case Zahlungsbedingung              = 'Zahlungsbedingung';                     // 1
    case Bezeichnung                    = 'Bezeichnung';                           // 2
    case ZielTage                       = 'Ziel Tage';                             // 3
    case SkontoProzent1                 = 'Skonto % 1';                            // 4
    case SkontoTage1                    = 'Skonto Tage 1';                         // 5
    case SkontoProzent2                 = 'Skonto % 2';                            // 6
    case SkontoTage2                    = 'Skonto Tage 2';                         // 7
    case SkontoProzent3                 = 'Skonto % 3';                            // 8
    case SkontoTage3                    = 'Skonto Tage 3';                         // 9
    case SkontoProzent4                 = 'Skonto % 4';                            // 10

        // Spalten 11-20: Weitere Skonto-Stufen und Zahlungsdetails
    case SkontoTage4                    = 'Skonto Tage 4';                         // 11
    case SkontoProzent5                 = 'Skonto % 5';                            // 12
    case SkontoTage5                    = 'Skonto Tage 5';                         // 13
    case Mindestbetrag                  = 'Mindestbetrag';                         // 14
    case Hoechstbetrag                  = 'Höchstbetrag';                          // 15
    case Zahlungsart                    = 'Zahlungsart';                           // 16
    case Basistage                      = 'Basistage';                             // 17
    case BasisMonatsende                = 'Basis Monatsende';                      // 18
    case Feiertage                      = 'Feiertage';                             // 19
    case Samstag                        = 'Samstag';                               // 20

        // Spalten 21-23: Sonntag und Zusatzfelder
    case Sonntag                        = 'Sonntag';                               // 21
    case Leerfeld1                      = 'Leerfeld 1';                            // 22
    case Leerfeld2                      = 'Leerfeld 2';                            // 23

    /**
     * Liefert alle 23 Felder in der korrekten DATEV-Reihenfolge.
     */
    public static function ordered(): array {
        return [
            self::Zahlungsbedingung,          // 1
            self::Bezeichnung,                // 2
            self::ZielTage,                   // 3
            self::SkontoProzent1,             // 4
            self::SkontoTage1,                // 5
            self::SkontoProzent2,             // 6
            self::SkontoTage2,                // 7
            self::SkontoProzent3,             // 8
            self::SkontoTage3,                // 9
            self::SkontoProzent4,             // 10
            self::SkontoTage4,                // 11
            self::SkontoProzent5,             // 12
            self::SkontoTage5,                // 13
            self::Mindestbetrag,              // 14
            self::Hoechstbetrag,              // 15
            self::Zahlungsart,                // 16
            self::Basistage,                  // 17
            self::BasisMonatsende,            // 18
            self::Feiertage,                  // 19
            self::Samstag,                    // 20
            self::Sonntag,                    // 21
            self::Leerfeld1,                  // 22
            self::Leerfeld2,                  // 23
        ];
    }

    /**
     * Liefert alle verpflichtenden Felder.
     */
    public static function required(): array {
        return [
            self::Zahlungsbedingung,          // Pflichtfeld: Eindeutige Nummer
            self::Bezeichnung,                // Pflichtfeld: Beschreibung der Zahlungsbedingung
            self::ZielTage,                   // Pflichtfeld: Zahlungsziel in Tagen
        ];
    }

    /**
     * Liefert alle optionalen Felder.
     */
    public static function optional(): array {
        return array_diff(self::ordered(), self::required());
    }

    /**
     * Liefert den Datentyp für DATEV-Validierung.
     */
    public function getDataType(): string {
        return match ($this) {
            self::Zahlungsbedingung, self::ZielTage,
            self::SkontoTage1, self::SkontoTage2, self::SkontoTage3, self::SkontoTage4, self::SkontoTage5,
            self::Zahlungsart, self::Basistage, self::BasisMonatsende,
            self::Feiertage, self::Samstag, self::Sonntag => 'integer',

            self::SkontoProzent1, self::SkontoProzent2, self::SkontoProzent3,
            self::SkontoProzent4, self::SkontoProzent5,
            self::Mindestbetrag, self::Hoechstbetrag => 'decimal',

            self::Bezeichnung, self::Leerfeld1, self::Leerfeld2 => 'string',
        };
    }

    /**
     * Liefert die maximale Feldlänge für DATEV.
     */
    public function getMaxLength(): ?int {
        return match ($this) {
            self::Zahlungsbedingung => 3,         // Max. 999 Zahlungsbedingungen
            self::Bezeichnung => 40,              // Bezeichnung max. 40 Zeichen
            self::ZielTage, self::SkontoTage1, self::SkontoTage2,
            self::SkontoTage3, self::SkontoTage4, self::SkontoTage5 => 3,  // Max. 999 Tage
            self::SkontoProzent1, self::SkontoProzent2, self::SkontoProzent3,
            self::SkontoProzent4, self::SkontoProzent5 => 6,      // xx.xx% Format
            self::Mindestbetrag, self::Hoechstbetrag => 12,       // Betrag mit 2 Nachkommastellen
            self::Zahlungsart => 1,                               // 1-9 Zahlungsarten
            self::Basistage => 2,                                 // 1-31 Tage
            self::BasisMonatsende, self::Feiertage,
            self::Samstag, self::Sonntag => 1,                    // 0/1 Boolean
            self::Leerfeld1, self::Leerfeld2 => null,            // Leerfelder ohne Längenbeschränkung
        };
    }

    /**
     * Liefert das Regex-Pattern für DATEV-Validierung.
     */
    public function getValidationPattern(): ?string {
        return match ($this) {
            // Zahlungsbedingungsnummer: 1-999
            self::Zahlungsbedingung => '^[1-9]\d{0,2}$',

            // Bezeichnung: Pflichtfeld, maximal 40 Zeichen
            self::Bezeichnung => '^"(.){1,40}"$',

            // Zieltage: 0-999 Tage
            self::ZielTage => '^\d{1,3}$',

            // Skonto-Prozentsätze: 0.00-99.99%
            self::SkontoProzent1, self::SkontoProzent2, self::SkontoProzent3,
            self::SkontoProzent4, self::SkontoProzent5 => '^(\d{1,2}[\,\.]\d{2})$',

            // Skonto-Tage: 0-999 Tage
            self::SkontoTage1, self::SkontoTage2, self::SkontoTage3,
            self::SkontoTage4, self::SkontoTage5 => '^\d{0,3}$',

            // Mindest-/Höchstbetrag: Beträge mit 2 Nachkommastellen
            self::Mindestbetrag, self::Hoechstbetrag => '^(\d{1,10}[\,\.]\d{2})$',

            // Zahlungsart: 1=Bar, 2=Überweisung, 3=Lastschrift, etc.
            self::Zahlungsart => '^[1-9]$',

            // Basistage: 1-31 (Tage im Monat)
            self::Basistage => '^([1-9]|[12]\d|3[01])$',

            // Boolean-Felder: 0=Nein, 1=Ja
            self::BasisMonatsende, self::Feiertage,
            self::Samstag, self::Sonntag => '^[01]$',

            // Leerfelder haben keine Validierung
            self::Leerfeld1, self::Leerfeld2 => null,
        };
    }

    /**
     * Liefert die unterstützten Zahlungsarten.
     */
    public static function getSupportedPaymentTypes(): array {
        return [
            1 => 'Barzahlung',
            2 => 'Überweisung',
            3 => 'Lastschrift',
            4 => 'Scheck',
            5 => 'Kreditkarte',
            6 => 'Wechsel',
            7 => 'Verrechnung',
            8 => 'Einzug',
            9 => 'Sonstige',
        ];
    }

    /**
     * Prüft, ob eine Zahlungsart gültig ist.
     */
    public static function isValidPaymentType(int $paymentType): bool {
        return array_key_exists($paymentType, self::getSupportedPaymentTypes());
    }

    /**
     * Liefert die Beschreibung einer Zahlungsart.
     */
    public static function getPaymentTypeDescription(int $paymentType): ?string {
        return self::getSupportedPaymentTypes()[$paymentType] ?? null;
    }

    /**
     * Prüft, ob ein Feld ein Prozentsatz-Feld ist.
     */
    public function isPercentageField(): bool {
        return in_array($this, [
            self::SkontoProzent1,
            self::SkontoProzent2,
            self::SkontoProzent3,
            self::SkontoProzent4,
            self::SkontoProzent5
        ]);
    }

    /**
     * Prüft, ob ein Feld ein Tage-Feld ist.
     */
    public function isDaysField(): bool {
        return in_array($this, [
            self::ZielTage,
            self::SkontoTage1,
            self::SkontoTage2,
            self::SkontoTage3,
            self::SkontoTage4,
            self::SkontoTage5,
            self::Basistage
        ]);
    }

    /**
     * Prüft, ob ein Feld ein Boolean-Feld ist.
     */
    public function isBooleanField(): bool {
        return in_array($this, [
            self::BasisMonatsende,
            self::Feiertage,
            self::Samstag,
            self::Sonntag
        ]);
    }

    /**
     * Prüft, ob ein Feld ein Betrag-Feld ist.
     */
    public function isAmountField(): bool {
        return in_array($this, [self::Mindestbetrag, self::Hoechstbetrag]);
    }

    /**
     * Prüft, ob das Feld verpflichtend ist.
     */
    public function isRequired(): bool {
        return in_array($this, self::required());
    }
}
