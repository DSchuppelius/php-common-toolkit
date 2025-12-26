<?php
/*
 * Created on   : Sat Dec 14 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingBatchHeaderField.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV\HeaderFields\V700;

use CommonToolkit\Contracts\Interfaces\DATEV\FieldHeaderInterface;
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;

/**
 * DATEV BookingBatch - Feldheader (Spaltenbeschreibungen) V700.
 * Vollständige Implementierung aller 125 DATEV-Felder in korrekter Reihenfolge,
 * einschließlich der 20 Zusatzinformationsfelder (ZI-Felder).
 * 
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/booking-batch
 */
enum BookingBatchHeaderField: string implements FieldHeaderInterface {
    // Spalten 1-10: Grunddaten der Buchung (gemäß DATEV-Dokumentation)
    case Umsatz                      = 'Umsatz (ohne Soll/Haben-Kz)';               // 1
    case SollHabenKennzeichen        = 'Soll/Haben-Kennzeichen';                    // 2
    case WKZUmsatz                   = 'WKZ Umsatz';                                // 3
    case Kurs                        = 'Kurs';                                      // 4
    case BasisUmsatz                 = 'Basis-Umsatz';                             // 5
    case WKZBasisUmsatz              = 'WKZ Basis-Umsatz';                         // 6
    case Konto                       = 'Konto';                                     // 7
    case Gegenkonto                  = 'Gegenkonto (ohne BU-Schlüssel)';            // 8
    case BUSchluessel                = 'BU-Schlüssel';                              // 9
    case Belegdatum                  = 'Belegdatum';                                // 10

        // Spalten 11-20: Belegdaten und Buchungstext
    case Belegfeld1                  = 'Belegfeld 1';                               // 11
    case Belegfeld2                  = 'Belegfeld 2';                               // 12
    case Skonto                      = 'Skonto';                                    // 13
    case Buchungstext                = 'Buchungstext';                              // 14
    case Postensperre                = 'Postensperre';                              // 15
    case DiverseAdressnummer         = 'Diverse Adressnummer';                      // 16
    case Geschaeftspartnerbank       = 'Geschäftspartnerbank';                      // 17
    case Sachverhalt                 = 'Sachverhalt';                               // 18
    case Zinssperre                  = 'Zinssperre';                                // 19
    case Beleglink                   = 'Beleglink';                                 // 20

        // Spalten 21-36: Beleginfo-Felder (Art/Inhalt-Paare)
    case BelegInfoArt1               = 'Beleginfo - Art 1';                         // 21
    case BelegInfoInhalt1            = 'Beleginfo - Inhalt 1';                      // 22
    case BelegInfoArt2               = 'Beleginfo - Art 2';                         // 23
    case BelegInfoInhalt2            = 'Beleginfo - Inhalt 2';                      // 24
    case BelegInfoArt3               = 'Beleginfo - Art 3';                         // 25
    case BelegInfoInhalt3            = 'Beleginfo - Inhalt 3';                      // 26
    case BelegInfoArt4               = 'Beleginfo - Art 4';                         // 27
    case BelegInfoInhalt4            = 'Beleginfo - Inhalt 4';                      // 28
    case BelegInfoArt5               = 'Beleginfo - Art 5';                         // 29
    case BelegInfoInhalt5            = 'Beleginfo - Inhalt 5';                      // 30
    case BelegInfoArt6               = 'Beleginfo - Art 6';                         // 31
    case BelegInfoInhalt6            = 'Beleginfo - Inhalt 6';                      // 32
    case BelegInfoArt7               = 'Beleginfo - Art 7';                         // 33
    case BelegInfoInhalt7            = 'Beleginfo - Inhalt 7';                      // 34
    case BelegInfoArt8               = 'Beleginfo - Art 8';                         // 35
    case BelegInfoInhalt8            = 'Beleginfo - Inhalt 8';                      // 36

        // Spalten 37-40: Kostenrechnung
    case KOST1                       = 'KOST1 - Kostenstelle';                      // 37
    case KOST2                       = 'KOST2 - Kostenstelle';                      // 38
    case KostMenge                   = 'Kost-Menge';                                // 39
    case EULandUStID                 = 'EU-Land u. UStID (Bestimmung)';              // 40

        // Spalten 41-47: EU/Steuer und BU-Schlüssel
    case EUSteuer                    = 'EU-Steuersatz (Bestimmung)';                 // 41
    case Abwkonto                    = 'Abw. Versteuerungsart';                     // 42
    case SachverhaltLuL              = 'Sachverhalt L+L';                           // 43
    case FunktionsergaenzungLuL      = 'Funktionsergänzung L+L';                    // 44
    case BU49Hauptfunktionstyp       = 'BU 49 Hauptfunktionstyp';                   // 45
    case BU49Hauptfunktionsnummer    = 'BU 49 Hauptfunktionsnummer';                // 46
    case BU49Funktionsergaenzung     = 'BU 49 Funktionsergänzung';                  // 47

