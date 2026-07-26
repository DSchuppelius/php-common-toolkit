<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PhoneNumberTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\ValueObjects\PhoneNumber;
use InvalidArgumentException;
use JsonSerializable;
use ReflectionClass;
use Stringable;
use Tests\Contracts\BaseTestCase;

class PhoneNumberTest extends BaseTestCase {
    // ==================== Konstruktion / E.164 ====================

    public function test_of_converts_national_format_to_e164(): void {
        $phone = PhoneNumber::of('089 / 12 34 56 78');
        $this->assertSame('+498912345678', $phone->getValue());
    }

    public function test_of_keeps_existing_international_format(): void {
        $this->assertSame('+498912345678', PhoneNumber::of('+49 89 12345678')->getValue());
        $this->assertSame('+4315321234', PhoneNumber::of('0043 1 5321234')->getValue());
    }

    public function test_of_with_other_default_country(): void {
        $phone = PhoneNumber::of('01 5321234', CountryCode::Austria);
        $this->assertSame('+4315321234', $phone->getValue());
    }

    public function test_of_rejects_garbage(): void {
        $this->expectException(InvalidArgumentException::class);
        PhoneNumber::of('kein telefon');
    }

    public function test_of_rejects_empty(): void {
        $this->expectException(InvalidArgumentException::class);
        PhoneNumber::of('  ');
    }

    public function test_try_from(): void {
        $this->assertNull(PhoneNumber::tryFrom(null));
        $this->assertNull(PhoneNumber::tryFrom(''));
        $this->assertNull(PhoneNumber::tryFrom('kein telefon'));

        $phone = PhoneNumber::tryFrom('089 12345678');
        $this->assertNotNull($phone);
        $this->assertSame('+498912345678', $phone->getValue());
    }

    // ==================== Land / Formatierung ====================

    public function test_get_country(): void {
        $this->assertSame(CountryCode::Germany, PhoneNumber::of('+498912345678')->getCountry());
        $this->assertSame(CountryCode::Austria, PhoneNumber::of('+4315321234')->getCountry());
    }

    public function test_international_format(): void {
        $this->assertSame('+49 891 2345678', PhoneNumber::of('089 12345678')->international());
    }

    public function test_national_format(): void {
        $phone = PhoneNumber::of('+498912345678');
        $this->assertSame('0891 2345678', $phone->national());
        $this->assertSame('0891 2345678', $phone->national(CountryCode::Germany));
    }

    public function test_is_from_country(): void {
        $phone = PhoneNumber::of('+498912345678');
        $this->assertTrue($phone->isFromCountry(CountryCode::Germany));
        $this->assertFalse($phone->isFromCountry(CountryCode::Austria));
    }

    // ==================== Maskierung ====================

    public function test_masked_shows_only_trailing_digits(): void {
        $masked = PhoneNumber::of('+498912345678')->masked();

        $this->assertSame('+XXXXXXXXX678', $masked);
        $this->assertSame('', preg_replace('/[^0-9]/', '', substr($masked, 1, -3)), 'Die Mitte darf keine Ziffern enthalten.');
        $this->assertStringEndsWith('678', $masked);
    }

    public function test_masked_with_custom_visibility(): void {
        $this->assertSame('+XXXXXXXX5678', PhoneNumber::of('+498912345678')->masked(4));
    }

    public function test_masked_rejects_revealing_everything(): void {
        $this->expectException(InvalidArgumentException::class);
        PhoneNumber::of('+498912345678')->masked(12);
    }

    // ==================== Gleichheit / Sensibilität ====================

    public function test_equals_ignores_input_formatting(): void {
        $a = PhoneNumber::of('089 / 12 34 56 78');
        $b = PhoneNumber::of('+49 89 12345678');
        $c = PhoneNumber::of('+4315321234');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_no_string_or_json_interface(): void {
        $reflection = new ReflectionClass(PhoneNumber::class);
        $this->assertFalse($reflection->implementsInterface(Stringable::class), 'Sensibler Wert darf nicht implizit zum String werden.');
        $this->assertFalse($reflection->implementsInterface(JsonSerializable::class), 'Sensibler Wert darf nicht implizit ins JSON gelangen.');
        $this->assertFalse($reflection->hasMethod('__toString'));
    }
}
