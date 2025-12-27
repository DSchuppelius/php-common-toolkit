<?php

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV\HeaderFields\ASCII;

/**
 * DATEV ASCII-Weiterverarbeitungsdatei Header-Felder
 * 
 * Definiert alle 34 Felder der DATEV ASCII-Weiterverarbeitungsdatei für Banktransaktionen.
 * Format basiert auf der offiziellen DATEV-Dokumentation (Dok.-Nr. 9226961).
 * Mindestens 7 Felder sind erforderlich, maximal 34 Felder sind möglich.
 * 
 * @see https://help-center.apps.datev.de/documents/9226961
 * 
 * @since 1.0.0
 * @package CommonToolkit\Enums\DATEV\ASCII
 */
enum BankTransactionHeaderField: string {
    // Felder 1-17: Grundlegende Banktransaktion im ASCII-Format (nach DATEV-Dokumentation)
    case BLZ_BIC_KONTOINHABER          = 'Bankleitzahl oder BIC des Kontoinhabers'; // 1: Bankleitzahl oder BIC des Kontoinhabers (M)
    case KONTONUMMER_IBAN_KONTOINHABER = 'Kontonummer oder IBAN des Kontoinhabers'; // 2: Kontonummer oder IBAN des Kontoinhabers (M)
    case AUSZUGSNUMMER                 = 'Auszugsnummer';                           // 3: Auszugsnummer (K)
    case AUSZUGSDATUM                  = 'Auszugsdatum';                            // 4: Auszugsdatum (K)
    case VALUTA                        = 'Valuta';                                  // 5: Valuta (K)
    case BUCHUNGSDATUM                 = 'Buchungsdatum';                           // 6: Buchungsdatum (M)
    case UMSATZ                        = 'Umsatz';                                  // 7: Umsatz (M)
    case AUFTRAGGEBERNAME_1            = 'Auftraggebername 1';                      // 8: Auftraggebername 1 (K)
    case AUFTRAGGEBERNAME_2            = 'Auftraggebername 2';                      // 9: Auftraggebername 2 (K)
    case BLZ_BIC_AUFTRAGGEBER          = 'Bankleitzahl oder BIC des Auftraggebers'; // 10: Bankleitzahl oder BIC des Auftraggebers (K)
    case KONTONUMMER_IBAN_AUFTRAGGEBER = 'Kontonummer oder IBAN des Auftraggebers'; // 11: Kontonummer oder IBAN des Auftraggebers (K)
    case VERWENDUNGSZWECK_1            = 'Verwendungszweck 1';                      // 12: Verwendungszweck 1 (K)
    case VERWENDUNGSZWECK_2            = 'Verwendungszweck 2';                      // 13: Verwendungszweck 2 (K)
    case VERWENDUNGSZWECK_3            = 'Verwendungszweck 3';                      // 14: Verwendungszweck 3 (K)
    case VERWENDUNGSZWECK_4            = 'Verwendungszweck 4';                      // 15: Verwendungszweck 4 (K)
    case GESCHAEFTSVORGANGSCODE        = 'Geschäftsvorgangscode';                   // 16: Geschäftsvorgangscode (K)
    case WAEHRUNG                      = 'Währung';                                 // 17: Währung (K)