        // Spalten 48-87: Zusatzinformationen (ZI-Felder) - Art/Inhalt-Paare
    case ZusatzInfo1                 = 'Zusatzinformation - Art 1';                 // 48
    case ZusatzInfoInhalt1           = 'Zusatzinformation- Inhalt 1';              // 49
    case ZusatzInfo2                 = 'Zusatzinformation - Art 2';                 // 50
    case ZusatzInfoInhalt2           = 'Zusatzinformation- Inhalt 2';              // 51
    case ZusatzInfo3                 = 'Zusatzinformation - Art 3';                 // 52
    case ZusatzInfoInhalt3           = 'Zusatzinformation- Inhalt 3';              // 53
    case ZusatzInfo4                 = 'Zusatzinformation - Art 4';                 // 54
    case ZusatzInfoInhalt4           = 'Zusatzinformation- Inhalt 4';              // 55
    case ZusatzInfo5                 = 'Zusatzinformation - Art 5';                 // 56
    case ZusatzInfoInhalt5           = 'Zusatzinformation- Inhalt 5';              // 57
    case ZusatzInfo6                 = 'Zusatzinformation - Art 6';                 // 58
    case ZusatzInfoInhalt6           = 'Zusatzinformation- Inhalt 6';              // 59
    case ZusatzInfo7                 = 'Zusatzinformation - Art 7';                 // 60
    case ZusatzInfoInhalt7           = 'Zusatzinformation- Inhalt 7';              // 61
    case ZusatzInfo8                 = 'Zusatzinformation - Art 8';                 // 62
    case ZusatzInfoInhalt8           = 'Zusatzinformation- Inhalt 8';              // 63
    case ZusatzInfo9                 = 'Zusatzinformation - Art 9';                 // 64
    case ZusatzInfoInhalt9           = 'Zusatzinformation- Inhalt 9';              // 65
    case ZusatzInfo10                = 'Zusatzinformation - Art 10';                // 66
    case ZusatzInfoInhalt10          = 'Zusatzinformation- Inhalt 10';             // 67
    case ZusatzInfo11                = 'Zusatzinformation - Art 11';                // 68
    case ZusatzInfoInhalt11          = 'Zusatzinformation- Inhalt 11';             // 69
    case ZusatzInfo12                = 'Zusatzinformation - Art 12';                // 70
    case ZusatzInfoInhalt12          = 'Zusatzinformation- Inhalt 12';             // 71
    case ZusatzInfo13                = 'Zusatzinformation - Art 13';                // 72
    case ZusatzInfoInhalt13          = 'Zusatzinformation- Inhalt 13';             // 73
    case ZusatzInfo14                = 'Zusatzinformation - Art 14';                // 74
    case ZusatzInfoInhalt14          = 'Zusatzinformation- Inhalt 14';             // 75
    case ZusatzInfo15                = 'Zusatzinformation - Art 15';                // 76
    case ZusatzInfoInhalt15          = 'Zusatzinformation- Inhalt 15';             // 77
    case ZusatzInfo16                = 'Zusatzinformation - Art 16';                // 78
    case ZusatzInfoInhalt16          = 'Zusatzinformation- Inhalt 16';             // 79
    case ZusatzInfo17                = 'Zusatzinformation - Art 17';                // 80
    case ZusatzInfoInhalt17          = 'Zusatzinformation- Inhalt 17';             // 81
    case ZusatzInfo18                = 'Zusatzinformation - Art 18';                // 82
    case ZusatzInfoInhalt18          = 'Zusatzinformation- Inhalt 18';             // 83
    case ZusatzInfo19                = 'Zusatzinformation - Art 19';                // 84
    case ZusatzInfoInhalt19          = 'Zusatzinformation- Inhalt 19';             // 85
    case ZusatzInfo20                = 'Zusatzinformation - Art 20';                // 86
    case ZusatzInfoInhalt20          = 'Zusatzinformation- Inhalt 20';             // 87

        // Spalten 88-99: Stückzahl, Gewicht und weitere Felder
    case Stueck                      = 'Stück';                                     // 88
    case Gewicht                     = 'Gewicht';                                   // 89
    case Zahlweise                   = 'Zahlweise';                                 // 90
    case Forderungsart               = 'Forderungsart';                             // 91
    case Veranlagungsjahr            = 'Veranlagungsjahr';                          // 92
    case ZugeordneteFaelligkeit      = 'Zugeordnete Fälligkeit';                    // 93
    case SkontoTyp                   = 'Skontotyp';                                 // 94
    case Auftragsnummer              = 'Auftragsnummer';                            // 95
    case Buchungstyp                 = 'Buchungstyp';                               // 96
    case UStSchluessel               = 'USt-Schlüssel (Anzahlungen)';               // 97
    case EUMitgliedstaatAnzahlung    = 'EU-Land (Anzahlungen)';                      // 98
    case SachverhaltLL               = 'Sachverhalt L+L (Anzahlungen)';             // 99

