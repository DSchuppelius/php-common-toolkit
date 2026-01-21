<?php
/*
 * Created on   : Sun Jan 19 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : IPHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\IPHelper;
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

class IPHelperTest extends BaseTestCase {

    // ===== IPv4 Validierung =====

    public function testIsIPv4WithValidAddresses(): void {
        $this->assertTrue(IPHelper::isIPv4('192.168.1.1'));
        $this->assertTrue(IPHelper::isIPv4('10.0.0.1'));
        $this->assertTrue(IPHelper::isIPv4('172.16.0.1'));
        $this->assertTrue(IPHelper::isIPv4('8.8.8.8'));
        $this->assertTrue(IPHelper::isIPv4('0.0.0.0'));
        $this->assertTrue(IPHelper::isIPv4('255.255.255.255'));
    }

    public function testIsIPv4WithInvalidAddresses(): void {
        $this->assertFalse(IPHelper::isIPv4('256.0.0.1'));
        $this->assertFalse(IPHelper::isIPv4('192.168.1'));
        $this->assertFalse(IPHelper::isIPv4('192.168.1.1.1'));
        $this->assertFalse(IPHelper::isIPv4('::1'));
        $this->assertFalse(IPHelper::isIPv4(''));
        $this->assertFalse(IPHelper::isIPv4(null));
        $this->assertFalse(IPHelper::isIPv4('abc.def.ghi.jkl'));
    }

    // ===== IPv6 Validierung =====

    public function testIsIPv6WithValidAddresses(): void {
        $this->assertTrue(IPHelper::isIPv6('::1'));
        $this->assertTrue(IPHelper::isIPv6('::'));
        $this->assertTrue(IPHelper::isIPv6('2001:db8::1'));
        $this->assertTrue(IPHelper::isIPv6('fe80::1'));
        $this->assertTrue(IPHelper::isIPv6('2001:0db8:0000:0000:0000:0000:0000:0001'));
        $this->assertTrue(IPHelper::isIPv6('::ffff:192.168.1.1')); // IPv4-mapped
    }

    public function testIsIPv6WithInvalidAddresses(): void {
        $this->assertFalse(IPHelper::isIPv6('192.168.1.1'));
        $this->assertFalse(IPHelper::isIPv6(''));
        $this->assertFalse(IPHelper::isIPv6(null));
        $this->assertFalse(IPHelper::isIPv6('gggg::1'));
        $this->assertFalse(IPHelper::isIPv6('2001:db8:::1'));
    }

    // ===== isValidIP =====

    public function testIsValidIP(): void {
        $this->assertTrue(IPHelper::isValidIP('192.168.1.1'));
        $this->assertTrue(IPHelper::isValidIP('::1'));
        $this->assertFalse(IPHelper::isValidIP('invalid'));
        $this->assertFalse(IPHelper::isValidIP(null));
    }

    // ===== Private IP =====

    public function testIsPrivateIP(): void {
        // Private IPv4
        $this->assertTrue(IPHelper::isPrivateIP('10.0.0.1'));
        $this->assertTrue(IPHelper::isPrivateIP('10.255.255.255'));
        $this->assertTrue(IPHelper::isPrivateIP('172.16.0.1'));
        $this->assertTrue(IPHelper::isPrivateIP('172.31.255.255'));
        $this->assertTrue(IPHelper::isPrivateIP('192.168.0.1'));
        $this->assertTrue(IPHelper::isPrivateIP('192.168.255.255'));

        // Öffentliche IPv4
        $this->assertFalse(IPHelper::isPrivateIP('8.8.8.8'));
        $this->assertFalse(IPHelper::isPrivateIP('1.1.1.1'));

        // Private IPv6 (Unique Local)
        $this->assertTrue(IPHelper::isPrivateIP('fd00::1'));
        $this->assertTrue(IPHelper::isPrivateIP('fc00::1'));

        // Ungültig
        $this->assertFalse(IPHelper::isPrivateIP('invalid'));
    }

    // ===== Loopback =====

    public function testIsLoopback(): void {
        // IPv4
        $this->assertTrue(IPHelper::isLoopback('127.0.0.1'));
        $this->assertTrue(IPHelper::isLoopback('127.255.255.255'));
        $this->assertFalse(IPHelper::isLoopback('128.0.0.1'));

        // IPv6
        $this->assertTrue(IPHelper::isLoopback('::1'));
        $this->assertFalse(IPHelper::isLoopback('::2'));
        $this->assertFalse(IPHelper::isLoopback('2001:db8::1'));
    }

    // ===== Link-Local =====

    public function testIsLinkLocal(): void {
        // IPv4
        $this->assertTrue(IPHelper::isLinkLocal('169.254.0.1'));
        $this->assertTrue(IPHelper::isLinkLocal('169.254.255.255'));
        $this->assertFalse(IPHelper::isLinkLocal('169.253.0.1'));

        // IPv6
        $this->assertTrue(IPHelper::isLinkLocal('fe80::1'));
        $this->assertTrue(IPHelper::isLinkLocal('fe80::ffff:ffff:ffff:ffff'));
        $this->assertFalse(IPHelper::isLinkLocal('fe00::1'));
    }

    // ===== Multicast =====

    public function testIsMulticast(): void {
        // IPv4
        $this->assertTrue(IPHelper::isMulticast('224.0.0.1'));
        $this->assertTrue(IPHelper::isMulticast('239.255.255.255'));
        $this->assertFalse(IPHelper::isMulticast('223.255.255.255'));
        $this->assertFalse(IPHelper::isMulticast('240.0.0.1'));

        // IPv6
        $this->assertTrue(IPHelper::isMulticast('ff02::1'));
        $this->assertTrue(IPHelper::isMulticast('ff00::'));
        $this->assertFalse(IPHelper::isMulticast('fe80::1'));
    }

    // ===== Public IP =====

    public function testIsPublicIP(): void {
        $this->assertTrue(IPHelper::isPublicIP('8.8.8.8'));
        $this->assertTrue(IPHelper::isPublicIP('1.1.1.1'));
        $this->assertTrue(IPHelper::isPublicIP('93.184.216.34'));

        $this->assertFalse(IPHelper::isPublicIP('10.0.0.1'));
        $this->assertFalse(IPHelper::isPublicIP('192.168.1.1'));
        $this->assertFalse(IPHelper::isPublicIP('127.0.0.1'));
    }

    // ===== isInRange =====

    public function testIsInRangeIPv4(): void {
        $this->assertTrue(IPHelper::isInRange('192.168.1.1', '192.168.1.0/24'));
        $this->assertTrue(IPHelper::isInRange('192.168.1.255', '192.168.1.0/24'));
        $this->assertFalse(IPHelper::isInRange('192.168.2.1', '192.168.1.0/24'));

        $this->assertTrue(IPHelper::isInRange('10.0.0.1', '10.0.0.0/8'));
        $this->assertTrue(IPHelper::isInRange('10.255.255.255', '10.0.0.0/8'));
        $this->assertFalse(IPHelper::isInRange('11.0.0.1', '10.0.0.0/8'));

        // Einzelne IP
        $this->assertTrue(IPHelper::isInRange('192.168.1.1', '192.168.1.1'));
        $this->assertFalse(IPHelper::isInRange('192.168.1.2', '192.168.1.1'));
    }

    public function testIsInRangeIPv6(): void {
        $this->assertTrue(IPHelper::isInRange('2001:db8::1', '2001:db8::/32'));
        $this->assertTrue(IPHelper::isInRange('2001:db8:ffff:ffff:ffff:ffff:ffff:ffff', '2001:db8::/32'));
        $this->assertFalse(IPHelper::isInRange('2001:db9::1', '2001:db8::/32'));
    }

    public function testIsInRangeMixedVersions(): void {
        // IPv4 in IPv6-Range sollte false sein
        $this->assertFalse(IPHelper::isInRange('192.168.1.1', '2001:db8::/32'));
        $this->assertFalse(IPHelper::isInRange('::1', '192.168.1.0/24'));
    }

    // ===== ipToLong / longToIp =====

    public function testIpToLong(): void {
        $this->assertEquals(3232235777, IPHelper::ipToLong('192.168.1.1'));
        $this->assertEquals(0, IPHelper::ipToLong('0.0.0.0'));
        $this->assertEquals(4294967295, IPHelper::ipToLong('255.255.255.255'));
        $this->assertEquals(2130706433, IPHelper::ipToLong('127.0.0.1'));
    }

    public function testIpToLongInvalidThrows(): void {
        $this->expectException(InvalidArgumentException::class);
        IPHelper::ipToLong('::1');
    }

    public function testLongToIp(): void {
        $this->assertEquals('192.168.1.1', IPHelper::longToIp(3232235777));
        $this->assertEquals('0.0.0.0', IPHelper::longToIp(0));
        $this->assertEquals('255.255.255.255', IPHelper::longToIp(4294967295));
    }

    public function testLongToIpInvalidThrows(): void {
        $this->expectException(InvalidArgumentException::class);
        IPHelper::longToIp(-1);
    }

    // ===== expandIPv6 / compressIPv6 =====

    public function testExpandIPv6(): void {
        $this->assertEquals('0000:0000:0000:0000:0000:0000:0000:0001', IPHelper::expandIPv6('::1'));
        $this->assertEquals('0000:0000:0000:0000:0000:0000:0000:0000', IPHelper::expandIPv6('::'));
        $this->assertEquals('2001:0db8:0000:0000:0000:0000:0000:0001', IPHelper::expandIPv6('2001:db8::1'));
    }

    public function testExpandIPv6InvalidThrows(): void {
        $this->expectException(InvalidArgumentException::class);
        IPHelper::expandIPv6('192.168.1.1');
    }

    public function testCompressIPv6(): void {
        $this->assertEquals('::1', IPHelper::compressIPv6('0000:0000:0000:0000:0000:0000:0000:0001'));
        $this->assertEquals('::', IPHelper::compressIPv6('0000:0000:0000:0000:0000:0000:0000:0000'));
        $this->assertEquals('2001:db8::1', IPHelper::compressIPv6('2001:0db8:0000:0000:0000:0000:0000:0001'));
    }

    // ===== Network Address =====

    public function testGetNetworkAddressIPv4(): void {
        $this->assertEquals('192.168.1.0', IPHelper::getNetworkAddress('192.168.1.100', 24));
        $this->assertEquals('10.0.0.0', IPHelper::getNetworkAddress('10.255.255.255', 8));
        $this->assertEquals('172.16.0.0', IPHelper::getNetworkAddress('172.16.5.10', 16));
        $this->assertEquals('192.168.1.128', IPHelper::getNetworkAddress('192.168.1.200', 25));
    }

    public function testGetNetworkAddressIPv6(): void {
        $this->assertEquals('2001:db8::', IPHelper::getNetworkAddress('2001:db8::1', 32));
        $this->assertEquals('2001:db8:85a3::', IPHelper::getNetworkAddress('2001:db8:85a3::8a2e:370:7334', 48));
    }

    // ===== Broadcast Address =====

    public function testGetBroadcastAddress(): void {
        $this->assertEquals('192.168.1.255', IPHelper::getBroadcastAddress('192.168.1.100', 24));
        $this->assertEquals('10.255.255.255', IPHelper::getBroadcastAddress('10.0.0.1', 8));
        $this->assertEquals('172.16.255.255', IPHelper::getBroadcastAddress('172.16.5.10', 16));
    }

    public function testGetBroadcastAddressIPv6Throws(): void {
        $this->expectException(InvalidArgumentException::class);
        IPHelper::getBroadcastAddress('2001:db8::1', 64);
    }

    // ===== CIDR Range =====

    public function testGetCIDRRangeIPv4(): void {
        $range = IPHelper::getCIDRRange('192.168.1.0/24');

        $this->assertEquals('192.168.1.0', $range['start']);
        $this->assertEquals('192.168.1.255', $range['end']);
        $this->assertEquals('192.168.1.0', $range['network']);
        $this->assertEquals('192.168.1.255', $range['broadcast']);
        $this->assertEquals(24, $range['prefix']);
        $this->assertEquals('256', $range['count']);
    }

    public function testGetCIDRRangeIPv6(): void {
        $range = IPHelper::getCIDRRange('2001:db8::/32');

        $this->assertEquals('2001:db8::', $range['start']);
        $this->assertEquals('2001:db8:ffff:ffff:ffff:ffff:ffff:ffff', $range['end']);
        $this->assertEquals(32, $range['prefix']);
    }

    // ===== Mask Conversion =====

    public function testMaskToPrefix(): void {
        $this->assertEquals(24, IPHelper::maskToPrefix('255.255.255.0'));
        $this->assertEquals(16, IPHelper::maskToPrefix('255.255.0.0'));
        $this->assertEquals(8, IPHelper::maskToPrefix('255.0.0.0'));
        $this->assertEquals(32, IPHelper::maskToPrefix('255.255.255.255'));
        $this->assertEquals(0, IPHelper::maskToPrefix('0.0.0.0'));
        $this->assertEquals(25, IPHelper::maskToPrefix('255.255.255.128'));
    }

    public function testMaskToPrefixInvalidThrows(): void {
        $this->expectException(InvalidArgumentException::class);
        IPHelper::maskToPrefix('255.255.255.1'); // Nicht kontiguierlich
    }

    public function testPrefixToMask(): void {
        $this->assertEquals('255.255.255.0', IPHelper::prefixToMask(24));
        $this->assertEquals('255.255.0.0', IPHelper::prefixToMask(16));
        $this->assertEquals('255.0.0.0', IPHelper::prefixToMask(8));
        $this->assertEquals('255.255.255.255', IPHelper::prefixToMask(32));
        $this->assertEquals('0.0.0.0', IPHelper::prefixToMask(0));
    }

    public function testPrefixToMaskInvalidThrows(): void {
        $this->expectException(InvalidArgumentException::class);
        IPHelper::prefixToMask(33);
    }

    // ===== IP Version =====

    public function testGetIPVersion(): void {
        $this->assertEquals('IPv4', IPHelper::getIPVersion('192.168.1.1'));
        $this->assertEquals('IPv6', IPHelper::getIPVersion('::1'));
        $this->assertNull(IPHelper::getIPVersion('invalid'));
        $this->assertNull(IPHelper::getIPVersion(null));
    }

    // ===== Normalize =====

    public function testNormalize(): void {
        // IPv4 mit führenden Nullen
        $this->assertEquals('192.168.1.1', IPHelper::normalize('192.168.001.001'));

        // IPv6 komprimieren
        $this->assertEquals('::1', IPHelper::normalize('0000:0000:0000:0000:0000:0000:0000:0001'));
        $this->assertEquals('2001:db8::1', IPHelper::normalize('2001:0db8:0000:0000:0000:0000:0000:0001'));
    }

    // ===== Compare =====

    public function testCompare(): void {
        $this->assertEquals(0, IPHelper::compare('192.168.1.1', '192.168.1.1'));
        $this->assertEquals(-1, IPHelper::compare('192.168.1.1', '192.168.1.2'));
        $this->assertEquals(1, IPHelper::compare('192.168.1.2', '192.168.1.1'));

        // IPv6
        $this->assertEquals(0, IPHelper::compare('::1', '::1'));
        $this->assertEquals(-1, IPHelper::compare('::1', '::2'));
        $this->assertEquals(1, IPHelper::compare('::2', '::1'));

        // IPv4 vs IPv6 (IPv4 ist "kleiner" wegen kürzerer Packed-Länge)
        $this->assertEquals(-1, IPHelper::compare('192.168.1.1', '::1'));
    }

    public function testCompareInvalidThrows(): void {
        $this->expectException(InvalidArgumentException::class);
        IPHelper::compare('invalid', '192.168.1.1');
    }
}
