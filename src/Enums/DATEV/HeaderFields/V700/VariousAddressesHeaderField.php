<?php
/*
 * Created on   : Sun Dec 15 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VariousAddressesHeaderField.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV\HeaderFields\V700;

use CommonToolkit\Contracts\Interfaces\DATEV\FieldHeaderInterface;
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;

/**
 * DATEV Diverse Adressen (Various Addresses) - Feldheader V700.
 * Vollständige Implementierung aller 191 DATEV-Felder für diverse Adressen
 * (Einmal-Debitoren/Kreditoren ohne eigenes Konto) basierend auf der offiziellen DATEV-Spezifikation.
 * 
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/various-addresses
 */
enum VariousAddressesHeaderField: string implements FieldHeaderInterface {
    // Spalten 1-10: Grunddaten und Namensangaben
    case Adressnummer                           = 'Adressnummer';                                // 1
    case Konto                                  = 'Konto';                                       // 2
    case Anrede                                 = 'Anrede';                                      // 3
    case NameUnternehmen                        = 'Name (Adressattyp Unternehmen)';              // 4
    case Unternehmensgegenstand                 = 'Unternehmensgegenstand';                      // 5
    case Kurzbezeichnung                        = 'Kurzbezeichnung';                             // 6
    case NameNatuerlichePerson                  = 'Name (Adressattyp natürl. Person)';           // 7
    case VornameNatuerlichePerson               = 'Vorname (Adressattyp natürl. Person)';        // 8
    case NameKeineAngabe                        = 'Name (Adressattyp keine Angabe)';             // 9
    case Adressattyp                            = 'Adressattyp';                                 // 10

        // Spalten 11-20: Persönliche Angaben und Adressdaten
    case TitelAkademischerGrad                  = 'Titel/Akad. Grad';                            // 11
    case Adelstitel                             = 'Adelstitel';                                  // 12
    case Namensvorsatz                          = 'Namensvorsatz';                               // 13
    case AbweichendeAnrede                      = 'Abweichende Anrede';                          // 14
    case Adressart                              = 'Adressart';                                   // 15
    case Strasse                                = 'Straße';                                      // 16
    case Postfach                               = 'Postfach';                                    // 17
    case Postleitzahl                           = 'Postleitzahl';                                // 18
    case Ort                                    = 'Ort';                                         // 19
    case Land                                   = 'Land';                                        // 20

        // Spalten 21-27: Weitere Adressdaten
    case Versandzusatz                          = 'Versandzusatz';                               // 21
    case Adresszusatz                           = 'Adresszusatz';                                // 22
    case AbwZustellbezeichnung1                 = 'Abw. Zustellbezeichnung 1';                   // 23
    case AbwZustellbezeichnung2                 = 'Abw. Zustellbezeichnung2';                    // 24
    case KennzKorrespondenzadresse              = 'Kennz. Korrespondenzadresse';                 // 25
    case AdresseGueltigVon                      = 'Adresse Gültig von';                          // 26
    case AdresseGueltigBis                      = 'Adresse Gültig bis';                          // 27

        // Spalten 28-40: Rechnungsadresse
    case AbweichendeAnredeRechnungsadresse      = 'Abweichende Anrede (Rechnungsadresse)';       // 28
    case AdressartRechnungsadresse              = 'Adressart (Rechnungsadresse)';                // 29
    case StrasseRechnungsadresse                = 'Straße (Rechnungsadresse)';                   // 30
    case PostfachRechnungsadresse               = 'Postfach (Rechnungsadresse)';                 // 31
    case PostleitzahlRechnungsadresse           = 'Postleitzahl (Rechnungsadresse)';             // 32
    case OrtRechnungsadresse                    = 'Ort (Rechnungsadresse)';                      // 33
    case LandRechnungsadresse                   = 'Land (Rechnungsadresse)';                     // 34
    case VersandzusatzRechnungsadresse          = 'Versandzusatz (Rechnungsadresse)';            // 35
    case AdresszusatzRechnungsadresse           = 'Adresszusatz (Rechnungsadresse)';             // 36
    case AbwZustellbezeichnung1Rechnungsadresse = 'Abw. Zustellbezeichung 1 (Rechnungsadresse)'; // 37
    case AbwZustellbezeichnung2Rechnungsadresse = 'Abw. Zustellbezeichung 2 (Rechnungsadresse)'; // 38
    case AdresseGueltigVonRechnungsadresse      = 'Adresse Gültig von (Rechnungsadresse)';       // 39
    case AdresseGueltigBisRechnungsadresse      = 'Adresse Gültig bis (Rechnungsadresse)';       // 40

