<?php
/*
 * Created on   : Mon Mar 31 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ExecutableAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Abstracts;

abstract class ExecutableAbstract {
    protected string $path;
    protected array $args = [];
    protected array $debugArgs = [];

    public function __construct(array $config) {
        $config = $this->normalizeExecutableConfig($config);

        $this->path = $config['path'] ?? '';
        $this->args = $config['arguments'] ?? [];
        $this->debugArgs = $config['debugArguments'] ?? [];
    }

    protected function normalizeExecutableConfig(array $config): array {
        $os = strtoupper(PHP_OS_FAMILY);
        $isWindows = $os === 'WINDOWS';

        $config['path'] = $config['path'] ?? ($isWindows ? ($config['windowsPath'] ?? '') : ($config['linuxPath'] ?? ''));

        if (isset($config['windowsArguments']) || isset($config['linuxArguments'])) {
            $config['arguments'] = $isWindows
                ? ($config['windowsArguments'] ?? $config['arguments'] ?? [])
                : ($config['linuxArguments'] ?? $config['arguments'] ?? []);
        }

        if (isset($config['windowsDebugArguments']) || isset($config['linuxDebugArguments'])) {
            $config['debugArguments'] = $isWindows
                ? ($config['windowsDebugArguments'] ?? $config['debugArguments'] ?? [])
                : ($config['linuxDebugArguments'] ?? $config['debugArguments'] ?? []);
        }

        return $config;
    }

    abstract public function execute(array $overrideArgs = []): string;
}