        // Felder 18-34: Erweiterte Felder für zusätzliche Informationen
    case BUCHUNGSTEXT                  = 'Buchungstext';                            // 18: Buchungstext (K)
    case VERWENDUNGSZWECK_5            = 'Verwendungszweck 5';                      // 19: Verwendungszweck 5 (K)
    case VERWENDUNGSZWECK_6            = 'Verwendungszweck 6';                      // 20: Verwendungszweck 6 (K)
    case VERWENDUNGSZWECK_7            = 'Verwendungszweck 7';                      // 21: Verwendungszweck 7 (K)
    case VERWENDUNGSZWECK_8            = 'Verwendungszweck 8';                      // 22: Verwendungszweck 8 (K)
    case VERWENDUNGSZWECK_9            = 'Verwendungszweck 9';                      // 23: Verwendungszweck 9 (K)
    case VERWENDUNGSZWECK_10           = 'Verwendungszweck 10';                     // 24: Verwendungszweck 10 (K)
    case URSPRUNGSBETRAG               = 'Ursprungsbetrag';                         // 25: Ursprungsbetrag (K)
    case WAEHRUNG_URSPRUNGSBETRAG      = 'Währung Ursprungsbetrag';                 // 26: Währung Ursprungsbetrag (K)
    case AEQUIVALENZBETRAG             = 'Äquivalenzbetrag';                        // 27: Äquivalenzbetrag (K)
    case WAEHRUNG_AEQUIVALENZBETRAG    = 'Währung Äquivalenzbetrag';                // 28: Währung Äquivalenzbetrag (K)
    case GEBUEHR                       = 'Gebühr';                                  // 29: Gebühr (K)
    case WAEHRUNG_GEBUEHR              = 'Währung Gebühr';                          // 30: Währung Gebühr (K)
    case VERWENDUNGSZWECK_11           = 'Verwendungszweck 11';                     // 31: Verwendungszweck 11 (K)
    case VERWENDUNGSZWECK_12           = 'Verwendungszweck 12';                     // 32: Verwendungszweck 12 (K)
    case VERWENDUNGSZWECK_13           = 'Verwendungszweck 13';                     // 33: Verwendungszweck 13 (K)
    case VERWENDUNGSZWECK_14           = 'Verwendungszweck 14';                     // 34: Verwendungszweck 14 (K)

    /**
     * Liefert alle 34 Felder in der korrekten Reihenfolge (nach DATEV-Dokumentation).
     *
     * @return static[]
     */
    public static function ordered(): array {
        return [
            // Felder 1-17: Grundfelder
            self::BLZ_BIC_KONTOINHABER,
            self::KONTONUMMER_IBAN_KONTOINHABER,
            self::AUSZUGSNUMMER,
            self::AUSZUGSDATUM,
            self::VALUTA,
            self::BUCHUNGSDATUM,
            self::UMSATZ,
            self::AUFTRAGGEBERNAME_1,
            self::AUFTRAGGEBERNAME_2,
            self::BLZ_BIC_AUFTRAGGEBER,
            self::KONTONUMMER_IBAN_AUFTRAGGEBER,
            self::VERWENDUNGSZWECK_1,
            self::VERWENDUNGSZWECK_2,
            self::VERWENDUNGSZWECK_3,
            self::VERWENDUNGSZWECK_4,
            self::GESCHAEFTSVORGANGSCODE,
            self::WAEHRUNG,
            // Felder 18-34: Erweiterte Felder
            self::BUCHUNGSTEXT,
            self::VERWENDUNGSZWECK_5,
            self::VERWENDUNGSZWECK_6,
            self::VERWENDUNGSZWECK_7,
            self::VERWENDUNGSZWECK_8,
            self::VERWENDUNGSZWECK_9,
            self::VERWENDUNGSZWECK_10,
            self::URSPRUNGSBETRAG,
            self::WAEHRUNG_URSPRUNGSBETRAG,
            self::AEQUIVALENZBETRAG,
            self::WAEHRUNG_AEQUIVALENZBETRAG,
            self::GEBUEHR,
            self::WAEHRUNG_GEBUEHR,
            self::VERWENDUNGSZWECK_11,
            self::VERWENDUNGSZWECK_12,
            self::VERWENDUNGSZWECK_13,
            self::VERWENDUNGSZWECK_14,
        ];
    }

    /**
     * Liefert die verpflichtenden Felder laut DATEV-Dokumentation.
     * Muss-Felder (M): 1, 2, 6, 7
     */
    public static function required(): array {
        return [
            self::BLZ_BIC_KONTOINHABER,          // Feld 1: Muss-Feld
            self::KONTONUMMER_IBAN_KONTOINHABER, // Feld 2: Muss-Feld
            self::BUCHUNGSDATUM,                 // Feld 6: Muss-Feld
            self::UMSATZ,                        // Feld 7: Muss-Feld
        ];
    }