        // Spalten 100-109: EU-Herkunft und Mahnung/Sperrung
    case EUSteuersatzAnzahlung       = 'EU-Steuersatz (Anzahlungen)';               // 100
    case ErloeskontoAnzahlung        = 'Erlöskonto (Anzahlungen)';                  // 101
    case HerkunftKZ                  = 'Herkunft-Kz';                               // 102
    case Leerfeld                    = 'Buchungs GUID';                              // 103
    case KOSTDatum                   = 'KOST-Datum';                                // 104
    case SEPAMandatsreferenz         = 'SEPA-Mandatsreferenz';                      // 105
    case Skontosperre                = 'Skontosperre';                              // 106
    case Gesellschaftername          = 'Gesellschaftername';                        // 107
    case Beteiligtennummer           = 'Beteiligtennummer';                         // 108
    case Identifikationsnummer       = 'Identifikationsnummer';                     // 109

        // Spalten 110-119: Weitere Felder
    case Zeichnernummer              = 'Zeichnernummer';                            // 110
    case PostensperreBis             = 'Postensperre bis';                          // 111
    case BezeichnungSoBilSachverhalt = 'Bezeichnung SoBil-Sachverhalt';             // 112
    case KennzeichenSoBilBuchung     = 'Kennzeichen SoBil-Buchung';                 // 113
    case Festschreibung              = 'Festschreibung';                            // 114
    case Leistungsdatum              = 'Leistungsdatum';                            // 115
    case DatumZuordnungSteuerperiode = 'Datum Zuord. Steuerperiode';                // 116
    case Faelligkeit                 = 'Fälligkeit';                                // 117
    case Generalumkehr               = 'Generalumkehr (GU)';                         // 118
    case Steuersatz                  = 'Steuersatz';                                // 119

        // Spalten 120-125: Weitere Felder
    case Land                        = 'Land';                                      // 120
    case Abrechnungsreferenz         = 'Abrechnungsreferenz';                       // 121
    case BVVPosition                 = 'BVV-Position';                               // 122
    case EUMitgliedstaatUstID        = 'EU-Land u. UStID (Ursprung)';                // 123
    case EUSteuersatzUrsprung        = 'EU-Steuersatz (Ursprung)';                  // 124
    case AbwSkontokonto              = 'Abw. Skontokonto';                          // 125

