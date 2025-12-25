<?php
/*
 * Created on   : Wed Nov 12 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MetaHeaderLineTest.php
 * License      : MIT License
 */

declare(strict_types=1);

namespace Tests\CommonToolkit\Entities\DATEV\V700;

use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\DATEV\MetaHeaderLine;
use CommonToolkit\Enums\DATEV\HeaderFields\V700\MetaHeaderField;
use CommonToolkit\Registries\DATEV\HeaderRegistry;
use Tests\Contracts\BaseTestCase;

class MetaHeaderLineTest extends BaseTestCase {
    private const METAHEADER_BUCHUNGSSTAPEL = '"EXTF";700;21;"Buchungsstapel";13;20240130140440439;;"RE";"";"";29098;55003;20240101;4;20240101;20240831;"Buchungsstapel";"WD";1;0;0;"EUR";;"";;;"03";;;"";""';
    private const METAHEADER_DEBITOREN_KREDITOR = '"EXTF";700;16;"Debitoren/Kreditoren";5;20240130140659583;;"RE";"";"";29098;55003;20240101;4;;;"";"";;;;"";;"";;;"03";;;"";""';
    private const METAHEADER_DIVERSE_ADRESSEN = '"EXTF";700;48;"Diverse Adressen";2;20240118132115652;;"RE";"Admin";"";29098;55003;20240101;4;;;"";"";;;;"";;"AW";;"";03;;"";"";';
    private const METAHEADER_NATURALSTAPEL = '"EXTF";700;66;"Natural-Stapel";2;20210511085305999;;"NA";"t03995a";"";29098;55314;20210101;7;;;"";"";;;0;"";;"KP";;;"14";1401;;"";""';
    private const METAHEADER_SACHKONTENBESCHRIFTUNGEN = '"EXTF";700;20;"Kontenbeschriftungen";3;20240729103107277;;"RE";"Admin";"";29098;55003;20240101;4;;;"";"";;;;"";;"";;"";;;"";"";';
    private const METAHEADER_WIEDERKEHRENDE_BUCHUNGEN = '"EXTF";700;65;"Wiederkehrende Buchungen";4;20240118094256087;;"WK";"Admin";"";29098;55003;20240101;4;;;"";"";;;;"";;"KP";"";;3;"";"";;';
    private const METAHEADER_ZAHLUNGSBEDINGUNGEN = '"EXTF";700;46;"Zahlungsbedingungen";2;20240118092951894;;"RE";"";"";29098;55003;20240101;4;;;"";"";;;;"";;"";;;"04";;;"";""';

    public function testParseAndRebuildForBookingBatch(): void {
        $this->assertHeaderRoundtrip(
            self::METAHEADER_BUCHUNGSSTAPEL,
            'BookingBatch-Header muss identisch zurückgegeben werden'
        );
    }

    public function testParseAndRebuildForDebitorenKreditoren(): void {
        $this->assertHeaderRoundtrip(
            self::METAHEADER_DEBITOREN_KREDITOR,
            'Debitoren/Kreditoren-Header muss identisch zurückgegeben werden'
        );
    }

    public function testParseAndRebuildForDiverseAdressen(): void {
        $this->assertHeaderRoundtrip(
            self::METAHEADER_DIVERSE_ADRESSEN,
            'Diverse-Adressen-Header muss identisch zurückgegeben werden'
        );
    }

    public function testParseAndRebuildForNaturalStapel(): void {
        $this->assertHeaderRoundtrip(
            self::METAHEADER_NATURALSTAPEL,
            'Natural-Stapel-Header muss identisch zurückgegeben werden'
        );
    }

    public function testParseAndRebuildForSachkontenbeschriftungen(): void {
        $this->assertHeaderRoundtrip(
            self::METAHEADER_SACHKONTENBESCHRIFTUNGEN,
            'Kontenbeschriftungen-Header muss identisch zurückgegeben werden'
        );
    }

    public function testParseAndRebuildForWiederkehrendeBuchungen(): void {
        $this->assertHeaderRoundtrip(
            self::METAHEADER_WIEDERKEHRENDE_BUCHUNGEN,
            'Wiederkehrende-Buchungen-Header muss identisch zurückgegeben werden'
        );
    }

    public function testParseAndRebuildForZahlungsbedingungen(): void {
        $this->assertHeaderRoundtrip(
            self::METAHEADER_ZAHLUNGSBEDINGUNGEN,
            'Zahlungsbedingungen-Header muss identisch zurückgegeben werden'
        );
    }

    public function testVersionDetection(): void {
        $line   = trim(self::METAHEADER_BUCHUNGSSTAPEL);
        $values = explode(';', $line);

        $detected = HeaderRegistry::detectFromValues($values);

        $this->assertSame(700, $detected->getVersion(), 'Versionserkennung aus Headerwerten muss funktionieren');
    }

    /**
     * Hilfsfunktion: Parst einen Header, baut ihn wieder zusammen
     * und vergleicht den normalisierten String (ohne überflüssige
     * abschließende Semikolons).
     */
    private function assertHeaderRoundtrip(string $header, string $message): void {
        $line = trim($header);

        // Nutze die bestehende CSV-Parser-Logik, die bereits Quote-Information erhält
        $dataLine = DataLine::fromString($line, ';', '"');
        $fields = $dataLine->getFields();

        $definition = HeaderRegistry::get(700);
        $meta = new MetaHeaderLine($definition);

        foreach (MetaHeaderField::ordered() as $i => $field) {
            $csvField = $fields[$i] ?? null;
            if ($csvField) {
                // Nutze die Quote-Information aus dem geparsten CSV-Feld
                $meta->setWithQuoteInfo($field, $csvField->getValue(), $csvField->isQuoted());
            } else {
                $meta->setWithQuoteInfo($field, '', false);
            }
        }

        $rebuilt = $meta->toString();
        $this->assertSame($line, $rebuilt, $message);
    }
}