    /**
     * Prüft, ob das Feld verpflichtend ist.
     */
    public function isRequired(): bool {
        return in_array($this, self::required(), true);
    }

    /**
     * Gibt das Regex-Validierungsmuster für das Feld zurück (nach DATEV-Dokumentation).
     */
    public function pattern(): ?string {
        return match ($this) {
            self::BLZ_BIC_KONTOINHABER => '/^(\d{5}|\d{8}|[A-Z]{11})$/',           // 5/8 Stellen BLZ oder 11 Stellen BIC
            self::KONTONUMMER_IBAN_KONTOINHABER => '/^.{1,34}$/',                  // 1-34 Zeichen
            self::AUSZUGSNUMMER => '/^.{0,4}$/',                                   // 0-4 Zeichen
            self::AUSZUGSDATUM => '/^.{0,10}$/',                                   // 4-10 Zeichen Datumsformat
            self::VALUTA => '/^.{0,10}$/',                                         // 4-10 Zeichen Datumsformat
            self::BUCHUNGSDATUM => '/^.{6,10}$/',                                  // 6-10 Zeichen, Jahr erforderlich
            self::UMSATZ => '/^[+-]?\d{1,13}([.,]\d{2})?$/',                       // 4-15 Zeichen mit Vorzeichen
            self::AUFTRAGGEBERNAME_1 => '/^.{0,27}$/',                             // 0-27 Zeichen
            self::AUFTRAGGEBERNAME_2 => '/^.{0,27}$/',                             // 0-27 Zeichen
            self::BLZ_BIC_AUFTRAGGEBER => '/^(\d{5}|\d{8}|[A-Z]{11})?$/',          // Optional: 5/8 Stellen BLZ oder 11 Stellen BIC
            self::KONTONUMMER_IBAN_AUFTRAGGEBER => '/^.{0,34}$/',                  // 0-34 Zeichen
            self::VERWENDUNGSZWECK_1,
            self::VERWENDUNGSZWECK_2,
            self::VERWENDUNGSZWECK_3,
            self::VERWENDUNGSZWECK_4,
            self::VERWENDUNGSZWECK_5,
            self::VERWENDUNGSZWECK_6,
            self::VERWENDUNGSZWECK_7,
            self::VERWENDUNGSZWECK_8,
            self::VERWENDUNGSZWECK_9,
            self::VERWENDUNGSZWECK_10,
            self::VERWENDUNGSZWECK_11,
            self::VERWENDUNGSZWECK_12,
            self::VERWENDUNGSZWECK_13,
            self::VERWENDUNGSZWECK_14 => '/^.{0,27}$/',                            // 0-27 Zeichen
            self::GESCHAEFTSVORGANGSCODE => '/^\d{0,3}$/',                         // 0-3 stelliger Code
            self::WAEHRUNG => '/^[A-Z]{3}$/',                                      // 3-stelliger Währungscode
            self::BUCHUNGSTEXT => '/^.{0,27}$/',                                   // 0-27 Zeichen
            self::URSPRUNGSBETRAG,
            self::AEQUIVALENZBETRAG,
            self::GEBUEHR => '/^([+-]?\d{1,13}([.,]\d{2})?)?$/',                   // Optional: 4-15 Zeichen mit Vorzeichen
            self::WAEHRUNG_URSPRUNGSBETRAG,
            self::WAEHRUNG_AEQUIVALENZBETRAG,
            self::WAEHRUNG_GEBUEHR => '/^([A-Z]{3})?$/',                           // Optional: 3-stelliger Währungscode
            default => null,  // Keine spezifische Validierung
        };
    }

    /**
     * Gibt die Position des Felds zurück (1-basiert).
     */
    public function position(): int {
        return $this->index() + 1;
    }

