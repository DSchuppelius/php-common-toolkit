<?php
/*
 * Created on   : Wed Jul 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EncodeTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper\CSV;

use CommonToolkit\Helper\Data\CSV\StringHelper;
use Tests\Contracts\BaseTestCase;

class EncodeTest extends BaseTestCase {
    public function test_encode_field_leaves_plain_values_unquoted(): void {
        $this->assertSame('hello', StringHelper::encodeField('hello', ';', '"'));
        $this->assertSame('123', StringHelper::encodeField('123', ';', '"'));
        $this->assertSame('', StringHelper::encodeField('', ';', '"'));
    }

    public function test_encode_field_quotes_when_delimiter_enclosure_or_newline_present(): void {
        $this->assertSame('"a;b"', StringHelper::encodeField('a;b', ';', '"'));
        $this->assertSame('"a,b"', StringHelper::encodeField('a,b', ',', '"'));
        $this->assertSame("\"a\nb\"", StringHelper::encodeField("a\nb", ';', '"'));
        $this->assertSame("\"a\rb\"", StringHelper::encodeField("a\rb", ';', '"'));
    }

    public function test_encode_field_doubles_embedded_enclosure(): void {
        // he"llo -> "he""llo"
        $this->assertSame('"he""llo"', StringHelper::encodeField('he"llo', ';', '"'));
        // pure quote
        $this->assertSame('""""', StringHelper::encodeField('"', ';', '"'));
    }

    public function test_encode_field_force_enclosure_always_quotes(): void {
        $this->assertSame('"plain"', StringHelper::encodeField('plain', ';', '"', true));
        $this->assertSame('""', StringHelper::encodeField('', ';', '"', true));
    }

    public function test_encode_field_without_enclosure_returns_raw(): void {
        $this->assertSame('a;b', StringHelper::encodeField('a;b', ';', ''));
    }

    public function test_encode_line_joins_and_quotes_conditionally(): void {
        $this->assertSame(
            'Kunde;"Meyer; Sohn & Co";1234',
            StringHelper::encodeLine(['Kunde', 'Meyer; Sohn & Co', '1234'], ';', '"')
        );
    }

    public function test_encode_line_casts_null_and_scalars(): void {
        $this->assertSame(
            'a;;3;1',
            StringHelper::encodeLine(['a', null, 3, true], ';', '"')
        );
    }

    public function test_encode_line_force_enclosure_quotes_every_field(): void {
        $this->assertSame(
            '"a";"b";""',
            StringHelper::encodeLine(['a', 'b', null], ';', '"', true)
        );
    }

    public function test_encoded_line_splits_back_into_correct_field_count(): void {
        // Eingebettete Trennzeichen müssen so gequotet sein, dass der Splitter
        // wieder genau 3 Felder erkennt (nicht 4).
        $line = StringHelper::encodeLine(['a;b', 'c', 'd'], ';', '"');
        $this->assertSame('"a;b";c;d', $line);

        $fields = StringHelper::parseLineToFields($line, ';', '"');
        $this->assertCount(3, $fields);
    }
}