        // Spalten 41-52: Kommunikationsdaten
    case Telefon                                = 'Telefon';                                     // 41
    case BemerkungTelefon                       = 'Bemerkung (Telefon)';                         // 42
    case TelefonGeschaeftsleitung               = 'Telefon Geschäftsleitung';                    // 43
    case BemerkungTelefonGL                     = 'Bemerktung (Telefon GL)';                     // 44
    case EMail                                  = 'E-Mail';                                      // 45
    case BemerkungEMail                         = 'Bemerkung (E-Mail)';                          // 46
    case Internet                               = 'Internet';                                    // 47
    case BemerkungInternet                      = 'Bemerkung (Internet)';                        // 48
    case Fax                                    = 'Fax';                                         // 49
    case BemerkungFax                           = 'Bemerkung (Fax)';                             // 50
    case Sonstige                               = 'Sonstige';                                    // 51
    case BemerkungSonstige                      = 'Bemerkung (Sonstige 1)';                      // 52

        // Spalten 53-63: Bankverbindung 1
    case Bankleitzahl1                          = 'Bankleitzahl 1';                              // 53
    case Bankbezeichnung1                       = 'Bankbezeichung 1';                            // 54
    case BankkontoNummer1                       = 'Bankkonto-Nummer 1';                          // 55
    case Laenderkennzeichen1                    = 'Länderkennzeichen 1';                         // 56
    case IBANNr1                                = 'IBAN-Nr. 1';                                  // 57
    case Leerfeld1                              = 'Leerfeld 1';                                  // 58
    case SWIFTCode1                             = 'SWIFT-Code 1';                                // 59
    case AbwKontoinhaber1                       = 'Abw. Kontoinhaber 1';                         // 60
    case KennzHauptbankverb1                    = 'Kennz. Hauptbankverb. 1';                     // 61
    case Bankverb1GueltigVon                    = 'Bankverb. 1 Gültig von';                      // 62
    case Bankverb1GueltigBis                    = 'Bankverb. 1 Gültig bis';                      // 63

        // Spalten 64-74: Bankverbindung 2
    case Bankleitzahl2                          = 'Bankleitzahl 2';                              // 64
    case Bankbezeichnung2                       = 'Bankbezeichung 2';                            // 65
    case BankkontoNummer2                       = 'Bankkonto-Nummer 2';                          // 66
    case Laenderkennzeichen2                    = 'Länderkennzeichen 2';                         // 67
    case IBANNr2                                = 'IBAN-Nr. 2';                                  // 68
    case Leerfeld2                              = 'Leerfeld 2';                                  // 69
    case SWIFTCode2                             = 'SWIFT-Code 2';                                // 70
    case AbwKontoinhaber2                       = 'Abw. Kontoinhaber 2';                         // 71
    case KennzHauptbankverb2                    = 'Kennz. Hauptbankverb. 2';                     // 72
    case Bankverb2GueltigVon                    = 'Bankverb. 2 Gültig von';                      // 73
    case Bankverb2GueltigBis                    = 'Bankverb. 2 Gültig bis';                      // 74

        // Spalten 75-85: Bankverbindung 3
    case Bankleitzahl3                          = 'Bankleitzahl 3';                              // 75
    case Bankbezeichnung3                       = 'Bankbezeichung 3';                            // 76
    case BankkontoNummer3                       = 'Bankkonto-Nummer 3';                          // 77
    case Laenderkennzeichen3                    = 'Länderkennzeichen 3';                         // 78
    case IBANNr3                                = 'IBAN-Nr. 3';                                  // 79
    case Leerfeld3                              = 'Leerfeld 3';                                  // 80
    case SWIFTCode3                             = 'SWIFT-Code 3';                                // 81
    case AbwKontoinhaber3                       = 'Abw. Kontoinhaber 3';                         // 82
    case KennzHauptbankverb3                    = 'Kennz. Hauptbankverb. 3';                     // 83
    case Bankverb3GueltigVon                    = 'Bankverb. 3 Gültig von';                      // 84
    case Bankverb3GueltigBis                    = 'Bankverb. 3 Gültig bis';                      // 85

        // Spalten 86-96: Bankverbindung 4
    case Bankleitzahl4                          = 'Bankleitzahl 4';                              // 86
    case Bankbezeichnung4                       = 'Bankbezeichung 4';                            // 87
    case BankkontoNummer4                       = 'Bankkonto-Nummer 4';                          // 88
    case Laenderkennzeichen4                    = 'Länderkennzeichen 4';                         // 89
    case IBANNr4                                = 'IBAN-Nr. 4';                                  // 90
    case Leerfeld4                              = 'Leerfeld 4';                                  // 91
    case SWIFTCode4                             = 'SWIFT-Code 4';                                // 92
    case AbwKontoinhaber4                       = 'Abw. Kontoinhaber 4';                         // 93
    case KennzHauptbankverb4                    = 'Kennz. Hauptbankverb. 4';                     // 94
    case Bankverb4GueltigVon                    = 'Bankverb. 4 Gültig von';                      // 95
    case Bankverb4GueltigBis                    = 'Bankverb. 4 Gültig bis';                      // 96

