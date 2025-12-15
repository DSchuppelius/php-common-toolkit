<?php
/*
 * Created on   : Wed Nov 05 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DatevDocumentBuilder.php
 * License      : MIT License
 */

declare(strict_types=1);

namespace CommonToolkit\Builders\DATEV;

use CKonverter\Core\Entities\DATEV\{Document, MetaHeaderLine};
use CommonToolkit\Builders\CSVDocumentBuilder;
use CommonToolkit\Entities\Common\CSV\CSVHeaderField;
use CommonToolkit\Enums\DATEV\Format;
use RuntimeException;

final class DatevDocumentBuilder extends CSVDocumentBuilder {
    private Format $format = Format::Buchungsstapel;
    private ?MetaHeaderLine $metaHeader = null;


    public function __construct(Format $format = Format::Buchungsstapel, string $delimiter = ';', string $enclosure = '"') {
        parent::__construct($delimiter, $enclosure);
        $this->format = $format;
    }

    public function setMetaHeader(MetaHeaderLine $header): self {
        $this->metaHeader = $header;
        return $this;
    }

    /**
     * Erzeugt automatisch den festen DATEV-Feldheader (BookingBatch-Format).
     */
    protected function createFixedFieldHeader(): FieldHeaderLine {
        $fields = [
            'Umsatz (ohne Soll/Haben-Kz)',
            'Soll/Haben-Kennzeichen',
            'WKZ Umsatz',
            'Kurs',
            'Basis-Umsatz',
            'WKZ Basis-Umsatz',
            'Konto',
            'Gegenkonto (ohne BU-Schlüssel)',
            'BU-Schlüssel',
            'Belegdatum',
            'Belegfeld 1',
            'Belegfeld 2',
            'Skonto',
            'Buchungstext',
            'Postensperre',
            'Diverse Adressnummer',
            'Geschäftspartnerbank',
            'Sachverhalt',
            'Zinssperre',
            'Beleglink',
            'Beleginfo - Art 1',
            'Beleginfo - Inhalt 1',
            'Beleginfo - Art 2',
            'Beleginfo - Inhalt 2',
            'Beleginfo - Art 3',
            'Beleginfo - Inhalt 3',
            'Beleginfo - Art 4',
            'Beleginfo - Inhalt 4',
            'Beleginfo - Art 5',
            'Beleginfo - Inhalt 5',
            'Beleginfo - Art 6',
            'Beleginfo - Inhalt 6',
            'Beleginfo - Art 7',
            'Beleginfo - Inhalt 7',
            'Beleginfo - Art 8',
            'Beleginfo - Inhalt 8',
            'KOST1 - Kostenstelle',
            'KOST2 - Kostenstelle',
            'Kost-Menge',
            'EU-Land u. UStID (Bestimmung)',
            'EU-Steuersatz (Bestimmung)',
            'Abw. Versteuerungsart',
            'Sachverhalt L+L',
            'Funktionsergänzung L+L',
            'BU 49 Hauptfunktionstyp',
            'BU 49 Hauptfunktionsnummer',
            'BU 49 Funktionsergänzung',
            'Zusatzinformation - Art 1',
            'Zusatzinformation- Inhalt 1',
            'Zusatzinformation - Art 2',
            'Zusatzinformation- Inhalt 2',
            'Zusatzinformation - Art 3',
            'Zusatzinformation- Inhalt 3',
            'Zusatzinformation - Art 4',
            'Zusatzinformation- Inhalt 4',
            'Zusatzinformation - Art 5',
            'Zusatzinformation- Inhalt 5',
            'Zusatzinformation - Art 6',
            'Zusatzinformation- Inhalt 6',
            'Zusatzinformation - Art 7',
            'Zusatzinformation- Inhalt 7',
            'Zusatzinformation - Art 8',
            'Zusatzinformation- Inhalt 8',
            'Zusatzinformation - Art 9',
            'Zusatzinformation- Inhalt 9',
            'Zusatzinformation - Art 10',
            'Zusatzinformation- Inhalt 10',
            'Zusatzinformation - Art 11',
            'Zusatzinformation- Inhalt 11',
            'Zusatzinformation - Art 12',
            'Zusatzinformation- Inhalt 12',
            'Zusatzinformation - Art 13',
            'Zusatzinformation- Inhalt 13',
            'Zusatzinformation - Art 14',
            'Zusatzinformation- Inhalt 14',
            'Zusatzinformation - Art 15',
            'Zusatzinformation- Inhalt 15',
            'Zusatzinformation - Art 16',
            'Zusatzinformation- Inhalt 16',
            'Zusatzinformation - Art 17',
            'Zusatzinformation- Inhalt 17',
            'Zusatzinformation - Art 18',
            'Zusatzinformation- Inhalt 18',
            'Zusatzinformation - Art 19',
            'Zusatzinformation- Inhalt 19',
            'Zusatzinformation - Art 20',
            'Zusatzinformation- Inhalt 20',
            'Stück',
            'Gewicht',
            'Zahlweise',
            'Forderungsart',
            'Veranlagungsjahr',
            'Zugeordnete Fälligkeit',
            'Skontotyp',
            'Auftragsnummer',
            'Buchungstyp',
            'USt-Schlüssel (Anzahlungen)',
            'EU-Land (Anzahlungen)',
            'Sachverhalt L+L (Anzahlungen)',
            'EU-Steuersatz (Anzahlungen)',
            'Erlöskonto (Anzahlungen)',
            'Herkunft-Kz',
            'Buchungs GUID',
            'KOST-Datum',
            'SEPA-Mandatsreferenz',
            'Skontosperre',
            'Gesellschaftername',
            'Beteiligtennummer',
            'Identifikationsnummer',
            'Zeichnernummer',
            'Postensperre bis',
            'Bezeichnung SoBil-Sachverhalt',
            'Kennzeichen SoBil-Buchung',
            'Festschreibung',
            'Leistungsdatum',
            'Datum Zuord. Steuerperiode',
            'Fälligkeit',
            'Generalumkehr (GU)',
            'Steuersatz',
            'Land',
            'Abrechnungsreferenz',
            'BVV-Position',
            'EU-Land u. UStID (Ursprung)',
            'EU-Steuersatz (Ursprung)',
            'Abw. Skontokonto',
        ];

        $headerFields = array_map(
            fn(string $v) => new CSVHeaderField('"' . $v . '"', '"'),
            $fields
        );

        return new DatevFieldHeaderLine($headerFields);
    }

    public function build(): Document {
        if (!$this->metaHeader) {
            throw new RuntimeException('DATEV-Metaheader muss gesetzt sein.');
        }

        $fieldHeader = new DatevFieldHeaderLine(
            array_map(fn($v) => new CSVHeaderField('"' . $v . '"', '"'), $this->format->fieldNames())
        );

        return new Document(
            $this->metaHeader,
            $fieldHeader,
            $this->rows,
            $this->delimiter,
            $this->enclosure
        );
    }
}
