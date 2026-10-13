<?php

// Eingabe fuer NoDirectProcessCallRuleTest - wird analysiert, nie ausgefuehrt.

declare(strict_types=1);

namespace Tests\PHPStan\data;

use Symfony\Component\Process\Process;

function freieFunktion(): void {
    exec('ls');
}

class Datenbank {
    public function exec(string $sql): int {
        return 0;
    }
}

class Protokoll {
    public static function system(string $aktion): void {}
}

class Fremd {
    public function verboten(): void {
        $output = [];
        exec('ls', $output);
        \shell_exec('ls');
        system('ls');
        passthru('ls');
        popen('ls', 'r');
        proc_open('ls', [], $pipes);
        $text = `ls`;
        new Process(['ls']);
        Process::fromShellCommandline('ls');
        $schliessen = static fn () => exec('ls');
    }

    public function erlaubt(Datenbank $db): void {
        $db->exec('SELECT 1');
        Protokoll::system('anmeldung');
        $name = 'exec';
        $name('ls');
    }
}

class ErlaubterWrapper {
    public function starte(): void {
        exec('ls');
        new Process(['ls']);
        $wert = `ls`;
    }
}
