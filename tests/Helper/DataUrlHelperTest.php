<?php
/*
 * Created on   : Thu Sep 17 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DataUrlHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\DataUrlHelper;
use Tests\Contracts\BaseTestCase;

class DataUrlHelperTest extends BaseTestCase {
    /** 1×1-PNG, Base64. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    /** 1×1-GIF, Base64. */
    private const GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function test_decodes_png_data_url_with_allowed_type(): void {
        $bytes = DataUrlHelper::decode('data:image/png;base64,' . self::PNG, ['image/png']);

        $this->assertIsString($bytes);
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $bytes);
    }

    public function test_decodes_raw_base64_without_prefix(): void {
        $this->assertSame(base64_decode(self::PNG), DataUrlHelper::decode(self::PNG, ['image/png']));
    }

    public function test_prefix_and_base64_marker_are_case_insensitive(): void {
        $this->assertIsString(DataUrlHelper::decode('DATA:image/PNG;BASE64,' . self::PNG, ['image/png']));
    }

    public function test_rejects_content_of_a_type_that_is_not_allowed(): void {
        $this->assertFalse(DataUrlHelper::decode(self::GIF, ['image/png']));
    }

    public function test_rejects_declared_type_that_does_not_match_the_content(): void {
        $this->assertFalse(DataUrlHelper::decode('data:image/png;base64,' . self::GIF, ['image/png', 'image/gif']));
    }

    public function test_rejects_invalid_base64(): void {
        $this->assertFalse(DataUrlHelper::decode('data:image/png;base64,%%%nicht-base64%%%'));
    }

    public function test_rejects_empty_input(): void {
        $this->assertFalse(DataUrlHelper::decode('   '));
        $this->assertFalse(DataUrlHelper::decode('data:image/png;base64,'));
    }

    public function test_enforces_the_maximum_size(): void {
        $size = strlen((string) base64_decode(self::PNG));

        $this->assertIsString(DataUrlHelper::decode(self::PNG, ['image/png'], $size));
        $this->assertFalse(DataUrlHelper::decode(self::PNG, ['image/png'], $size - 1));
    }

    public function test_rejects_oversized_input_before_decoding(): void {
        $this->assertFalse(DataUrlHelper::decode(str_repeat('A', 4000), [], 100));
    }

    public function test_without_allowed_types_any_content_is_returned(): void {
        $this->assertSame(base64_decode(self::GIF), DataUrlHelper::decode(self::GIF));
    }

    public function test_decodes_percent_encoded_data_url(): void {
        $this->assertSame('Hello, World', DataUrlHelper::decode('data:,Hello%2C%20World'));
    }

    public function test_parse_returns_components(): void {
        $this->assertSame(
            ['mime_type' => 'image/png', 'base64' => true, 'data' => self::PNG],
            DataUrlHelper::parse('data:image/png;base64,' . self::PNG),
        );
        $this->assertSame(
            ['mime_type' => 'text/plain', 'base64' => false, 'data' => 'abc'],
            DataUrlHelper::parse('data:,abc'),
        );
        $this->assertSame('text/html', (DataUrlHelper::parse('data:text/html;charset=utf-8,<b>x</b>') ?: [])['mime_type'] ?? null);
    }

    public function test_parse_rejects_malformed_input(): void {
        $this->assertFalse(DataUrlHelper::parse('image/png;base64,' . self::PNG));
        $this->assertFalse(DataUrlHelper::parse('data:image/png;base64'));
        $this->assertFalse(DataUrlHelper::parse('data:not a type;base64,AAAA'));
    }

    public function test_encode_round_trip(): void {
        $bytes = (string) base64_decode(self::PNG);
        $dataUrl = DataUrlHelper::encode($bytes);

        $this->assertSame('data:image/png;base64,' . self::PNG, $dataUrl);
        $this->assertSame($bytes, DataUrlHelper::decode((string) $dataUrl, ['image/png']));
        $this->assertSame('data:text/plain;base64,YQ==', DataUrlHelper::encode('a', 'TEXT/PLAIN'));
        $this->assertFalse(DataUrlHelper::encode(''));
    }
}
