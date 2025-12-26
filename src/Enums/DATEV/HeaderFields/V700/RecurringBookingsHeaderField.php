<?php
/*
 * Created on   : Sun Dec 15 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : RecurringBookingsBatchHeaderField.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV\HeaderFields\V700;

use CommonToolkit\Contracts\Interfaces\DATEV\FieldHeaderInterface;
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;

/**
 * DATEV Wiederkehrende Buchungen - Feldheader (Spaltenbeschreibungen) V700.
 * Vollständige Implementierung aller 101 DATEV-Felder für wiederkehrende Buchungen
 * basierend auf der offiziellen DATEV-Spezifikation.
 * 
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/recurring-bookings
 */
enum RecurringBookingsHeaderField: string implements FieldHeaderInterface {
    // Spalten 1-10: Grunddaten der wiederkehrenden Buchung
    case B1                          = 'B1';                                       // 1
    case WKZUmsatz                   = 'WKZ Umsatz';                               // 2
    case Umsatz                      = 'Umsatz (ohne Soll/Haben-Kennzeichen)';     // 3
    case SollHabenKennzeichen        = 'Soll-/Haben-Kennzeichen';                  // 4
    case Kurs                        = 'Kurs';                                     // 5
    case BasisUmsatz                 = 'Basisumsatz';                              // 6
    case WKZBasisUmsatz              = 'WKZ Basisumsatz';                          // 7
    case BUSchluessel                = 'BU-Schlüssel';                             // 8
    case Gegenkonto                  = 'Gegenkonto (ohne BU-Schlüssel)';           // 9
    case Belegfeld1                  = 'Belegfeld 1';                              // 10

        // Spalten 11-20: Weitere Grunddaten
    case Belegfeld2                  = 'Belegfeld 2';                              // 11
    case Beginndatum                 = 'Beginndatum';                              // 12
    case Konto                       = 'Konto';                                    // 13
    case Stueck                      = 'Stück';                                    // 14
    case Gewicht                     = 'Gewicht';                                  // 15
    case KOST1                       = 'KOST1 -Kostenstelle';                      // 16
    case KOST2                       = 'KOST2 -Kostenstelle';                      // 17
    case KOSTMenge                   = 'KOST-Menge';                               // 18
    case Skonto                      = 'Skonto';                                   // 19
    case Buchungstext                = 'Buchungstext';                             // 20

        // Spalten 21-30: Sperren und Adressdaten
    case Postensperre                = 'Postensperre';                             // 21
    case DiverseAdressnummer         = 'Diverse Adressnummer';                     // 22
    case Geschaeftspartnerbank       = 'Geschäftspartnerbank';                     // 23
    case Sachverhalt                 = 'Sachverhalt';                              // 24
    case Zinssperre                  = 'Zinssperre';                               // 25
    case Beleglink                   = 'Beleglink';                                // 26
    case EULandUStIDBestimmung       = 'EU-Land u. UStID (Bestimmung)';            // 27
    case EUSteuersatzBestimmung      = 'EU-Steuersatz (Bestimmung)';               // 28
    case Leerfeld                    = 'Leerfeld';                                 // 29
    case SachverhaltLuL              = 'Sachverhalt L+L';                          // 30

        // Spalten 31-40: BU 49 und Zusatzinformationen (1-5)
    case BU49Hauptfunktionstyp       = 'BU 49 Hauptfunktionstyp';                  // 31
    case BU49Hauptfunktionsnummer    = 'BU 49 Hauptfunktionsnummer';               // 32
    case BU49Funktionsergaenzung     = 'BU 49 Funktionsergänzung';                 // 33
    case ZusatzinformationArt1       = 'Zusatzinformation - Art 1';                // 34
    case ZusatzinformationInhalt1    = 'Zusatzinformation - Inhalt 1';             // 35
    case ZusatzinformationArt2       = 'Zusatzinformation - Art 2';                // 36
    case ZusatzinformationInhalt2    = 'Zusatzinformation - Inhalt 2';             // 37
    case ZusatzinformationArt3       = 'Zusatzinformation - Art 3';                // 38
    case ZusatzinformationInhalt3    = 'Zusatzinformation - Inhalt 3';             // 39
    case ZusatzinformationArt4       = 'Zusatzinformation - Art 4';                // 40

