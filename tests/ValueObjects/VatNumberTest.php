<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VatNumberTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Helper\Data\VatNumberHelper;
use CommonToolkit\ValueObjects\VatNumber;
use InvalidArgumentException;
use JsonSerializable;
use ReflectionClass;
use Stringable;
use Tests\Contracts\BaseTestCase;

class VatNumberTest extends BaseTestCase {
    // ==================== Konstruktion / Normalisierung ====================

    public function test_of_normalizes_case_and_whitespace(): void {
        $this->assertSame('DE136695976', VatNumber::of('de 136 695 976')->getValue());
    }

    public function test_of_strict_rejects_invalid_checksum(): void {
        $this->expectException(InvalidArgumentException::class);
        VatNumber::of('DE136695975');
    }

    public function test_of_non_strict_accepts_format_only(): void {
        $this->assertSame('DE136695975', VatNumber::of('DE136695975', false)->getValue());
    }

    public function test_of_accepts_austrian_vat_id(): void {
        $this->assertSame('ATU13585627', VatNumber::of('ATU13585627')->getValue());
    }

    public function test_of_rejects_garbage(): void {
        $this->expectException(InvalidArgumentException::class);
        VatNumber::of('keine UStID');
    }

    public function test_of_rejects_empty(): void {
        $this->expectException(InvalidArgumentException::class);
        VatNumber::of('  ');
    }

    public function test_try_from(): void {
        $this->assertNull(VatNumber::tryFrom(null));
        $this->assertNull(VatNumber::tryFrom(''));
        $this->assertNull(VatNumber::tryFrom('DE136695975'));

        $vat = VatNumber::tryFrom('de136695976');
        $this->assertNotNull($vat);
        $this->assertSame('DE136695976', $vat->getValue());
    }

    // ==================== Länder-Präfixe (auch Nicht-ISO-Fälle) ====================

    public function test_country_code_is_string_including_non_iso_prefixes(): void {
        $this->assertSame('DE', VatNumber::of('DE136695976')->getCountryCode());

        // CHE (Schweiz) und XI (Nordirland) sind gültige Präfixe, aber keine
        // durchgehenden ISO-3166-Alpha-2-Fälle — deshalb bewusst string.
        $this->assertSame('CHE', VatNumber::of('CHE-123.456.788 MWST')->getCountryCode());
        $this->assertSame('XI', VatNumber::of('XI123456789')->getCountryCode());
    }

    public function test_country_resolves_non_iso_prefixes(): void {
        $this->assertSame(CountryCode::Germany, VatNumber::of('DE136695976')->getCountry());
        $this->assertSame(CountryCode::Greece, VatNumber::of('EL123456789')->getCountry());
        $this->assertSame(CountryCode::Switzerland, VatNumber::of('CHE-123.456.788 MWST')->getCountry());
        $this->assertSame(CountryCode::NorthernIreland, VatNumber::of('XI123456789')->getCountry());
    }

    public function test_country_for_vat_prefix(): void {
        $this->assertSame(CountryCode::Greece, VatNumberHelper::getCountryForVatPrefix('el'));
        $this->assertSame(CountryCode::Austria, VatNumberHelper::getCountryForVatPrefix('AT'));
        $this->assertSame(CountryCode::UnitedKingdomOfGreatBritainAndNorthernIreland, VatNumberHelper::getCountryForVatPrefix('GB'));
        // GR ist ISO, aber kein USt-ID-Präfix (Griechenland nutzt EL).
        $this->assertNull(VatNumberHelper::getCountryForVatPrefix('GR'));
        $this->assertNull(VatNumberHelper::getCountryForVatPrefix('US'));

        foreach (VatNumberHelper::getSupportedCountries() as $prefix) {
            $country = VatNumberHelper::getCountryForVatPrefix($prefix);
            $this->assertNotNull($country, $prefix);
            if ($prefix !== 'XI') {
                $this->assertSame($prefix, VatNumberHelper::getVatPrefix($country), 'Hin- und Rückweg für ' . $prefix);
            }
        }
    }

    public function test_national_number(): void {
        $this->assertSame('136695976', VatNumber::of('DE136695976')->getNationalNumber());
    }

    // ==================== Formatierung / Maskierung ====================

    public function test_formatted(): void {
        $this->assertSame('DE 136 695 976', VatNumber::of('DE136695976')->formatted());
    }

    public function test_masked_keeps_prefix_and_trailing_chars(): void {
        $this->assertSame('DEXXXXX5976', VatNumber::of('DE136695976')->masked());
        $this->assertSame('DEXXXXXXX76', VatNumber::of('DE136695976')->masked(2));
    }

    public function test_masked_rejects_revealing_everything(): void {
        $this->expectException(InvalidArgumentException::class);
        VatNumber::of('DE136695976')->masked(9);
    }

    // ==================== Gleichheit / Sensibilität ====================

    public function test_equals_ignores_input_formatting(): void {
        $this->assertTrue(VatNumber::of('de 136 695 976')->equals(VatNumber::of('DE136695976')));
        $this->assertFalse(VatNumber::of('DE136695976')->equals(VatNumber::of('ATU13585627')));
    }

    public function test_romanian_number_needs_its_check_digit(): void {
        $this->assertNull(VatNumber::tryFrom('RO12'), 'Format zulässig, Prüfziffer falsch (Befund 2.17.1)');
        $this->assertNotNull(VatNumber::tryFrom('RO12', false), 'nur Formatprüfung');
        $this->assertSame('RO19', VatNumber::of('ro 19')->getValue());
    }

    public function test_no_string_or_json_interface(): void {
        $reflection = new ReflectionClass(VatNumber::class);
        $this->assertFalse($reflection->implementsInterface(Stringable::class), 'Sensibler Wert darf nicht implizit zum String werden.');
        $this->assertFalse($reflection->implementsInterface(JsonSerializable::class), 'Sensibler Wert darf nicht implizit ins JSON gelangen.');
        $this->assertFalse($reflection->hasMethod('__toString'));
    }
}
