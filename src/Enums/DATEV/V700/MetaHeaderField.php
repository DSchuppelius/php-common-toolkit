<?php
/*
 * Created on   : Sun Dec 07 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MetaHeaderField.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV\V700;

use CommonToolkit\Contracts\Interfaces\DATEV\MetaHeaderFieldInterface;

/**
 * DATEV Metaheader (Version 700), Felder der Kopfzeile 1.
 * Die Cases benennen das Feld, die Position ergibt sich aus ordered().
 */
enum MetaHeaderField: string implements MetaHeaderFieldInterface {
    // 1–5: Formatdefinition
    case Kennzeichen           = 'Kennzeichen';           // 1
    case Versionsnummer        = 'Versionsnummer';        // 2
    case Formatkategorie       = 'Formatkategorie';       // 3
    case Formatname            = 'Formatname';            // 4
    case Formatversion         = 'Formatversion';         // 5

        // 6–10: Zeit/Herkunft
    case ErzeugtAm             = 'Erzeugt am';            // 6
    case Importiert            = 'Importiert';            // 7
    case Herkunft              = 'Herkunft';              // 8
    case ExportiertVon         = 'Exportiert von';        // 9
    case ImportiertVon         = 'Importiert von';        // 10

        // 11–16: Berater/Mandant/Zeiträume
    case Beraternummer         = 'Beraternummer';         // 11
    case Mandantennummer       = 'Mandantennummer';       // 12
    case WJBeginn              = 'WJ-Beginn';             // 13
    case Sachkontenlaenge      = 'Sachkontenlänge';       // 14
    case DatumVon              = 'Datum von';             // 15
    case DatumBis              = 'Datum bis';             // 16

        // 17–22: Bezeichnung/Typ/Zweck/Währung
    case Bezeichnung           = 'Bezeichnung';           // 17
    case Diktatkuerzel         = 'Diktatkürzel';          // 18
    case Buchungstyp           = 'Buchungstyp';           // 19
    case Rechnungslegungszweck = 'Rechnungslegungszweck'; // 20
    case Festschreibung        = 'Festschreibung';        // 21
    case Waehrungskennzeichen  = 'WKZ';                   // 22

        // 23–26: Reserviert/Derivat
    case Reserviert23          = 'Reserviert23';          // 23
    case Derivatskennzeichen   = 'Derivatskennzeichen';   // 24
    case Reserviert25          = 'Reserviert25';          // 25
    case Reserviert26          = 'Reserviert26';          // 26

        // 27–31: Rahmen/Branche/Reserviert/App-Info
    case Sachkontenrahmen      = 'Sachkontenrahmen';      // 27
    case BranchenloesungID     = 'ID der Branchenlösung'; // 28
    case Reserviert29          = 'Reserviert29';          // 29
    case Reserviert30          = 'Reserviert30';          // 30
    case Anwendungsinformation = 'Anwendungsinformation'; // 31

    /**
     * Slots ohne Hochkomma: alphanumerisch inkl. Unterstrich, optional leer.
     * Passt zu deinem Wunsch “ohne Hochkommata”.
     */
    private static function alphaNumericSlotPattern(): string {
        return '^(?:\w*|"{2})$';
    }

    /**
     * Text-Slot mit Hochkomma: "" oder "ddsasdads3223" etc.
     * Wichtig: KEINE eigenen Delimiter, weil preg_match('/'.$pattern.'/u') verwendet wird.
     */
    private static function textSlotPattern(): string {
        return '^(?:""|"[^"]*"|)$';
    }

    public function label(): string {
        return match ($this) {
            self::Reserviert23,
            self::Reserviert25,
            self::Reserviert26,
            self::Reserviert29,
            self::Reserviert30 => 'Reserviert',
            default => $this->value,
        };
    }

