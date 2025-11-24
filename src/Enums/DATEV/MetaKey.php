<?php

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

/**
 * Enum für die Feldnamen des DATEV-Headerblocks (Zeile 1).
 */
enum MetaKey: string {
    case Kennzeichen = 'Kennzeichen';
    case Versionsnummer = 'Versionsnummer';
    case Datenkategorie = 'Datenkategorie';
    case Formatname = 'Formatname';
    case Formatversion = 'Formatversion';
    case ErzeugtAm = 'Erzeugt am';
    case ImportiertAm = 'Importiert am';
    case ImportiertDurch = 'Importiert durch';
    case Beraternummer = 'Beraternummer';
    case Mandantennummer = 'Mandantennummer';
    case Wirtschaftsjahresbeginn = 'Wirtschaftsjahresbeginn';
    case DatumVom = 'Datum vom';
    case DatumBis = 'Datum bis';
    case Bezeichnung = 'Bezeichnung';
    case Diktatkennzeichen = 'Diktatkennzeichen';
    case Beratername = 'Beratername';
    case Mandantenname = 'Mandantenname';
    case Waehrungskennzeichen = 'Währungskennzeichen';
    case Datenlieferant = 'Datenlieferant';
    case Datenempfaenger = 'Datenempfänger';
    case Reserve = 'Reserve';

    /**
     * Reihenfolge laut offizieller Spezifikation.
     * @return list<self>
     */
    public static function order(): array {
        return [
            self::Kennzeichen,
            self::Versionsnummer,
            self::Datenkategorie,
            self::Formatname,
            self::Formatversion,
            self::ErzeugtAm,
            self::ImportiertAm,
            self::ImportiertDurch,
            self::Beraternummer,
            self::Mandantennummer,
            self::Wirtschaftsjahresbeginn,
            self::DatumVom,
            self::DatumBis,
            self::Bezeichnung,
            self::Diktatkennzeichen,
            self::Beratername,
            self::Mandantenname,
            self::Waehrungskennzeichen,
            self::Datenlieferant,
            self::Datenempfaenger,
            self::Reserve,
        ];
    }
}