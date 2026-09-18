<?php
/*
 * Created on   : Fri Sep 18 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ShellResult.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Executables;

/**
 * Ergebnis von {@see \CommonToolkit\Helper\Shell::run()}.
 */
final class ShellResult {
    /**
     * @param int|null $exitCode Exit-Code; null, wenn der Prozess abgebrochen wurde (Timeout).
     * @param string $output Standardausgabe (leer, wenn sie an einen Callback ging).
     * @param string $errorOutput Fehlerausgabe.
     * @param bool $timedOut Ob der Prozess wegen Zeitüberschreitung beendet wurde.
     * @param float $duration Laufzeit in Sekunden.
     */
    public function __construct(
        public readonly ?int $exitCode,
        public readonly string $output,
        public readonly string $errorOutput,
        public readonly bool $timedOut,
        public readonly float $duration,
    ) {}

    /** Exit-Code 0 und kein Timeout. */
    public function isSuccessful(): bool {
        return $this->exitCode === 0 && !$this->timedOut;
    }
}
