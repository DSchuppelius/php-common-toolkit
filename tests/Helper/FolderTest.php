<?php
/*
 * Created on   : Wed Jan 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FolderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Helper\FileSystem\Folder;
use ERRORToolkit\Exceptions\FileSystem\FolderNotFoundException;
use Tests\Contracts\BaseTestCase;

class FolderTest extends BaseTestCase {
    private string $testDir;

    protected function setUp(): void {
        parent::setUp();
        $this->testDir = sys_get_temp_dir() . '/folder_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
    }

    protected function tearDown(): void {
        if (is_dir($this->testDir)) {
            $this->recursiveDelete($this->testDir);
        }
        parent::tearDown();
    }

    private function recursiveDelete(string $dir): void {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($dir);
    }

    public function testSize(): void {
        // Erstelle Testdateien
        file_put_contents($this->testDir . '/file1.txt', str_repeat('a', 100));
        file_put_contents($this->testDir . '/file2.txt', str_repeat('b', 200));
        mkdir($this->testDir . '/subdir');
        file_put_contents($this->testDir . '/subdir/file3.txt', str_repeat('c', 300));

        // Rekursiv
        $size = Folder::size($this->testDir, true);
        $this->assertEquals(600, $size);

        // Nicht rekursiv
        $sizeNonRecursive = Folder::size($this->testDir, false);
        $this->assertEquals(300, $sizeNonRecursive);
    }

    public function testIsEmpty(): void {
        $this->assertTrue(Folder::isEmpty($this->testDir));

        file_put_contents($this->testDir . '/file.txt', 'content');
        $this->assertFalse(Folder::isEmpty($this->testDir));
    }

    public function testClean(): void {
        file_put_contents($this->testDir . '/file1.txt', 'content');
        mkdir($this->testDir . '/subdir');
        file_put_contents($this->testDir . '/subdir/file2.txt', 'content');

        Folder::clean($this->testDir, true);

        $this->assertTrue(Folder::isEmpty($this->testDir));
        $this->assertTrue(Folder::exists($this->testDir));
    }

    public function testFileCount(): void {
        file_put_contents($this->testDir . '/file1.txt', 'content');
        file_put_contents($this->testDir . '/file2.php', 'content');
        mkdir($this->testDir . '/subdir');
        file_put_contents($this->testDir . '/subdir/file3.txt', 'content');

        // Nicht rekursiv
        $this->assertEquals(2, Folder::fileCount($this->testDir, false));

        // Rekursiv
        $this->assertEquals(3, Folder::fileCount($this->testDir, true));

        // Mit Extension-Filter
        $this->assertEquals(2, Folder::fileCount($this->testDir, true, ['txt']));
        $this->assertEquals(1, Folder::fileCount($this->testDir, true, ['php']));
    }

    public function testFolderCount(): void {
        mkdir($this->testDir . '/sub1');
        mkdir($this->testDir . '/sub2');
        mkdir($this->testDir . '/sub1/subsub');

        $this->assertEquals(2, Folder::folderCount($this->testDir, false));
        $this->assertEquals(3, Folder::folderCount($this->testDir, true));
    }

    public function testFindByPattern(): void {
        file_put_contents($this->testDir . '/file1.txt', 'content');
        file_put_contents($this->testDir . '/file2.php', 'content');
        file_put_contents($this->testDir . '/file3.txt', 'content');

        $txtFiles = Folder::findByPattern($this->testDir, '*.txt', false);
        $this->assertCount(2, $txtFiles);

        $phpFiles = Folder::findByPattern($this->testDir, '*.php', false);
        $this->assertCount(1, $phpFiles);
    }

    public function testGetNewestAndOldest(): void {
        file_put_contents($this->testDir . '/old.txt', 'old');
        sleep(1);
        file_put_contents($this->testDir . '/new.txt', 'new');

        $newest = Folder::getNewest($this->testDir);
        $oldest = Folder::getOldest($this->testDir);

        $this->assertStringContainsString('new.txt', $newest);
        $this->assertStringContainsString('old.txt', $oldest);
    }

    public function testGetLargest(): void {
        file_put_contents($this->testDir . '/small.txt', str_repeat('a', 10));
        file_put_contents($this->testDir . '/large.txt', str_repeat('b', 1000));

        $largest = Folder::getLargest($this->testDir);
        $this->assertStringContainsString('large.txt', $largest);
    }

    public function testSizeThrowsExceptionForNonExistingFolder(): void {
        $this->expectException(FolderNotFoundException::class);
        Folder::size('/path/to/nonexistent/folder');
    }

    public function testIsEmptyThrowsExceptionForNonExistingFolder(): void {
        $this->expectException(FolderNotFoundException::class);
        Folder::isEmpty('/path/to/nonexistent/folder');
    }

    public function testGetNewestReturnsNullForEmptyFolder(): void {
        $this->assertNull(Folder::getNewest($this->testDir));
    }

    public function testGetOldestReturnsNullForEmptyFolder(): void {
        $this->assertNull(Folder::getOldest($this->testDir));
    }
}