        // Spalten 97-107: Bankverbindung 5
    case Bankleitzahl5                          = 'Bankleitzahl 5';                              // 97
    case Bankbezeichnung5                       = 'Bankbezeichung 5';                            // 98
    case BankkontoNummer5                       = 'Bankkonto-Nummer 5';                          // 99
    case Laenderkennzeichen5                    = 'Länderkennzeichen 5';                         // 100
    case IBANNr5                                = 'IBAN-Nr. 5';                                  // 101
    case Leerfeld5                              = 'Leerfeld 5';                                  // 102
    case SWIFTCode5                             = 'SWIFT-Code 5';                                // 103
    case AbwKontoinhaber5                       = 'Abw. Kontoinhaber 5';                         // 104
    case KennzHauptbankverb5                    = 'Kennz. Hauptbankverb. 5';                     // 105
    case Bankverb5GueltigVon                    = 'Bankverb. 5 Gültig von';                      // 106
    case Bankverb5GueltigBis                    = 'Bankverb. 5 Gültig bis';                      // 107

        // Spalten 108-118: Bankverbindung 6
    case Bankleitzahl6                          = 'Bankleitzahl 6';                              // 108
    case Bankbezeichnung6                       = 'Bankbezeichung 6';                            // 109
    case BankkontoNummer6                       = 'Bankkonto-Nummer 6';                          // 110
    case Laenderkennzeichen6                    = 'Länderkennzeichen 6';                         // 111
    case IBANNr6                                = 'IBAN-Nr. 6';                                  // 112
    case Leerfeld6                              = 'Leerfeld 6';                                  // 113
    case SWIFTCode6                             = 'SWIFT-Code 6';                                // 114
    case AbwKontoinhaber6                       = 'Abw. Kontoinhaber 6';                         // 115
    case KennzHauptbankverb6                    = 'Kennz. Hauptbankverb. 6';                     // 116
    case Bankverb6GueltigVon                    = 'Bankverb. 6 Gültig von';                      // 117
    case Bankverb6GueltigBis                    = 'Bankverb. 6 Gültig bis';                      // 118

        // Spalten 119-129: Bankverbindung 7
    case Bankleitzahl7                          = 'Bankleitzahl 7';                              // 119
    case Bankbezeichnung7                       = 'Bankbezeichung 7';                            // 120
    case BankkontoNummer7                       = 'Bankkonto-Nummer 7';                          // 121
    case Laenderkennzeichen7                    = 'Länderkennzeichen 7';                         // 122
    case IBANNr7                                = 'IBAN-Nr. 7';                                  // 123
    case Leerfeld7                              = 'Leerfeld 7';                                  // 124
    case SWIFTCode7                             = 'SWIFT-Code 7';                                // 125
    case AbwKontoinhaber7                       = 'Abw. Kontoinhaber 7';                         // 126
    case KennzHauptbankverb7                    = 'Kennz. Hauptbankverb. 7';                     // 127
    case Bankverb7GueltigVon                    = 'Bankverb. 7 Gültig von';                      // 128
    case Bankverb7GueltigBis                    = 'Bankverb. 7 Gültig bis';                      // 129

        // Spalten 130-140: Bankverbindung 8
    case Bankleitzahl8                          = 'Bankleitzahl 8';                              // 130
    case Bankbezeichnung8                       = 'Bankbezeichung 8';                            // 131
    case BankkontoNummer8                       = 'Bankkonto-Nummer 8';                          // 132
    case Laenderkennzeichen8                    = 'Länderkennzeichen 8';                         // 133
    case IBANNr8                                = 'IBAN-Nr. 8';                                  // 134
    case Leerfeld8                              = 'Leerfeld 8';                                  // 135
    case SWIFTCode8                             = 'SWIFT-Code 8';                                // 136
    case AbwKontoinhaber8                       = 'Abw. Kontoinhaber 8';                         // 137
    case KennzHauptbankverb8                    = 'Kennz. Hauptbankverb. 8';                     // 138
    case Bankverb8GueltigVon                    = 'Bankverb. 8 Gültig von';                      // 139
    case Bankverb8GueltigBis                    = 'Bankverb. 8 Gültig bis';                      // 140

        // Spalten 141-151: Bankverbindung 9
    case Bankleitzahl9                          = 'Bankleitzahl 9';                              // 141
    case Bankbezeichnung9                       = 'Bankbezeichung 9';                            // 142
    case BankkontoNummer9                       = 'Bankkonto-Nummer 9';                          // 143
    case Laenderkennzeichen9                    = 'Länderkennzeichen 9';                         // 144
    case IBANNr9                                = 'IBAN-Nr. 9';                                  // 145
    case Leerfeld9                              = 'Leerfeld 9';                                  // 146
    case SWIFTCode9                             = 'SWIFT-Code 9';                                // 147
    case AbwKontoinhaber9                       = 'Abw. Kontoinhaber 9';                         // 148
    case KennzHauptbankverb9                    = 'Kennz. Hauptbankverb. 9';                     // 149
    case Bankverb9GueltigVon                    = 'Bankverb. 9 Gültig von';                      // 150
    case Bankverb9GueltigBis                    = 'Bankverb. 9 Gültig bis';                      // 151

