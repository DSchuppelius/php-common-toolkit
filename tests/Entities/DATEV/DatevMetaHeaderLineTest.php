<?php
/*
 * Created on   : Wed Nov 12 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DatevMetaHeaderLineTest.php
 * License      : MIT License
 */

declare(strict_types=1);

namespace Tests\CommonToolkit\Entities\DATEV;

use CommonToolkit\Entities\Datev\DatevMetaHeaderLine;
use CommonToolkit\Registries\DatevHeaderRegistry;
use CommonToolkit\Enums\DATEV\V700\DatevMetaHeaderField;
use PHPUnit\Framework\TestCase;

class DatevMetaHeaderLineTest extends TestCase {
    private const METAHEADER_FIBU = <<<CSV
    "EXTF";700;21;"Buchungsstapel";13;20240130140440439;;"RE";"";"";29098;55003;20240101;4;20240101;20240831;"Buchungsstapel";"WD";1;0;0;"EUR";;"";;;"03";;;"";""
    CSV;

    private const METAHEADER_DEBITOREN = <<<CSV
    "EXTF";700;16;"Debitoren/Kreditoren";5;20240130140659583;;"RE";"";"";29098;55003;20240101;4;;;"";"";;;;"";;"";;;"03";;;"";""
    CSV;

    public function testParseAndRebuildForBuchungsstapel(): void {
        $values = str_getcsv(self::METAHEADER_FIBU, ';');
        $definition = DatevHeaderRegistry::get(700);
        $meta = new DatevMetaHeaderLine($definition);

        // setze Werte in definierter Reihenfolge
        foreach (DatevMetaHeaderField::ordered() as $i => $field) {
            $meta->set($field, $values[$i] ?? '');
        }

        $rebuilt = $meta->toString(';', '"');
        $this->assertSame(self::METAHEADER_FIBU, $rebuilt, 'Buchungsstapel-Header muss identisch zurückgegeben werden');
    }

    public function testParseAndRebuildForDebitorenKreditoren(): void {
        $values = str_getcsv(self::METAHEADER_DEBITOREN, ';');
        $definition = DatevHeaderRegistry::get(700);
        $meta = new DatevMetaHeaderLine($definition);

        foreach (DatevMetaHeaderField::ordered() as $i => $field) {
            $meta->set($field, $values[$i] ?? '');
        }

        $rebuilt = $meta->toString(';', '"');
        $this->assertSame(self::METAHEADER_DEBITOREN, $rebuilt, 'Debitoren/Kreditoren-Header muss identisch zurückgegeben werden');
    }

    public function testVersionDetection(): void {
        $values = str_getcsv(self::METAHEADER_FIBU, ';');
        $detected = DatevHeaderRegistry::detectFromValues($values);
        $this->assertSame(700, $detected->getVersion(), 'Versionserkennung aus Headerwerten muss funktionieren');
    }
}
