<?php
/*
 * Created on   : Mon Sep 28 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CreditorReferenceHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\CreditorReferenceHelper;
use Tests\Contracts\BaseTestCase;

class CreditorReferenceHelperTest extends BaseTestCase {
    public function test_create_matches_the_iso_11649_example(): void {
        $this->assertSame('RF18539007547034', CreditorReferenceHelper::create('539007547034'));
        $this->assertSame('RF18539007547034', CreditorReferenceHelper::create('5390 0754 7034'));
        $this->assertSame('RF40RE20260001', CreditorReferenceHelper::create('re20260001'));
    }

    public function test_create_rejects_other_characters_and_overlong_references(): void {
        $this->assertNull(CreditorReferenceHelper::create('RE-2026-0001'));
        $this->assertNull(CreditorReferenceHelper::create(''));
        $this->assertNull(CreditorReferenceHelper::create(str_repeat('1', 22)));
        $this->assertNotNull(CreditorReferenceHelper::create(str_repeat('1', 21)));
    }

    public function test_is_valid_checks_structure_and_check_digits(): void {
        $this->assertTrue(CreditorReferenceHelper::isValid('RF18 5390 0754 7034'));
        $this->assertTrue(CreditorReferenceHelper::isValid('rf18539007547034'));
        $this->assertFalse(CreditorReferenceHelper::isValid('RF19539007547034'));
        $this->assertFalse(CreditorReferenceHelper::isValid('RF18'));
        $this->assertFalse(CreditorReferenceHelper::isValid('DE18539007547034'));
        $this->assertFalse(CreditorReferenceHelper::isValid(null));
    }

    public function test_every_created_reference_is_valid(): void {
        foreach (['1', 'A', 'RE2026000123', 'ZZZZZZZZZZZZZZZZZZZZZ', '000000000000000000001'] as $reference) {
            $created = CreditorReferenceHelper::create($reference);
            $this->assertNotNull($created, $reference);
            $this->assertTrue(CreditorReferenceHelper::isValid($created), $reference);
            $this->assertSame($reference, CreditorReferenceHelper::reference($created));
        }
    }

    public function test_format_groups_by_four(): void {
        $this->assertSame('RF18 5390 0754 7034', CreditorReferenceHelper::format('RF18539007547034'));
        $this->assertSame('RF40 RE20 2600 01', CreditorReferenceHelper::format('rf40re20260001'));
        $this->assertSame('kein RF', CreditorReferenceHelper::format('kein RF'));
    }

    public function test_extract_finds_references_in_free_text(): void {
        $this->assertSame(['RF18539007547034'], CreditorReferenceHelper::extract('Zahlung RF18 5390 0754 7034 Rechnung 12'));
        $this->assertSame(['RF40RE20260001'], CreditorReferenceHelper::extract('SVWZ+rf40re20260001 danke'));
        $this->assertSame([], CreditorReferenceHelper::extract('RF19539007547034'));
        $this->assertSame([], CreditorReferenceHelper::extract('XRF18539007547034'));
        $this->assertSame([], CreditorReferenceHelper::extract(null));
    }
}
