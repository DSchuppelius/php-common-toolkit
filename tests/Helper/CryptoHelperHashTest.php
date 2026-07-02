<?php
/*
 * Created on   : Thu Jul 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CryptoHelperHashTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Enums\HashAlgorithm;
use CommonToolkit\Helper\Data\CryptoHelper;
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für CryptoHelper::hash() (deterministisch, ungesalzen) und die
 * HashAlgorithm|string-Union-Parameter der bestehenden Krypto-APIs.
 */
class CryptoHelperHashTest extends BaseTestCase {
    public function test_null_returns_null(): void {
        $this->assertNull(CryptoHelper::hash(null));
    }

    public function test_empty_string_is_hashed_regularly(): void {
        $this->assertSame(hash('sha256', ''), CryptoHelper::hash(''));
    }

    public function test_hex_parity_with_native_hash(): void {
        $this->assertSame(hash('sha256', 'DE89370400440532013000'), CryptoHelper::hash('DE89370400440532013000'));
        $this->assertSame(hash('sha512', 'täst'), CryptoHelper::hash('täst', HashAlgorithm::SHA512));
        $this->assertSame(hash('xxh128', 'schnell'), CryptoHelper::hash('schnell', HashAlgorithm::XXH128));
        $this->assertSame(hash('crc32b', 'checksum'), CryptoHelper::hash('checksum', HashAlgorithm::CRC32B));
    }

    public function test_binary_output_length(): void {
        $binary = CryptoHelper::hash('daten', HashAlgorithm::SHA256, true);
        $this->assertNotNull($binary);
        $this->assertSame(32, strlen($binary));
    }

    public function test_deterministic(): void {
        $this->assertSame(CryptoHelper::hash('gleich'), CryptoHelper::hash('gleich'));
    }

    public function test_secure_hash_accepts_enum_like_string(): void {
        $salt = base64_encode('0123456789abcdef0123456789abcdef');
        $viaEnum = CryptoHelper::secureHash('geheim', $salt, HashAlgorithm::SHA256);
        $viaString = CryptoHelper::secureHash('geheim', $salt, 'sha256');

        $this->assertSame($viaString['hash'], $viaEnum['hash']);
        $this->assertSame('sha256', $viaEnum['algorithm']);
    }

    public function test_secure_hash_rejects_non_cryptographic_enum(): void {
        $this->expectException(InvalidArgumentException::class);
        CryptoHelper::secureHash('geheim', '', HashAlgorithm::MD5);
    }

    public function test_create_hmac_accepts_enum_like_string(): void {
        $this->assertSame(
            CryptoHelper::createHmac('payload', 'key', 'sha256'),
            CryptoHelper::createHmac('payload', 'key', HashAlgorithm::SHA256)
        );
    }

    public function test_verify_hmac_with_enum(): void {
        $signature = CryptoHelper::createHmac('payload', 'key', HashAlgorithm::SHA384);
        $this->assertTrue(CryptoHelper::verifyHmac('payload', $signature, 'key', HashAlgorithm::SHA384));
        $this->assertFalse(CryptoHelper::verifyHmac('anders', $signature, 'key', HashAlgorithm::SHA384));
    }

    public function test_derive_key_accepts_enum_like_string(): void {
        $salt = '0123456789abcdef';
        $this->assertSame(
            CryptoHelper::deriveKey('passwort', $salt, 10000, 32, 'sha256'),
            CryptoHelper::deriveKey('passwort', $salt, 10000, 32, HashAlgorithm::SHA256)
        );
    }
}
