<?php
/*
 * Created on   : Thu Aug 20 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FormulaGuardTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper\CSV;

use CommonToolkit\Enums\Common\CSV\QuotingStyle;
use CommonToolkit\Helper\Data\CSV\StringHelper;
use Tests\Contracts\BaseTestCase;

/**
 * Formel-Injektions-Guard (CWE-1236) am Zeilen-Encoder: Opt-in, eigene
 * Präfixliste, optionales ltrim.
 */
class FormulaGuardTest extends BaseTestCase {
    public function test_guard_is_off_by_default(): void {
        $this->assertSame('=SUM(A1:A9);x', StringHelper::encodeLine(['=SUM(A1:A9)', 'x'], ';'));
    }

    public function test_guard_prefixes_every_field_of_the_line(): void {
        $this->assertSame(
            "'=SUM(A1:A9);'@cmd;harmlos",
            StringHelper::encodeLine(['=SUM(A1:A9)', '@cmd', 'harmlos'], ';', '"', QuotingStyle::MINIMAL, '\\', true),
        );
    }

    /** Eigene Liste: ein Export mit „+49 …" soll das Plus behalten dürfen. */
    public function test_custom_prefix_list_leaves_plus_alone(): void {
        $this->assertSame(
            "'=SUM(A1);+49 511 1234",
            StringHelper::encodeLine(['=SUM(A1)', '+49 511 1234'], ';', '"', QuotingStyle::MINIMAL, '\\', true, ['=', '@']),
        );
    }

    public function test_leading_whitespace_is_only_caught_when_asked(): void {
        $this->assertSame('  =SUM(A1)', StringHelper::neutralizeFormulaInjection('  =SUM(A1)'));
        $this->assertSame("'  =SUM(A1)", StringHelper::neutralizeFormulaInjection('  =SUM(A1)', null, true));
    }

    public function test_empty_value_stays_empty(): void {
        $this->assertSame('', StringHelper::neutralizeFormulaInjection(''));
        $this->assertSame('   ', StringHelper::neutralizeFormulaInjection('   ', null, true));
    }

    /** Der Guard läuft VOR dem Quoting — das Apostroph steht innerhalb der Anführungszeichen. */
    public function test_guard_runs_before_quoting(): void {
        $this->assertSame(
            '"\'=A1;B1"',
            StringHelper::encodeLine(['=A1;B1'], ';', '"', QuotingStyle::MINIMAL, '\\', true),
        );
    }
}
