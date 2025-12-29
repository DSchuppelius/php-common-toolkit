<?php

namespace Tests\Helper;

use CommonToolkit\Helper\Data\CryptoHelper;
use Tests\Contracts\BaseTestCase;

class CryptoHelperTest extends BaseTestCase {
    private string $testData = 'This is a test message for encryption';
    private string $testKey;
    private string $testSalt;

    protected function setUp(): void {
        parent::setUp();
        $this->testKey = CryptoHelper::generateKey(32, false);
        $this->testSalt = random_bytes(32);
    }

    public function testGenerateKey(): void {
        $key = CryptoHelper::generateKey();

        $this->assertEquals(32, strlen($key)); // Default 32 bytes binary

        $base64Key = CryptoHelper::generateKey(32, true);
        $this->assertGreaterThan(40, strlen($base64Key)); // Base64 encoded is longer

        $shortKey = CryptoHelper::generateKey(16);
        $this->assertEquals(16, strlen($shortKey));
    }

    public function testGenerateKeyInvalidLength(): void {
        $this->expectException(\InvalidArgumentException::class);
        CryptoHelper::generateKey(15); // Too short

        $this->expectException(\InvalidArgumentException::class);
        CryptoHelper::generateKey(300); // Too long
    }

    public function testEncryptDecrypt(): void {
        $encrypted = CryptoHelper::encrypt($this->testData, $this->testKey);

        $this->assertIsArray($encrypted);
        $this->assertArrayHasKey('ciphertext', $encrypted);
        $this->assertArrayHasKey('iv', $encrypted);
        $this->assertArrayHasKey('tag', $encrypted);
        $this->assertArrayHasKey('algorithm', $encrypted);
        $this->assertEquals('aes-256-gcm', $encrypted['algorithm']);
        $this->assertNotEmpty($encrypted['ciphertext']);
        $this->assertNotEmpty($encrypted['iv']);
        $this->assertNotEmpty($encrypted['tag']);

        $decrypted = CryptoHelper::decrypt($encrypted, $this->testKey);
        $this->assertEquals($this->testData, $decrypted);
    }

    public function testEncryptEmptyData(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Plaintext darf nicht leer sein');

        CryptoHelper::encrypt('', $this->testKey);
    }

    public function testEncryptInvalidCipher(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nicht unterstützter Verschlüsselungsalgorithmus');

        CryptoHelper::encrypt($this->testData, $this->testKey, 'invalid-cipher');
    }

    public function testEncryptInvalidKeyLength(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Key muss 32 Bytes lang sein');

        $shortKey = 'too_short_key';
        CryptoHelper::encrypt($this->testData, $shortKey);
    }