    /**
     * Liefert alle 125 Felder in der korrekten DATEV-Reihenfolge.
     * Einschließlich der 40 Zusatzinformationsfelder (ZI-Felder 48-87).
     */
    public static function ordered(): array {
        return [
            // Spalten 1-10: Grunddaten der Buchung
            self::Umsatz,                      // 1
            self::SollHabenKennzeichen,        // 2
            self::WKZUmsatz,                   // 3
            self::Kurs,                        // 4
            self::BasisUmsatz,                 // 5
            self::WKZBasisUmsatz,              // 6
            self::Konto,                       // 7
            self::Gegenkonto,                  // 8
            self::BUSchluessel,                // 9
            self::Belegdatum,                  // 10

            // Spalten 11-20: Belegdaten und Buchungstext
            self::Belegfeld1,                  // 11
            self::Belegfeld2,                  // 12
            self::Skonto,                      // 13
            self::Buchungstext,                // 14
            self::Postensperre,                // 15
            self::DiverseAdressnummer,         // 16
            self::Geschaeftspartnerbank,       // 17
            self::Sachverhalt,                 // 18
            self::Zinssperre,                  // 19
            self::Beleglink,                   // 20

            // Spalten 21-36: Beleginfo-Felder (Art/Inhalt-Paare)
            self::BelegInfoArt1,               // 21
            self::BelegInfoInhalt1,            // 22
            self::BelegInfoArt2,               // 23
            self::BelegInfoInhalt2,            // 24
            self::BelegInfoArt3,               // 25
            self::BelegInfoInhalt3,            // 26
            self::BelegInfoArt4,               // 27
            self::BelegInfoInhalt4,            // 28
            self::BelegInfoArt5,               // 29
            self::BelegInfoInhalt5,            // 30
            self::BelegInfoArt6,               // 31
            self::BelegInfoInhalt6,            // 32
            self::BelegInfoArt7,               // 33
            self::BelegInfoInhalt7,            // 34
            self::BelegInfoArt8,               // 35
            self::BelegInfoInhalt8,            // 36

            // Spalten 37-40: Kostenrechnung
            self::KOST1,                       // 37
            self::KOST2,                       // 38
            self::KostMenge,                   // 39
            self::EULandUStID,                 // 40

            // Spalten 41-47: EU/Steuer und BU-Schlüssel
            self::EUSteuer,                    // 41
            self::Abwkonto,                    // 42
            self::SachverhaltLuL,              // 43
            self::FunktionsergaenzungLuL,      // 44
            self::BU49Hauptfunktionstyp,       // 45
            self::BU49Hauptfunktionsnummer,    // 46
            self::BU49Funktionsergaenzung,     // 47

            // Spalten 48-87: Zusatzinformationen (ZI-Felder) - Art/Inhalt-Paare
            self::ZusatzInfo1,                 // 48
            self::ZusatzInfoInhalt1,           // 49
            self::ZusatzInfo2,                 // 50
            self::ZusatzInfoInhalt2,           // 51
            self::ZusatzInfo3,                 // 52
            self::ZusatzInfoInhalt3,           // 53
            self::ZusatzInfo4,                 // 54
            self::ZusatzInfoInhalt4,           // 55
            self::ZusatzInfo5,                 // 56
            self::ZusatzInfoInhalt5,           // 57
            self::ZusatzInfo6,                 // 58
            self::ZusatzInfoInhalt6,           // 59
            self::ZusatzInfo7,                 // 60
            self::ZusatzInfoInhalt7,           // 61
            self::ZusatzInfo8,                 // 62
            self::ZusatzInfoInhalt8,           // 63
            self::ZusatzInfo9,                 // 64
            self::ZusatzInfoInhalt9,           // 65
            self::ZusatzInfo10,                // 66
            self::ZusatzInfoInhalt10,          // 67
            self::ZusatzInfo11,                // 68
            self::ZusatzInfoInhalt11,          // 69
            self::ZusatzInfo12,                // 70
            self::ZusatzInfoInhalt12,          // 71
            self::ZusatzInfo13,                // 72
            self::ZusatzInfoInhalt13,          // 73
            self::ZusatzInfo14,                // 74
            self::ZusatzInfoInhalt14,          // 75
            self::ZusatzInfo15,                // 76
            self::ZusatzInfoInhalt15,          // 77
            self::ZusatzInfo16,                // 78
            self::ZusatzInfoInhalt16,          // 79
            self::ZusatzInfo17,                // 80
            self::ZusatzInfoInhalt17,          // 81
            self::ZusatzInfo18,                // 82
            self::ZusatzInfoInhalt18,          // 83
            self::ZusatzInfo19,                // 84
            self::ZusatzInfoInhalt19,          // 85
            self::ZusatzInfo20,                // 86
            self::ZusatzInfoInhalt20,          // 87

            // Spalten 88-99: Weitere Felder
            self::Stueck,                      // 88
            self::Gewicht,                     // 89
            self::Zahlweise,                   // 90
            self::Forderungsart,               // 91
            self::Veranlagungsjahr,            // 92
            self::ZugeordneteFaelligkeit,      // 93
            self::SkontoTyp,                   // 94
            self::Auftragsnummer,              // 95
            self::Buchungstyp,                 // 96
            self::UStSchluessel,               // 97
            self::EUMitgliedstaatAnzahlung,    // 98
            self::SachverhaltLL,               // 99

            // Spalten 100-109: EU-Herkunft und weitere Felder
            self::EUSteuersatzAnzahlung,       // 100
            self::ErloeskontoAnzahlung,        // 101
            self::HerkunftKZ,                  // 102
            self::Leerfeld,                    // 103
            self::KOSTDatum,                   // 104
            self::SEPAMandatsreferenz,         // 105
            self::Skontosperre,                // 106
            self::Gesellschaftername,          // 107
            self::Beteiligtennummer,           // 108
            self::Identifikationsnummer,       // 109

            // Spalten 110-125: Weitere Felder
            self::Zeichnernummer,              // 110
            self::PostensperreBis,             // 111
            self::BezeichnungSoBilSachverhalt, // 112
            self::KennzeichenSoBilBuchung,     // 113
            self::Festschreibung,              // 114
            self::Leistungsdatum,              // 115
            self::DatumZuordnungSteuerperiode, // 116
            self::Faelligkeit,                 // 117
            self::Generalumkehr,               // 118
            self::Steuersatz,                  // 119
            self::Land,                        // 120
            self::Abrechnungsreferenz,         // 121
            self::BVVPosition,                 // 122
            self::EUMitgliedstaatUstID,        // 123
            self::EUSteuersatzUrsprung,        // 124
            self::AbwSkontokonto,              // 125
        ];
    }

    /**
     * Liefert die Mindestfelder für einen gültigen BookingBatch.
     */
    public static function required(): array {
        return [
            self::Umsatz,                    // Betrag ist Pflicht
            self::SollHabenKennzeichen,      // S oder H ist Pflicht
            self::Konto,                     // Konto ist Pflicht
            self::Gegenkonto,                // Gegenkonto ist Pflicht
            self::Belegdatum,                // Belegdatum ist Pflicht
            self::Belegfeld1,                // Belegnummer ist Pflicht
            self::Buchungstext,              // Buchungstext ist Pflicht
        ];
    }

    /**
     * Liefert die empfohlenen Felder für Standard-Buchungen.
     */
    public static function recommended(): array {
        return array_merge(self::required(), [
            self::BUSchluessel,              // BU-Schlüssel für Steuerung
            self::Belegfeld2,                // Zusätzliche Beleginfo
            self::KOST1,                     // Kostenstelle
            self::Skonto,                    // Skonto-Behandlung
        ]);
    }

