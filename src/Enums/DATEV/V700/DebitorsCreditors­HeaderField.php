<?php
/*
 * Created on   : Mon Dec 15 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DebitorsCreditors­HeaderField.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV\V700;

use CommonToolkit\Contracts\Interfaces\DATEV\FieldHeaderInterface;

/**
 * DATEV Debitoren/Kreditoren - Feldheader (Spaltenbeschreibungen) V700.
 * Vollständige Implementierung aller 254 DATEV-Felder für Debitorenstamm und Kreditorenstamm
 * basierend auf der offiziellen DATEV-Spezifikation.
 * 
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/debitorskreditors
 */
enum DebitorsCreditors­HeaderField: string implements FieldHeaderInterface {
    // Spalten 1-10: Grunddaten und Namensangaben
    case Konto                                  = 'Konto';                                             // 1
    case NameUnternehmen                        = 'Name (Adressattyp Unternehmen)';                    // 2
    case Unternehmensgegenstand                 = 'Unternehmensgegenstand';                            // 3
    case NameNatuerlichePerson                  = 'Name (Adressattyp natürl. Person)';                 // 4
    case VornameNatuerlichePerson               = 'Vorname (Adressattyp natürl. Person)';              // 5
    case NameKeineAngabe                        = 'Name (Adressattyp keine Angabe)';                   // 6
    case Adressattyp                            = 'Adressattyp';                                       // 7
    case Kurzbezeichnung                        = 'Kurzbezeichnung';                                   // 8
    case EULand                                 = 'EU-Land';                                           // 9
    case EUUStID                                = 'EU-UStID';                                          // 10

        // Spalten 11-20: Persönliche Angaben und Adressdaten
    case Anrede                                 = 'Anrede';                                            // 11
    case TitelAkademischerGrad                  = 'Titel/Akad. Grad';                                  // 12
    case Adelstitel                             = 'Adelstitel';                                        // 13
    case Namensvorsatz                          = 'Namensvorsatz';                                     // 14
    case Adressart                              = 'Adressart';                                         // 15
    case Strasse                                = 'Straße';                                            // 16
    case Postfach                               = 'Postfach';                                          // 17
    case Postleitzahl                           = 'Postleitzahl';                                      // 18
    case Ort                                    = 'Ort';                                               // 19
    case Land                                   = 'Land';                                              // 20

        // Spalten 21-30: Weitere Adressdaten
    case Versandzusatz                          = 'Versandzusatz';                                     // 21
    case Adresszusatz                           = 'Adresszusatz';                                      // 22
    case AbweichendeAnrede                      = 'Abweichende Anrede';                                // 23
    case AbwZustellbezeichnung1                 = 'Abw. Zustellbezeichnung 1';                         // 24
    case AbwZustellbezeichnung2                 = 'Abw. Zustellbezeichnung 2';                         // 25
    case KennzKorrespondenzadresse              = 'Kennz. Korrespondenzadresse';                       // 26
    case AdresseGueltigVon                      = 'Adresse Gültig von';                                // 27
    case AdresseGueltigBis                      = 'Adresse Gültig bis';                                // 28
    case Telefon                                = 'Telefon';                                           // 29
    case BemerkungTelefon                       = 'Bemerkung (Telefon)';                               // 30

        // Spalten 31-40: Kommunikationsdaten
    case TelefonGeschaeftsleitung               = 'Telefon GL';                                        // 31
    case BemerkungTelefonGL                     = 'Bemerkung (Telefon GL)';                            // 32
    case EMail                                  = 'E-Mail';                                            // 33
    case BemerkungEMail                         = 'Bemerkung (E-Mail)';                                // 34
    case Internet                               = 'Internet';                                          // 35
    case BemerkungInternet                      = 'Bemerkung (Internet)';                              // 36
    case Fax                                    = 'Fax';                                               // 37
    case BemerkungFax                           = 'Bemerkung (Fax)';                                   // 38
    case Sonstige                               = 'Sonstige';                                          // 39
    case BemerkungSonstige                      = 'Bemerkung (Sonstige)';                              // 40

        // Spalten 41-51: Bankverbindung 1
    case Bankleitzahl1                          = 'Bankleitzahl 1';                                    // 41
    case Bankbezeichnung1                       = 'Bankbezeichnung 1';                                 // 42
    case BankkontoNummer1                       = 'Bank-Kontonummer 1';                                // 43
    case Laenderkennzeichen1                    = 'Länderkennzeichen 1';                               // 44
    case IBANNr1                                = 'IBAN-Nr. 1';                                        // 45
    case Leerfeld1                              = 'Leerfeld 1';                                        // 46
    case SWIFTCode1                             = 'SWIFT-Code 1';                                      // 47
    case AbwKontoinhaber1                       = 'Abw. Kontoinhaber 1';                               // 48
    case KennzHauptbankverb1                    = 'Kennz. Hauptbankverb. 1';                           // 49
    case Bankverb1GueltigVon                    = 'Bankverb 1 Gültig von';                             // 50
    case Bankverb1GueltigBis                    = 'Bankverb 1 Gültig bis';                             // 51

        // Spalten 52-62: Bankverbindung 2
    case Bankleitzahl2                          = 'Bankleitzahl 2';                                    // 52
    case Bankbezeichnung2                       = 'Bankbezeichnung 2';                                 // 53
    case BankkontoNummer2                       = 'Bank-Kontonummer 2';                                // 54
    case Laenderkennzeichen2                    = 'Länderkennzeichen 2';                               // 55
    case IBANNr2                                = 'IBAN-Nr. 2';                                        // 56
    case Leerfeld2                              = 'Leerfeld 2';                                        // 57
    case SWIFTCode2                             = 'SWIFT-Code 2';                                      // 58
    case AbwKontoinhaber2                       = 'Abw. Kontoinhaber 2';                               // 59
    case KennzHauptbankverb2                    = 'Kennz. Hauptbankverb. 2';                           // 60
    case Bankverb2GueltigVon                    = 'Bankverb 2 Gültig von';                             // 61
    case Bankverb2GueltigBis                    = 'Bankverb 2 Gültig bis';                             // 62