        // Spalten 41-50: Zusatzinformationen (4-8)
    case ZusatzinformationInhalt4    = 'Zusatzinformation - Inhalt 4';             // 41
    case ZusatzinformationArt5       = 'Zusatzinformation - Art 5';                // 42
    case ZusatzinformationInhalt5    = 'Zusatzinformation - Inhalt 5';             // 43
    case ZusatzinformationArt6       = 'Zusatzinformation - Art 6';                // 44
    case ZusatzinformationInhalt6    = 'Zusatzinformation - Inhalt 6';             // 45
    case ZusatzinformationArt7       = 'Zusatzinformation - Art 7';                // 46
    case ZusatzinformationInhalt7    = 'Zusatzinformation - Inhalt 7';             // 47
    case ZusatzinformationArt8       = 'Zusatzinformation - Art 8';                // 48
    case ZusatzinformationInhalt8    = 'Zusatzinformation - Inhalt 8';             // 49
    case ZusatzinformationArt9       = 'Zusatzinformation - Art 9';                // 50

        // Spalten 51-60: Zusatzinformationen (9-13)
    case ZusatzinformationInhalt9    = 'Zusatzinformation - Inhalt 9';             // 51
    case ZusatzinformationArt10      = 'Zusatzinformation - Art 10';               // 52
    case ZusatzinformationInhalt10   = 'Zusatzinformation - Inhalt 10';            // 53
    case ZusatzinformationArt11      = 'Zusatzinformation - Art 11';               // 54
    case ZusatzinformationInhalt11   = 'Zusatzinformation - Inhalt 11';            // 55
    case ZusatzinformationArt12      = 'Zusatzinformation - Art 12';               // 56
    case ZusatzinformationInhalt12   = 'Zusatzinformation - Inhalt 12';            // 57
    case ZusatzinformationArt13      = 'Zusatzinformation - Art 13';               // 58
    case ZusatzinformationInhalt13   = 'Zusatzinformation - Inhalt 13';            // 59
    case ZusatzinformationArt14      = 'Zusatzinformation - Art 14';               // 60

        // Spalten 61-70: Zusatzinformationen (14-18)
    case ZusatzinformationInhalt14   = 'Zusatzinformation - Inhalt 14';            // 61
    case ZusatzinformationArt15      = 'Zusatzinformation - Art 15';               // 62
    case ZusatzinformationInhalt15   = 'Zusatzinformation - Inhalt 15';            // 63
    case ZusatzinformationArt16      = 'Zusatzinformation - Art 16';               // 64
    case ZusatzinformationInhalt16   = 'Zusatzinformation - Inhalt 16';            // 65
    case ZusatzinformationArt17      = 'Zusatzinformation - Art 17';               // 66
    case ZusatzinformationInhalt17   = 'Zusatzinformation - Inhalt 17';            // 67
    case ZusatzinformationArt18      = 'Zusatzinformation - Art 18';               // 68
    case ZusatzinformationInhalt18   = 'Zusatzinformation - Inhalt 18';            // 69
    case ZusatzinformationArt19      = 'Zusatzinformation - Art 19';               // 70

        // Spalten 71-80: Zusatzinformationen (19-20) und Zahlungsdaten
    case ZusatzinformationInhalt19   = 'Zusatzinformation - Inhalt 19';            // 71
    case ZusatzinformationArt20      = 'Zusatzinformation - Art 20';               // 72
    case ZusatzinformationInhalt20   = 'Zusatzinformation - Inhalt 20';            // 73
    case Zahlungsweise               = 'Zahlungsweise';                            // 74
    case Forderungsart               = 'Forderungsart';                            // 75
    case Veranlagungsjahr            = 'Veranlagungsjahr';                         // 76
    case ZugeordneteFaelligkeit      = 'Zugeordnete Fälligkeit';                   // 77
    case ZuletztPer                  = 'Zuletzt per';                              // 78
    case NaechsteFaelligkeit         = 'Nächste Fälligkeit';                       // 79
    case Enddatum                    = 'Enddatum';                                 // 80

        // Spalten 81-90: Zeitintervall und Gesellschafterdaten
    case Zeitintervallart            = 'Zeitintervallart';                         // 81
    case Zeitabstand                 = 'Zeitabstand';                              // 82
    case Wochentag                   = 'Wochentag';                                // 83
    case Monat                       = 'Monat';                                    // 84
    case OrdnungszahlTagImMonat      = 'Ordnungszahl: Tag im Monat';               // 85
    case OrdnungszahlWochentag       = 'Ordnungszahl: Wochentag';                  // 86
    case Endetyp                     = 'Endetyp';                                  // 87
    case Gesellschaftername          = 'Gesellschaftername';                       // 88
    case Beteiligtennummer           = 'Beteiligtennummer';                        // 89
    case Identifikationsnummer       = 'Identifikationsnummer';                    // 90