    /**
     * Regex für Validierung des jeweiligen Feldes (inkl. Anführungszeichen, wo spezifiziert).
     */
    public function pattern(): ?string {
        return match ($this) {
            // 1–5
            self::Kennzeichen            => '^"(EXTF|DTVF)"$',
            self::Versionsnummer         => '^(700)$',
            self::Formatkategorie        => '^(16|20|21|46|48|65|66)$',
            self::Formatname             => '^"(Buchungsstapel|Wiederkehrende Buchungen|Debitoren\/Kreditoren|Kontenbeschriftungen|Zahlungsbedingungen|Diverse Adressen|Natural\-Stapel)"$',
            self::Formatversion          => '^(2|3|4|5|13)$',

            // 6–10
            self::ErzeugtAm              => '^20\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])(2[0-3]|[01]\d)([0-5]\d)([0-5]\d)\d{3}$',
            self::Importiert             => self::alphaNumericSlotPattern(),
            self::Herkunft               => '^"\w{0,2}"$',
            self::ExportiertVon          => '^"\w{0,25}"$',
            self::ImportiertVon          => '^"\w{0,25}"$',

            // 11–16
            self::Beraternummer          => '^(\d{4,6}|\d{7})$',
            self::Mandantennummer        => '^\d{1,5}$',
            self::WJBeginn               => '^20\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])$',
            self::Sachkontenlaenge       => '^[4-8]$',
            self::DatumVon               => '^(?:20\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01]))?$',
            self::DatumBis               => '^(?:20\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01]))?$',

            // 17–22
            self::Bezeichnung            => '^"[\w.\-\/ ]{0,30}"$',
            self::Diktatkuerzel          => '^"([A-Z]{2}){0,2}"$',
            self::Buchungstyp            => '^(?:[1-2])?$',
            self::Rechnungslegungszweck  => '^(?:0|30|40|50|64)?$',
            self::Festschreibung         => '^(?:0|1)?$',
            self::Waehrungskennzeichen   => '^(?:"[A-Z]{3}"|"")$',

            // 23–26
            self::Reserviert23           => self::alphaNumericSlotPattern(),
            self::Derivatskennzeichen    => self::textSlotPattern(),
            self::Reserviert25           => self::alphaNumericSlotPattern(),
            self::Reserviert26           => self::alphaNumericSlotPattern(),

            // 27–31
            self::Sachkontenrahmen       => '^(?:\d{1,4}|""|"[0-9]{1,4}"|)$',
            self::BranchenloesungID      => '^(?:\d{0,4}|"{2})$',
            self::Reserviert29           => self::alphaNumericSlotPattern(),
            self::Reserviert30           => self::textSlotPattern(),
            self::Anwendungsinformation  => '^(?:"[^"]{0,16}"|)$',

            default => null,
        };
    }

    public function position(): int {
        return array_search($this, self::ordered(), true) + 1;
    }

    /**
     * Reihenfolge 1..31 für Export/Parsing.
     *
     * @return list<self>
     */
    public static function ordered(): array {
        return [
            self::Kennzeichen,
            self::Versionsnummer,
            self::Formatkategorie,
            self::Formatname,
            self::Formatversion,
            self::ErzeugtAm,
            self::Importiert,
            self::Herkunft,
            self::ExportiertVon,
            self::ImportiertVon,
            self::Beraternummer,
            self::Mandantennummer,
            self::WJBeginn,
            self::Sachkontenlaenge,
            self::DatumVon,
            self::DatumBis,
            self::Bezeichnung,
            self::Diktatkuerzel,
            self::Buchungstyp,
            self::Rechnungslegungszweck,
            self::Festschreibung,
            self::Waehrungskennzeichen,
            self::Reserviert23,
            self::Derivatskennzeichen,
            self::Reserviert25,
            self::Reserviert26,
            self::Sachkontenrahmen,
            self::BranchenloesungID,
            self::Reserviert29,
            self::Reserviert30,
            self::Anwendungsinformation,
        ];
    }
}