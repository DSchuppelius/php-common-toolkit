<?php
/*
 * Created on   : Mon Mar 31 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ShellExecutable.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Executables;

use CommonToolkit\Contracts\Abstracts\ExecutableAbstract;

class ShellExecutable extends ExecutableAbstract {
    public function execute(array $overrideArgs = []): string {
        $args = !empty($overrideArgs) ? $overrideArgs : $this->args;
        $cmd = escapeshellcmd($this->path . ' ' . implode(' ', $args));
        return shell_exec($cmd);
    }
}
