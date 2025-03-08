<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileSystemInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Interfaces;

interface FileSystemInterface {
    public static function exists(string $object): bool;
    public static function rename(string $oldName, string $newName): void;
    public static function delete(string $object): void;
    public static function move(string $sourceObject, string $destinationObject): void;
    public static function create(string $object, int $permissions = 0755): void;
}
