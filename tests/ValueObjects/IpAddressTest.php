<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : IpAddressTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\ValueObjects\IpAddress;
use InvalidArgumentException;
use JsonSerializable;
use ReflectionClass;
use Stringable;
use Tests\Contracts\BaseTestCase;

class IpAddressTest extends BaseTestCase {
    // ==================== Konstruktion / Normalisierung ====================

    public function test_of_ipv4(): void {
        $ip = IpAddress::of('192.168.2.77');
        $this->assertSame('192.168.2.77', $ip->getValue());
        $this->assertSame(4, $ip->getVersion());
        $this->assertTrue($ip->isIpv4());
        $this->assertFalse($ip->isIpv6());
    }

    public function test_of_rejects_ipv4_with_leading_zeros(): void {
        // Führende Nullen sind mehrdeutig (Oktal-Interpretation einiger
        // Parser) und werden bewusst abgelehnt statt still normalisiert.
        $this->expectException(InvalidArgumentException::class);
        IpAddress::of('192.168.001.001');
    }

    public function test_of_normalizes_ipv6_to_compressed_lowercase(): void {
        $ip = IpAddress::of('2001:0DB8:0000:0000:0000:0000:0000:0001');
        $this->assertSame('2001:db8::1', $ip->getValue());
        $this->assertSame(6, $ip->getVersion());
        $this->assertTrue($ip->isIpv6());
    }

    public function test_of_rejects_invalid_input(): void {
        $this->expectException(InvalidArgumentException::class);
        IpAddress::of('999.1.1.1');
    }

    public function test_of_rejects_garbage(): void {
        $this->expectException(InvalidArgumentException::class);
        IpAddress::of('keine IP');
    }

    public function test_of_rejects_empty(): void {
        $this->expectException(InvalidArgumentException::class);
        IpAddress::of('  ');
    }

    public function test_try_from(): void {
        $this->assertNull(IpAddress::tryFrom(null));
        $this->assertNull(IpAddress::tryFrom(''));
        $this->assertNull(IpAddress::tryFrom('999.1.1.1'));

        $ip = IpAddress::tryFrom('8.8.8.8');
        $this->assertNotNull($ip);
        $this->assertSame('8.8.8.8', $ip->getValue());
    }

    // ==================== Klassifizierung ====================

    public function test_private_and_public(): void {
        $this->assertTrue(IpAddress::of('192.168.2.1')->isPrivate());
        $this->assertFalse(IpAddress::of('192.168.2.1')->isPublic());
        $this->assertTrue(IpAddress::of('8.8.8.8')->isPublic());
        $this->assertFalse(IpAddress::of('8.8.8.8')->isPrivate());
    }

    public function test_loopback_link_local_multicast_reserved(): void {
        $this->assertTrue(IpAddress::of('127.0.0.1')->isLoopback());
        $this->assertTrue(IpAddress::of('::1')->isLoopback());
        $this->assertTrue(IpAddress::of('169.254.1.1')->isLinkLocal());
        $this->assertTrue(IpAddress::of('fe80::1')->isLinkLocal());
        $this->assertTrue(IpAddress::of('224.0.0.1')->isMulticast());
        $this->assertTrue(IpAddress::of('ff02::1')->isMulticast());
        $this->assertTrue(IpAddress::of('240.0.0.1')->isReserved());
        $this->assertFalse(IpAddress::of('8.8.8.8')->isReserved());
    }

    public function test_is_in_range(): void {
        $ip = IpAddress::of('192.168.2.77');
        $this->assertTrue($ip->isInRange('192.168.2.0/24'));
        $this->assertFalse($ip->isInRange('192.168.3.0/24'));
    }

    // ==================== Anonymisierung (DSGVO) ====================

    public function test_anonymized_ipv4_defaults_to_slash_24(): void {
        $anonymized = IpAddress::of('192.168.2.77')->anonymized();
        $this->assertSame('192.168.2.0', $anonymized->getValue());
    }

    public function test_anonymized_ipv6_defaults_to_slash_48(): void {
        $anonymized = IpAddress::of('2001:db8:abcd:1234::5678')->anonymized();
        $this->assertSame('2001:db8:abcd::', $anonymized->getValue());
    }

    public function test_anonymized_with_explicit_prefix(): void {
        $this->assertSame('192.168.0.0', IpAddress::of('192.168.2.77')->anonymized(16)->getValue());
    }

    public function test_anonymized_rejects_invalid_prefix(): void {
        $this->expectException(InvalidArgumentException::class);
        IpAddress::of('192.168.2.77')->anonymized(33);
    }

    public function test_anonymized_is_idempotent_and_immutable(): void {
        $ip = IpAddress::of('192.168.2.77');
        $anonymized = $ip->anonymized();

        $this->assertSame('192.168.2.77', $ip->getValue(), 'Ursprung bleibt unverändert.');
        $this->assertTrue($anonymized->anonymized()->equals($anonymized), 'Anonymisierung ist idempotent.');
    }

    // ==================== Gleichheit / Sensibilität ====================

    public function test_equals_across_ipv6_notations(): void {
        $this->assertTrue(IpAddress::of('::1')->equals(IpAddress::of('0:0:0:0:0:0:0:1')));
        $this->assertTrue(IpAddress::of('2001:DB8::1')->equals(IpAddress::of('2001:db8:0:0:0:0:0:1')));
        $this->assertFalse(IpAddress::of('192.168.2.1')->equals(IpAddress::of('192.168.2.2')));
    }

    public function test_no_string_or_json_interface(): void {
        $reflection = new ReflectionClass(IpAddress::class);
        $this->assertFalse($reflection->implementsInterface(Stringable::class), 'Sensibler Wert darf nicht implizit zum String werden.');
        $this->assertFalse($reflection->implementsInterface(JsonSerializable::class), 'Sensibler Wert darf nicht implizit ins JSON gelangen.');
        $this->assertFalse($reflection->hasMethod('__toString'));
    }
}