        // Spalten 91-101: Weitere Felder und EU-Daten
    case Zeichnernummer              = 'Zeichnernummer';                           // 91
    case SEPAMandatsreferenz         = 'SEPA-Mandatsreferenz';                     // 92
    case PostensperreBis             = 'Postensperre bis';                         // 93
    case KOSTDatum                   = 'KOST-Datum';                               // 94
    case BezeichnungSoBilSachverhalt = 'Bezeichnung SoBil-Sachverhalt';            // 95
    case KennzeichenSoBilBuchung     = 'Kennzeichen SoBil-Buchung';                // 96
    case Generalumkehr               = 'Generalumkehr';                            // 97
    case Steuersatz                  = 'Steuersatz';                               // 98
    case Land                        = 'Land';                                     // 99
    case EULandUStIDUrsprung         = 'EU-Land u. UStID (Ursprung)';              // 100
    case EUSteuersatzUrsprung        = 'EU-Steuersatz (Ursprung)';                 // 101

    /**
     * Liefert alle 101 Felder in der korrekten DATEV-Reihenfolge.
     */
    public static function ordered(): array {
        return [
            // Spalten 1-10: Grunddaten der wiederkehrenden Buchung
            self::B1,                          // 1
            self::WKZUmsatz,                   // 2
            self::Umsatz,                      // 3
            self::SollHabenKennzeichen,        // 4
            self::Kurs,                        // 5
            self::BasisUmsatz,                 // 6
            self::WKZBasisUmsatz,              // 7
            self::BUSchluessel,                // 8
            self::Gegenkonto,                  // 9
            self::Belegfeld1,                  // 10

            // Spalten 11-20: Weitere Grunddaten
            self::Belegfeld2,                  // 11
            self::Beginndatum,                 // 12
            self::Konto,                       // 13
            self::Stueck,                      // 14
            self::Gewicht,                     // 15
            self::KOST1,                       // 16
            self::KOST2,                       // 17
            self::KOSTMenge,                   // 18
            self::Skonto,                      // 19
            self::Buchungstext,                // 20

            // Spalten 21-30: Sperren und Adressdaten
            self::Postensperre,                // 21
            self::DiverseAdressnummer,         // 22
            self::Geschaeftspartnerbank,       // 23
            self::Sachverhalt,                 // 24
            self::Zinssperre,                  // 25
            self::Beleglink,                   // 26
            self::EULandUStIDBestimmung,       // 27
            self::EUSteuersatzBestimmung,      // 28
            self::Leerfeld,                    // 29
            self::SachverhaltLuL,              // 30

            // Spalten 31-40: BU 49 und Zusatzinformationen (1-5)
            self::BU49Hauptfunktionstyp,       // 31
            self::BU49Hauptfunktionsnummer,    // 32
            self::BU49Funktionsergaenzung,     // 33
            self::ZusatzinformationArt1,       // 34
            self::ZusatzinformationInhalt1,    // 35
            self::ZusatzinformationArt2,       // 36
            self::ZusatzinformationInhalt2,    // 37
            self::ZusatzinformationArt3,       // 38
            self::ZusatzinformationInhalt3,    // 39
            self::ZusatzinformationArt4,       // 40

            // Spalten 41-50: Zusatzinformationen (4-8)
            self::ZusatzinformationInhalt4,    // 41
            self::ZusatzinformationArt5,       // 42
            self::ZusatzinformationInhalt5,    // 43
            self::ZusatzinformationArt6,       // 44
            self::ZusatzinformationInhalt6,    // 45
            self::ZusatzinformationArt7,       // 46
            self::ZusatzinformationInhalt7,    // 47
            self::ZusatzinformationArt8,       // 48
            self::ZusatzinformationInhalt8,    // 49
            self::ZusatzinformationArt9,       // 50

            // Spalten 51-60: Zusatzinformationen (9-13)
            self::ZusatzinformationInhalt9,    // 51
            self::ZusatzinformationArt10,      // 52
            self::ZusatzinformationInhalt10,   // 53
            self::ZusatzinformationArt11,      // 54
            self::ZusatzinformationInhalt11,   // 55
            self::ZusatzinformationArt12,      // 56
            self::ZusatzinformationInhalt12,   // 57
            self::ZusatzinformationArt13,      // 58
            self::ZusatzinformationInhalt13,   // 59
            self::ZusatzinformationArt14,      // 60

            // Spalten 61-70: Zusatzinformationen (14-18)
            self::ZusatzinformationInhalt14,   // 61
            self::ZusatzinformationArt15,      // 62
            self::ZusatzinformationInhalt15,   // 63
            self::ZusatzinformationArt16,      // 64
            self::ZusatzinformationInhalt16,   // 65
            self::ZusatzinformationArt17,      // 66
            self::ZusatzinformationInhalt17,   // 67
            self::ZusatzinformationArt18,      // 68
            self::ZusatzinformationInhalt18,   // 69
            self::ZusatzinformationArt19,      // 70

            // Spalten 71-80: Zusatzinformationen (19-20) und Zahlungsdaten
            self::ZusatzinformationInhalt19,   // 71
            self::ZusatzinformationArt20,      // 72
            self::ZusatzinformationInhalt20,   // 73
            self::Zahlungsweise,               // 74
            self::Forderungsart,               // 75
            self::Veranlagungsjahr,            // 76
            self::ZugeordneteFaelligkeit,      // 77
            self::ZuletztPer,                  // 78
            self::NaechsteFaelligkeit,         // 79
            self::Enddatum,                    // 80

            // Spalten 81-90: Zeitintervall und Gesellschafterdaten
            self::Zeitintervallart,            // 81
            self::Zeitabstand,                 // 82
            self::Wochentag,                   // 83
            self::Monat,                       // 84
            self::OrdnungszahlTagImMonat,      // 85
            self::OrdnungszahlWochentag,       // 86
            self::Endetyp,                     // 87
            self::Gesellschaftername,          // 88
            self::Beteiligtennummer,           // 89
            self::Identifikationsnummer,       // 90

            // Spalten 91-101: Weitere Felder und EU-Daten
            self::Zeichnernummer,              // 91
            self::SEPAMandatsreferenz,         // 92
            self::PostensperreBis,             // 93
            self::KOSTDatum,                   // 94
            self::BezeichnungSoBilSachverhalt, // 95
            self::KennzeichenSoBilBuchung,     // 96
            self::Generalumkehr,               // 97
            self::Steuersatz,                  // 98
            self::Land,                        // 99
            self::EULandUStIDUrsprung,         // 100
            self::EUSteuersatzUrsprung,        // 101
        ];
    }