        // Spalten 152-162: Bankverbindung 10
    case Bankleitzahl10                         = 'Bankleitzahl 10';                             // 152
    case Bankbezeichnung10                      = 'Bankbezeichung 10';                           // 153
    case BankkontoNummer10                      = 'Bankkonto-Nummer 10';                         // 154
    case Laenderkennzeichen10                   = 'Länderkennzeichen 10';                        // 155
    case IBANNr10                               = 'IBAN-Nr. 10';                                 // 156
    case Leerfeld10                             = 'Leerfeld 10';                                 // 157
    case SWIFTCode10                            = 'SWIFT-Code 10';                               // 158
    case AbwKontoinhaber10                      = 'Abw. Kontoinhaber 10';                        // 159
    case KennzHauptbankverb10                   = 'Kennz. Hauptbankverb. 10';                    // 160
    case Bankverb10GueltigVon                   = 'Bankverb. 10 Gültig von';                     // 161
    case Bankverb10GueltigBis                   = 'Bankverb. 10 Gültig bis';                     // 162

        // Spalten 163-180: Weitere Stammdaten und individuelle Felder
    case Kundennummer                           = 'Kundennummer';                                // 163
    case Ansprechpartner                        = 'Ansprechpartner';                             // 164
    case Vertreter                              = 'Vertreter';                                   // 165
    case Sachbearbeiter                         = 'Sachbearbeiter';                              // 166
    case Briefanrede                            = 'Briefanrede';                                 // 167
    case Grussformel                            = 'Grußformel';                                  // 168
    case Sprache                                = 'Sprache';                                     // 169
    case Ausgabeziel                            = 'Ausgabeziel';                                 // 170
    case IndivFeld1                             = 'Indiv. Feld 1';                               // 171
    case IndivFeld2                             = 'Indiv. Feld 2';                               // 172
    case IndivFeld3                             = 'Indiv. Feld 3';                               // 173
    case IndivFeld4                             = 'Indiv. Feld 4';                               // 174
    case IndivFeld5                             = 'Indiv. Feld 5';                               // 175
    case IndivFeld6                             = 'Indiv. Feld 6';                               // 176
    case IndivFeld7                             = 'Indiv. Feld 7';                               // 177
    case IndivFeld8                             = 'Indiv. Feld 8';                               // 178
    case IndivFeld9                             = 'Indiv. Feld 9';                               // 179
    case IndivFeld10                            = 'Indiv. Feld 10';                              // 180

        // Spalten 181-191: SEPA-Mandate und Fremdsystem
    case SEPAMandatsreferenz1                   = 'SEPA-Mandatsreferenz 1';                      // 181
    case SEPAMandatsreferenz2                   = 'SEPA-Mandatsreferenz 2';                      // 182
    case SEPAMandatsreferenz3                   = 'SEPA-Mandatsreferenz 3';                      // 183
    case SEPAMandatsreferenz4                   = 'SEPA-Mandatsreferenz 4';                      // 184
    case SEPAMandatsreferenz5                   = 'SEPA-Mandatsreferenz 5';                      // 185
    case SEPAMandatsreferenz6                   = 'SEPA-Mandatsreferenz 6';                      // 186
    case SEPAMandatsreferenz7                   = 'SEPA-Mandatsreferenz 7';                      // 187
    case SEPAMandatsreferenz8                   = 'SEPA-Mandatsreferenz 8';                      // 188
    case SEPAMandatsreferenz9                   = 'SEPA-Mandatsreferenz 9';                      // 189
    case SEPAMandatsreferenz10                  = 'SEPA-Mandatsreferenz 10';                     // 190
    case NummerFremdsystem                      = 'Nummer Fremdsystem';                          // 191

    /**
     * Liefert alle 191 Felder in der korrekten DATEV-Reihenfolge.
     */
    public static function ordered(): array {
        return array_values(self::cases());
    }

    /**
     * Liefert alle verpflichtenden Felder.
     */
    public static function required(): array {
        return [
            self::Adressnummer,                // Pflichtfeld: Adressnummer muss angegeben werden
            self::Konto,                       // Pflichtfeld: Konto der diversen Adresse zuordnen
        ];
    }

    /**
     * Liefert alle Bankverbindungsfelder (1-10).
     */
    public static function bankConnectionFields(): array {
        $fields = [];
        for ($i = 1; $i <= 10; $i++) {
            $fields = array_merge($fields, [
                constant("self::Bankleitzahl{$i}"),
                constant("self::Bankbezeichnung{$i}"),
                constant("self::BankkontoNummer{$i}"),
                constant("self::Laenderkennzeichen{$i}"),
                constant("self::IBANNr{$i}"),
                constant("self::SWIFTCode{$i}"),
                constant("self::AbwKontoinhaber{$i}"),
                constant("self::KennzHauptbankverb{$i}"),
                constant("self::Bankverb{$i}GueltigVon"),
                constant("self::Bankverb{$i}GueltigBis"),
            ]);
        }
        return $fields;
    }

