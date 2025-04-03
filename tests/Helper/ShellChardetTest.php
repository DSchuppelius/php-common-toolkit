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
use Tests\Contracts\BaseTestCase;
use Throwable;

class ShellChardetTest extends BaseTestCase {

    private string $sampleText = "Grüße aus Potsdam mit Umlauten äöüß";

    public function testShellChardetDetectTemporary(): void {
        $this->runChardetDetection(false);
    }

    public function testShellChardetDetectPersistent(): void {
        // Start persistent Prozess vorher
        ShellChardet::start();
        try {
            $this->runChardetDetection(true);
        } finally {
            ShellChardet::stop();
        }
    }

    private function runChardetDetection(bool $persistent): void {
        $variant = $persistent ? 'persistent' : 'temporary';
        $start = microtime(true);

        try {
            $encoding = ShellChardet::detect($this->sampleText, $persistent);
        } catch (Throwable $e) {
            $this->fail("ShellChardet::detect($variant) warf eine Ausnahme: " . $e->getMessage());
        }

        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, "ShellChardet::detect($variant) hat zu lange gebraucht (evtl. blockiert?)");

        if ($encoding === false) {
            $this->markTestIncomplete("ShellChardet ($variant) konnte Kodierung nicht ermitteln – chardet installiert?");
        } else {
            $this->assertMatchesRegularExpression('/utf-?8|iso-8859/i', $encoding, "Kodierung ($variant) plausibel?");
        }
    }
}