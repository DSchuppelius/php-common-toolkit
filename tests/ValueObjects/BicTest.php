<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BicTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\ValueObjects\Bic;
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

class BicTest extends BaseTestCase {
    // ==================== Konstruktion / Normalisierung ====================

    public function test_of_normalizes_case_and_whitespace(): void {
        $this->assertSame('DEUTDEFFXXX', Bic::of(' deutdeff ')->getValue());
    }

    public function test_bic8_is_stored_canonically_as_bic11(): void {
        $bic = Bic::of('DEUTDEFF');
        $this->assertSame('DEUTDEFFXXX', $bic->getValue());
        $this->assertSame('DEUTDEFFXXX', $bic->asBic11());
    }

    public function test_of_accepts_bic11_with_branch(): void {
        $bic = Bic::of('DEUTDEFF500');
        $this->assertSame('DEUTDEFF500', $bic->getValue());
        $this->assertSame('500', $bic->getBranchCode());
    }

    public function test_of_rejects_invalid_structure(): void {
        $this->expectException(InvalidArgumentException::class);
        Bic::of('DEUTD3FF'); // Ziffer im Bankcode
    }

    public function test_of_rejects_unknown_country(): void {
        $this->expectException(InvalidArgumentException::class);
        Bic::of('ZZZZZZFF'); // Land "ZZ" existiert nicht
    }

    public function test_of_rejects_empty(): void {
        $this->expectException(InvalidArgumentException::class);
        Bic::of('   ');
    }

    public function test_try_from(): void {
        $this->assertNull(Bic::tryFrom(null));
        $this->assertNull(Bic::tryFrom(''));
        $this->assertNull(Bic::tryFrom('DEUTD3FF'));

        $bic = Bic::tryFrom('markdef1100');
        $this->assertNotNull($bic);
        $this->assertSame('MARKDEF1100', $bic->getValue());
    }

    // ==================== Strukturzugriff ====================

    public function test_structure_accessors(): void {
        $bic = Bic::of('DEUTDEFF');
        $this->assertSame('DEUT', $bic->getInstitutionCode());
        $this->assertSame(CountryCode::Germany, $bic->getCountry());
        $this->assertSame('FF', $bic->getLocationCode());
        $this->assertNull($bic->getBranchCode(), 'XXX-Filialcode gilt als Hauptsitz (kein echter Filialcode).');
    }

    public function test_branch_code_of_explicit_xxx_is_null(): void {
        $this->assertNull(Bic::of('DEUTDEFFXXX')->getBranchCode());
    }

    // ==================== Gleichheit ====================

    public function test_bic8_equals_bic11_with_xxx(): void {
        $this->assertTrue(Bic::of('DEUTDEFF')->equals(Bic::of('DEUTDEFFXXX')));
        $this->assertFalse(Bic::of('DEUTDEFF')->equals(Bic::of('DEUTDEFF500')));
        $this->assertFalse(Bic::of('DEUTDEFF')->equals(Bic::of('MARKDEF1100')));
    }

    // ==================== String / JSON (kein Konto — unbedenklich) ====================

    public function test_to_string_and_json(): void {
        $bic = Bic::of('deutdeff');
        $this->assertSame('DEUTDEFFXXX', (string) $bic);
        $this->assertSame('"DEUTDEFFXXX"', json_encode($bic));
    }
}