    /**
     * Liefert alle Adressfelder (Korrespondenzadresse).
     */
    public static function correspondenceAddressFields(): array {
        return [
            self::Adressart,
            self::Strasse,
            self::Postfach,
            self::Postleitzahl,
            self::Ort,
            self::Land,
            self::Versandzusatz,
            self::Adresszusatz,
            self::AbweichendeAnrede,
            self::AbwZustellbezeichnung1,
            self::AbwZustellbezeichnung2,
            self::KennzKorrespondenzadresse,
            self::AdresseGueltigVon,
            self::AdresseGueltigBis,
        ];
    }

    /**
     * Liefert alle Rechnungsadressfelder.
     */
    public static function billingAddressFields(): array {
        return [
            self::AbweichendeAnredeRechnungsadresse,
            self::AdressartRechnungsadresse,
            self::StrasseRechnungsadresse,
            self::PostfachRechnungsadresse,
            self::PostleitzahlRechnungsadresse,
            self::OrtRechnungsadresse,
            self::LandRechnungsadresse,
            self::VersandzusatzRechnungsadresse,
            self::AdresszusatzRechnungsadresse,
            self::AbwZustellbezeichnung1Rechnungsadresse,
            self::AbwZustellbezeichnung2Rechnungsadresse,
            self::AdresseGueltigVonRechnungsadresse,
            self::AdresseGueltigBisRechnungsadresse,
        ];
    }

    /**
     * Liefert alle Kommunikationsfelder.
     */
    public static function communicationFields(): array {
        return [
            self::Telefon,
            self::BemerkungTelefon,
            self::TelefonGeschaeftsleitung,
            self::BemerkungTelefonGL,
            self::EMail,
            self::BemerkungEMail,
            self::Internet,
            self::BemerkungInternet,
            self::Fax,
            self::BemerkungFax,
            self::Sonstige,
            self::BemerkungSonstige,
        ];
    }

    /**
     * Liefert alle individuellen Felder.
     */
    public static function individualFields(): array {
        return [
            self::IndivFeld1,
            self::IndivFeld2,
            self::IndivFeld3,
            self::IndivFeld4,
            self::IndivFeld5,
            self::IndivFeld6,
            self::IndivFeld7,
            self::IndivFeld8,
            self::IndivFeld9,
            self::IndivFeld10,
        ];
    }

    /**
     * Liefert alle SEPA-Mandatsreferenz-Felder.
     */
    public static function sepaMandateFields(): array {
        return [
            self::SEPAMandatsreferenz1,
            self::SEPAMandatsreferenz2,
            self::SEPAMandatsreferenz3,
            self::SEPAMandatsreferenz4,
            self::SEPAMandatsreferenz5,
            self::SEPAMandatsreferenz6,
            self::SEPAMandatsreferenz7,
            self::SEPAMandatsreferenz8,
            self::SEPAMandatsreferenz9,
            self::SEPAMandatsreferenz10,
        ];
    }

    /**
     * Liefert alle Namensfelder (verschiedene Adressatentypen).
     */
    public static function nameFields(): array {
        return [
            self::NameUnternehmen,
            self::NameNatuerlichePerson,
            self::VornameNatuerlichePerson,
            self::NameKeineAngabe,
            self::TitelAkademischerGrad,
            self::Adelstitel,
            self::Namensvorsatz,
        ];
    }

    /**
     * Prüft, ob ein Feld verpflichtend ist.
     */
    public function isRequired(): bool {
        return in_array($this, self::required(), true);
    }

    /**
     * Prüft, ob ein Feld für Bankverbindungen relevant ist.
     */
    public function isBankConnectionField(): bool {
        return in_array($this, self::bankConnectionFields(), true);
    }

    /**
     * Prüft, ob ein Feld für SEPA-Mandate relevant ist.
     */
    public function isSepaField(): bool {
        return in_array($this, self::sepaMandateFields(), true);
    }

    /**
     * Prüft, ob ein Feld für Kommunikation relevant ist.
     */
    public function isCommunicationField(): bool {
        return in_array($this, self::communicationFields(), true);
    }

    /**
     * Prüft, ob ein Feld ein individuelles Feld ist.
     */
    public function isIndividualField(): bool {
        return in_array($this, self::individualFields(), true);
    }

    /**
     * Prüft, ob ein Feld ein Namensfeld ist.
     */
    public function isNameField(): bool {
        return in_array($this, self::nameFields(), true);
    }

    /**
     * Prüft, ob ein Feld zur Korrespondenzadresse gehört.
     */
    public function isCorrespondenceAddressField(): bool {
        return in_array($this, self::correspondenceAddressFields(), true);
    }

