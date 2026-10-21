<?php
/*
 * Created on   : Wed Oct 21 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StringHelperWordsLinesTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\StringHelper;
use Tests\Contracts\BaseTestCase;

class StringHelperWordsLinesTest extends BaseTestCase {
    public function test_words_split_on_any_whitespace_without_empty_parts(): void {
        $this->assertSame(['Gesamt', '1.234,56', 'EUR'], StringHelper::words("  Gesamt\t1.234,56\u{00A0}EUR \n"));
        $this->assertSame([], StringHelper::words(null));
        $this->assertSame([], StringHelper::words("   \n "));
    }

    public function test_lines_treat_every_line_ending_alike(): void {
        $this->assertSame(['eins', 'zwei', 'drei', ''], StringHelper::lines("eins\r\nzwei\rdrei\n"));
        $this->assertSame(['eins', 'zwei'], StringHelper::lines("eins\r\n  \r\nzwei\n", skipEmpty: true));
        $this->assertSame([], StringHelper::lines(''));
    }
}