        // Spalten 63-73: Bankverbindung 3
    case Bankleitzahl3                          = 'Bankleitzahl 3';                                    // 63
    case Bankbezeichnung3                       = 'Bankbezeichnung 3';                                 // 64
    case BankkontoNummer3                       = 'Bank-Kontonummer 3';                                // 65
    case Laenderkennzeichen3                    = 'Länderkennzeichen 3';                               // 66
    case IBANNr3                                = 'IBAN-Nr. 3';                                        // 67
    case Leerfeld3                              = 'Leerfeld 3';                                        // 68
    case SWIFTCode3                             = 'SWIFT-Code 3';                                      // 69
    case AbwKontoinhaber3                       = 'Abw. Kontoinhaber 3';                               // 70
    case KennzHauptbankverb3                    = 'Kennz. Hauptbankverb. 3';                           // 71
    case Bankverb3GueltigVon                    = 'Bankverb 3 Gültig von';                             // 72
    case Bankverb3GueltigBis                    = 'Bankverb 3 Gültig bis';                             // 73

        // Spalten 74-84: Bankverbindung 4
    case Bankleitzahl4                          = 'Bankleitzahl 4';                                    // 74
    case Bankbezeichnung4                       = 'Bankbezeichnung 4';                                 // 75
    case BankkontoNummer4                       = 'Bank-Kontonummer 4';                                // 76
    case Laenderkennzeichen4                    = 'Länderkennzeichen 4';                               // 77
    case IBANNr4                                = 'IBAN-Nr. 4';                                        // 78
    case Leerfeld4                              = 'Leerfeld 4';                                        // 79
    case SWIFTCode4                             = 'SWIFT-Code 4';                                      // 80
    case AbwKontoinhaber4                       = 'Abw. Kontoinhaber 4';                               // 81
    case KennzHauptbankverb4                    = 'Kennz. Hauptbankverb. 4';                           // 82
    case Bankverb4GueltigVon                    = 'Bankverb 4 Gültig von';                             // 83
    case Bankverb4GueltigBis                    = 'Bankverb 4 Gültig bis';                             // 84

        // Spalten 85-95: Bankverbindung 5
    case Bankleitzahl5                          = 'Bankleitzahl 5';                                    // 85
    case Bankbezeichnung5                       = 'Bankbezeichnung 5';                                 // 86
    case BankkontoNummer5                       = 'Bank-Kontonummer 5';                                // 87
    case Laenderkennzeichen5                    = 'Länderkennzeichen 5';                               // 88
    case IBANNr5                                = 'IBAN-Nr. 5';                                        // 89
    case Leerfeld5                              = 'Leerfeld 5';                                        // 90
    case SWIFTCode5                             = 'SWIFT-Code 5';                                      // 91
    case AbwKontoinhaber5                       = 'Abw. Kontoinhaber 5';                               // 92
    case KennzHauptbankverb5                    = 'Kennz. Hauptbankverb. 5';                           // 93
    case Bankverb5GueltigVon                    = 'Bankverb 5 Gültig von';                             // 94
    case Bankverb5GueltigBis                    = 'Bankverb 5 Gültig bis';                             // 95

        // Spalten 96-106: Weitere Stammdaten
    case Leerfeld6                              = 'Leerfeld 6';                                        // 96
    case Briefanrede                            = 'Briefanrede';                                       // 97
    case Grussformel                            = 'Grußformel';                                        // 98
    case Kundennummer                           = 'Kunden-/Lief.-Nr.';                                 // 99
    case Steuernummer                           = 'Steuernummer';                                      // 100
    case Sprache                                = 'Sprache';                                           // 101
    case Ansprechpartner                        = 'Ansprechpartner';                                   // 102
    case Vertreter                              = 'Vertreter';                                         // 103
    case Sachbearbeiter                         = 'Sachbearbeiter';                                    // 104
    case DiverseKonto                           = 'Diverse-Konto';                                     // 105
    case Ausgabeziel                            = 'Ausgabeziel';                                       // 106

        // Spalten 107-120: Zahlungskonditionen
    case Waehrungssteuerung                     = 'Währungssteuerung';                                 // 107
    case KreditlimitDebitor                     = 'Kreditlimit (Debitor)';                             // 108
    case Zahlungsbedingung                      = 'Zahlungsbedingung';                                 // 109
    case FaelligkeitInTagenDebitor              = 'Fälligkeit in Tagen (Debitor)';                     // 110
    case SkontoInProzentDebitor                 = 'Skonto in Prozent (Debitor)';                       // 111
    case KreditorenZiel1Tage                    = 'Kreditoren-Ziel 1 Tg.';                             // 112
    case KreditorenSkonto1Prozent               = 'Kreditoren-Skonto 1 %';                             // 113
    case KreditorenZiel2Tage                    = 'Kreditoren-Ziel 2 Tg.';                             // 114
    case KreditorenSkonto2Prozent               = 'Kreditoren-Skonto 2 %';                             // 115
    case KreditorenZiel3BruttoTage              = 'Kreditoren-Ziel 3 Brutto Tg.';                      // 116
    case KreditorenZiel4Tage                    = 'Kreditoren-Ziel 4 Tg.';                             // 117
    case KreditorenSkonto4Prozent               = 'Kreditoren-Skonto 4 %';                             // 118
    case KreditorenZiel5Tage                    = 'Kreditoren-Ziel 5 Tg.';                             // 119
    case KreditorenSkonto5Prozent               = 'Kreditoren-Skonto 5 %';                             // 120