    /**
     * Prüft, ob ein Feld zur Rechnungsadresse gehört.
     */
    public function isBillingAddressField(): bool {
        return in_array($this, self::billingAddressFields(), true);
    }

    /**
     * Liefert eine Beschreibung des Feldtyps.
     */
    public function getFieldType(): string {
        return match ($this) {
            self::Konto, self::Sprache, self::Ausgabeziel,
            self::Adressattyp => 'integer',

            self::AdresseGueltigVon, self::AdresseGueltigBis,
            self::AdresseGueltigVonRechnungsadresse, self::AdresseGueltigBisRechnungsadresse,
            self::Bankverb1GueltigVon, self::Bankverb1GueltigBis,
            self::Bankverb2GueltigVon, self::Bankverb2GueltigBis,
            self::Bankverb3GueltigVon, self::Bankverb3GueltigBis,
            self::Bankverb4GueltigVon, self::Bankverb4GueltigBis,
            self::Bankverb5GueltigVon, self::Bankverb5GueltigBis,
            self::Bankverb6GueltigVon, self::Bankverb6GueltigBis,
            self::Bankverb7GueltigVon, self::Bankverb7GueltigBis,
            self::Bankverb8GueltigVon, self::Bankverb8GueltigBis,
            self::Bankverb9GueltigVon, self::Bankverb9GueltigBis,
            self::Bankverb10GueltigVon, self::Bankverb10GueltigBis => 'date',

            self::Adressart, self::AdressartRechnungsadresse,
            self::KennzKorrespondenzadresse,
            self::KennzHauptbankverb1, self::KennzHauptbankverb2,
            self::KennzHauptbankverb3, self::KennzHauptbankverb4,
            self::KennzHauptbankverb5, self::KennzHauptbankverb6,
            self::KennzHauptbankverb7, self::KennzHauptbankverb8,
            self::KennzHauptbankverb9, self::KennzHauptbankverb10 => 'enum',

            default => 'string',
        };
    }

    /**
     * Liefert die maximale Feldlänge für DATEV.
     */
    public function getMaxLength(): ?int {
        return match ($this) {
            self::Adressnummer => 9,
            self::Konto => 9,
            self::NameUnternehmen, self::Unternehmensgegenstand,
            self::NameKeineAngabe => 50,
            self::NameNatuerlichePerson, self::VornameNatuerlichePerson,
            self::Ort, self::OrtRechnungsadresse, self::Anrede,
            self::AbweichendeAnrede, self::AbweichendeAnredeRechnungsadresse => 30,
            self::Kurzbezeichnung => 15,
            self::Land, self::LandRechnungsadresse,
            self::Laenderkennzeichen1, self::Laenderkennzeichen2,
            self::Laenderkennzeichen3, self::Laenderkennzeichen4,
            self::Laenderkennzeichen5, self::Laenderkennzeichen6,
            self::Laenderkennzeichen7, self::Laenderkennzeichen8,
            self::Laenderkennzeichen9, self::Laenderkennzeichen10 => 2,
            self::TitelAkademischerGrad => 25,
            self::Adelstitel => 15,
            self::Namensvorsatz => 14,
            self::Adressart, self::AdressartRechnungsadresse => 3,
            self::Strasse, self::StrasseRechnungsadresse,
            self::Adresszusatz, self::AdresszusatzRechnungsadresse => 36,
            self::Postfach, self::PostfachRechnungsadresse => 10,
            self::Postleitzahl, self::PostleitzahlRechnungsadresse => 10,
            self::Versandzusatz, self::VersandzusatzRechnungsadresse,
            self::AbwZustellbezeichnung1, self::AbwZustellbezeichnung1Rechnungsadresse => 50,
            self::AbwZustellbezeichnung2, self::AbwZustellbezeichnung2Rechnungsadresse => 36,
            self::Telefon, self::TelefonGeschaeftsleitung, self::EMail,
            self::Internet, self::Fax, self::Sonstige => 60,
            self::BemerkungTelefon, self::BemerkungTelefonGL, self::BemerkungEMail,
            self::BemerkungInternet, self::BemerkungFax, self::BemerkungSonstige,
            self::IndivFeld1, self::IndivFeld2, self::IndivFeld3, self::IndivFeld4,
            self::IndivFeld5, self::IndivFeld6, self::IndivFeld7, self::IndivFeld8,
            self::IndivFeld9, self::IndivFeld10,
            self::Ansprechpartner, self::Vertreter, self::Sachbearbeiter => 40,
            self::Briefanrede => 100,
            self::Grussformel => 50,
            self::Kundennummer, self::NummerFremdsystem => 15,
            self::SEPAMandatsreferenz1, self::SEPAMandatsreferenz2, self::SEPAMandatsreferenz3,
            self::SEPAMandatsreferenz4, self::SEPAMandatsreferenz5, self::SEPAMandatsreferenz6,
            self::SEPAMandatsreferenz7, self::SEPAMandatsreferenz8, self::SEPAMandatsreferenz9,
            self::SEPAMandatsreferenz10 => 35,
            default => null,
        };
    }

