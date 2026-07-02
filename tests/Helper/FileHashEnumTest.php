<?php
/*
 * Created on   : Thu Jul 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileHashEnumTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Enums\HashAlgorithm;
use CommonToolkit\Helper\FileSystem\File;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für die HashAlgorithm|string-Union-Parameter von File::hash()
 * und File::compare().
 */
class FileHashEnumTest extends BaseTestCase {
    private string $file;
    private string $copy;

    protected function setUp(): void {
        parent::setUp();
        $this->file = tempnam(sys_get_temp_dir(), 'cthash');
        $this->copy = tempnam(sys_get_temp_dir(), 'cthash');
        file_put_contents($this->file, "Inhalt mit Umlauten äöü\n");
        file_put_contents($this->copy, "Inhalt mit Umlauten äöü\n");
    }

    protected function tearDown(): void {
        @unlink($this->file);
        @unlink($this->copy);
        parent::tearDown();
    }

    public function test_default_is_sha256(): void {
        $this->assertSame(hash_file('sha256', $this->file), File::hash($this->file));
    }

    public function test_enum_and_string_are_equivalent(): void {
        $this->assertSame(File::hash($this->file, 'sha512'), File::hash($this->file, HashAlgorithm::SHA512));
        $this->assertSame(hash_file('xxh3', $this->file), File::hash($this->file, HashAlgorithm::XXH3));
    }

    public function test_compare_accepts_enum(): void {
        $this->assertTrue(File::compare($this->file, $this->copy, true, HashAlgorithm::SHA512));

        // Gleiche Byte-Länge, anderer Inhalt — erzwingt den Hash-Pfad
        // (der Größen-Schnellvergleich greift sonst zuerst).
        file_put_contents($this->copy, "Xnhalt mit Umlauten äöü\n");
        $this->assertFalse(File::compare($this->file, $this->copy, true, HashAlgorithm::SHA512));
    }
}
