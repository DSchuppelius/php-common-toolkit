<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CreditorIdentifierTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\ValueObjects\CreditorIdentifier;
use InvalidArgumentException;
use JsonSerializable;
use ReflectionClass;
use Stringable;
use Tests\Contracts\BaseTestCase;

class CreditorIdentifierTest extends BaseTestCase {
    /** Offizielle Beispiel-Gläubiger-ID der Deutschen Bundesbank. */
    private const VALID_DE = 'DE98ZZZ09999999999';

    // ==================== Konstruktion / Normalisierung ====================

    public function test_of_normalizes_case_and_separators(): void {
        $this->assertSame(self::VALID_DE, CreditorIdentifier::of('de 98 zzz 09999999999')->getValue());
    }

    public function test_of_rejects_invalid_check_digits(): void {
        $this->expectException(InvalidArgumentException::class);
        CreditorIdentifier::of('DE97ZZZ09999999999');
    }

    public function test_of_rejects_garbage(): void {
        $this->expectException(InvalidArgumentException::class);
        CreditorIdentifier::of('keine Gläubiger-ID');
    }

    public function test_of_rejects_empty(): void {
        $this->expectException(InvalidArgumentException::class);
        CreditorIdentifier::of('  ');
    }

    public function test_try_from(): void {
        $this->assertNull(CreditorIdentifier::tryFrom(null));
        $this->assertNull(CreditorIdentifier::tryFrom(''));
        $this->assertNull(CreditorIdentifier::tryFrom('DE97ZZZ09999999999'));

        $creditorId = CreditorIdentifier::tryFrom('de98zzz09999999999');
        $this->assertNotNull($creditorId);
        $this->assertSame(self::VALID_DE, $creditorId->getValue());
    }

    // ==================== Strukturzugriff ====================

    public function test_structure_accessors(): void {
        $creditorId = CreditorIdentifier::of(self::VALID_DE);
        $this->assertSame(CountryCode::Germany, $creditorId->getCountry());
        $this->assertSame('ZZZ', $creditorId->getBusinessAreaCode());
        $this->assertSame('09999999999', $creditorId->getNationalIdentifier());
    }

    public function test_french_creditor_id(): void {
        $creditorId = CreditorIdentifier::of('FR72ZZZ123456');
        $this->assertSame(CountryCode::France, $creditorId->getCountry());
        $this->assertSame('123456', $creditorId->getNationalIdentifier());
    }

    // ==================== Formatierung / Maskierung ====================

    public function test_formatted(): void {
        $creditorId = CreditorIdentifier::of(self::VALID_DE);
        $this->assertSame('DE98 ZZZ 0999 9999 999', $creditorId->formatted());
        $this->assertSame('DE98-ZZZ-0999-9999-999', $creditorId->formatted('-'));
    }

    public function test_masked_defaults(): void {
        // 18 Zeichen: 7 sichtbar vorn (Land+Prüfziffer+Geschäftsbereich), 4 hinten.
        $this->assertSame('DE98ZZZXXXXXXX9999', CreditorIdentifier::of(self::VALID_DE)->masked());
    }

    public function test_masked_with_custom_visibility(): void {
        $this->assertSame('DEXXXXXXXXXXXXXX99', CreditorIdentifier::of(self::VALID_DE)->masked(2, 2));
    }

    public function test_masked_rejects_revealing_everything(): void {
        $this->expectException(InvalidArgumentException::class);
        CreditorIdentifier::of(self::VALID_DE)->masked(9, 9);
    }

    // ==================== Gleichheit / Sensibilität ====================

    public function test_equals_ignores_input_formatting(): void {
        $this->assertTrue(CreditorIdentifier::of('de98zzz09999999999')->equals(CreditorIdentifier::of(self::VALID_DE)));
        $this->assertFalse(CreditorIdentifier::of(self::VALID_DE)->equals(CreditorIdentifier::of('FR72ZZZ123456')));
    }

    public function test_no_string_or_json_interface(): void {
        $reflection = new ReflectionClass(CreditorIdentifier::class);
        $this->assertFalse($reflection->implementsInterface(Stringable::class), 'Sensibler Wert darf nicht implizit zum String werden.');
        $this->assertFalse($reflection->implementsInterface(JsonSerializable::class), 'Sensibler Wert darf nicht implizit ins JSON gelangen.');
        $this->assertFalse($reflection->hasMethod('__toString'));
    }
}
