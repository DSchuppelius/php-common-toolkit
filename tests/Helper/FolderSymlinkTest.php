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

        $this->assertSame([$this->base . '/work/link', $this->base . '/work/sub'], $dirs);
    }

    /** Ab v1.37: Pfade wie übergeben — ein Präfix-Vergleich unter einem Link-Pfad (Deploy über Symlink) bleibt gültig. */
    public function test_folder_get_keeps_the_given_path_prefix(): void {
        symlink($this->base . '/work', $this->base . '/current');

        $this->assertSame([$this->base . '/current/sub'], Folder::get($this->base . '/current/'));
        $this->assertSame([$this->base . '/current/sub/own.txt'], Files::get($this->base . '/current', true));
    }

    public function test_folder_get_skip_prunes_the_whole_subtree(): void {
        mkdir($this->base . '/work/vendor/pkg/src', 0755, true);
        $seen = [];

        $dirs = Folder::get($this->base . '/work', true, skip: static function (string $path) use (&$seen): bool {
            $seen[] = basename($path);

            return basename($path) === 'vendor';
        });

        $this->assertSame([$this->base . '/work/sub'], $dirs);
        $this->assertNotContains('pkg', $seen, 'Ein ausgelassenes Verzeichnis darf nicht betreten werden.');
    }

    public function test_files_get_skip_prunes_directories_and_drops_files(): void {
        mkdir($this->base . '/work/cache', 0755, true);
        file_put_contents($this->base . '/work/cache/big.bin', 'x');
        file_put_contents($this->base . '/work/sub/skip.log', 'x');
        $seen = [];

        $files = Files::get($this->base . '/work', true, skip: static function (string $path) use (&$seen): bool {
            $seen[] = $path;

            return basename($path) === 'cache' || str_ends_with($path, '.log');
        });

        $this->assertSame([$this->base . '/work/sub/own.txt'], $files);
        $this->assertNotContains($this->base . '/work/cache/big.bin', $seen);
    }

    public function test_is_directory_answers_without_following_files(): void {
        symlink($this->base . '/outside', $this->base . '/work/link');

        $this->assertTrue(Folder::isDirectory($this->base . '/work/sub'));
        $this->assertTrue(Folder::isDirectory($this->base . '/work/link'));
        $this->assertFalse(Folder::isDirectory($this->base . '/work/sub/own.txt'));
        $this->assertFalse(Folder::isDirectory($this->base . '/missing'));
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
