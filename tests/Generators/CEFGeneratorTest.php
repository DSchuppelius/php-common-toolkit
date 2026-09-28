<?php
/*
 * Created on   : Mon Sep 28 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CEFGeneratorTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Generators;

use CommonToolkit\Generators\CEF\CEFGenerator;
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

class CEFGeneratorTest extends BaseTestCase {
    public function test_line_has_header_and_extensions(): void {
        $line = CEFGenerator::line('Acme', 'App', '1.0', 'auth.failed', 'Login failed', 5, ['src' => '203.0.113.7', 'suser' => 'j.doe', 'empty' => '', 'none' => null, 'cnt' => 3]);

        $this->assertSame('CEF:0|Acme|App|1.0|auth.failed|Login failed|5|src=203.0.113.7 suser=j.doe cnt=3', $line);
    }

    public function test_header_escapes_pipes_backslashes_and_line_breaks(): void {
        $line = CEFGenerator::line('A|B', 'C\\D', "E\nF", 'x', 'y', 3);

        $this->assertSame('CEF:0|A\\|B|C\\\\D|E F|x|y|3|', $line);
    }

    public function test_extension_escapes_equals_backslashes_and_line_breaks(): void {
        $line = CEFGenerator::line('V', 'P', '1', 'id', 'n', 1, ['msg' => "a=b\\c\r\nd|e"]);

        $this->assertSame('CEF:0|V|P|1|id|n|1|msg=a\\=b\\\\c\\nd|e', $line);
    }

    public function test_severity_is_clamped(): void {
        $this->assertStringContainsString('|10|', CEFGenerator::line('V', 'P', '1', 'id', 'n', 42));
        $this->assertStringContainsString('|0|', CEFGenerator::line('V', 'P', '1', 'id', 'n', -1));
    }

    public function test_invalid_extension_key_is_rejected(): void {
        $this->expectException(InvalidArgumentException::class);
        CEFGenerator::line('V', 'P', '1', 'id', 'n', 1, ['bad key' => 'x']);
    }
}
