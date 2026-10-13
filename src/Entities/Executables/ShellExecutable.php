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
use CommonToolkit\Helper\Shell;

class ShellExecutable extends ExecutableAbstract {
    /**
     * Startet das Programm ohne Shell (Argumente einzeln, kein Escaping noetig)
     * mit der Standard-Zeitgrenze und liefert die Standardausgabe; leer, wenn es
     * nichts ausgibt, nicht startet oder abgebrochen wird.
     */
    public function execute(array $overrideArgs = []): string {
        if (trim($this->path) === '') {
            return '';
        }

        $result = Shell::run([$this->path, ...$this->prepareArguments($overrideArgs)], Shell::getDefaultTimeout());

        return $result->timedOut ? '' : $result->output;
    }
}