    public function testDecryptMissingKey(): void {
        $encryptedData = [
            'ciphertext' => 'some_data',
            'iv' => 'some_iv',
            // Missing 'algorithm' key
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fehlender Key in verschlüsselten Daten: algorithm');

        CryptoHelper::decrypt($encryptedData, $this->testKey);
    }

    public function testDecryptInvalidBase64(): void {
        $encryptedData = [
            'ciphertext' => 'invalid_base64!@#',
            'iv' => 'valid_base64',
            'tag' => 'valid_base64',
            'algorithm' => 'aes-256-gcm'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültige Base64-Kodierung');

        CryptoHelper::decrypt($encryptedData, $this->testKey);
    }

    public function testEncryptDecryptWithCBC(): void {
        $encrypted = CryptoHelper::encrypt($this->testData, $this->testKey, 'aes-256-cbc');

        $this->assertEquals('aes-256-cbc', $encrypted['algorithm']);
        $this->assertEmpty($encrypted['tag']); // CBC mode has no tag

        $decrypted = CryptoHelper::decrypt($encrypted, $this->testKey);
        $this->assertEquals($this->testData, $decrypted);
    }

    public function testDeriveKey(): void {
        $password = 'test_password';
        $derivedKey = CryptoHelper::deriveKey($password, base64_encode($this->testSalt));

        $this->assertNotEmpty($derivedKey);
        $this->assertEquals(44, strlen($derivedKey)); // Base64 encoded 32 bytes

        // Same inputs should produce same key
        $derivedKey2 = CryptoHelper::deriveKey($password, base64_encode($this->testSalt));
        $this->assertEquals($derivedKey, $derivedKey2);

        // Different password should produce different key
        $derivedKey3 = CryptoHelper::deriveKey('different_password', base64_encode($this->testSalt));
        $this->assertNotEquals($derivedKey, $derivedKey3);
    }

    public function testDeriveKeyInvalidParameters(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password darf nicht leer sein');
        CryptoHelper::deriveKey('', base64_encode($this->testSalt));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Salt muss mindestens 16 Bytes lang sein');
        CryptoHelper::deriveKey('password', 'short_salt');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mindestens 10000 Iterationen erforderlich');
        CryptoHelper::deriveKey('password', base64_encode($this->testSalt), 5000);
    }

    public function testSecureHashAndVerify(): void {
        $hashData = CryptoHelper::secureHash($this->testData);

        $this->assertIsArray($hashData);
        $this->assertArrayHasKey('hash', $hashData);
        $this->assertArrayHasKey('salt', $hashData);
        $this->assertArrayHasKey('algorithm', $hashData);
        $this->assertEquals('sha256', $hashData['algorithm']);

        $this->assertTrue(CryptoHelper::verifyHash($this->testData, $hashData));
        $this->assertFalse(CryptoHelper::verifyHash('different_data', $hashData));

        // Test with custom salt
        $customSalt = base64_encode($this->testSalt);
        $hashData2 = CryptoHelper::secureHash($this->testData, $customSalt);
        $this->assertEquals($customSalt, $hashData2['salt']);
    }

    public function testSecureHashEmptyData(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Zu hashende Daten dürfen nicht leer sein');

        CryptoHelper::secureHash('');
    }

    public function testSecureHashInvalidAlgorithm(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nicht unterstützter Hash-Algorithmus');

        CryptoHelper::secureHash($this->testData, '', 'md5');
    }

    public function testVerifyHashMissingKeys(): void {
        $invalidHashData = ['hash' => 'some_hash']; // Missing salt and algorithm

        $this->assertFalse(CryptoHelper::verifyHash($this->testData, $invalidHashData));
    }

    public function testCreateAndVerifyHmac(): void {
        $key = 'secret_hmac_key';
        $hmac = CryptoHelper::createHmac($this->testData, $key);

        $this->assertNotEmpty($hmac);
        $this->assertGreaterThan(40, strlen($hmac)); // Base64 encoded SHA256

        $this->assertTrue(CryptoHelper::verifyHmac($this->testData, $hmac, $key));
        $this->assertFalse(CryptoHelper::verifyHmac('different_data', $hmac, $key));
        $this->assertFalse(CryptoHelper::verifyHmac($this->testData, $hmac, 'wrong_key'));
    }

    public function testCreateHmacInvalidParameters(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Zu signierende Daten dürfen nicht leer sein');
        CryptoHelper::createHmac('', 'key');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('HMAC-Schlüssel darf nicht leer sein');
        CryptoHelper::createHmac($this->testData, '');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nicht unterstützter Hash-Algorithmus');
        CryptoHelper::createHmac($this->testData, 'key', 'md5');
    }

    public function testGenerateRsaKeyPair(): void {
        $keyPair = CryptoHelper::generateRsaKeyPair(2048);

        $this->assertIsArray($keyPair);
        $this->assertArrayHasKey('private_key', $keyPair);
        $this->assertArrayHasKey('public_key', $keyPair);
        $this->assertStringContainsString('BEGIN PRIVATE KEY', $keyPair['private_key']);
        $this->assertStringContainsString('BEGIN PUBLIC KEY', $keyPair['public_key']);
    }

    public function testGenerateRsaKeyPairInvalidSize(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('RSA-Schlüsselgröße muss 2048, 3072 oder 4096 Bits sein');

        CryptoHelper::generateRsaKeyPair(1024);
    }

    public function testRsaEncryptDecrypt(): void {
        $keyPair = CryptoHelper::generateRsaKeyPair(2048);
        $shortData = 'Short message for RSA'; // RSA can't encrypt large data

        $encrypted = CryptoHelper::rsaEncrypt($shortData, $keyPair['public_key']);
        $this->assertNotEmpty($encrypted);

        $decrypted = CryptoHelper::rsaDecrypt($encrypted, $keyPair['private_key']);
        $this->assertEquals($shortData, $decrypted);
    }

    public function testRsaEncryptEmptyData(): void {
        $keyPair = CryptoHelper::generateRsaKeyPair(2048);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Zu verschlüsselnde Daten dürfen nicht leer sein');

        CryptoHelper::rsaEncrypt('', $keyPair['public_key']);
    }

    public function testRsaDecryptInvalidData(): void {
        $keyPair = CryptoHelper::generateRsaKeyPair(2048);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültige Base64-kodierte Daten');

        CryptoHelper::rsaDecrypt('invalid_base64!@#', $keyPair['private_key']);
    }

    public function testRsaSignVerify(): void {
        $keyPair = CryptoHelper::generateRsaKeyPair(2048);

        $signature = CryptoHelper::rsaSign($this->testData, $keyPair['private_key']);
        $this->assertNotEmpty($signature);

        $this->assertTrue(CryptoHelper::rsaVerify($this->testData, $signature, $keyPair['public_key']));
        $this->assertFalse(CryptoHelper::rsaVerify('different_data', $signature, $keyPair['public_key']));
    }

    public function testRsaSignEmptyData(): void {
        $keyPair = CryptoHelper::generateRsaKeyPair(2048);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Zu signierende Daten dürfen nicht leer sein');

        CryptoHelper::rsaSign('', $keyPair['private_key']);
    }

    public function testRsaVerifyInvalidSignature(): void {
        $keyPair = CryptoHelper::generateRsaKeyPair(2048);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültige Base64-kodierte Signatur');

        CryptoHelper::rsaVerify($this->testData, 'invalid_signature!@#', $keyPair['public_key']);
    }

    public function testBase64UrlEncoding(): void {
        $data = 'Hello World with special chars: +/=';

        $encoded = CryptoHelper::base64UrlEncode($data);
        $this->assertNotEmpty($encoded);
        $this->assertStringNotContainsString('+', $encoded);
        $this->assertStringNotContainsString('/', $encoded);
        $this->assertStringNotContainsString('=', $encoded);

        $decoded = CryptoHelper::base64UrlDecode($encoded);
        $this->assertEquals($data, $decoded);
    }

    public function testBase64UrlDecodeInvalid(): void {
        $result = CryptoHelper::base64UrlDecode('invalid_base64!@#');
        $this->assertFalse($result);
    }

    public function testBinHexConversion(): void {
        $binaryData = random_bytes(32);

        $hex = CryptoHelper::binToHex($binaryData);
        $this->assertEquals(64, strlen($hex)); // 32 bytes = 64 hex chars
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $hex);

        $convertedBack = CryptoHelper::hexToBin($hex);
        $this->assertEquals($binaryData, $convertedBack);
    }

    public function testHexToBinInvalid(): void {
        $result = CryptoHelper::hexToBin('invalid_hex_zz');
        $this->assertFalse($result);
    }

    public function testSecureRandomInt(): void {
        $random = CryptoHelper::secureRandomInt(1, 100);
        $this->assertGreaterThanOrEqual(1, $random);
        $this->assertLessThanOrEqual(100, $random);

        $random2 = CryptoHelper::secureRandomInt(1, 100);
        $this->assertIsInt($random2);

        // Very unlikely to be the same (but theoretically possible)
        // Just test that we get integers in range
        for ($i = 0; $i < 10; $i++) {
            $r = CryptoHelper::secureRandomInt(50, 60);
            $this->assertGreaterThanOrEqual(50, $r);
            $this->assertLessThanOrEqual(60, $r);
        }
    }

    public function testSecureRandomIntInvalidRange(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Min-Wert muss kleiner als Max-Wert sein');

        CryptoHelper::secureRandomInt(100, 50);
    }

    public function testGetOpenSslStatus(): void {
        $status = CryptoHelper::getOpenSslStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('available', $status);
        $this->assertArrayHasKey('version', $status);
        $this->assertArrayHasKey('ciphers', $status);
        $this->assertArrayHasKey('hashes', $status);

        $this->assertIsBool($status['available']);
        $this->assertIsArray($status['ciphers']);
        $this->assertIsArray($status['hashes']);

        if ($status['available']) {
            $this->assertNotEmpty($status['version']);
            // Should have at least some supported ciphers and hashes
            $this->assertGreaterThan(0, count($status['ciphers']));
            $this->assertGreaterThan(0, count($status['hashes']));
        }
    }

    public function testDifferentHashAlgorithms(): void {
        $algorithms = ['sha256', 'sha384', 'sha512'];

        foreach ($algorithms as $algo) {
            $hashData = CryptoHelper::secureHash($this->testData, '', $algo);
            $this->assertEquals($algo, $hashData['algorithm']);
            $this->assertTrue(CryptoHelper::verifyHash($this->testData, $hashData));

            $hmac = CryptoHelper::createHmac($this->testData, 'key', $algo);
            $this->assertTrue(CryptoHelper::verifyHmac($this->testData, $hmac, 'key', $algo));
        }
    }
}
