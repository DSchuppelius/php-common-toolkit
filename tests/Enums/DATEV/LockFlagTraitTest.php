<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : LockFlagTraitTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Enums\DATEV;

use CommonToolkit\Enums\DATEV\DiscountLock;
use CommonToolkit\Enums\DATEV\ItemLock;
use CommonToolkit\Enums\DATEV\InterestLock;
use CommonToolkit\Enums\DATEV\SoBilFlag;
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

final class LockFlagTraitTest extends BaseTestCase {
    public static function lockEnumsProvider(): array {
        return [
            [DiscountLock::class],
            [SoBilFlag::class],
            [ItemLock::class],
            [InterestLock::class],
        ];
    }

    /**
     * @dataProvider lockEnumsProvider
     */
    public function testNoneIsRecognizedAsNone(string $enumClass): void {
        $enum = $enumClass::NONE;

        $this->assertTrue($enum->isNone());
        $this->assertFalse($enum->isLocked());
        $this->assertSame(0, $enum->value);
    }

    /**
     * @dataProvider lockEnumsProvider
     */
    public function testLockedIsRecognizedAsLocked(string $enumClass): void {
        $enum = $enumClass::LOCKED;

        $this->assertTrue($enum->isLocked());
        $this->assertFalse($enum->isNone());
        $this->assertSame(1, $enum->value);
    }

    /**
     * @dataProvider lockEnumsProvider
     */
    public function testFromIntReturnsCorrectEnum(string $enumClass): void {
        $this->assertSame($enumClass::NONE,   $enumClass::fromInt(0));
        $this->assertSame($enumClass::LOCKED, $enumClass::fromInt(1));
    }

    /**
     * @dataProvider lockEnumsProvider
     */
    public function testFromIntThrowsOnInvalidValue(string $enumClass): void {
        $this->expectException(InvalidArgumentException::class);
        $enumClass::fromInt(2);
    }
}
