<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EmailHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Helper\Data\EmailHelper;
use Tests\Contracts\BaseTestCase;

class EmailHelperTest extends BaseTestCase {
    // ========================================
    // Format-Tests
    // ========================================

    public function test_is_email_with_valid_formats(): void {
        $this->assertTrue(EmailHelper::isEmail('user@example.com'));
        $this->assertTrue(EmailHelper::isEmail('user.name@example.com'));
        $this->assertTrue(EmailHelper::isEmail('user+tag@example.com'));
        $this->assertTrue(EmailHelper::isEmail('user@subdomain.example.com'));
        $this->assertTrue(EmailHelper::isEmail('USER@EXAMPLE.COM'));
    }

    public function test_is_email_with_invalid_formats(): void {
        $this->assertFalse(EmailHelper::isEmail(null));
        $this->assertFalse(EmailHelper::isEmail(''));
        $this->assertFalse(EmailHelper::isEmail('user'));
        $this->assertFalse(EmailHelper::isEmail('@example.com'));
        $this->assertFalse(EmailHelper::isEmail('user@'));
        $this->assertFalse(EmailHelper::isEmail('user@.com'));
    }

    // ========================================
    // Validierungs-Tests
    // ========================================

    public function test_validate_email_with_valid_addresses(): void {
        $this->assertTrue(EmailHelper::validateEmail('user@example.com'));
        $this->assertTrue(EmailHelper::validateEmail('test.user@example.org'));
    }

    public function test_validate_email_with_invalid_addresses(): void {
        $this->assertFalse(EmailHelper::validateEmail('.user@example.com')); // Beginnt mit Punkt
        $this->assertFalse(EmailHelper::validateEmail('user.@example.com')); // Endet mit Punkt
        $this->assertFalse(EmailHelper::validateEmail('user..name@example.com')); // Doppelte Punkte
    }

    // ========================================
    // Normalisierungs-Tests
    // ========================================

    public function test_normalize(): void {
        $this->assertEquals('user@example.com', EmailHelper::normalize('USER@EXAMPLE.COM'));
        $this->assertEquals('user@example.com', EmailHelper::normalize('  user@example.com  '));
    }

    public function test_normalize_with_dot_removal(): void {
        $this->assertEquals('username@gmail.com', EmailHelper::normalize('user.name@gmail.com', true));
    }

    // ========================================
    // Extraktions-Tests
    // ========================================

    public function test_extract_local_part(): void {
        $this->assertEquals('user', EmailHelper::extractLocalPart('user@example.com'));
        $this->assertEquals('user.name', EmailHelper::extractLocalPart('user.name@example.com'));
    }

    public function test_extract_local_part_with_invalid(): void {
        $this->assertNull(EmailHelper::extractLocalPart('invalid'));
    }

    public function test_extract_domain(): void {
        $this->assertEquals('example.com', EmailHelper::extractDomain('user@example.com'));
        $this->assertEquals('sub.example.com', EmailHelper::extractDomain('user@sub.example.com'));
    }

    public function test_extract_domain_with_invalid(): void {
        $this->assertNull(EmailHelper::extractDomain('invalid'));
    }

    // ========================================
    // Wegwerf-E-Mail Tests
    // ========================================

    public function test_is_disposable_email(): void {
        $this->assertTrue(EmailHelper::isDisposableEmail('test@mailinator.com'));
        $this->assertTrue(EmailHelper::isDisposableEmail('test@tempmail.com'));
        $this->assertFalse(EmailHelper::isDisposableEmail('test@gmail.com'));
        $this->assertFalse(EmailHelper::isDisposableEmail('test@example.com'));
    }

    // ========================================
    // Kostenlose Provider Tests
    // ========================================

    public function test_is_free_email_provider(): void {
        $this->assertTrue(EmailHelper::isFreeEmailProvider('test@gmail.com'));
        $this->assertTrue(EmailHelper::isFreeEmailProvider('test@web.de'));
        $this->assertTrue(EmailHelper::isFreeEmailProvider('test@gmx.de'));
        $this->assertTrue(EmailHelper::isFreeEmailProvider('test@t-online.de'));
        $this->assertFalse(EmailHelper::isFreeEmailProvider('test@company.com'));
    }

    // ========================================
    // Maskierungs-Tests
    // ========================================

    public function test_mask(): void {
        $this->assertEquals('jo*****th@example.com', EmailHelper::mask('johnsmith@example.com', 2));
        $this->assertEquals('joh***ith@example.com', EmailHelper::mask('johnsmith@example.com', 3));
    }

    public function test_mask_with_short_local_part(): void {
        // Bei kurzen lokalen Teilen wird nur der erste Buchstabe gezeigt
        $this->assertEquals('u***@example.com', EmailHelper::mask('user@example.com', 2));
        $this->assertEquals('a**@example.com', EmailHelper::mask('abc@example.com', 2));
    }

    // ========================================
    // Mailto-Link Tests
    // ========================================

    public function test_create_mailto_link(): void {
        $this->assertEquals('mailto:user%40example.com', EmailHelper::createMailtoLink('user@example.com'));
    }

    public function test_create_mailto_link_with_subject(): void {
        $link = EmailHelper::createMailtoLink('user@example.com', 'Hello World');
        $this->assertStringContainsString('subject=Hello%20World', $link);
    }

    public function test_create_mailto_link_with_body(): void {
        $link = EmailHelper::createMailtoLink('user@example.com', null, 'Test message');
        $this->assertStringContainsString('body=Test%20message', $link);
    }

    public function test_create_mailto_link_with_invalid_email(): void {
        $this->assertEquals('', EmailHelper::createMailtoLink('invalid'));
    }

    // ========================================
    // Vergleichs-Tests
    // ========================================

    public function test_equals(): void {
        $this->assertTrue(EmailHelper::equals('user@example.com', 'USER@EXAMPLE.COM'));
        $this->assertTrue(EmailHelper::equals('user@example.com', 'user@example.com'));
        $this->assertFalse(EmailHelper::equals('user1@example.com', 'user2@example.com'));
    }

    public function test_equals_with_gmail_normalization(): void {
        $this->assertTrue(EmailHelper::equals('user.name@gmail.com', 'username@gmail.com', true));
        $this->assertTrue(EmailHelper::equals('u.s.e.r@gmail.com', 'user@gmail.com', true));
    }

    // ========================================
    // Gmail-Tests
    // ========================================

    public function test_is_gmail_address(): void {
        $this->assertTrue(EmailHelper::isGmailAddress('user@gmail.com'));
        $this->assertTrue(EmailHelper::isGmailAddress('user@googlemail.com'));
        $this->assertFalse(EmailHelper::isGmailAddress('user@yahoo.com'));
        $this->assertFalse(EmailHelper::isGmailAddress('user@example.com'));
    }
}
