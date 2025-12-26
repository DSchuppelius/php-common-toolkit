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

namespace CommonToolkit\Enums\DATEV\HeaderFields\V700;

use CommonToolkit\Contracts\Interfaces\DATEV\FieldHeaderInterface;
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;

/**
 * DATEV Zahlungsbedingungen (Payment Terms) - Feldheader V700.
 * Vollständige Implementierung aller 31 DATEV-Felder für Zahlungsbedingungen
 * basierend auf der offiziellen DATEV-Spezifikation (Formatversion 2).
 *
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/payment-terms
 */
enum PaymentTermsHeaderField: string implements FieldHeaderInterface {
    // Spalten 1-10: Grunddaten der Zahlungsbedingung
    case Nummer                         = 'Nummer';                                // 1
    case Bezeichnung                    = 'Bezeichnung';                           // 2
    case Faelligkeitstyp                = 'Fälligkeitstyp';                        // 3
    case Skonto1Prozent                 = 'Skonto 1%';                             // 4
    case Skonto1Tage                    = 'Skonto 1 Tage';                         // 5
    case Skonto2Prozent                 = 'Skonto 2 %';                            // 6
    case Skonto2Tage                    = 'Skonto 2 Tage';                         // 7
    case FaelligTage                    = 'Fällig Tage';                           // 8
    case RechnungBisZeitraum1           = 'Rechnung bis / Zeitraum 1';             // 9
    case Skonto1DatumZeitraum1          = 'Skonto1 Datum / Zeitraum 1';            // 10

        // Spalten 11-20: Zeitraum 1 + 2 Felder
    case Skonto1MonatZeitraum1          = 'Skonto 1 Monat / Zeitraum 1';           // 11
    case Skonto2DatumZeitraum1          = 'Skonto 2 Datum / Zeitraum 1';           // 12
    case Skonto2MonatZeitraum1          = 'Skonto 2 Monat / Zeitraum 1';           // 13
    case FaelligDatumZeitraum1          = 'Fällig Datum / Zeitraum 1';             // 14
    case FaelligMonatZeitraum1          = 'Fällig Monat / Zeitraum 1';             // 15
    case RechnungBisZeitraum2           = 'Rechnung bis / Zeitraum 2';             // 16
    case Skonto1DatumZeitraum2          = 'Skonto1 Datum / Zeitraum 2';            // 17
    case Skonto1MonatZeitraum2          = 'Skonto 1 Monat / Zeitraum 2';           // 18
    case Skonto2DatumZeitraum2          = 'Skonto 2 Datum / Zeitraum 2';           // 19
    case Skonto2MonatZeitraum2          = 'Skonto 2 Monat / Zeitraum 2';           // 20

        // Spalten 21-31: Zeitraum 2 + 3 Felder und Sonstiges
    case FaelligDatumZeitraum2          = 'Fällig Datum / Zeitraum 2';             // 21
    case FaelligMonatZeitraum2          = 'Fällig Monat / Zeitraum 2';             // 22
    case RechnungBisZeitraum3           = 'Rechnung bis / Zeitraum 3';             // 23
    case Skonto1DatumZeitraum3          = 'Skonto1 Datum / Zeitraum 3';            // 24
    case Skonto1MonatZeitraum3          = 'Skonto1 Monat / Zeitraum 3';            // 25
    case Skonto2DatumZeitraum3          = 'Skonto 2 Datum / Zeitraum 3';           // 26
    case Skonto2MonatZeitraum3          = 'Skonto 2 Monat / Zeitraum 3';           // 27
    case FaelligDatumZeitraum3          = 'Fällig Datum / Zeitraum 3';             // 28
    case FaelligMonatZeitraum3          = 'Fällig Monat / Zeitraum 3';             // 29
    case Leerfeld                       = 'Leerfeld';                              // 30
    case Verwendung                     = 'Verwendung';                            // 31

    /**
     * Liefert alle 31 Felder in der korrekten DATEV-Reihenfolge.
     */
    public static function ordered(): array {
        return [
            self::Nummer,                     // 1
            self::Bezeichnung,                // 2
            self::Faelligkeitstyp,            // 3
            self::Skonto1Prozent,             // 4
            self::Skonto1Tage,                // 5
            self::Skonto2Prozent,             // 6
            self::Skonto2Tage,                // 7
            self::FaelligTage,                // 8
            self::RechnungBisZeitraum1,       // 9
            self::Skonto1DatumZeitraum1,      // 10
            self::Skonto1MonatZeitraum1,      // 11
            self::Skonto2DatumZeitraum1,      // 12
            self::Skonto2MonatZeitraum1,      // 13
            self::FaelligDatumZeitraum1,      // 14
            self::FaelligMonatZeitraum1,      // 15
            self::RechnungBisZeitraum2,       // 16
            self::Skonto1DatumZeitraum2,      // 17
            self::Skonto1MonatZeitraum2,      // 18
            self::Skonto2DatumZeitraum2,      // 19
            self::Skonto2MonatZeitraum2,      // 20
            self::FaelligDatumZeitraum2,      // 21
            self::FaelligMonatZeitraum2,      // 22
            self::RechnungBisZeitraum3,       // 23
            self::Skonto1DatumZeitraum3,      // 24
            self::Skonto1MonatZeitraum3,      // 25
            self::Skonto2DatumZeitraum3,      // 26
            self::Skonto2MonatZeitraum3,      // 27
            self::FaelligDatumZeitraum3,      // 28
            self::FaelligMonatZeitraum3,      // 29
            self::Leerfeld,                   // 30
            self::Verwendung,                 // 31
        ];
    }