    /**
     * Liefert alle verpflichtenden Felder.
     */
    public static function required(): array {
        return [
            self::Umsatz,                      // Pflichtfeld: Umsatz muss angegeben werden
            self::SollHabenKennzeichen,        // Pflichtfeld: S oder H
            self::Konto,                       // Pflichtfeld: Konto für die Buchung
            self::Beginndatum,                 // Pflichtfeld: Beginndatum der Wiederholung
            self::Zeitintervallart,            // Pflichtfeld: TAG oder MON
            self::Zeitabstand,                 // Pflichtfeld: Intervall für Wiederholung
            self::Endetyp,                     // Pflichtfeld: Art des Endes (1-3)
        ];
    }

    /**
     * Liefert alle EU-relevanten Felder.
     */
    public static function euFields(): array {
        return [
            self::EULandUStIDBestimmung,
            self::EUSteuersatzBestimmung,
            self::EULandUStIDUrsprung,
            self::EUSteuersatzUrsprung,
        ];
    }

    /**
     * Liefert alle SEPA-relevanten Felder.
     */
    public static function sepaFields(): array {
        return [
            self::SEPAMandatsreferenz,
        ];
    }

    /**
     * Liefert alle Zeitintervall-relevanten Felder.
     */
    public static function timeIntervalFields(): array {
        return [
            self::Beginndatum,
            self::Enddatum,
            self::Zeitintervallart,
            self::Zeitabstand,
            self::Wochentag,
            self::Monat,
            self::OrdnungszahlTagImMonat,
            self::OrdnungszahlWochentag,
            self::Endetyp,
            self::ZuletztPer,
            self::NaechsteFaelligkeit,
        ];
    }

