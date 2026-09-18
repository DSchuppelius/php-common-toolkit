<?php
/*
 * Created on   : Fri Sep 18 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : JsonHelperMaskTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\JsonHelper;
use Tests\Contracts\BaseTestCase;

/**
 * Maskierung sensibler Felder: JsonHelper::maskSensitiveArray() und das darauf
 * aufbauende maskSensitiveData().
 */
class JsonHelperMaskTest extends BaseTestCase {
    public function test_masks_default_fields_recursively(): void {
        $data = [
            'user' => 'max',
            'password' => 'geheim',
            'card' => ['number' => '4111', 'CVV' => '123'],
            'items' => [['token' => 'abc'], ['pin' => 1234]],
        ];

        $this->assertSame([
            'user' => 'max',
            'password' => '***',
            'card' => ['number' => '4111', 'CVV' => '***'],
            'items' => [['token' => '***'], ['pin' => '***']],
        ], JsonHelper::maskSensitiveArray($data));
    }

    public function test_key_match_is_case_insensitive_but_exact(): void {
        $data = ['Password' => 'a', 'PASSWORD' => 'b', 'password_hint' => 'c', 'my_token' => 'd'];

        $this->assertSame(
            ['Password' => '***', 'PASSWORD' => '***', 'password_hint' => 'c', 'my_token' => 'd'],
            JsonHelper::maskSensitiveArray($data)
        );
    }

    public function test_custom_fields_are_case_insensitive_too(): void {
        $data = ['ownershipId' => 'o-1', 'apikey' => 'k', 'name' => 'Firma'];

        $this->assertSame(
            ['ownershipId' => '[redacted]', 'apikey' => '[redacted]', 'name' => 'Firma'],
            JsonHelper::maskSensitiveArray($data, ['OWNERSHIPID', 'apiKey'], '[redacted]')
        );
    }

    public function test_non_recursive_masks_top_level_only(): void {
        $data = ['password' => 'x', 'nested' => ['password' => 'y']];

        $this->assertSame(
            ['password' => '***', 'nested' => ['password' => 'y']],
            JsonHelper::maskSensitiveArray($data, recursive: false)
        );
    }

    public function test_array_and_null_values_are_masked_completely(): void {
        $data = ['token' => ['access' => 'a', 'refresh' => 'r'], 'pin' => null];

        $this->assertSame(['token' => '***', 'pin' => '***'], JsonHelper::maskSensitiveArray($data));
    }

    public function test_missing_keys_are_not_added_and_empty_input(): void {
        $this->assertSame([], JsonHelper::maskSensitiveArray([]));
        $this->assertSame(['a' => 1], JsonHelper::maskSensitiveArray(['a' => 1]));
        $this->assertSame(['a' => 1], JsonHelper::maskSensitiveArray(['a' => 1], []));
    }

    public function test_numeric_and_unicode_keys(): void {
        $data = [0 => 'x', 'KENNWÖRT' => 'geheim'];

        $this->assertSame([0 => 'x', 'KENNWÖRT' => '***'], JsonHelper::maskSensitiveArray($data, ['kennwört']));
    }

    public function test_input_array_is_not_modified(): void {
        $data = ['password' => 'geheim', 'nested' => ['token' => 't']];
        JsonHelper::maskSensitiveArray($data);

        $this->assertSame(['password' => 'geheim', 'nested' => ['token' => 't']], $data);
    }

    public function test_mask_sensitive_data_uses_array_masking(): void {
        $json = '{"user":"max","Password":"geheim","nested":{"token":"t","keep":1}}';
        $masked = JsonHelper::decode(JsonHelper::maskSensitiveData($json));

        $this->assertSame(['user' => 'max', 'Password' => '***', 'nested' => ['token' => '***', 'keep' => 1]], $masked);
    }

    public function test_mask_sensitive_data_keeps_scalars_and_invalid_json(): void {
        $this->assertSame('"password"', JsonHelper::maskSensitiveData('"password"'));
        $this->assertSame('{kaputt', JsonHelper::maskSensitiveData('{kaputt'));
    }
}