        // Spalten 121-135: Mahnung und Zahlungsabwicklung
    case Mahnung                                = 'Mahnung';                                           // 121
    case Kontoauszug                            = 'Kontoauszug';                                       // 122
    case Mahntext1                              = 'Mahntext 1';                                        // 123
    case Mahntext2                              = 'Mahntext 2';                                        // 124
    case Mahntext3                              = 'Mahntext 3';                                        // 125
    case Kontoauszugstext                       = 'Kontoauszugstext';                                  // 126
    case MahnlimitBetrag                        = 'Mahnlimit Betrag';                                  // 127
    case MahnlimitProzent                       = 'Mahnlimit %';                                       // 128
    case Zinsberechnung                         = 'Zinsberechnung';                                    // 129
    case Mahnzinssatz1                          = 'Mahnzinssatz 1';                                    // 130
    case Mahnzinssatz2                          = 'Mahnzinssatz 2';                                    // 131
    case Mahnzinssatz3                          = 'Mahnzinssatz 3';                                    // 132
    case Lastschrift                            = 'Lastschrift';                                       // 133
    case Leerfeld7                              = 'Leerfeld 7';                                        // 134
    case Mandantenbank                          = 'Mandantenbank';                                     // 135

        // Spalten 136-151: Zahlungsabwicklung und individuelle Felder
    case Zahlungstraeger                        = 'Zahlungsträger';                                    // 136
    case IndivFeld1                             = 'Indiv. Feld 1';                                     // 137
    case IndivFeld2                             = 'Indiv. Feld 2';                                     // 138
    case IndivFeld3                             = 'Indiv. Feld 3';                                     // 139
    case IndivFeld4                             = 'Indiv. Feld 4';                                     // 140
    case IndivFeld5                             = 'Indiv. Feld 5';                                     // 141
    case IndivFeld6                             = 'Indiv. Feld 6';                                     // 142
    case IndivFeld7                             = 'Indiv. Feld 7';                                     // 143
    case IndivFeld8                             = 'Indiv. Feld 8';                                     // 144
    case IndivFeld9                             = 'Indiv. Feld 9';                                     // 145
    case IndivFeld10                            = 'Indiv. Feld 10';                                    // 146
    case IndivFeld11                            = 'Indiv. Feld 11';                                    // 147
    case IndivFeld12                            = 'Indiv. Feld 12';                                    // 148
    case IndivFeld13                            = 'Indiv. Feld 13';                                    // 149
    case IndivFeld14                            = 'Indiv. Feld 14';                                    // 150
    case IndivFeld15                            = 'Indiv. Feld 15';                                    // 151

        // Spalten 152-164: Rechnungsadresse
    case AbweichendeAnredeRechnungsadresse      = 'Abweichende Anrede (Rechnungsadresse)';             // 152
    case AdressartRechnungsadresse              = 'Adressart (Rechnungsadresse)';                      // 153
    case StrasseRechnungsadresse                = 'Straße (Rechnungsadresse)';                         // 154
    case PostfachRechnungsadresse               = 'Postfach (Rechnungsadresse)';                       // 155
    case PostleitzahlRechnungsadresse           = 'Postleitzahl (Rechnungsadresse)';                   // 156
    case OrtRechnungsadresse                    = 'Ort (Rechnungsadresse)';                            // 157
    case LandRechnungsadresse                   = 'Land (Rechnungsadresse)';                           // 158
    case VersandzusatzRechnungsadresse          = 'Versandzusatz (Rechnungsadresse)';                  // 159
    case AdresszusatzRechnungsadresse           = 'Adresszusatz (Rechnungsadresse)';                   // 160
    case AbwZustellbezeichnung1Rechnungsadresse = 'Abw. Zustellbezeichnung 1 (Rechnungsadresse)';      // 161
    case AbwZustellbezeichnung2Rechnungsadresse = 'Abw. Zustellbezeichnung 2 (Rechnungsadresse)';      // 162
    case AdresseGueltigVonRechnungsadresse      = 'Adresse Gültig von (Rechnungsadresse)';             // 163
    case AdresseGueltigBisRechnungsadresse      = 'Adresse Gültig bis (Rechnungsadresse)';             // 164

        // Spalten 165-175: Bankverbindung 6
    case Bankleitzahl6                          = 'Bankleitzahl 6';                                    // 165
    case Bankbezeichnung6                       = 'Bankbezeichnung 6';                                 // 166
    case BankkontoNummer6                       = 'Bank-Kontonummer 6';                                // 167
    case Laenderkennzeichen6                    = 'Länderkennzeichen 6';                               // 168
    case IBANNr6                                = 'IBAN-Nr. 6';                                        // 169
    case Leerfeld8                              = 'Leerfeld 8';                                        // 170
    case SWIFTCode6                             = 'SWIFT-Code 6';                                      // 171
    case AbwKontoinhaber6                       = 'Abw. Kontoinhaber 6';                               // 172
    case KennzHauptbankverb6                    = 'Kennz. Hauptbankverb. 6';                           // 173
    case Bankverb6GueltigVon                    = 'Bankverb 6 Gültig von';                             // 174
    case Bankverb6GueltigBis                    = 'Bankverb 6 Gültig bis';                             // 175