    /**
     * Liefert alle Zusatzinformationsfelder (ZI-Felder 34-73).
     */
    public static function additionalInfoFields(): array {
        return [
            self::ZusatzinformationArt1,
            self::ZusatzinformationInhalt1,
            self::ZusatzinformationArt2,
            self::ZusatzinformationInhalt2,
            self::ZusatzinformationArt3,
            self::ZusatzinformationInhalt3,
            self::ZusatzinformationArt4,
            self::ZusatzinformationInhalt4,
            self::ZusatzinformationArt5,
            self::ZusatzinformationInhalt5,
            self::ZusatzinformationArt6,
            self::ZusatzinformationInhalt6,
            self::ZusatzinformationArt7,
            self::ZusatzinformationInhalt7,
            self::ZusatzinformationArt8,
            self::ZusatzinformationInhalt8,
            self::ZusatzinformationArt9,
            self::ZusatzinformationInhalt9,
            self::ZusatzinformationArt10,
            self::ZusatzinformationInhalt10,
            self::ZusatzinformationArt11,
            self::ZusatzinformationInhalt11,
            self::ZusatzinformationArt12,
            self::ZusatzinformationInhalt12,
            self::ZusatzinformationArt13,
            self::ZusatzinformationInhalt13,
            self::ZusatzinformationArt14,
            self::ZusatzinformationInhalt14,
            self::ZusatzinformationArt15,
            self::ZusatzinformationInhalt15,
            self::ZusatzinformationArt16,
            self::ZusatzinformationInhalt16,
            self::ZusatzinformationArt17,
            self::ZusatzinformationInhalt17,
            self::ZusatzinformationArt18,
            self::ZusatzinformationInhalt18,
            self::ZusatzinformationArt19,
            self::ZusatzinformationInhalt19,
            self::ZusatzinformationArt20,
            self::ZusatzinformationInhalt20,
        ];
    }

    /**
     * Liefert alle Kostenrechnungsfelder.
     */
    public static function costAccountingFields(): array {
        return [
            self::KOST1,
            self::KOST2,
            self::KOSTMenge,
            self::KOSTDatum,
        ];
    }

    /**
     * Liefert alle Gesellschafterfelder.
     */
    public static function partnerFields(): array {
        return [
            self::Gesellschaftername,
            self::Beteiligtennummer,
            self::Identifikationsnummer,
            self::Zeichnernummer,
        ];
    }

    /**
     * Prüft, ob ein Feld verpflichtend ist.
     */
    public function isRequired(): bool {
        return in_array($this, self::required(), true);
    }

    /**
     * Prüft, ob ein Feld für EU-Buchungen relevant ist.
     */
    public function isEuField(): bool {
        return in_array($this, self::euFields(), true);
    }

    /**
     * Prüft, ob ein Feld für SEPA-Zahlungen relevant ist.
     */
    public function isSepaField(): bool {
        return in_array($this, self::sepaFields(), true);
    }

    /**
     * Prüft, ob ein Feld ein Zusatzinformationsfeld (ZI-Feld) ist.
     */
    public function isAdditionalInfoField(): bool {
        return in_array($this, self::additionalInfoFields(), true);
    }

    /**
     * Prüft, ob ein Feld für Zeitintervalle relevant ist.
     */
    public function isTimeIntervalField(): bool {
        return in_array($this, self::timeIntervalFields(), true);
    }

    /**
     * Prüft, ob ein Feld für Kostenrechnung relevant ist.
     */
    public function isCostAccountingField(): bool {
        return in_array($this, self::costAccountingFields(), true);
    }

    /**
     * Prüft, ob ein Feld für Gesellschafter relevant ist.
     */
    public function isPartnerField(): bool {
        return in_array($this, self::partnerFields(), true);
    }

    /**
     * Liefert eine Beschreibung des Feldtyps.
     */
    public function getFieldType(): string {
        return match ($this) {
            self::Umsatz, self::BasisUmsatz, self::Skonto,
            self::KOSTMenge, self::Stueck, self::Gewicht => 'numeric',

            self::Beginndatum, self::Enddatum, self::ZuletztPer,
            self::NaechsteFaelligkeit, self::ZugeordneteFaelligkeit,
            self::PostensperreBis, self::KOSTDatum => 'date',

            self::Kurs, self::Steuersatz,
            self::EUSteuersatzBestimmung, self::EUSteuersatzUrsprung => 'decimal',

            self::SollHabenKennzeichen, self::Postensperre,
            self::Zinssperre, self::Zeitintervallart,
            self::Endetyp, self::KennzeichenSoBilBuchung,
            self::Generalumkehr => 'enum',

            self::B1, self::Zeitabstand, self::Wochentag,
            self::Monat, self::OrdnungszahlTagImMonat,
            self::OrdnungszahlWochentag, self::Veranlagungsjahr,
            self::Beteiligtennummer => 'integer',

            default => 'string',
        };
    }