    /**
     * Liefert alle verpflichtenden Felder.
     */
    public static function required(): array {
        return [
            self::Nummer,                     // Pflichtfeld: Eindeutige Nummer
            self::Bezeichnung,                // Pflichtfeld: Beschreibung der Zahlungsbedingung
            self::Faelligkeitstyp,            // Pflichtfeld: Art der Fälligkeit
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
            self::Nummer, self::Faelligkeitstyp,
            self::Skonto1Tage, self::Skonto2Tage, self::FaelligTage,
            self::RechnungBisZeitraum1, self::RechnungBisZeitraum2, self::RechnungBisZeitraum3,
            self::Skonto1DatumZeitraum1, self::Skonto1DatumZeitraum2, self::Skonto1DatumZeitraum3,
            self::Skonto1MonatZeitraum1, self::Skonto1MonatZeitraum2, self::Skonto1MonatZeitraum3,
            self::Skonto2DatumZeitraum1, self::Skonto2DatumZeitraum2, self::Skonto2DatumZeitraum3,
            self::Skonto2MonatZeitraum1, self::Skonto2MonatZeitraum2, self::Skonto2MonatZeitraum3,
            self::FaelligDatumZeitraum1, self::FaelligDatumZeitraum2, self::FaelligDatumZeitraum3,
            self::FaelligMonatZeitraum1, self::FaelligMonatZeitraum2, self::FaelligMonatZeitraum3,
            self::Verwendung => 'integer',

            self::Skonto1Prozent, self::Skonto2Prozent => 'decimal',

            self::Bezeichnung, self::Leerfeld => 'string',
        };
    }

    /**
     * Liefert die maximale Feldlänge für DATEV.
     */
    public function getMaxLength(): ?int {
        return match ($this) {
            self::Nummer => 4,                            // Max. 9999 Zahlungsbedingungen
            self::Bezeichnung => 60,                      // Bezeichnung max. 60 Zeichen
            self::Faelligkeitstyp => 1,                   // 0-9 Fälligkeitstypen
            self::Skonto1Prozent, self::Skonto2Prozent => 6,  // xx.xx% Format
            self::Skonto1Tage, self::Skonto2Tage, self::FaelligTage => 3,  // Max. 999 Tage
            self::RechnungBisZeitraum1, self::RechnungBisZeitraum2, self::RechnungBisZeitraum3,
            self::Skonto1DatumZeitraum1, self::Skonto1DatumZeitraum2, self::Skonto1DatumZeitraum3,
            self::Skonto2DatumZeitraum1, self::Skonto2DatumZeitraum2, self::Skonto2DatumZeitraum3,
            self::FaelligDatumZeitraum1, self::FaelligDatumZeitraum2, self::FaelligDatumZeitraum3 => 2,  // 1-31 Tag
            self::Skonto1MonatZeitraum1, self::Skonto1MonatZeitraum2, self::Skonto1MonatZeitraum3,
            self::Skonto2MonatZeitraum1, self::Skonto2MonatZeitraum2, self::Skonto2MonatZeitraum3,
            self::FaelligMonatZeitraum1, self::FaelligMonatZeitraum2, self::FaelligMonatZeitraum3 => 2,  // 0-12 Monate
            self::Leerfeld => null,                       // Leerfeld ohne Längenbeschränkung
            self::Verwendung => 1,                        // 0/1 Boolean
        };
    }

    /**
     * Liefert das Regex-Pattern für DATEV-Validierung.
     */
    public function getValidationPattern(): ?string {
        return match ($this) {
            // Nummer: 1-9999
            self::Nummer => '^\d{1,4}$',

            // Bezeichnung: Pflichtfeld, maximal 60 Zeichen
            self::Bezeichnung => '^"(.){1,60}"$',

            // Fälligkeitstyp: 0-9
            self::Faelligkeitstyp => '^\d$',

            // Skonto-Prozentsätze: 0.00-99.99%
            self::Skonto1Prozent, self::Skonto2Prozent => '^(\d{1,4})$',

            // Tage-Felder: 0-999 Tage
            self::Skonto1Tage, self::Skonto2Tage, self::FaelligTage => '^\d{0,3}$',

            // Datum-Felder (Tag des Monats): 1-31 oder leer
            self::RechnungBisZeitraum1, self::RechnungBisZeitraum2, self::RechnungBisZeitraum3,
            self::Skonto1DatumZeitraum1, self::Skonto1DatumZeitraum2, self::Skonto1DatumZeitraum3,
            self::Skonto2DatumZeitraum1, self::Skonto2DatumZeitraum2, self::Skonto2DatumZeitraum3,
            self::FaelligDatumZeitraum1, self::FaelligDatumZeitraum2, self::FaelligDatumZeitraum3 => '^\d{0,2}$',

            // Monat-Felder: 0-12 oder leer
            self::Skonto1MonatZeitraum1, self::Skonto1MonatZeitraum2, self::Skonto1MonatZeitraum3,
            self::Skonto2MonatZeitraum1, self::Skonto2MonatZeitraum2, self::Skonto2MonatZeitraum3,
            self::FaelligMonatZeitraum1, self::FaelligMonatZeitraum2, self::FaelligMonatZeitraum3 => '^\d{0,2}$',

            // Leerfeld: keine Validierung
            self::Leerfeld => null,

            // Verwendung: 0/1
            self::Verwendung => '^\d$',
        };
    }

