<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GermanTaxIdTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\ValueObjects\GermanTaxId;
use InvalidArgumentException;
use JsonSerializable;
use ReflectionClass;
use Stringable;
use Tests\Contracts\BaseTestCase;

class GermanTaxIdTest extends BaseTestCase {
    /** Bekannte gültige Test-IdNr. (BZSt-Beispiel). */
    private const VALID = '65929970489';

    // ==================== Konstruktion / Normalisierung ====================

    public function test_of_normalizes_separators(): void {
        $this->assertSame(self::VALID, GermanTaxId::of('65 929 970 489')->getValue());
        $this->assertSame(self::VALID, GermanTaxId::of('65929970489')->getValue());
    }

    public function test_of_rejects_invalid_check_digit(): void {
        $this->expectException(InvalidArgumentException::class);
        GermanTaxId::of('65929970488');
    }

    public function test_of_rejects_invalid_structure(): void {
        // Formal 11 Ziffern, aber ungültige Ziffernverteilung/Prüfziffer
        $this->expectException(InvalidArgumentException::class);
        GermanTaxId::of('12345678901');
    }

    public function test_of_rejects_wrong_length(): void {
        $this->expectException(InvalidArgumentException::class);
        GermanTaxId::of('6592997048');
    }

    public function test_of_rejects_empty(): void {
        $this->expectException(InvalidArgumentException::class);
        GermanTaxId::of('  ');
    }

    public function test_try_from(): void {
        $this->assertNull(GermanTaxId::tryFrom(null));
        $this->assertNull(GermanTaxId::tryFrom(''));
        $this->assertNull(GermanTaxId::tryFrom('65929970488'));

        $taxId = GermanTaxId::tryFrom('65 929 970 489');
        $this->assertNotNull($taxId);
        $this->assertSame(self::VALID, $taxId->getValue());
    }

    // ==================== Formatierung / Maskierung ====================

    public function test_formatted(): void {
        $this->assertSame('65 929 970 489', GermanTaxId::of(self::VALID)->formatted());
    }

    public function test_masked_defaults(): void {
        $this->assertSame('XXXXXXXX489', GermanTaxId::of(self::VALID)->masked());
    }

    public function test_masked_with_custom_visibility(): void {
        $this->assertSame('XXXXXXXXXX9', GermanTaxId::of(self::VALID)->masked(1));
    }

    public function test_masked_rejects_revealing_everything(): void {
        $this->expectException(InvalidArgumentException::class);
        GermanTaxId::of(self::VALID)->masked(11);
    }

    // ==================== Gleichheit / Sensibilität ====================

    public function test_equals_ignores_input_formatting(): void {
        $this->assertTrue(GermanTaxId::of('65 929 970 489')->equals(GermanTaxId::of(self::VALID)));
    }

    public function test_no_string_or_json_interface(): void {
        $reflection = new ReflectionClass(GermanTaxId::class);
        $this->assertFalse($reflection->implementsInterface(Stringable::class), 'Sensibler Wert darf nicht implizit zum String werden.');
        $this->assertFalse($reflection->implementsInterface(JsonSerializable::class), 'Sensibler Wert darf nicht implizit ins JSON gelangen.');
        $this->assertFalse($reflection->hasMethod('__toString'));
    }
}