    /**
     * Liefert die maximale Feldlänge für DATEV.
     */
    public function getMaxLength(): ?int {
        return match ($this) {
            self::B1 => 1,
            self::WKZUmsatz, self::WKZBasisUmsatz => 3,
            self::SollHabenKennzeichen => 1,
            self::BUSchluessel => 4,
            self::Konto, self::Gegenkonto => 9,
            self::Belegfeld1 => 36,
            self::Belegfeld2 => 12,
            self::Stueck => 8,
            self::KOST1, self::KOST2 => 36,
            self::Buchungstext => 60,
            self::Zeitintervallart => 3,
            self::Zeitabstand => 3,
            self::Wochentag => 3,
            self::Monat => 2,
            self::OrdnungszahlTagImMonat => 2,
            self::OrdnungszahlWochentag => 1,
            self::Endetyp => 1,
            self::Gesellschaftername => 76,
            self::Beteiligtennummer => 4,
            self::Identifikationsnummer => 11,
            self::Zeichnernummer => 20,
            self::SEPAMandatsreferenz => 35,
            self::BezeichnungSoBilSachverhalt => 30,
            self::KennzeichenSoBilBuchung => 2,
            self::Generalumkehr => 1,
            self::Land => 2,
            self::EULandUStIDBestimmung, self::EULandUStIDUrsprung => 15,
            default => null, // Unbegrenzt oder unbekannt
        };
    }

