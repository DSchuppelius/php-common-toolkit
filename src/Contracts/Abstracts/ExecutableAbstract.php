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

use CommonToolkit\Helper\Platform;

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
        $config['path'] = $config['path'] ?? $this->resolveOsSpecificValue($config, 'Path');
        $config['arguments'] = $this->resolveOsSpecificValue($config, 'Arguments', $config['arguments'] ?? []);
        $config['debugArguments'] = $this->resolveOsSpecificValue($config, 'DebugArguments', $config['debugArguments'] ?? []);

        return $config;
    }

    protected function resolveOsSpecificValue(array $config, string $name, mixed $fallback = null): mixed {
        $osKey = Platform::isWindows() ? "windows{$name}" : "linux{$name}";
        return $config[$osKey] ?? $fallback;
    }

    protected function prepareArguments(array $overrideArgs = []): array {
        $baseArgs = $this->args ?? [];
        $resolvedArgs = [];
        $usedKeys = [];

        foreach ($baseArgs as $arg) {
            if (is_string($arg)) {
                foreach ($overrideArgs as $key => $value) {
                    if (is_string($key) && str_contains($arg, $key)) {
                        $arg = str_replace($key, $value, $arg);
                        $usedKeys[] = $key;
                    }
                }
            }
            $resolvedArgs[] = $arg;
        }

        foreach ($overrideArgs as $key => $value) {
            if (is_int($key)) {
                $resolvedArgs[] = $value;
            } elseif (!in_array($key, $usedKeys, true)) {
                $resolvedArgs[] = $value;
            }
        }

        return $resolvedArgs;
    }

    abstract public function execute(array $overrideArgs = []): string;
}
