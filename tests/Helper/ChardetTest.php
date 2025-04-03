<?php
/*
 * Created on   : Thu Apr 03 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ChardetTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Tests\Helper;

use CommonToolkit\Helper\Shell\ShellChardet;
use PHPUnit\Framework\TestCase;
use Throwable;

class ChardetTest extends TestCase {
    public function testShellChardetDetectWithTimeout(): void {
        $text = "Grüße aus Potsdam mit Umlauten äöüß";

        $start = microtime(true);

        $encoding = null;

        try {
            $encoding = ShellChardet::detect($text);
        } catch (Throwable $e) {
            $this->fail("ShellChardet::detect() warf eine Ausnahme: " . $e->getMessage());
        }

        $duration = microtime(true) - $start;

        // Timebox, z. B. max. 1 Sekunde
        $this->assertLessThan(1.0, $duration, "ShellChardet::detect() hat zu lange gebraucht (evtl. blockiert?)");

        if ($encoding === false) {
            $this->markTestIncomplete("ShellChardet konnte Kodierung nicht ermitteln – chardet installiert?");
        } else {
            $this->assertMatchesRegularExpression('/utf-?8|iso-8859/i', $encoding, "Kodierung plausibel?");
        }
    }
}