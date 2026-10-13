<?php
/*
 * Created on   : Tue Oct 13 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : NoDirectProcessCallRuleTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\PHPStan;

use CommonToolkit\PHPStan\NoDirectProcessCallRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoDirectProcessCallRule>
 */
class NoDirectProcessCallRuleTest extends RuleTestCase {
    protected function getRule(): Rule {
        return new NoDirectProcessCallRule(self::createReflectionProvider(), [
            'CommonToolkit\\Helper\\Shell',
            '\\Tests\\PHPStan\\data\\ErlaubterWrapper',
        ]);
    }

    /**
     * Die mitgelieferte Konfiguration muss sich laden lassen (Schema, Dienst, Parameter).
     * Die Eingabedateien unter data/ kennt PHPStan ueber die classmap in autoload-dev.
     *
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array {
        return [dirname(__DIR__, 2) . '/phpstan-rules.neon'];
    }

    public function test_meldet_direkte_prozessaufrufe_ausserhalb_der_erlaubten_klassen(): void {
        $this->analyse([__DIR__ . '/data/process-calls.php'], [
            [self::message('exec()'), 12],
            [self::message('exec()'), 28],
            [self::message('shell_exec()'), 29],
            [self::message('system()'), 30],
            [self::message('passthru()'), 31],
            [self::message('popen()'), 32],
            [self::message('proc_open()'), 33],
            [self::message('Der Backtick-Operator'), 34],
            [self::message('new Process()'), 35],
            [self::message('Process::fromShellCommandline()'), 36],
            // auch in einer Closure innerhalb der Klasse
            [self::message('exec()'), 37],
        ]);
    }

    public function test_eigene_funktion_gleichen_namens_ist_kein_prozessaufruf(): void {
        $this->analyse([__DIR__ . '/data/own-exec-function.php'], []);
    }

    public function test_mitgelieferte_konfiguration_erlaubt_nur_shell(): void {
        $container = self::getContainer();

        $this->assertSame(['CommonToolkit\\Helper\\Shell'], $container->getParameter('processCallAllowedClasses'));
        $rules = array_filter(
            $container->getServicesByTag('phpstan.rules.rule'),
            static fn (object $rule): bool => $rule instanceof NoDirectProcessCallRule,
        );
        $this->assertCount(1, $rules);
    }

    private static function message(string $call): string {
        return $call . ' startet einen Prozess ohne Zeitgrenze bzw. ohne Abbruch des Prozessbaums - '
            . 'Shell::executeShellCommand(), Shell::run() oder Shell::executeParallel() verwenden.';
    }
}