    /**
     * Liefert die unterstützten Fälligkeitstypen.
     */
    public static function getSupportedDueTypes(): array {
        return [
            0 => 'Keine Fälligkeit',
            1 => 'Tage nach Rechnungsdatum',
            2 => 'Fester Tag im Monat',
            3 => 'Zeitraum-basiert',
        ];
    }

    /**
     * Prüft, ob ein Fälligkeitstyp gültig ist.
     */
    public static function isValidDueType(int $dueType): bool {
        return array_key_exists($dueType, self::getSupportedDueTypes());
    }

    /**
     * Prüft, ob ein Feld ein Prozentsatz-Feld ist.
     */
    public function isPercentageField(): bool {
        return in_array($this, [
            self::Skonto1Prozent,
            self::Skonto2Prozent,
        ]);
    }

    /**
     * Prüft, ob ein Feld ein Tage-Feld ist.
     */
    public function isDaysField(): bool {
        return in_array($this, [
            self::Skonto1Tage,
            self::Skonto2Tage,
            self::FaelligTage,
        ]);
    }

    /**
     * Prüft, ob ein Feld ein Datum-Feld (Tag des Monats) ist.
     */
    public function isDateField(): bool {
        return in_array($this, [
            self::RechnungBisZeitraum1,
            self::RechnungBisZeitraum2,
            self::RechnungBisZeitraum3,
            self::Skonto1DatumZeitraum1,
            self::Skonto1DatumZeitraum2,
            self::Skonto1DatumZeitraum3,
            self::Skonto2DatumZeitraum1,
            self::Skonto2DatumZeitraum2,
            self::Skonto2DatumZeitraum3,
            self::FaelligDatumZeitraum1,
            self::FaelligDatumZeitraum2,
            self::FaelligDatumZeitraum3,
        ]);
    }

    /**
     * Prüft, ob ein Feld ein Monat-Feld ist.
     */
    public function isMonthField(): bool {
        return in_array($this, [
            self::Skonto1MonatZeitraum1,
            self::Skonto1MonatZeitraum2,
            self::Skonto1MonatZeitraum3,
            self::Skonto2MonatZeitraum1,
            self::Skonto2MonatZeitraum2,
            self::Skonto2MonatZeitraum3,
            self::FaelligMonatZeitraum1,
            self::FaelligMonatZeitraum2,
            self::FaelligMonatZeitraum3,
        ]);
    }

    /**
     * Prüft, ob das Feld verpflichtend ist.
     */
    public function isRequired(): bool {
        return in_array($this, self::required());
    }

    /**
     * Liefert die DATEV-Kategorie für dieses Header-Format.
     */
    public static function getCategory(): Category {
        return Category::Zahlungsbedingungen;
    }

    /**
     * Liefert die DATEV-Version für dieses Header-Format.
     */
    public static function getVersion(): int {
        return 700;
    }

    /**
     * Liefert die Anzahl der definierten Felder.
     */
    public static function getFieldCount(): int {
        return count(self::ordered());
    }

    /**
     * Prüft, ob ein Feldwert gültig ist (im Enum enthalten).
     */
    public static function isValidFieldValue(string $value): bool {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gibt an, ob der FieldHeader (Spaltenüberschrift) in Anführungszeichen gesetzt wird.
     * DATEV-FieldHeaders werden NICHT gequoted.
     */
    public function isQuotedHeader(): bool {
        return false;
    }

    /**
     * Gibt an, ob der Feldwert in Anführungszeichen gesetzt wird.
     * Basierend auf dem Validierungspattern: Pattern mit ^"... = gequotet
     */
    public function isQuotedValue(): bool {
        $pattern = $this->getValidationPattern();
        if ($pattern === null) {
            return true; // Default: gequotet (sicherer für Text)
        }
        // Prüfe ob Pattern mit Anführungszeichen beginnt
        return (bool) preg_match('/^\^(\(?\[?"|\(?")/u', $pattern);
    }

    /**
     * Liefert den tatsächlichen Header-Namen für die CSV-Ausgabe.
     * Weicht ggf. vom Enum-Wert ab, um Kompatibilität mit DATEV-Sample-Dateien zu gewährleisten.
     */
    public function headerName(): string {
        return $this->value;
    }
}
