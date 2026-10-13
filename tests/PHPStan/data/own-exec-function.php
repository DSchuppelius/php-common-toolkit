<?php

// Eingabe fuer NoDirectProcessCallRuleTest: eine eigene Funktion namens exec ist kein Prozessaufruf.

declare(strict_types=1);

namespace Tests\PHPStan\data\Eigen;

function exec(string $befehl): string {
    return $befehl;
}

class Nutzer {
    public function rufe(): string {
        return exec('kein Prozess');
    }
}
