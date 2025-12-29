<?php

namespace Tests\Helper;

use CommonToolkit\Helper\Data\SecurityHelper;
use Tests\Contracts\BaseTestCase;

class SecurityHelperTest extends BaseTestCase {
    private string $testPassword = 'SecureTestPassword123!';
    private string $testHash;
    private string $testSessionId = 'test_session_12345';

    protected function setUp(): void {
        parent::setUp();
        $this->testHash = SecurityHelper::hashPassword($this->testPassword);
    }

    public function testHashPassword(): void {
        $hash = SecurityHelper::hashPassword($this->testPassword);

        $this->assertNotEmpty($hash);
        $this->assertNotEquals($this->testPassword, $hash);
        $this->assertGreaterThan(50, strlen($hash)); // Argon2ID hashes sind lang
    }

    public function testHashPasswordWithShortPassword(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password muss mindestens 8 Zeichen lang sein');

        SecurityHelper::hashPassword('short');
    }

    public function testVerifyPassword(): void {
        $this->assertTrue(SecurityHelper::verifyPassword($this->testPassword, $this->testHash));
        $this->assertFalse(SecurityHelper::verifyPassword('wrong_password', $this->testHash));
        $this->assertFalse(SecurityHelper::verifyPassword('', $this->testHash));
        $this->assertFalse(SecurityHelper::verifyPassword($this->testPassword, ''));
    }

    public function testNeedsRehash(): void {
        $oldHash = password_hash($this->testPassword, PASSWORD_DEFAULT);
        $newHash = SecurityHelper::hashPassword($this->testPassword);

        // Old hash might need rehash depending on system defaults
        $this->assertIsBool(SecurityHelper::needsRehash($oldHash));
        // New Argon2ID hash should not need rehash
        $this->assertFalse(SecurityHelper::needsRehash($newHash));
    }

    public function testGenerateSecureToken(): void {
        $token = SecurityHelper::generateSecureToken();

        $this->assertNotEmpty($token);
        $this->assertGreaterThan(40, strlen($token)); // Base64 encoded 32 bytes

        // Test different length
        $shortToken = SecurityHelper::generateSecureToken(16);
        $this->assertNotEmpty($shortToken);
        $this->assertNotEquals($token, $shortToken);

        // Test binary output
        $binaryToken = SecurityHelper::generateSecureToken(32, false);
        $this->assertEquals(64, strlen($binaryToken)); // Hex encoded 32 bytes
    }

    public function testGenerateSecureTokenInvalidLength(): void {
        $this->expectException(\InvalidArgumentException::class);
        SecurityHelper::generateSecureToken(15); // Too short

        $this->expectException(\InvalidArgumentException::class);
        SecurityHelper::generateSecureToken(300); // Too long
    }

    public function testGenerateAndValidateCsrfToken(): void {
        $token = SecurityHelper::generateCsrfToken($this->testSessionId, 'login');

        $this->assertNotEmpty($token);

        // Validate immediately - should work
        $this->assertTrue(SecurityHelper::validateCsrfToken($token, $this->testSessionId, 'login'));

        // Wrong session ID
        $this->assertFalse(SecurityHelper::validateCsrfToken($token, 'wrong_session', 'login'));

        // Wrong action
        $this->assertFalse(SecurityHelper::validateCsrfToken($token, $this->testSessionId, 'logout'));

        // Invalid token format
        $this->assertFalse(SecurityHelper::validateCsrfToken('invalid_token', $this->testSessionId, 'login'));
    }

    public function testSanitizeInput(): void {
        $maliciousInput = '<script>alert("XSS")</script>Hello World';
        $sanitized = SecurityHelper::sanitizeInput($maliciousInput);

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('alert', $sanitized);
        $this->assertStringContainsString('Hello World', $sanitized);

        // Test with HTML allowed
        $htmlInput = '<b>Bold</b> and <script>evil</script>';
        $sanitizedHtml = SecurityHelper::sanitizeInput($htmlInput, true);

        $this->assertStringContainsString('&lt;b&gt;', $sanitizedHtml);
        $this->assertStringNotContainsString('<script>', $sanitizedHtml);
    }

    public function testSanitizeBankingData(): void {
        // Test IBAN
        $iban = 'de89 3704 0044 0532 0130 00';
        $sanitizedIban = SecurityHelper::sanitizeBankingData($iban, 'iban');
        $this->assertEquals('DE89370400440532013000', $sanitizedIban);

        // Test BIC
        $bic = 'cobadeff';
        $sanitizedBic = SecurityHelper::sanitizeBankingData($bic, 'bic');
        $this->assertEquals('COBADEFF', $sanitizedBic);

        // Test amount
        $amount = '1.234,56 €';
        $sanitizedAmount = SecurityHelper::sanitizeBankingData($amount, 'amount');
        $this->assertEquals('1.234.56', $sanitizedAmount);

        // Test name
        $name = 'Müller & Co. <script>';
        $sanitizedName = SecurityHelper::sanitizeBankingData($name, 'name');
        $this->assertEquals('Müller Co.', $sanitizedName);
    }