        // Spalten 176-186: Bankverbindung 7
    case Bankleitzahl7                          = 'Bankleitzahl 7';                                    // 176
    case Bankbezeichnung7                       = 'Bankbezeichnung 7';                                 // 177
    case BankkontoNummer7                       = 'Bank-Kontonummer 7';                                // 178
    case Laenderkennzeichen7                    = 'Länderkennzeichen 7';                               // 179
    case IBANNr7                                = 'IBAN-Nr. 7';                                        // 180
    case Leerfeld9                              = 'Leerfeld 9';                                        // 181
    case SWIFTCode7                             = 'SWIFT-Code 7';                                      // 182
    case AbwKontoinhaber7                       = 'Abw. Kontoinhaber 7';                               // 183
    case KennzHauptbankverb7                    = 'Kennz. Hauptbankverb. 7';                           // 184
    case Bankverb7GueltigVon                    = 'Bankverb 7 Gültig von';                             // 185
    case Bankverb7GueltigBis                    = 'Bankverb 7 Gültig bis';                             // 186

        // Spalten 187-197: Bankverbindung 8
    case Bankleitzahl8                          = 'Bankleitzahl 8';                                    // 187
    case Bankbezeichnung8                       = 'Bankbezeichnung 8';                                 // 188
    case BankkontoNummer8                       = 'Bank-Kontonummer 8';                                // 189
    case Laenderkennzeichen8                    = 'Länderkennzeichen 8';                               // 190
    case IBANNr8                                = 'IBAN-Nr. 8';                                        // 191
    case Leerfeld10                             = 'Leerfeld 10';                                       // 192
    case SWIFTCode8                             = 'SWIFT-Code 8';                                      // 193
    case AbwKontoinhaber8                       = 'Abw. Kontoinhaber 8';                               // 194
    case KennzHauptbankverb8                    = 'Kennz. Hauptbankverb. 8';                           // 195
    case Bankverb8GueltigVon                    = 'Bankverb 8 Gültig von';                             // 196
    case Bankverb8GueltigBis                    = 'Bankverb 8 Gültig bis';                             // 197

        // Spalten 198-208: Bankverbindung 9
    case Bankleitzahl9                          = 'Bankleitzahl 9';                                    // 198
    case Bankbezeichnung9                       = 'Bankbezeichnung 9';                                 // 199
    case BankkontoNummer9                       = 'Bank-Kontonummer 9';                                // 200
    case Laenderkennzeichen9                    = 'Länderkennzeichen 9';                               // 201
    case IBANNr9                                = 'IBAN-Nr. 9';                                        // 202
    case Leerfeld11                             = 'Leerfeld 11';                                       // 203
    case SWIFTCode9                             = 'SWIFT-Code 9';                                      // 204
    case AbwKontoinhaber9                       = 'Abw. Kontoinhaber 9';                               // 205
    case KennzHauptbankverb9                    = 'Kennz. Hauptbankverb. 9';                           // 206
    case Bankverb9GueltigVon                    = 'Bankverb 9 Gültig von';                             // 207
    case Bankverb9GueltigBis                    = 'Bankverb 9 Gültig bis';                             // 208

        // Spalten 209-219: Bankverbindung 10
    case Bankleitzahl10                         = 'Bankleitzahl 10';                                   // 209
    case Bankbezeichnung10                      = 'Bankbezeichnung 10';                                // 210
    case BankkontoNummer10                      = 'Bank-Kontonummer 10';                               // 211
    case Laenderkennzeichen10                   = 'Länderkennzeichen 10';                              // 212
    case IBANNr10                               = 'IBAN-Nr. 10';                                       // 213
    case Leerfeld12                             = 'Leerfeld 12';                                       // 214
    case SWIFTCode10                            = 'SWIFT-Code 10';                                     // 215
    case AbwKontoinhaber10                      = 'Abw. Kontoinhaber 10';                              // 216
    case KennzHauptbankverb10                   = 'Kennz. Hauptbankverb. 10';                          // 217
    case Bankverb10GueltigVon                   = 'Bankverb 10 Gültig von';                            // 218
    case Bankverb10GueltigBis                   = 'Bankverb 10 Gültig bis';                            // 219

        // Spalten 220-231: Weitere Daten und SEPA-Mandate
    case NummerFremdsystem                      = 'Nummer Fremdsystem';                                // 220
    case Insolvent                              = 'Insolvent';                                         // 221
    case SEPAMandatsreferenz1                   = 'SEPA-Mandatsreferenz 1';                            // 222
    case SEPAMandatsreferenz2                   = 'SEPA-Mandatsreferenz 2';                            // 223
    case SEPAMandatsreferenz3                   = 'SEPA-Mandatsreferenz 3';                            // 224
    case SEPAMandatsreferenz4                   = 'SEPA-Mandatsreferenz 4';                            // 225
    case SEPAMandatsreferenz5                   = 'SEPA-Mandatsreferenz 5';                            // 226
    case SEPAMandatsreferenz6                   = 'SEPA-Mandatsreferenz 6';                            // 227
    case SEPAMandatsreferenz7                   = 'SEPA-Mandatsreferenz 7';                            // 228
    case SEPAMandatsreferenz8                   = 'SEPA-Mandatsreferenz 8';                            // 229
    case SEPAMandatsreferenz9                   = 'SEPA-Mandatsreferenz 9';                            // 230
    case SEPAMandatsreferenz10                  = 'SEPA-Mandatsreferenz 10';                           // 231

        // Spalten 232-243: Sperren und Gebühren
    case VerknuepftesOPOSKonto                  = 'Verknüpftes OPOS-Konto';                            // 232
    case MahnsperreBis                          = 'Mahnsperre bis';                                    // 233
    case LastschriftsperreBis                   = 'Lastschriftsperre bis';                             // 234
    case ZahlungssperreBis                      = 'Zahlungssperre bis';                                // 235
    case Gebuehrenberechnung                    = 'Gebührenberechnung';                                // 236
    case Mahngebuehr1                           = 'Mahngebühr 1';                                      // 237
    case Mahngebuehr2                           = 'Mahngebühr 2';                                      // 238
    case Mahngebuehr3                           = 'Mahngebühr 3';                                      // 239
    case Pauschalenberechnung                   = 'Pauschalenberechnung';                              // 240
    case Verzugspauschale1                      = 'Verzugspauschale 1';                                // 241
    case Verzugspauschale2                      = 'Verzugspauschale 2';                                // 242
    case Verzugspauschale3                      = 'Verzugspauschale 3';                                // 243

