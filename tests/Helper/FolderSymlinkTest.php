<?php
/*
 * Created on   : Fri Sep 18 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FolderSymlinkTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Helper\FileSystem\{Files, Folder};
use Tests\Contracts\BaseTestCase;

/**
 * Löschen und Auflisten dürfen symbolischen Links nicht folgen: ein Link im
 * Arbeitsverzeichnis löschte sonst das Linkziel mit (bis v1.35.1 belegt).
 */
class FolderSymlinkTest extends BaseTestCase {
    private string $base;

    protected function setUp(): void {
        parent::setUp();
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Symlinks brauchen unter Windows erhöhte Rechte.');
        }
        $this->base = sys_get_temp_dir() . '/folder_symlink_test_' . uniqid();
        mkdir($this->base . '/work/sub', 0755, true);
        mkdir($this->base . '/outside', 0755, true);
        file_put_contents($this->base . '/outside/keep.txt', 'wichtig');
        file_put_contents($this->base . '/work/sub/own.txt', 'eigene Datei');
    }

    protected function tearDown(): void {
        if (isset($this->base)) {
            exec('rm -rf ' . escapeshellarg($this->base));
        }
        parent::tearDown();
    }

    public function test_delete_removes_nested_link_but_keeps_its_target(): void {
        symlink($this->base . '/outside', $this->base . '/work/sub/link');

        Folder::delete($this->base . '/work', true);

        $this->assertDirectoryDoesNotExist($this->base . '/work');
        $this->assertFileExists($this->base . '/outside/keep.txt');
    }

    public function test_delete_of_a_link_removes_only_the_link(): void {
        symlink($this->base . '/outside', $this->base . '/work/link');

        Folder::delete($this->base . '/work/link', true);

        $this->assertFalse(is_link($this->base . '/work/link'));
        $this->assertFileExists($this->base . '/outside/keep.txt');
    }

    public function test_delete_removes_file_links_without_touching_the_file(): void {
        symlink($this->base . '/outside/keep.txt', $this->base . '/work/sub/file-link');

        Folder::delete($this->base . '/work', true);

        $this->assertDirectoryDoesNotExist($this->base . '/work');
        $this->assertSame('wichtig', file_get_contents($this->base . '/outside/keep.txt'));
    }

    public function test_clean_removes_links_and_keeps_their_targets(): void {
        symlink($this->base . '/outside', $this->base . '/work/dir-link');
        symlink($this->base . '/outside/keep.txt', $this->base . '/work/file-link');

        Folder::clean($this->base . '/work');

        $this->assertDirectoryExists($this->base . '/work');
        $this->assertSame([], array_values(array_diff(scandir($this->base . '/work'), ['.', '..'])));
        $this->assertFileExists($this->base . '/outside/keep.txt');
    }

    public function test_get_does_not_descend_into_linked_directories_by_default(): void {
        mkdir($this->base . '/outside/deep');
        symlink($this->base . '/outside', $this->base . '/work/link');

        $dirs = Folder::get($this->base . '/work', true);
        sort($dirs);

        $work = realpath($this->base . '/work');
        $this->assertSame([$work . '/link', $work . '/sub'], $dirs);
    }

    public function test_get_follows_links_on_request_and_survives_cycles(): void {
        mkdir($this->base . '/outside/deep');
        symlink($this->base . '/outside', $this->base . '/work/link');
        symlink($this->base . '/work', $this->base . '/outside/back');

        $dirs = Folder::get($this->base . '/work', true, true);

        $this->assertContains($this->base . '/work/link/deep', $dirs);
    }

    public function test_files_get_skips_linked_directories_by_default(): void {
        symlink($this->base . '/outside', $this->base . '/work/link');

        $files = Files::get($this->base . '/work', true);

        $this->assertSame([$this->base . '/work/sub/own.txt'], $files);
    }

    public function test_files_get_follows_links_on_request_and_survives_cycles(): void {
        symlink($this->base . '/outside', $this->base . '/work/link');
        symlink($this->base . '/work', $this->base . '/outside/back');

        $files = Files::get($this->base . '/work', true, followSymlinks: true);
        sort($files);

        $this->assertSame([$this->base . '/work/link/keep.txt', $this->base . '/work/sub/own.txt'], $files);
    }
}