    /**
     * Liefert das Regex-Pattern für DATEV-Validierung.
     */
    public function getValidationPattern(): ?string {
        return match ($this) {
            self::B1 => '^[\d]$',
            self::WKZUmsatz, self::WKZBasisUmsatz => '^["]([A-Z]{3})["]$',
            self::Umsatz, self::BasisUmsatz => '^(?!0{1,10}\,00)\d{1,10}\,\d{2}$',
            self::SollHabenKennzeichen => '^["][S|H]["]$',
            self::Kurs => '^([1-9]\d{0,3}[,]\d{2,6})$',
            self::BUSchluessel => '^(["][\d]{4}["])$',
            self::Konto, self::Gegenkonto => '^(?!0{1,9}$)(\d{1,9})$',
            self::Belegfeld1 => '^(["][\w$%-/]{0,36}["])$',
            self::Belegfeld2 => '^(["][\w$%-/]{0,12}["])$',
            self::Beginndatum, self::Enddatum => '^(["](0[1-9]|[1-2][0-9]|3[0-1])(0[1-9]|1[0-2])([2])([0])([0-9]{2})["])$',
            self::Stueck => '^[\d]{0,8}$',
            self::Gewicht => '^([\d]{1,8}[,][\d]{2})$',
            self::KOST1, self::KOST2 => '^(["](.){0,36}["])$',
            self::KOSTMenge => '^[\d]{12}[,][\d]{4}$',
            self::Skonto => '^([1-9][\d]{0,7}[,][\d]{2})$',
            self::Buchungstext => '^(["](.){0,60}["])$',
            self::Postensperre, self::Zinssperre => '^(0|1)$',
            self::DiverseAdressnummer => '^(["][\w]{0,9}["])$',
            self::Geschaeftspartnerbank => '^([\d]{3})$',
            self::Sachverhalt => '^([\d]{2})$',
            self::Beleglink => '^(["](.){0,210}["])$',
            self::EULandUStIDBestimmung, self::EULandUStIDUrsprung => '^(["](.){0,15}["])$',
            self::EUSteuersatzBestimmung, self::EUSteuersatzUrsprung => '^[\d]{2}[,][\d]{2}$',
            self::SachverhaltLuL => '^([\d]{1,3})$',
            self::BU49Hauptfunktionstyp => '^[\d]$',
            self::BU49Hauptfunktionsnummer => '^[\d]{0,2}$',
            self::BU49Funktionsergaenzung => '^[\d]{0,3}$',
            self::Zeitintervallart => '^["][TAG|MON]["]$',
            self::Zeitabstand => '^[\d]{1,3}$',
            self::Wochentag => '^[\d]{0,3}$',
            self::Monat => '^[\d]{0,2}$',
            self::OrdnungszahlTagImMonat => '^([1-9]|[1-2][0-9]|3[0-1])$',
            self::OrdnungszahlWochentag => '^[1-5]$',
            self::Endetyp => '^[1-3]$',
            self::Gesellschaftername => '^(["](.){0,76}["])$',
            self::Beteiligtennummer => '^([\d]{4})$',
            self::Identifikationsnummer => '^(["](.){0,11}["])$',
            self::Zeichnernummer => '^(["](.){0,20}["])$',
            self::SEPAMandatsreferenz => '^(["](.){0,35}["])$',
            self::Generalumkehr => '^(["](0|1)["])$',
            self::Steuersatz => '^(["][\d]{1,2}[,][\d]{2}["])$',
            self::Land => '^(["][A-Z]{2}["])$',
            default => null,
        };
    }
    /**
     * Liefert die DATEV-Kategorie für dieses Header-Format.
     */
    public static function getCategory(): Category {
        return Category::WiederkehrendeBuchungen;
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
     * Basierend auf dem Validierungspattern: Pattern mit ^["]... oder ^(["... = gequotet
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
        return match ($this) {
            self::WKZUmsatz => 'WKZ (Umsatz)',
            self::Umsatz => 'Umsatz (ohne Soll/Haben-Kz)',
            self::SollHabenKennzeichen => 'Soll/Haben-Kennzeichen',
            self::BasisUmsatz => 'Basis-Umsatz',
            self::WKZBasisUmsatz => 'WKZ Basis-Umsatz',
            self::BUSchluessel => 'BU-Schlüssel',
            self::Belegfeld1 => 'Belegfeld1',
            self::Belegfeld2 => 'Belegfeld2',
            self::Konto => 'Kontonummer',
            self::KOST1 => 'KOST1-Kostenstelle',
            self::KOST2 => 'KOST2-Kostenstelle',
            self::EULandUStIDBestimmung => 'EU-Land u. UStId (Bestimmung)',
            self::ZusatzinformationInhalt1 => 'Zusatzinformation- Inhalt 1',
            self::ZusatzinformationInhalt2 => 'Zusatzinformation- Inhalt 2',
            self::ZusatzinformationInhalt3 => 'Zusatzinformation- Inhalt 3',
            self::ZusatzinformationInhalt4 => 'Zusatzinformation- Inhalt 4',
            self::ZusatzinformationInhalt5 => 'Zusatzinformation- Inhalt 5',
            self::ZusatzinformationInhalt6 => 'Zusatzinformation- Inhalt 6',
            self::ZusatzinformationInhalt7 => 'Zusatzinformation- Inhalt 7',
            self::ZusatzinformationInhalt8 => 'Zusatzinformation- Inhalt 8',
            self::ZusatzinformationInhalt9 => 'Zusatzinformation- Inhalt 9',
            self::ZusatzinformationInhalt10 => 'Zusatzinformation- Inhalt 10',
            self::ZusatzinformationInhalt11 => 'Zusatzinformation- Inhalt 11',
            self::ZusatzinformationInhalt12 => 'Zusatzinformation- Inhalt 12',
            self::ZusatzinformationInhalt13 => 'Zusatzinformation- Inhalt 13',
            self::ZusatzinformationInhalt14 => 'Zusatzinformation- Inhalt 14',
            self::ZusatzinformationInhalt15 => 'Zusatzinformation- Inhalt 15',
            self::ZusatzinformationInhalt16 => 'Zusatzinformation- Inhalt 16',
            self::ZusatzinformationInhalt17 => 'Zusatzinformation- Inhalt 17',
            self::ZusatzinformationInhalt18 => 'Zusatzinformation- Inhalt 18',
            self::ZusatzinformationInhalt19 => 'Zusatzinformation- Inhalt 19',
            self::ZusatzinformationInhalt20 => 'Zusatzinformation- Inhalt 20',
            self::Zahlungsweise => 'Zahlweise',
            self::OrdnungszahlTagImMonat => 'Ordnungszahl Tag im Monat',
            self::OrdnungszahlWochentag => 'Ordnungszahl Wochentag',
            self::Endetyp => 'EndeTyp',
            self::Generalumkehr => 'Generalumkehr (GU)',
            default => $this->value,
        };
    }
}
