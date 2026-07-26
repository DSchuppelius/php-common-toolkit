<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GermanTaxNumberTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\ValueObjects\GermanTaxNumber;
use InvalidArgumentException;
use JsonSerializable;
use ReflectionClass;
use Stringable;
use Tests\Contracts\BaseTestCase;

class GermanTaxNumberTest extends BaseTestCase {
    // ==================== Konstruktion / Normalisierung ====================

    public function test_of_state_format_without_state(): void {
        $taxNumber = GermanTaxNumber::of('21/815/08150');
        $this->assertSame('2181508150', $taxNumber->getValue());
        $this->assertFalse($taxNumber->isUnifiedFormat());
        $this->assertNull($taxNumber->getFederalState());
    }

    public function test_of_normalizes_federal_state(): void {
        $taxNumber = GermanTaxNumber::of('181/815/08155', ' by ');
        $this->assertSame('18181508155', $taxNumber->getValue());
        $this->assertSame('BY', $taxNumber->getFederalState());
        $this->assertFalse($taxNumber->isUnifiedFormat());
    }

    public function test_of_unified_format_derives_state(): void {
        $taxNumber = GermanTaxNumber::of('9181081508155');
        $this->assertTrue($taxNumber->isUnifiedFormat());
        $this->assertSame('BY', $taxNumber->getFederalState());
    }

    public function test_of_unified_format_with_conflicting_state_throws(): void {
        $this->expectException(InvalidArgumentException::class);
        GermanTaxNumber::of('9181081508155', 'HH');
    }

    public function test_of_rejects_unknown_federal_state(): void {
        $this->expectException(InvalidArgumentException::class);
        GermanTaxNumber::of('21/815/08150', 'XX');
    }

    public function test_of_rejects_garbage(): void {
        $this->expectException(InvalidArgumentException::class);
        GermanTaxNumber::of('keine Steuernummer');
    }

    public function test_of_rejects_empty(): void {
        $this->expectException(InvalidArgumentException::class);
        GermanTaxNumber::of('  ');
    }

    public function test_try_from(): void {
        $this->assertNull(GermanTaxNumber::tryFrom(null));
        $this->assertNull(GermanTaxNumber::tryFrom(''));
        $this->assertNull(GermanTaxNumber::tryFrom('123'));

        $taxNumber = GermanTaxNumber::tryFrom('21/815/08150', 'HH');
        $this->assertNotNull($taxNumber);
        $this->assertSame('2181508150', $taxNumber->getValue());
        $this->assertSame('HH', $taxNumber->getFederalState());
    }

    public function test_try_from_with_invalid_state_still_throws(): void {
        // Ein ungültiges Bundesland-Kürzel ist ein Programmierfehler, kein Datenfall.
        $this->expectException(InvalidArgumentException::class);
        GermanTaxNumber::tryFrom('21/815/08150', 'ZZ');
    }

    // ==================== Formatierung / Maskierung ====================

    public function test_formatted_unified(): void {
        $this->assertSame('9181/0815/08155', GermanTaxNumber::of('9181081508155')->formatted());
    }

    public function test_masked(): void {
        $this->assertSame('XXXXXXX150', GermanTaxNumber::of('21/815/08150')->masked());
        $this->assertSame('XXXXXXXX50', GermanTaxNumber::of('21/815/08150')->masked(2));
    }

    public function test_masked_rejects_revealing_everything(): void {
        $this->expectException(InvalidArgumentException::class);
        GermanTaxNumber::of('21/815/08150')->masked(10);
    }

    // ==================== Gleichheit / Sensibilität ====================

    public function test_equals_requires_same_value_and_state(): void {
        $this->assertTrue(GermanTaxNumber::of('21/815/08150', 'HH')->equals(GermanTaxNumber::of('2181508150', 'HH')));
        $this->assertFalse(
            GermanTaxNumber::of('181/815/08155', 'BY')->equals(GermanTaxNumber::of('181/815/08155', 'BW')),
            'Gleiche Ziffern in verschiedenen Bundesländern sind verschiedene Steuernummern.'
        );
        $this->assertFalse(GermanTaxNumber::of('21/815/08150')->equals(GermanTaxNumber::of('21/815/08151')));
    }

    public function test_no_string_or_json_interface(): void {
        $reflection = new ReflectionClass(GermanTaxNumber::class);
        $this->assertFalse($reflection->implementsInterface(Stringable::class), 'Sensibler Wert darf nicht implizit zum String werden.');
        $this->assertFalse($reflection->implementsInterface(JsonSerializable::class), 'Sensibler Wert darf nicht implizit ins JSON gelangen.');
        $this->assertFalse($reflection->hasMethod('__toString'));
    }
}