    /**
     * Gibt den Array-Index des Felds zurück (0-basiert).
     * 
     * Zum direkten Zugriff auf Feld-Arrays: $fields[$field->index()]
     */
    public function index(): int {
        $ordered = self::ordered();
        $index = array_search($this, $ordered, true);
        return $index !== false ? $index : -1;
    }

    /**
     * Gibt die maximale Feldlänge entsprechend der DATEV-Dokumentation zurück.
     * 
     * @return int|null Maximale Anzahl Zeichen oder null für unbegrenzt
     */
    public function getMaxLength(): ?int {
        return match ($this) {
            // Felder mit fester Länge
            self::BLZ_BIC_KONTOINHABER => 11,              // BIC: 11 Zeichen, BLZ: 8 Zeichen (max. 11)
            self::KONTONUMMER_IBAN_KONTOINHABER => 34,     // IBAN max. 34 Zeichen
            self::AUSZUGSNUMMER => 4,                      // Max. 4 Zeichen
            self::AUSZUGSDATUM => 10,                      // tt.mm.jjjj = 10 Zeichen
            self::VALUTA => 10,                            // tt.mm.jjjj = 10 Zeichen
            self::BUCHUNGSDATUM => 10,                     // tt.mm.jjjj = 10 Zeichen
            self::UMSATZ => 15,                            // ±9999999999999,99 = 15 Zeichen max
            self::AUFTRAGGEBERNAME_1 => 27,                // Max. 27 Zeichen
            self::AUFTRAGGEBERNAME_2 => 27,                // Max. 27 Zeichen
            self::BLZ_BIC_AUFTRAGGEBER => 11,              // BIC: 11 Zeichen, BLZ: 8 Zeichen (max. 11)
            self::KONTONUMMER_IBAN_AUFTRAGGEBER => 34,     // IBAN max. 34 Zeichen
            self::VERWENDUNGSZWECK_1 => 27,                // Max. 27 Zeichen
            self::VERWENDUNGSZWECK_2 => 27,                // Max. 27 Zeichen
            self::VERWENDUNGSZWECK_3 => 27,                // Max. 27 Zeichen
            self::VERWENDUNGSZWECK_4 => 27,                // Max. 27 Zeichen
            self::GESCHAEFTSVORGANGSCODE => 3,             // Max. 3 Zeichen
            self::WAEHRUNG => 3,                           // ISO 4217: 3 Zeichen
            self::BUCHUNGSTEXT => 27,                      // Max. 27 Zeichen
            self::VERWENDUNGSZWECK_5 => 27,                // Max. 27 Zeichen
            self::VERWENDUNGSZWECK_6 => 27,                // Max. 27 Zeichen
            self::VERWENDUNGSZWECK_7 => 27,                // Max. 27 Zeichen
            self::VERWENDUNGSZWECK_8 => 27,                // Max. 27 Zeichen
            self::VERWENDUNGSZWECK_9 => 27,                // Max. 27 Zeichen
            self::VERWENDUNGSZWECK_10 => 27,               // Max. 27 Zeichen
            self::URSPRUNGSBETRAG => 15,                   // ±9999999999999,99 = 15 Zeichen max
            self::WAEHRUNG_URSPRUNGSBETRAG => 3,           // ISO 4217: 3 Zeichen
            self::AEQUIVALENZBETRAG => 15,                 // ±9999999999999,99 = 15 Zeichen max
            self::WAEHRUNG_AEQUIVALENZBETRAG => 3,         // ISO 4217: 3 Zeichen
            self::GEBUEHR => 15,                           // ±9999999999999,99 = 15 Zeichen max
            self::WAEHRUNG_GEBUEHR => 3,                   // ISO 4217: 3 Zeichen
            self::VERWENDUNGSZWECK_11 => 27,               // Max. 27 Zeichen
            self::VERWENDUNGSZWECK_12 => 27,               // Max. 27 Zeichen
            self::VERWENDUNGSZWECK_13 => 27,               // Max. 27 Zeichen
            self::VERWENDUNGSZWECK_14 => 27,               // Max. 27 Zeichen
        };
    }