    /**
     * Liefert Felder für EU-Buchungen.
     */
    public static function euFields(): array {
        return [
            self::EULandUStID,
            self::EUSteuer,
            self::EUMitgliedstaatAnzahlung,
            self::EUSteuersatzAnzahlung,
            self::EUMitgliedstaatUstID,
            self::EUSteuersatzUrsprung,
        ];
    }

    /**
     * Liefert SEPA-relevante Felder.
     */
    public static function sepaFields(): array {
        return [
            self::SEPAMandatsreferenz,
            self::Zahlweise,
            self::Faelligkeit,
            self::Geschaeftspartnerbank,
            self::Skontosperre,
        ];
    }

    /**
     * Liefert Kostenstellenfelder.
     */
    public static function costFields(): array {
        return [
            self::KOST1,
            self::KOST2,
            self::KostMenge,
            self::Stueck,
            self::Gewicht,
            self::KOSTDatum,
        ];
    }

    /**
     * Liefert Steuerfelder.
     */
    public static function taxFields(): array {
        return [
            self::UStSchluessel,
            self::Steuersatz,
            self::EUSteuer,
            self::EUSteuersatzAnzahlung,
            self::EUSteuersatzUrsprung,
            self::Abwkonto,
        ];
    }

    /**
     * Liefert alle Zusatzinformationsfelder (ZI-Felder 48-87).
     */
    public static function additionalInfoFields(): array {
        return [
            self::ZusatzInfo1,
            self::ZusatzInfoInhalt1,
            self::ZusatzInfo2,
            self::ZusatzInfoInhalt2,
            self::ZusatzInfo3,
            self::ZusatzInfoInhalt3,
            self::ZusatzInfo4,
            self::ZusatzInfoInhalt4,
            self::ZusatzInfo5,
            self::ZusatzInfoInhalt5,
            self::ZusatzInfo6,
            self::ZusatzInfoInhalt6,
            self::ZusatzInfo7,
            self::ZusatzInfoInhalt7,
            self::ZusatzInfo8,
            self::ZusatzInfoInhalt8,
            self::ZusatzInfo9,
            self::ZusatzInfoInhalt9,
            self::ZusatzInfo10,
            self::ZusatzInfoInhalt10,
            self::ZusatzInfo11,
            self::ZusatzInfoInhalt11,
            self::ZusatzInfo12,
            self::ZusatzInfoInhalt12,
            self::ZusatzInfo13,
            self::ZusatzInfoInhalt13,
            self::ZusatzInfo14,
            self::ZusatzInfoInhalt14,
            self::ZusatzInfo15,
            self::ZusatzInfoInhalt15,
            self::ZusatzInfo16,
            self::ZusatzInfoInhalt16,
            self::ZusatzInfo17,
            self::ZusatzInfoInhalt17,
            self::ZusatzInfo18,
            self::ZusatzInfoInhalt18,
            self::ZusatzInfo19,
            self::ZusatzInfoInhalt19,
            self::ZusatzInfo20,
            self::ZusatzInfoInhalt20,
        ];
    }