        // Spalten 244-254: Status und Anschrift
    case AlternativerSuchname                   = 'Alternativer Suchname';                             // 244
    case Status                                 = 'Status';                                            // 245
    case AnschriftManuellGeaendertKorrespondenz = 'Anschrift manuell geändert (Korrespondenzadresse)'; // 246
    case AnschriftIndividuellKorrespondenz      = 'Anschrift individuell (Korrespondenzadresse)';      // 247
    case AnschriftManuellGeaendertRechnung      = 'Anschrift manuell geändert (Rechnungsadresse)';     // 248
    case AnschriftIndividuellRechnung           = 'Anschrift individuell (Rechnungsadresse)';          // 249
    case FristberechnungBeiDebitor              = 'Fristberechnung bei Debitor';                       // 250
    case Mahnfrist1                             = 'Mahnfrist 1';                                       // 251
    case Mahnfrist2                             = 'Mahnfrist 2';                                       // 252
    case Mahnfrist3                             = 'Mahnfrist 3';                                       // 253
    case LetztefRist                            = 'Letzte Frist';                                      // 254

    /**
     * Liefert alle 254 Felder in der korrekten DATEV-Reihenfolge.
     */
    public static function ordered(): array {
        return array_values(self::cases());
    }

    /**
     * Liefert alle verpflichtenden Felder.
     */
    public static function required(): array {
        return [
            self::Konto,                       // Pflichtfeld: Konto muss angegeben werden
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
            self::IndivFeld11,
            self::IndivFeld12,
            self::IndivFeld13,
            self::IndivFeld14,
            self::IndivFeld15,
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
     * Liefert alle Debitor-spezifischen Felder.
     */
    public static function debitorFields(): array {
        return [
            self::KreditlimitDebitor,
            self::FaelligkeitInTagenDebitor,
            self::SkontoInProzentDebitor,
            self::FristberechnungBeiDebitor,
            self::Mahnfrist1,
            self::Mahnfrist2,
            self::Mahnfrist3,
            self::LetztefRist,
        ];
    }

    /**
     * Liefert alle Kreditor-spezifischen Felder.
     */
    public static function creditorFields(): array {
        return [
            self::KreditorenZiel1Tage,
            self::KreditorenSkonto1Prozent,
            self::KreditorenZiel2Tage,
            self::KreditorenSkonto2Prozent,
            self::KreditorenZiel3BruttoTage,
            self::KreditorenZiel4Tage,
            self::KreditorenSkonto4Prozent,
            self::KreditorenZiel5Tage,
            self::KreditorenSkonto5Prozent,
        ];
    }

    /**
     * Liefert alle Mahnungsfelder.
     */
    public static function dunningFields(): array {
        return [
            self::Mahnung,
            self::Mahntext1,
            self::Mahntext2,
            self::Mahntext3,
            self::MahnlimitBetrag,
            self::MahnlimitProzent,
            self::Zinsberechnung,
            self::Mahnzinssatz1,
            self::Mahnzinssatz2,
            self::Mahnzinssatz3,
            self::MahnsperreBis,
            self::Mahngebuehr1,
            self::Mahngebuehr2,
            self::Mahngebuehr3,
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
     * Prüft, ob ein Feld für Debitoren spezifisch ist.
     */
    public function isDebitorField(): bool {
        return in_array($this, self::debitorFields(), true);
    }

    /**
     * Prüft, ob ein Feld für Kreditoren spezifisch ist.
     */
    public function isCreditorField(): bool {
        return in_array($this, self::creditorFields(), true);
    }

    /**
     * Prüft, ob ein Feld für Mahnungen relevant ist.
     */
    public function isDunningField(): bool {
        return in_array($this, self::dunningFields(), true);
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
     * Liefert eine Beschreibung des Feldtyps.
     */
    public function getFieldType(): string {
        return match ($this) {
            self::Konto, self::Sprache, self::DiverseKonto,
            self::Ausgabeziel, self::Waehrungssteuerung,
            self::Zahlungsbedingung, self::Mahnung,
            self::Kontoauszug, self::Zinsberechnung,
            self::Mandantenbank, self::Zahlungstraeger,
            self::Insolvent, self::Gebuehrenberechnung,
            self::Pauschalenberechnung, self::Status,
            self::FristberechnungBeiDebitor => 'integer',

            self::AdresseGueltigVon, self::AdresseGueltigBis,
            self::Bankverb1GueltigVon, self::Bankverb1GueltigBis,
            self::Bankverb2GueltigVon, self::Bankverb2GueltigBis,
            self::Bankverb3GueltigVon, self::Bankverb3GueltigBis,
            self::Bankverb4GueltigVon, self::Bankverb4GueltigBis,
            self::Bankverb5GueltigVon, self::Bankverb5GueltigBis,
            self::Bankverb6GueltigVon, self::Bankverb6GueltigBis,
            self::Bankverb7GueltigVon, self::Bankverb7GueltigBis,
            self::Bankverb8GueltigVon, self::Bankverb8GueltigBis,
            self::Bankverb9GueltigVon, self::Bankverb9GueltigBis,
            self::Bankverb10GueltigVon, self::Bankverb10GueltigBis,
            self::AdresseGueltigVonRechnungsadresse, self::AdresseGueltigBisRechnungsadresse,
            self::MahnsperreBis, self::LastschriftsperreBis,
            self::ZahlungssperreBis => 'date',

            self::KreditlimitDebitor, self::SkontoInProzentDebitor,
            self::KreditorenSkonto1Prozent, self::KreditorenSkonto2Prozent,
            self::KreditorenSkonto4Prozent, self::KreditorenSkonto5Prozent,
            self::MahnlimitBetrag, self::MahnlimitProzent,
            self::Mahnzinssatz1, self::Mahnzinssatz2, self::Mahnzinssatz3,
            self::Mahngebuehr1, self::Mahngebuehr2, self::Mahngebuehr3,
            self::Verzugspauschale1, self::Verzugspauschale2,
            self::Verzugspauschale3 => 'decimal',

            self::Adressattyp, self::Adressart, self::KennzKorrespondenzadresse,
            self::KennzHauptbankverb1, self::KennzHauptbankverb2,
            self::KennzHauptbankverb3, self::KennzHauptbankverb4,
            self::KennzHauptbankverb5, self::KennzHauptbankverb6,
            self::KennzHauptbankverb7, self::KennzHauptbankverb8,
            self::KennzHauptbankverb9, self::KennzHauptbankverb10,
            self::AdressartRechnungsadresse, self::Lastschrift,
            self::AnschriftManuellGeaendertKorrespondenz,
            self::AnschriftManuellGeaendertRechnung => 'enum',

            default => 'string',
        };
    }

    /**
     * Liefert die maximale Feldlänge für DATEV.
     */
    public function getMaxLength(): ?int {
        return match ($this) {
            self::Konto => 9,
            self::NameUnternehmen, self::Unternehmensgegenstand,
            self::NameKeineAngabe => 50,
            self::NameNatuerlichePerson, self::VornameNatuerlichePerson,
            self::Ort, self::OrtRechnungsadresse, self::Anrede,
            self::AbweichendeAnrede, self::AbweichendeAnredeRechnungsadresse => 30,
            self::Kurzbezeichnung => 15,
            self::EULand, self::Land, self::LandRechnungsadresse,
            self::Laenderkennzeichen1, self::Laenderkennzeichen2,
            self::Laenderkennzeichen3, self::Laenderkennzeichen4,
            self::Laenderkennzeichen5, self::Laenderkennzeichen6,
            self::Laenderkennzeichen7, self::Laenderkennzeichen8,
            self::Laenderkennzeichen9, self::Laenderkennzeichen10 => 2,
            self::EUUStID => 13,
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
            self::IndivFeld9, self::IndivFeld10, self::IndivFeld11, self::IndivFeld12,
            self::IndivFeld13, self::IndivFeld14, self::IndivFeld15,
            self::Ansprechpartner, self::Vertreter, self::Sachbearbeiter => 40,
            self::Bankleitzahl1, self::Bankleitzahl2, self::Bankleitzahl3,
            self::Bankleitzahl4, self::Bankleitzahl5, self::Bankleitzahl6,
            self::Bankleitzahl7, self::Bankleitzahl8, self::Bankleitzahl9,
            self::Bankleitzahl10 => 8,
            self::Bankbezeichnung1, self::Bankbezeichnung2, self::Bankbezeichnung3,
            self::Bankbezeichnung4, self::Bankbezeichnung5, self::Bankbezeichnung6,
            self::Bankbezeichnung7, self::Bankbezeichnung8, self::Bankbezeichnung9,
            self::Bankbezeichnung10 => 30,
            self::BankkontoNummer1, self::BankkontoNummer2, self::BankkontoNummer3,
            self::BankkontoNummer4, self::BankkontoNummer5, self::BankkontoNummer6,
            self::BankkontoNummer7, self::BankkontoNummer8, self::BankkontoNummer9,
            self::BankkontoNummer10 => 10,
            self::IBANNr1, self::IBANNr2, self::IBANNr3, self::IBANNr4,
            self::IBANNr5, self::IBANNr6, self::IBANNr7, self::IBANNr8,
            self::IBANNr9, self::IBANNr10 => 34,
            self::SWIFTCode1, self::SWIFTCode2, self::SWIFTCode3, self::SWIFTCode4,
            self::SWIFTCode5, self::SWIFTCode6, self::SWIFTCode7, self::SWIFTCode8,
            self::SWIFTCode9, self::SWIFTCode10 => 11,
            self::AbwKontoinhaber1, self::AbwKontoinhaber2, self::AbwKontoinhaber3,
            self::AbwKontoinhaber4, self::AbwKontoinhaber5, self::AbwKontoinhaber6,
            self::AbwKontoinhaber7, self::AbwKontoinhaber8, self::AbwKontoinhaber9,
            self::AbwKontoinhaber10 => 70,
            self::Briefanrede => 100,
            self::Grussformel, self::AlternativerSuchname => 50,
            self::Kundennummer, self::NummerFremdsystem => 15,
            self::Steuernummer => 20,
            self::SEPAMandatsreferenz1, self::SEPAMandatsreferenz2, self::SEPAMandatsreferenz3,
            self::SEPAMandatsreferenz4, self::SEPAMandatsreferenz5, self::SEPAMandatsreferenz6,
            self::SEPAMandatsreferenz7, self::SEPAMandatsreferenz8, self::SEPAMandatsreferenz9,
            self::SEPAMandatsreferenz10 => 35,
            self::AnschriftIndividuellKorrespondenz, self::AnschriftIndividuellRechnung => 306,
            default => null,
        };
    }

    /**
     * Liefert das Regex-Pattern für DATEV-Validierung.
     */
    public function getValidationPattern(): ?string {
        return match ($this) {
            self::Konto => '^(?!0{1,9}$)(\d{1,9})$',
            self::NameUnternehmen, self::Unternehmensgegenstand,
            self::NameKeineAngabe => '^("(.){0,50}")$',
            self::NameNatuerlichePerson => '^("(.){0,30}")$',
            self::VornameNatuerlichePerson => '^("(.){0,30}")$',
            self::Adressattyp => '^("[\d]")$',
            self::Kurzbezeichnung => '^("(.){0,15}")$',
            self::EULand => '^("[\w]{0,2}")$',
            self::EUUStID => '^("(.){0,13}")$',
            self::Anrede, self::AbweichendeAnrede,
            self::AbweichendeAnredeRechnungsadresse => '^("(.){0,30}")$',
            self::TitelAkademischerGrad => '^("(.){0,25}")$',
            self::Adelstitel => '^("(.){0,15}")$',
            self::Namensvorsatz => '^("(.){0,14}")$',
            self::Adressart, self::AdressartRechnungsadresse => '^("[\w]{0,3}")$',
            self::Strasse, self::StrasseRechnungsadresse => '^("(.){0,36}")$',
            self::Postfach, self::PostfachRechnungsadresse => '^("(.){0,10}")$',
            self::Postleitzahl, self::PostleitzahlRechnungsadresse => '^("[\d]{0,10}")$',
            self::Ort, self::OrtRechnungsadresse => '^("(.){0,30}")$',
            self::Land, self::LandRechnungsadresse => '^("[A-Z]{2}")$',
            self::Versandzusatz, self::VersandzusatzRechnungsadresse => '^("(.){0,50}")$',
            self::Adresszusatz, self::AdresszusatzRechnungsadresse => '^("(.){0,36}")$',
            self::AbwZustellbezeichnung1, self::AbwZustellbezeichnung1Rechnungsadresse => '^("(.){0,50}")$',
            self::AbwZustellbezeichnung2, self::AbwZustellbezeichnung2Rechnungsadresse => '^("(.){0,36}")$',
            self::KennzKorrespondenzadresse => '^[\d]$',
            self::AdresseGueltigVon, self::AdresseGueltigBis,
            self::AdresseGueltigVonRechnungsadresse, self::AdresseGueltigBisRechnungsadresse => '^((0[1-9]|[1-2][\d]|3[0-1])(0[1-9]|1[0-2])([2])([0])([\d]{2}))$',
            self::Telefon, self::TelefonGeschaeftsleitung, self::EMail,
            self::Internet, self::Fax, self::Sonstige => '^("(.){0,60}")$',
            self::BemerkungTelefon, self::BemerkungTelefonGL, self::BemerkungEMail,
            self::BemerkungInternet, self::BemerkungFax, self::BemerkungSonstige => '^("(.){0,40}")$',
            self::Bankleitzahl1, self::Bankleitzahl2, self::Bankleitzahl3,
            self::Bankleitzahl4, self::Bankleitzahl5, self::Bankleitzahl6,
            self::Bankleitzahl7, self::Bankleitzahl8, self::Bankleitzahl9,
            self::Bankleitzahl10 => '^("[\d]{0,8}")$',
            self::Sprache => '^[\d]{0,2}$',
            self::DiverseKonto => '^[\d]$',
            self::Ausgabeziel => '^[\d]$',
            self::Waehrungssteuerung => '^[\d]$',
            self::KreditlimitDebitor => '^[\d.]{0,13}$',
            self::SkontoInProzentDebitor, self::KreditorenSkonto1Prozent,
            self::KreditorenSkonto2Prozent, self::KreditorenSkonto4Prozent,
            self::KreditorenSkonto5Prozent => '^([1-9][\d]{0,1}[,][\d]{2})$',
            self::Lastschrift => '^("[\d]")$',

            // Bankverbindungen (weitere Felder)
            self::Bankbezeichnung1, self::Bankbezeichnung2, self::Bankbezeichnung3,
            self::Bankbezeichnung4, self::Bankbezeichnung5, self::Bankbezeichnung6,
            self::Bankbezeichnung7, self::Bankbezeichnung8, self::Bankbezeichnung9,
            self::Bankbezeichnung10 => '^("(.){0,30}")$',

            self::BankkontoNummer1, self::BankkontoNummer2, self::BankkontoNummer3,
            self::BankkontoNummer4, self::BankkontoNummer5, self::BankkontoNummer6,
            self::BankkontoNummer7, self::BankkontoNummer8, self::BankkontoNummer9,
            self::BankkontoNummer10 => '^("(.){0,10}")$',

            self::Laenderkennzeichen1, self::Laenderkennzeichen2, self::Laenderkennzeichen3,
            self::Laenderkennzeichen4, self::Laenderkennzeichen5, self::Laenderkennzeichen6,
            self::Laenderkennzeichen7, self::Laenderkennzeichen8, self::Laenderkennzeichen9,
            self::Laenderkennzeichen10 => '^("[A-Z]{2}")$',

            self::IBANNr1, self::IBANNr2, self::IBANNr3, self::IBANNr4,
            self::IBANNr5, self::IBANNr6, self::IBANNr7, self::IBANNr8,
            self::IBANNr9, self::IBANNr10 => '^("[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}")$',

            self::SWIFTCode1, self::SWIFTCode2, self::SWIFTCode3, self::SWIFTCode4,
            self::SWIFTCode5, self::SWIFTCode6, self::SWIFTCode7, self::SWIFTCode8,
            self::SWIFTCode9, self::SWIFTCode10 => '^("[A-Z]{4}[A-Z0-9]{2}([A-Z0-9]{3})?")$',

            self::AbwKontoinhaber1, self::AbwKontoinhaber2, self::AbwKontoinhaber3,
            self::AbwKontoinhaber4, self::AbwKontoinhaber5, self::AbwKontoinhaber6,
            self::AbwKontoinhaber7, self::AbwKontoinhaber8, self::AbwKontoinhaber9,
            self::AbwKontoinhaber10 => '^("(.){0,70}")$',

            self::KennzHauptbankverb1, self::KennzHauptbankverb2, self::KennzHauptbankverb3,
            self::KennzHauptbankverb4, self::KennzHauptbankverb5, self::KennzHauptbankverb6,
            self::KennzHauptbankverb7, self::KennzHauptbankverb8, self::KennzHauptbankverb9,
            self::KennzHauptbankverb10 => '^("[01]")$',

            self::Bankverb1GueltigVon, self::Bankverb1GueltigBis, self::Bankverb2GueltigVon, self::Bankverb2GueltigBis,
            self::Bankverb3GueltigVon, self::Bankverb3GueltigBis, self::Bankverb4GueltigVon, self::Bankverb4GueltigBis,
            self::Bankverb5GueltigVon, self::Bankverb5GueltigBis, self::Bankverb6GueltigVon, self::Bankverb6GueltigBis,
            self::Bankverb7GueltigVon, self::Bankverb7GueltigBis, self::Bankverb8GueltigVon, self::Bankverb8GueltigBis,
            self::Bankverb9GueltigVon, self::Bankverb9GueltigBis, self::Bankverb10GueltigVon, self::Bankverb10GueltigBis
            => '^((0[1-9]|[1-2][\d]|3[0-1])(0[1-9]|1[0-2])([2])([0])([\d]{2}))$',

            // Leerfelder
            self::Leerfeld1, self::Leerfeld2, self::Leerfeld3, self::Leerfeld4, self::Leerfeld5,
            self::Leerfeld6, self::Leerfeld7, self::Leerfeld8, self::Leerfeld9, self::Leerfeld10,
            self::Leerfeld11, self::Leerfeld12 => '^$',

            // Weitere Stammdaten
            self::Briefanrede => '^("(.){0,100}")$',
            self::Grussformel => '^("(.){0,50}")$',
            self::Kundennummer => '^("(.){0,15}")$',
            self::Steuernummer => '^("(.){0,20}")$',
            self::Ansprechpartner, self::Vertreter, self::Sachbearbeiter => '^("(.){0,40}")$',

            // Zahlungskonditionen
            self::Zahlungsbedingung => '^[\d]{1,3}$',
            self::FaelligkeitInTagenDebitor => '^[\d]{0,3}$',
            self::KreditorenZiel1Tage, self::KreditorenZiel2Tage, self::KreditorenZiel3BruttoTage,
            self::KreditorenZiel4Tage, self::KreditorenZiel5Tage => '^[\d]{0,3}$',

            // Mahnung und Zahlungsabwicklung  
            self::Mahnung => '^[0-3]$',
            self::Kontoauszug => '^[0-2]$',
            self::Mahntext1, self::Mahntext2, self::Mahntext3 => '^("(.){0,40}")$',
            self::Kontoauszugstext => '^("(.){0,40}")$',
            self::MahnlimitBetrag => '^[\d]{0,13}([,][\d]{2})?$',
            self::MahnlimitProzent => '^[\d]{1,3}([,][\d]{2})?$',
            self::Zinsberechnung => '^[0-2]$',
            self::Mahnzinssatz1, self::Mahnzinssatz2, self::Mahnzinssatz3 => '^[\d]{1,2}([,][\d]{2})?$',
            self::Mandantenbank => '^[\d]{1,3}$',

            // Zahlungsabwicklung
            self::Zahlungstraeger => '^[\d]{1,2}$',

            // Individuelle Felder
            self::IndivFeld1, self::IndivFeld2, self::IndivFeld3, self::IndivFeld4, self::IndivFeld5,
            self::IndivFeld6, self::IndivFeld7, self::IndivFeld8, self::IndivFeld9, self::IndivFeld10,
            self::IndivFeld11, self::IndivFeld12, self::IndivFeld13, self::IndivFeld14, self::IndivFeld15
            => '^("(.){0,40}")$',

            // SEPA-Mandate
            self::SEPAMandatsreferenz1, self::SEPAMandatsreferenz2, self::SEPAMandatsreferenz3,
            self::SEPAMandatsreferenz4, self::SEPAMandatsreferenz5, self::SEPAMandatsreferenz6,
            self::SEPAMandatsreferenz7, self::SEPAMandatsreferenz8, self::SEPAMandatsreferenz9,
            self::SEPAMandatsreferenz10 => '^("(.){0,35}")$',

            // Sperren und weitere Daten
            self::VerknuepftesOPOSKonto => '^[\d]{1,9}$',
            self::MahnsperreBis, self::LastschriftsperreBis, self::ZahlungssperreBis
            => '^((0[1-9]|[1-2][\d]|3[0-1])(0[1-9]|1[0-2])([2])([0])([\d]{2}))$',

            // Gebühren und Pauschalen
            self::Gebuehrenberechnung => '^[0-2]$',
            self::Mahngebuehr1, self::Mahngebuehr2, self::Mahngebuehr3 => '^[\d]{1,8}([,][\d]{2})?$',
            self::Pauschalenberechnung => '^[0-1]$',
            self::Verzugspauschale1, self::Verzugspauschale2, self::Verzugspauschale3 => '^[\d]{1,8}([,][\d]{2})?$',

            // Status und weitere Felder
            self::AlternativerSuchname => '^("(.){0,50}")$',
            self::Status => '^[1-3]$',
            self::AnschriftManuellGeaendertKorrespondenz, self::AnschriftIndividuellKorrespondenz,
            self::AnschriftManuellGeaendertRechnung, self::AnschriftIndividuellRechnung => '^[01]$',

            // Fristberechnung und Mahnfristen
            self::FristberechnungBeiDebitor => '^[1-2]$',
            self::Mahnfrist1, self::Mahnfrist2, self::Mahnfrist3, self::LetztefRist => '^[\d]{0,3}$',

            // Nummer Fremdsystem
            self::NummerFremdsystem => '^("(.){0,15}")$',
            self::Insolvent => '^[01]$',

            default => null,
        };
    }
}