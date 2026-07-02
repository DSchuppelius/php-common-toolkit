<?php
/*
 * Created on   : Thu Jul 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HashAlgorithmTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Enums;

use CommonToolkit\Enums\HashAlgorithm;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für das HashAlgorithm-Enum: jeder Case muss ein gültiger
 * PHP-hash()-Bezeichner sein, und die Kryptographie-Einstufung muss stimmen.
 */
class HashAlgorithmTest extends BaseTestCase {
    public function test_every_case_is_a_valid_php_hash_algorithm(): void {
        $available = hash_algos();
        foreach (HashAlgorithm::cases() as $case) {
            $this->assertContains($case->value, $available, "hash_algos() kennt '{$case->value}' nicht");
        }
    }

    public function test_cryptographic_classification(): void {
        $nonCryptographic = [
            HashAlgorithm::MD5,
            HashAlgorithm::SHA1,
            HashAlgorithm::CRC32B,
            HashAlgorithm::XXH64,
            HashAlgorithm::XXH128,
            HashAlgorithm::XXH3,
        ];

        foreach (HashAlgorithm::cases() as $case) {
            $expected = !in_array($case, $nonCryptographic, true);
            $this->assertSame($expected, $case->isCryptographic(), "isCryptographic() für {$case->name}");
        }
    }

    public function test_cases_hash_like_native_hash_function(): void {
        foreach (HashAlgorithm::cases() as $case) {
            $this->assertSame(hash($case->value, 'workdiary'), hash($case->value, 'workdiary'));
        }
    }
}