    public function testSanitizeBankingDataInvalidIban(): void {
        $this->expectException(\InvalidArgumentException::class);
        $longIban = str_repeat('A', 35); // Too long
        SecurityHelper::sanitizeBankingData($longIban, 'iban');
    }

    public function testSetSecurityHeaders(): void {
        $headers = SecurityHelper::setSecurityHeaders();

        $this->assertIsArray($headers);
        $this->assertArrayHasKey('X-Content-Type-Options', $headers);
        $this->assertArrayHasKey('X-Frame-Options', $headers);
        $this->assertArrayHasKey('Strict-Transport-Security', $headers);
        $this->assertEquals('nosniff', $headers['X-Content-Type-Options']);
        $this->assertEquals('DENY', $headers['X-Frame-Options']);

        // Test with custom headers
        $customHeaders = SecurityHelper::setSecurityHeaders(['Custom-Header' => 'CustomValue']);
        $this->assertArrayHasKey('Custom-Header', $customHeaders);
        $this->assertEquals('CustomValue', $customHeaders['Custom-Header']);
    }

    public function testCheckRateLimit(): void {
        $identifier = 'test_user_123';

        // First few attempts should succeed
        $this->assertTrue(SecurityHelper::checkRateLimit($identifier, 3, 60));
        $this->assertTrue(SecurityHelper::checkRateLimit($identifier, 3, 60));
        $this->assertTrue(SecurityHelper::checkRateLimit($identifier, 3, 60));

        // Fourth attempt should fail (exceeded limit)
        $this->assertFalse(SecurityHelper::checkRateLimit($identifier, 3, 60));

        // Different identifier should work
        $this->assertTrue(SecurityHelper::checkRateLimit('different_user', 3, 60));
    }

    public function testGenerateSecureSessionId(): void {
        $sessionId1 = SecurityHelper::generateSecureSessionId('Mozilla/5.0', '127.0.0.1');
        $sessionId2 = SecurityHelper::generateSecureSessionId('Chrome/91.0', '192.168.1.1');

        $this->assertNotEmpty($sessionId1);
        $this->assertNotEmpty($sessionId2);
        $this->assertNotEquals($sessionId1, $sessionId2);
        $this->assertEquals(64, strlen($sessionId1)); // SHA256 hex = 64 chars
    }

    public function testMaskSensitiveData(): void {
        // Test IBAN masking
        $iban = 'DE89370400440532013000';
        $maskedIban = SecurityHelper::maskSensitiveData($iban, 'iban');
        $this->assertStringStartsWith('DE89', $maskedIban);
        $this->assertStringEndsWith('3000', $maskedIban);
        $this->assertStringContainsString('*', $maskedIban);

        // Test credit card masking
        $cc = '1234567890123456';
        $maskedCc = SecurityHelper::maskSensitiveData($cc, 'creditcard');
        $this->assertStringStartsWith('1234', $maskedCc);
        $this->assertStringEndsWith('3456', $maskedCc);
        $this->assertStringContainsString('*', $maskedCc);

        // Test email masking
        $email = 'john.doe@example.com';
        $maskedEmail = SecurityHelper::maskSensitiveData($email, 'email');
        $this->assertStringStartsWith('jo', $maskedEmail);
        $this->assertStringEndsWith('.com', $maskedEmail);
        $this->assertStringContainsString('*', $maskedEmail);
        $this->assertStringContainsString('@', $maskedEmail);

        // Test phone masking
        $phone = '+49123456789';
        $maskedPhone = SecurityHelper::maskSensitiveData($phone, 'phone');
        $this->assertStringStartsWith('+49', $maskedPhone);
        $this->assertStringEndsWith('89', $maskedPhone);
        $this->assertStringContainsString('*', $maskedPhone);

        // Test generic masking
        $generic = 'sensitivedata';
        $maskedGeneric = SecurityHelper::maskSensitiveData($generic, 'generic', 3);
        $this->assertStringStartsWith('sen', $maskedGeneric);
        $this->assertStringEndsWith('ata', $maskedGeneric);
        $this->assertStringContainsString('*', $maskedGeneric);
    }

    public function testMaskSensitiveDataEmptyInput(): void {
        $result = SecurityHelper::maskSensitiveData('', 'iban');
        $this->assertEquals('', $result);
    }

    public function testMaskSensitiveDataShortInput(): void {
        $short = 'ABC';
        $masked = SecurityHelper::maskSensitiveData($short, 'iban');
        $this->assertEquals('***', $masked); // Fallback für kurze Strings
    }

    protected function tearDown(): void {
        // Clean up rate limit files
        $tempDir = sys_get_temp_dir();
        $files = glob($tempDir . DIRECTORY_SEPARATOR . 'rate_limit_*.tmp');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }
}