    /**
     * Liefert das Regex-Pattern für DATEV-Validierung.
     */
    public function getValidationPattern(): ?string {
        return match ($this) {
            self::Adressnummer => '^["](.){0,9}["]$',
            self::Konto => '^[\d]{1,9}$',
            self::Anrede, self::AbweichendeAnrede,
            self::AbweichendeAnredeRechnungsadresse => '^(["](.){0,30}["])$',
            self::NameUnternehmen, self::Unternehmensgegenstand,
            self::NameKeineAngabe => '^(["](.){0,50}["])$',
            self::Kurzbezeichnung => '^(["](.){0,15}["])$',
            self::NameNatuerlichePerson, self::VornameNatuerlichePerson => '^(["](.){0,30}["])$',
            self::Adressattyp => '^(["][\d]["])$',
            self::TitelAkademischerGrad => '^(["](.){0,25}["])$',
            self::Adelstitel => '^(["](.){0,15}["])$',
            self::Namensvorsatz => '^(["](.){0,14}["])$',
            self::Adressart, self::AdressartRechnungsadresse => '^(["][\w]{0,3}["])$',
            self::Strasse, self::StrasseRechnungsadresse => '^(["](.){0,36}["])$',
            self::Postfach, self::PostfachRechnungsadresse => '^(["](.){0,10}["])$',
            self::Postleitzahl, self::PostleitzahlRechnungsadresse => '^(["][\d]{0,10}["])$',
            self::Ort, self::OrtRechnungsadresse => '^(["](.){0,30}["])$',
            self::Land, self::LandRechnungsadresse => '^(["][A-Z]{2}["])$',
            self::Versandzusatz, self::VersandzusatzRechnungsadresse => '^(["](.){0,50}["])$',
            self::Adresszusatz, self::AdresszusatzRechnungsadresse => '^(["](.){0,36}["])$',
            self::AbwZustellbezeichnung1, self::AbwZustellbezeichnung1Rechnungsadresse => '^(["](.){0,50}["])$',
            self::AbwZustellbezeichnung2, self::AbwZustellbezeichnung2Rechnungsadresse => '^(["](.){0,36}["])$',
            self::KennzKorrespondenzadresse => '^[\d]$',
            self::AdresseGueltigVon, self::AdresseGueltigBis,
            self::AdresseGueltigVonRechnungsadresse, self::AdresseGueltigBisRechnungsadresse => '^((0[1-9]|[1-2][\d]|3[0-1])(0[1-9]|1[0-2])([2])([0])([\d]{2}))$',
            self::Telefon, self::TelefonGeschaeftsleitung, self::EMail,
            self::Internet, self::Fax, self::Sonstige => '^(["](.){0,60}["])$',
            self::BemerkungTelefon, self::BemerkungTelefonGL, self::BemerkungEMail,
            self::BemerkungInternet, self::BemerkungFax, self::BemerkungSonstige => '^(["](.){0,40}["])$',
            self::Sprache => '^[\d]{0,2}$',
            self::Ausgabeziel => '^[\d]$',
            self::SEPAMandatsreferenz1, self::SEPAMandatsreferenz2, self::SEPAMandatsreferenz3,
            self::SEPAMandatsreferenz4, self::SEPAMandatsreferenz5, self::SEPAMandatsreferenz6,
            self::SEPAMandatsreferenz7, self::SEPAMandatsreferenz8, self::SEPAMandatsreferenz9,
            self::SEPAMandatsreferenz10, self::NummerFremdsystem => '^(["](.){0,35}["])$',
            default => null,
        };
    }

