<?php

declare(strict_types=1);

namespace CommonToolkit;

use ERRORToolkit\Exceptions\FileSystem\FolderNotFoundException;
use ReflectionClass;
use Exception;

class ClassLoader {
    /**
     * Lädt alle Klassen aus einem Verzeichnis, die ein bestimmtes Interface implementieren.
     *
     * @param string $directory Das Basisverzeichnis
     * @param string $namespace Der Namespace der Klassen
     * @param string $interface Das Interface, das die Klassen implementieren müssen
     * @return array Liste der geladenen Klassen
     * @throws Exception Falls das Verzeichnis nicht existiert
     */
    public static function loadClasses(string $directory, string $namespace, string $interface): array {
        if (!is_dir($directory)) {
            throw new FolderNotFoundException("Das Verzeichnis für Klassen konnte nicht aufgelöst werden: $directory");
        }

        $classes = [];
        $files = scandir($directory);

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            $className = $namespace . '\\' . pathinfo($file, PATHINFO_FILENAME);

            if (!class_exists($className)) {
                continue;
            }

            $reflectionClass = new ReflectionClass($className);
            if ($reflectionClass->isInstantiable() && $reflectionClass->implementsInterface($interface)) {
                $classes[] = $className;
            }
        }

        return $classes;
    }
}