    /**
     * Gibt zurück, ob das Feld in Anführungszeichen gesetzt werden muss.
     * 
     * Nach DATEV-Dokumentation (Dok.-Nr. 9226961):
     * - Format "A" (alphanumerisch): Anführungszeichen erforderlich
     * - Format "N" (numerisch): Keine Anführungszeichen
     * - Format "D" (Datum): Keine Anführungszeichen (optional erlaubt)
     * 
     * HINWEIS: In der Praxis werden einige "N"-Felder trotzdem gequotet (z.B. Geschäftsvorgangscode).
     * Diese Implementierung orientiert sich an der tatsächlichen DATEV-Praxis.
     * 
     * @return bool True wenn das Feld gequotet werden muss
     */
    public function isQuoted(): bool {
        return match ($this) {
            // Alphanumerische Felder (Format A) - müssen gequotet werden
            self::BLZ_BIC_KONTOINHABER,              // Feld 1: A (BLZ oder BIC)
            self::KONTONUMMER_IBAN_KONTOINHABER,     // Feld 2: A (Kontonummer oder IBAN)
            self::AUFTRAGGEBERNAME_1,                // Feld 8: A
            self::AUFTRAGGEBERNAME_2,                // Feld 9: A
            self::BLZ_BIC_AUFTRAGGEBER,              // Feld 10: A (BLZ oder BIC)
            self::KONTONUMMER_IBAN_AUFTRAGGEBER,     // Feld 11: A (Kontonummer oder IBAN)
            self::VERWENDUNGSZWECK_1,                // Feld 12: A
            self::VERWENDUNGSZWECK_2,                // Feld 13: A
            self::VERWENDUNGSZWECK_3,                // Feld 14: A
            self::VERWENDUNGSZWECK_4,                // Feld 15: A
            self::GESCHAEFTSVORGANGSCODE,            // Feld 16: A
            self::WAEHRUNG,                          // Feld 17: A (Währungscode)
            self::BUCHUNGSTEXT,                      // Feld 18: A
            self::VERWENDUNGSZWECK_5,                // Feld 19: A
            self::VERWENDUNGSZWECK_6,                // Feld 20: A
            self::VERWENDUNGSZWECK_7,                // Feld 21: A
            self::VERWENDUNGSZWECK_8,                // Feld 22: A
            self::VERWENDUNGSZWECK_9,                // Feld 23: A
            self::VERWENDUNGSZWECK_10,               // Feld 24: A
            self::WAEHRUNG_URSPRUNGSBETRAG,          // Feld 26: A (Währungscode)
            self::WAEHRUNG_AEQUIVALENZBETRAG,        // Feld 28: A (Währungscode)
            self::WAEHRUNG_GEBUEHR,                  // Feld 30: A (Währungscode)
            self::VERWENDUNGSZWECK_11,               // Feld 31: A
            self::VERWENDUNGSZWECK_12,               // Feld 32: A
            self::VERWENDUNGSZWECK_13,               // Feld 33: A
            self::VERWENDUNGSZWECK_14                // Feld 34: A
            => true,

            // Numerische Felder (Format N) und Datumsfelder (Format D) - nicht gequotet
            self::AUSZUGSNUMMER,                     // Feld 3: N
            self::AUSZUGSDATUM,                      // Feld 4: D
            self::VALUTA,                            // Feld 5: D
            self::BUCHUNGSDATUM,                     // Feld 6: D
            self::UMSATZ,                            // Feld 7: N
            self::URSPRUNGSBETRAG,                   // Feld 25: N
            self::AEQUIVALENZBETRAG,                 // Feld 27: N
            self::GEBUEHR                            // Feld 29: N
            => false,
        };
    }
}
