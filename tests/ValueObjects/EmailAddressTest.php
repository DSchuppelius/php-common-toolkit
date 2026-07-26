<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EmailAddressTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\ValueObjects\EmailAddress;
use InvalidArgumentException;
use JsonSerializable;
use ReflectionClass;
use Stringable;
use Tests\Contracts\BaseTestCase;

class EmailAddressTest extends BaseTestCase {
    // ==================== Konstruktion / Normalisierung ====================

    public function test_of_normalizes_case_and_whitespace(): void {
        $email = EmailAddress::of('  Max.Mustermann@EXAMPLE.com ');
        $this->assertSame('max.mustermann@example.com', $email->getValue());
    }

    public function test_of_keeps_dots_in_local_part(): void {
        // Kein providerspezifisches Entfernen von Punkten (Gmail-Sonderlogik).
        $this->assertSame('max.mustermann@gmail.com', EmailAddress::of('Max.Mustermann@gmail.com')->getValue());
    }

    public function test_of_rejects_invalid_format(): void {
        $this->expectException(InvalidArgumentException::class);
        EmailAddress::of('keine-mail');
    }

    public function test_of_rejects_missing_tld(): void {
        $this->expectException(InvalidArgumentException::class);
        EmailAddress::of('max@localhost');
    }

    public function test_of_rejects_empty(): void {
        $this->expectException(InvalidArgumentException::class);
        EmailAddress::of('   ');
    }

    public function test_try_from(): void {
        $this->assertNull(EmailAddress::tryFrom(null));
        $this->assertNull(EmailAddress::tryFrom(''));
        $this->assertNull(EmailAddress::tryFrom('keine-mail'));

        $email = EmailAddress::tryFrom('Max@Example.com');
        $this->assertNotNull($email);
        $this->assertSame('max@example.com', $email->getValue());
    }

    // ==================== Strukturzugriff ====================

    public function test_local_part_and_domain(): void {
        $email = EmailAddress::of('Max.Mustermann@Example.COM');
        $this->assertSame('max.mustermann', $email->getLocalPart());
        $this->assertSame('example.com', $email->getDomain());
    }

    // ==================== Maskierung ====================

    public function test_masked(): void {
        $this->assertSame('ma**********nn@example.com', EmailAddress::of('max.mustermann@example.com')->masked());
        $this->assertSame('max********ann@example.com', EmailAddress::of('max.mustermann@example.com')->masked(3));
    }

    public function test_masked_short_local_part_stays_masked(): void {
        // Auch bei kurzen Local-Parts wird nie alles offengelegt.
        $this->assertSame('a*@example.com', EmailAddress::of('ab@example.com')->masked());
    }

    // ==================== Klassifizierung ====================

    public function test_is_disposable(): void {
        $this->assertTrue(EmailAddress::of('test@mailinator.com')->isDisposable());
        $this->assertFalse(EmailAddress::of('test@example.com')->isDisposable());
    }

    public function test_is_free_provider(): void {
        $this->assertTrue(EmailAddress::of('test@gmail.com')->isFreeProvider());
        $this->assertFalse(EmailAddress::of('test@example.com')->isFreeProvider());
    }

    // ==================== Gleichheit / Sensibilität ====================

    public function test_equals_ignores_case(): void {
        $this->assertTrue(EmailAddress::of('Max@Example.com')->equals(EmailAddress::of('max@example.com')));
        $this->assertFalse(EmailAddress::of('max@example.com')->equals(EmailAddress::of('moritz@example.com')));
    }

    public function test_no_string_or_json_interface(): void {
        $reflection = new ReflectionClass(EmailAddress::class);
        $this->assertFalse($reflection->implementsInterface(Stringable::class), 'Sensibler Wert darf nicht implizit zum String werden.');
        $this->assertFalse($reflection->implementsInterface(JsonSerializable::class), 'Sensibler Wert darf nicht implizit ins JSON gelangen.');
        $this->assertFalse($reflection->hasMethod('__toString'));
    }
}
