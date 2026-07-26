<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ByteSizeTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\ValueObjects\ByteSize;
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

class ByteSizeTest extends BaseTestCase {
    // ==================== Konstruktion ====================

    public function test_of_bytes(): void {
        $this->assertSame(1572864, ByteSize::ofBytes(1572864)->getBytes());
        $this->assertSame(0, ByteSize::ofBytes(0)->getBytes());
        $this->assertTrue(ByteSize::zero()->isZero());
    }

    public function test_of_bytes_rejects_negative(): void {
        $this->expectException(InvalidArgumentException::class);
        ByteSize::ofBytes(-1);
    }

    public function test_parse_with_dot_and_comma_decimal(): void {
        $this->assertSame(1572864, ByteSize::parse('1.5 MB')->getBytes());
        $this->assertSame(1610612736, ByteSize::parse('1,5 GB')->getBytes());
    }

    public function test_parse_requires_unit(): void {
        // Helper-Vertrag: parseByteString() verlangt eine Einheit.
        $this->expectException(\RuntimeException::class);
        ByteSize::parse('1024');
    }

    public function test_try_parse(): void {
        $this->assertNull(ByteSize::tryParse(null));
        $this->assertNull(ByteSize::tryParse(''));
        $this->assertNull(ByteSize::tryParse('1024'), 'Ohne Einheit: null statt Exception.');
        $this->assertNull(ByteSize::tryParse('viele Bytes'));

        $size = ByteSize::tryParse('1.5 MB');
        $this->assertNotNull($size);
        $this->assertSame(1572864, $size->getBytes());
    }

    // ==================== Arithmetik ====================

    public function test_plus_minus_times(): void {
        $mb = ByteSize::parse('1 MB');
        $this->assertSame(2097152, $mb->plus($mb)->getBytes());
        $this->assertSame(0, $mb->minus($mb)->getBytes());
        $this->assertSame(3145728, $mb->times(3)->getBytes());
        $this->assertSame(0, $mb->times(0)->getBytes());
    }

    public function test_minus_below_zero_throws(): void {
        $this->expectException(InvalidArgumentException::class);
        ByteSize::ofBytes(100)->minus(ByteSize::ofBytes(101));
    }

    public function test_times_rejects_negative_factor(): void {
        $this->expectException(InvalidArgumentException::class);
        ByteSize::ofBytes(100)->times(-1);
    }

    public function test_sum(): void {
        $sum = ByteSize::sum([ByteSize::ofBytes(100), ByteSize::ofBytes(200), ByteSize::ofBytes(3)]);
        $this->assertSame(303, $sum->getBytes());
        $this->assertTrue(ByteSize::sum([])->isZero());
    }

    public function test_immutability(): void {
        $size = ByteSize::ofBytes(1000);
        $size->plus(ByteSize::ofBytes(1));
        $size->times(5);
        $this->assertSame(1000, $size->getBytes(), 'Ursprung darf sich nicht ändern.');
    }

    // ==================== Vergleich ====================

    public function test_compare_and_equals(): void {
        $this->assertTrue(ByteSize::ofBytes(1024)->equals(ByteSize::parse('1 KB')));
        $this->assertSame(1, ByteSize::ofBytes(2)->compareTo(ByteSize::ofBytes(1)));
        $this->assertSame(-1, ByteSize::ofBytes(1)->compareTo(ByteSize::ofBytes(2)));
        $this->assertSame(0, ByteSize::ofBytes(1)->compareTo(ByteSize::ofBytes(1)));
    }

    // ==================== Formatierung / Serialisierung ====================

    public function test_format(): void {
        $this->assertSame('1.5 MB', ByteSize::ofBytes(1572864)->format());
        $this->assertSame('1.43 MB', ByteSize::ofBytes(1500000)->format());
    }

    public function test_format_parse_roundtrip(): void {
        $original = ByteSize::ofBytes(1572864);
        $this->assertTrue(ByteSize::parse($original->format())->equals($original));
    }

    public function test_to_string_and_json(): void {
        $size = ByteSize::ofBytes(1572864);
        $this->assertSame('1572864 B', (string) $size);
        $this->assertSame('1572864', json_encode($size));
    }
}
