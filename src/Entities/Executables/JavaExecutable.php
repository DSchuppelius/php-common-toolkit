<?php
/*
 * Created on   : Mon Mar 31 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : JavaExecutable.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Executables;

use CommonToolkit\Contracts\Abstracts\ExecutableAbstract;
use CommonToolkit\Helper\Java;
use Exception;

class JavaExecutable extends ExecutableAbstract {
    public function execute(array $overrideArgs = []): string {
        if (!Java::exists()) {
            throw new Exception("Java ist auf diesem System nicht verfügbar.");
        }

        return Java::execute($this->path, $this->prepareArguments($overrideArgs));
    }
}