    /**
     * Liefert alle Beleginfo-Felder (21-36).
     */
    public static function documentInfoFields(): array {
        return [
            self::BelegInfoArt1,
            self::BelegInfoInhalt1,
            self::BelegInfoArt2,
            self::BelegInfoInhalt2,
            self::BelegInfoArt3,
            self::BelegInfoInhalt3,
            self::BelegInfoArt4,
            self::BelegInfoInhalt4,
            self::BelegInfoArt5,
            self::BelegInfoInhalt5,
            self::BelegInfoArt6,
            self::BelegInfoInhalt6,
            self::BelegInfoArt7,
            self::BelegInfoInhalt7,
            self::BelegInfoArt8,
            self::BelegInfoInhalt8,
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
     * Prüft, ob ein Feld ein Beleginfo-Feld ist.
     */
    public function isDocumentInfoField(): bool {
        return in_array($this, self::documentInfoFields(), true);
    }

    /**
     * Liefert eine Beschreibung des Feldtyps.
     */
    public function getFieldType(): string {
        return match ($this) {
            self::Umsatz, self::BasisUmsatz, self::Skonto,
            self::KostMenge, self::Stueck, self::Gewicht => 'numeric',

            self::Belegdatum, self::Leistungsdatum,
            self::Faelligkeit, self::KOSTDatum,
            self::DatumZuordnungSteuerperiode => 'date',

            self::Kurs, self::Steuersatz,
            self::EUSteuer, self::EUSteuersatzAnzahlung,
            self::EUSteuersatzUrsprung => 'decimal',

            self::SollHabenKennzeichen, self::Postensperre,
            self::Zinssperre => 'enum',

            default => 'string',
        };
    }

    /**
     * Liefert die maximale Feldlänge für DATEV.
     */
    public function getMaxLength(): ?int {
        return match ($this) {
            self::Konto, self::Gegenkonto => 9,
            self::BUSchluessel => 2,
            self::SollHabenKennzeichen => 1,
            self::WKZUmsatz, self::WKZBasisUmsatz => 3,
            self::Belegfeld1, self::Belegfeld2 => 36,
            self::Buchungstext => 60,
            self::KOST1, self::KOST2 => 8,
            default => null, // Unbegrenzt oder unbekannt
        };
    }

    /**
     * Liefert das Regex-Pattern für DATEV-Validierung basierend auf der offiziellen Spezifikation.
     */
    public function getValidationPattern(): ?string {
        return match ($this) {
            // Spalte 1: Umsatz - Betrag muss positiv und darf nicht 0,00 sein
            self::Umsatz => '^(?!0{1,10}\,00)\d{1,10}\,\d{2}$',

            // Spalte 2: Soll-/Haben-Kennzeichen - S oder H
            self::SollHabenKennzeichen => '^["](S|H)["]$',

            // Spalte 3: WKZ Umsatz - ISO-Code der Währung (3 Zeichen)
            self::WKZUmsatz => '^["]([A-Z]{3})["]$',

            // Spalte 4: Kurs - Fremdwährungskurs, darf nicht 0 sein
            self::Kurs => '^([1-9]\d{0,3}[,]\d{2,6})$',

            // Spalte 5: Basisumsatz - Betrag muss positiv und darf nicht 0,00 sein
            self::BasisUmsatz => '^(?!0{1,10}\,00)\d{1,10}\,\d{2}$',

            // Spalten 7,8: Konto/Gegenkonto - darf nicht 0 sein, max. 9-stellig
            self::Konto, self::Gegenkonto => '^(?!0{1,9}$)(\d{1,9})$',

            // Spalte 9: BU-Schlüssel - 4-stelliger Schlüssel
            self::BUSchluessel => '^(["]\d{4}["])$',

            // Spalte 10: Belegdatum - Format TTMM
            self::Belegdatum => '^(\d{4})$',

            // Spalte 11: Belegfeld 1 - Rechnungs-/Belegnummer mit erlaubten Sonderzeichen
            self::Belegfeld1 => '^(["][\w$%\-\/]{0,36}["])$',

            // Spalte 12: Belegfeld 2 - OPOS-Verarbeitungsinformationen
            self::Belegfeld2 => '^(["][\w$%\-\/]{0,12}["])$',

            // Spalte 13: Skonto - Skonto-Betrag, darf nicht 0 sein
            self::Skonto => '^([1-9]\d{0,7}[,]\d{2})$',

            // Spalte 14: Buchungstext - 0-60 Zeichen
            self::Buchungstext => '^(["].{0,60}["])$',

            // Spalte 15: Postensperre - 0 oder 1
            self::Postensperre => '^(0|1)$',

            // Spalte 16: Diverse Adressnummer - Alphanumerisch, max. 9 Zeichen
            self::DiverseAdressnummer => '^(["]\w{0,9}["])$',

            // Spalte 17: Geschäftspartnerbank - 3-stellige Nummer
            self::Geschaeftspartnerbank => '^(\d{3})$',

            // Spalte 18: Sachverhalt - 2-stellige Kennzeichnung
            self::Sachverhalt => '^(\d{2})$',

            // Spalte 19: Zinssperre - 0 oder 1
            self::Zinssperre => '^(0|1)$',

            // Spalte 20: Beleglink - Allgemein max. 210 Zeichen oder spezifisch für DATEV Apps
            self::Beleglink => '^(["].{0,210}["])$',

            // Spalten 21,23,25,27,29,31,33,35: Beleginfo-Art - max. 20 Zeichen
            self::BelegInfoArt1, self::BelegInfoArt2, self::BelegInfoArt3, self::BelegInfoArt4,
            self::BelegInfoArt5, self::BelegInfoArt6, self::BelegInfoArt7, self::BelegInfoArt8 => '^(["].{0,20}["])$',

            // Spalten 22,24,26,28,30,32,34,36: Beleginfo-Inhalt - max. 210 Zeichen
            self::BelegInfoInhalt1, self::BelegInfoInhalt2, self::BelegInfoInhalt3, self::BelegInfoInhalt4,
            self::BelegInfoInhalt5, self::BelegInfoInhalt6, self::BelegInfoInhalt7, self::BelegInfoInhalt8 => '^(["].{0,210}["])$',

            // Spalten 37,38: KOST-Kostenstelle - Alphanumerisch mit Leerzeichen, max. 36 Zeichen
            self::KOST1, self::KOST2 => '^(["][\w ]{0,36}["])$',

            // Spalte 39: KOST-Menge - 12 Vor- und 4 Nachkommastellen
            self::KostMenge => '^\d{12}[,]\d{4}$',

            // Spalte 40: EU-Mitgliedstaat u. UStID (Bestimmung) - max. 15 Zeichen
            self::EULandUStID => '^(["].{0,15}["])$',

            // Spalte 41: EU-Steuersatz (Bestimmung) - Format XX,XX
            self::EUSteuer => '^\d{2}[,]\d{2}$',

            // Spalte 42: Abweichende Versteuerungsart - I, K, P oder S
            self::Abwkonto => '^(["](I|K|P|S)["])$',

            // Spalte 43: Sachverhalt L+L - 1-3 Stellen, darf nicht 0 sein
            self::SachverhaltLuL => '^(\d{1,3})$',

            // Spalte 44: Funktionsergänzung L+L - max. 3 Stellen, darf 0 sein aber nicht nur 0
            self::FunktionsergaenzungLuL => '^\d{0,3}$',

            // Spalte 45: BU 49 Hauptfunktionstyp - 1 Stelle
            self::BU49Hauptfunktionstyp => '^\d$',

            // Spalte 46: BU 49 Hauptfunktionsnummer - max. 2 Stellen
            self::BU49Hauptfunktionsnummer => '^\d{0,2}$',

            // Spalte 47: BU 49 Funktionsergänzung - max. 3 Stellen
            self::BU49Funktionsergaenzung => '^\d{0,3}$',

            // Spalten 48-87: Zusatzinformation Art - max. 20 Zeichen
            self::ZusatzInfo1, self::ZusatzInfo2, self::ZusatzInfo3, self::ZusatzInfo4, self::ZusatzInfo5,
            self::ZusatzInfo6, self::ZusatzInfo7, self::ZusatzInfo8, self::ZusatzInfo9, self::ZusatzInfo10,
            self::ZusatzInfo11, self::ZusatzInfo12, self::ZusatzInfo13, self::ZusatzInfo14, self::ZusatzInfo15,
            self::ZusatzInfo16, self::ZusatzInfo17, self::ZusatzInfo18, self::ZusatzInfo19, self::ZusatzInfo20 => '^(["].{0,20}["])$',

            // Spalten 49-87: Zusatzinformation Inhalt - max. 210 Zeichen
            self::ZusatzInfoInhalt1, self::ZusatzInfoInhalt2, self::ZusatzInfoInhalt3, self::ZusatzInfoInhalt4,
            self::ZusatzInfoInhalt5, self::ZusatzInfoInhalt6, self::ZusatzInfoInhalt7, self::ZusatzInfoInhalt8,
            self::ZusatzInfoInhalt9, self::ZusatzInfoInhalt10, self::ZusatzInfoInhalt11, self::ZusatzInfoInhalt12,
            self::ZusatzInfoInhalt13, self::ZusatzInfoInhalt14, self::ZusatzInfoInhalt15, self::ZusatzInfoInhalt16,
            self::ZusatzInfoInhalt17, self::ZusatzInfoInhalt18, self::ZusatzInfoInhalt19, self::ZusatzInfoInhalt20 => '^(["].{0,210}["])$',

            // Spalte 88: Stück - max. 8 Stellen
            self::Stueck => '^\d{0,8}$',

            // Spalte 89: Gewicht - 1-8 Stellen mit 2 Nachkommastellen
            self::Gewicht => '^(\d{1,8}[,]\d{2})$',

            // Spalte 90: Zahlweise - max. 2 Stellen
            self::Zahlweise => '^\d{0,2}$',

            // Spalte 91: Forderungsart - Alphanumerisch, max. 10 Zeichen
            self::Forderungsart => '^(["]\w{0,10}["])$',

            // Spalte 92: Veranlagungsjahr - Format JJJJ (20XX)
            self::Veranlagungsjahr => '^(([2])([0])([0-9]{2}))$',

            // Spalte 93: Zugeordnete Fälligkeit - Format TTMMJJJJ
            self::ZugeordneteFaelligkeit => '^((0[1-9]|[1-2][0-9]|3[0-1])(0[1-9]|1[0-2])([2])([0])([0-9]{2}))$',

            // Spalte 94: Skontotyp - 1 Stelle
            self::SkontoTyp => '^\d$',

            // Spalte 95: Auftragsnummer - max. 30 Zeichen
            self::Auftragsnummer => '^(["].{0,30}["])$',

            // Spalte 96: Buchungstyp - 2 Großbuchstaben
            self::Buchungstyp => '^(["][A-Z]{2}["])$',

            // Spalte 97: USt-Schlüssel (Anzahlungen) - max. 4 Stellen
            self::UStSchluessel => '^\d{0,4}$',

            // Spalte 98: EU-Mitgliedstaat (Anzahlungen) - 2 Großbuchstaben
            self::EUMitgliedstaatAnzahlung => '^(["][A-Z]{2}["])$',

            // Spalte 99: Sachverhalt L+L (Anzahlungen) - max. 3 Stellen, darf nicht 0 sein
            self::SachverhaltLL => '^\d{0,3}$',

            // Spalte 100: EU-Steuersatz (Anzahlungen) - Format X,XX oder XX,XX
            self::EUSteuersatzAnzahlung => '^(\d{1,2}[,]\d{2})$',

            // Spalte 101: Erlöskonto (Anzahlungen) - 4-8 Stellen
            self::ErloeskontoAnzahlung => '^(\d{4,8})$',

            // Spalte 102: Herkunft-Kz - 2 Großbuchstaben
            self::HerkunftKZ => '^(["][A-Z]{2}["])$',

            // Spalte 103: Leerfeld - max. 36 Zeichen
            self::Leerfeld => '^(["].{0,36}["])$',

            // Spalte 104: KOST-Datum - Format TTMMJJJJ
            self::KOSTDatum => '^((0[1-9]|[1-2]\d|3[0-1])(0[1-9]|1[0-2])([2])([0])(\d{2}))$',

            // Spalte 105: SEPA-Mandatsreferenz - max. 35 Zeichen
            self::SEPAMandatsreferenz => '^(["].{0,35}["])$',

            // Spalte 106: Skontosperre - 0 oder 1
            self::Skontosperre => '^[0|1]$',

            // Spalte 107: Gesellschaftername - max. 76 Zeichen
            self::Gesellschaftername => '^(["].{0,76}["])$',

            // Spalte 108: Beteiligtennummer - 4-stellige Nummer
            self::Beteiligtennummer => '^(\d{4})$',

            // Spalte 109: Identifikationsnummer - max. 11 Zeichen
            self::Identifikationsnummer => '^(["].{0,11}["])$',

            // Spalte 110: Zeichnernummer - max. 20 Zeichen
            self::Zeichnernummer => '^(["].{0,20}["])$',

            // Spalte 111: Postensperre bis - Format TTMMJJJJ
            self::PostensperreBis => '^((0[1-9]|[1-2]\d|3[0-1])(0[1-9]|1[0-2])([2])([0])(\d{2}))$',

            // Spalte 112: Bezeichnung SoBil-Sachverhalt - max. 30 Zeichen
            self::BezeichnungSoBilSachverhalt => '^(["].{0,30}["])$',

            // Spalte 113: Kennzeichen SoBil-Buchung - 1-2 Stellen
            self::KennzeichenSoBilBuchung => '^(\d{1,2})$',

            // Spalte 114: Festschreibung - 0 oder 1
            self::Festschreibung => '^(0|1)$',

            // Spalte 115: Leistungsdatum - Format TTMMJJJJ
            self::Leistungsdatum => '^((0[1-9]|[1-2]\d|3[0-1])(0[1-9]|1[0-2])([2])([0])(\d{2}))$',

            // Spalte 116: Datum Zuordnung Steuerperiode - Format TTMMJJJJ
            self::DatumZuordnungSteuerperiode => '^((0[1-9]|[1-2]\d|3[0-1])(0[1-9]|1[0-2])([2])([0])(\d{2}))$',

            // Spalte 117: Fälligkeit - Format TTMMJJJJ
            self::Faelligkeit => '^((0[1-9]|[1-2]\d|3[0-1])(0[1-9]|1[0-2])([2])([0])(\d{2}))$',

            // Spalte 118: Generalumkehr - "0" oder "1"
            self::Generalumkehr => '^(["](0|1)["])$',

            // Spalte 119: Steuersatz - Format XX,XX
            self::Steuersatz => '^(\d{1,2}[,]\d{2})$',

            // Spalte 120: Land - ISO-Code (2 Großbuchstaben)
            self::Land => '^(["][A-Z]{2}["])$',

            // Spalte 121: Abrechnungsreferenz - max. 50 Zeichen
            self::Abrechnungsreferenz => '^(["].{0,50}["])$',

            // Spalte 122: BVV-Position - 1-5
            self::BVVPosition => '^([1|2|3|4|5])$',

            // Spalte 123: EU-Mitgliedstaat u. UStID (Ursprung) - max. 15 Zeichen
            self::EUMitgliedstaatUstID => '^(["].{0,15}["])$',

            // Spalte 124: EU-Steuersatz (Ursprung) - Format XX,XX
            self::EUSteuersatzUrsprung => '^\d{2}[,]\d{2}$',

            // Spalte 125: Abweichendes Skontokonto - 1-9 Stellen
            self::AbwSkontokonto => '^(\d{1,9})$',

            default => null,
        };
    }

    /**
     * Gibt die Position/den Index des Feldes in der Feldreihenfolge zurück.
     * 
     * @return int Die nullbasierte Position des Feldes
     */
    public function getPosition(): int {
        $ordered = self::ordered();
        return array_search($this, $ordered, true) ?: 0;
    }

    /**
     * Liefert die DATEV-Kategorie für dieses Header-Format.
     */
    public static function getCategory(): Category {
        return Category::Buchungsstapel;
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
     * Gibt an, ob das Feld im FieldHeader gequotet werden soll.
     * DATEV FieldHeader sind standardmäßig nicht gequotet.
     */
    public function isQuotedHeader(): bool {
        return false;
    }

    /**
     * Gibt an, ob Datenwerte für dieses Feld gequotet werden sollen.
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
}