    /**
     * Liefert die DATEV-Kategorie für dieses Header-Format.
     */
    public static function getCategory(): Category {
        return Category::DiverseAdressen;
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
            // Zustellbezeichnung 2: Sample hat kein Leerzeichen
            self::AbwZustellbezeichnung2 => 'Abw. Zustellbezeichnung 2',
            // Rechnungsadresse Zustellbezeichnungen: Sample schreibt "Zustellbezeichnung" ohne 'n'
            self::AbwZustellbezeichnung1Rechnungsadresse => 'Abw. Zustellbezeichnung 1 (Rechnungsadresse)',
            self::AbwZustellbezeichnung2Rechnungsadresse => 'Abw. Zustellbezeichnung 2 (Rechnungsadresse)',
            // Telefon Geschäftsleitung
            self::TelefonGeschaeftsleitung => 'Telefon GL',
            self::BemerkungTelefonGL => 'Bemerkung (Telefon GL)',
            // Bemerkung Sonstige
            self::BemerkungSonstige => 'Bemerkung (Sonstige)',
            // Bankbezeichnungen: Sample schreibt "Bankbezeichnung" mit 'n'
            self::Bankbezeichnung1 => 'Bankbezeichnung 1',
            self::Bankbezeichnung2 => 'Bankbezeichnung 2',
            self::Bankbezeichnung3 => 'Bankbezeichnung 3',
            self::Bankbezeichnung4 => 'Bankbezeichnung 4',
            self::Bankbezeichnung5 => 'Bankbezeichnung 5',
            self::Bankbezeichnung6 => 'Bankbezeichnung 6',
            self::Bankbezeichnung7 => 'Bankbezeichnung 7',
            self::Bankbezeichnung8 => 'Bankbezeichnung 8',
            self::Bankbezeichnung9 => 'Bankbezeichnung 9',
            self::Bankbezeichnung10 => 'Bankbezeichnung 10',
            // Bankkonto-Nummer: Sample schreibt "Bank-Kontonummer"
            self::BankkontoNummer1 => 'Bank-Kontonummer 1',
            self::BankkontoNummer2 => 'Bank-Kontonummer 2',
            self::BankkontoNummer3 => 'Bank-Kontonummer 3',
            self::BankkontoNummer4 => 'Bank-Kontonummer 4',
            self::BankkontoNummer5 => 'Bank-Kontonummer 5',
            self::BankkontoNummer6 => 'Bank-Kontonummer 6',
            self::BankkontoNummer7 => 'Bank-Kontonummer 7',
            self::BankkontoNummer8 => 'Bank-Kontonummer 8',
            self::BankkontoNummer9 => 'Bank-Kontonummer 9',
            self::BankkontoNummer10 => 'Bank-Kontonummer 10',
            // Leerfeld: Sample hat keine Nummer
            self::Leerfeld1 => 'Leerfeld',
            self::Leerfeld2 => 'Leerfeld',
            self::Leerfeld3 => 'Leerfeld',
            self::Leerfeld4 => 'Leerfeld',
            self::Leerfeld5 => 'Leerfeld',
            self::Leerfeld6 => 'Leerfeld',
            self::Leerfeld7 => 'Leerfeld',
            self::Leerfeld8 => 'Leerfeld',
            self::Leerfeld9 => 'Leerfeld',
            self::Leerfeld10 => 'Leerfeld',
            // Bankverb: Sample ohne Punkt nach "Bankverb"
            self::Bankverb1GueltigVon => 'Bankverb 1 Gültig von',
            self::Bankverb1GueltigBis => 'Bankverb 1 Gültig bis',
            self::Bankverb2GueltigVon => 'Bankverb 2 Gültig von',
            self::Bankverb2GueltigBis => 'Bankverb 2 Gültig bis',
            self::Bankverb3GueltigVon => 'Bankverb 3 Gültig von',
            self::Bankverb3GueltigBis => 'Bankverb 3 Gültig bis',
            self::Bankverb4GueltigVon => 'Bankverb 4 Gültig von',
            self::Bankverb4GueltigBis => 'Bankverb 4 Gültig bis',
            self::Bankverb5GueltigVon => 'Bankverb 5 Gültig von',
            self::Bankverb5GueltigBis => 'Bankverb 5 Gültig bis',
            self::Bankverb6GueltigVon => 'Bankverb 6 Gültig von',
            self::Bankverb6GueltigBis => 'Bankverb 6 Gültig bis',
            self::Bankverb7GueltigVon => 'Bankverb 7 Gültig von',
            self::Bankverb7GueltigBis => 'Bankverb 7 Gültig bis',
            self::Bankverb8GueltigVon => 'Bankverb 8 Gültig von',
            self::Bankverb8GueltigBis => 'Bankverb 8 Gültig bis',
            self::Bankverb9GueltigVon => 'Bankverb 9 Gültig von',
            self::Bankverb9GueltigBis => 'Bankverb 9 Gültig bis',
            self::Bankverb10GueltigVon => 'Bankverb 10 Gültig von',
            self::Bankverb10GueltigBis => 'Bankverb 10 Gültig bis',
            // SEPA-Mandatsreferenz: Sample hat kein "SEPA-" Präfix
            self::SEPAMandatsreferenz1 => 'Mandatsreferenz 1',
            self::SEPAMandatsreferenz2 => 'Mandatsreferenz 2',
            self::SEPAMandatsreferenz3 => 'Mandatsreferenz 3',
            self::SEPAMandatsreferenz4 => 'Mandatsreferenz 4',
            self::SEPAMandatsreferenz5 => 'Mandatsreferenz 5',
            self::SEPAMandatsreferenz6 => 'Mandatsreferenz 6',
            self::SEPAMandatsreferenz7 => 'Mandatsreferenz 7',
            self::SEPAMandatsreferenz8 => 'Mandatsreferenz 8',
            self::SEPAMandatsreferenz9 => 'Mandatsreferenz 9',
            self::SEPAMandatsreferenz10 => 'Mandatsreferenz 10',
            default => $this->value,
        };
    }
}
