<?php
/*
 * Created on   : Tue Oct 13 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : NoDirectProcessCallRule.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\{FuncCall, New_, ShellExec, StaticCall};
use PhpParser\Node\{Identifier, Name};
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\{Rule, RuleErrorBuilder};

/**
 * PHPStan-Regel: Prozesse startet nur {@see \CommonToolkit\Helper\Shell}.
 *
 * Meldet ausserhalb der erlaubten Klassen exec(), shell_exec(), system(),
 * passthru(), popen(), proc_open(), pcntl_exec(), den Backtick-Operator und
 * Symfony Process (`new Process`, `Process::fromShellCommandline()`). Diese
 * Wege haben keine Zeitgrenze bzw. beenden beim Abbruch nur den direkten
 * Kindprozess - ein haengendes Werkzeug blockierte so den Worker dauerhaft.
 *
 * Einbinden ueber die mitgelieferte Konfiguration:
 *
 *     includes:
 *         - vendor/dschuppelius/php-common-toolkit/phpstan-rules.neon
 *
 * Eigene Prozess-Wrapper als erlaubte Klassen ergaenzen:
 *
 *     parameters:
 *         processCallAllowedClasses:
 *             - Mein\Namespace\Wrapper
 *
 * Nicht erkannt werden dynamische Aufrufe (`$f = 'exec'; $f()`,
 * `call_user_func('exec', ...)`).
 *
 * @implements Rule<Expr>
 */
final class NoDirectProcessCallRule implements Rule {
    public const IDENTIFIER = 'commonToolkit.processCall';

    /** Funktionen, die ein externes Programm starten. */
    private const FUNCTIONS = ['exec', 'shell_exec', 'system', 'passthru', 'popen', 'proc_open', 'pcntl_exec'];

    private const SYMFONY_PROCESS = 'Symfony\\Component\\Process\\Process';

    /** @var array<string, true> Erlaubte Klassen, klein geschrieben. */
    private array $allowedClasses = [];

    /**
     * @param list<string> $allowedClasses Klassen, in denen Prozessaufrufe erlaubt sind.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        array $allowedClasses = ['CommonToolkit\\Helper\\Shell'],
    ) {
        foreach ($allowedClasses as $class) {
            $this->allowedClasses[strtolower(ltrim($class, '\\'))] = true;
        }
    }

    public function getNodeType(): string {
        return Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array {
        $call = $this->describe($node, $scope);
        if ($call === null || $this->isAllowed($scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                '%s startet einen Prozess ohne Zeitgrenze bzw. ohne Abbruch des Prozessbaums - '
                    . 'Shell::executeShellCommand(), Shell::run() oder Shell::executeParallel() verwenden.',
                $call,
            ))
                ->identifier(self::IDENTIFIER)
                ->build(),
        ];
    }

    /**
     * Kurzname des Prozessaufrufs fuer die Meldung; null, wenn der Knoten keiner ist.
     */
    private function describe(Node $node, Scope $scope): ?string {
        if ($node instanceof ShellExec) {
            return 'Der Backtick-Operator';
        }

        if ($node instanceof FuncCall && $node->name instanceof Name) {
            if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
                return null;
            }
            $name = strtolower($this->reflectionProvider->getFunction($node->name, $scope)->getName());

            return in_array($name, self::FUNCTIONS, true) ? $name . '()' : null;
        }

        if ($node instanceof New_ && $node->class instanceof Name) {
            return $this->isSymfonyProcess($scope->resolveName($node->class)) ? 'new Process()' : null;
        }

        if ($node instanceof StaticCall && $node->class instanceof Name && $node->name instanceof Identifier
            && $node->name->toLowerString() === 'fromshellcommandline') {
            return $this->isSymfonyProcess($scope->resolveName($node->class)) ? 'Process::fromShellCommandline()' : null;
        }

        return null;
    }

    private function isSymfonyProcess(string $className): bool {
        if (strcasecmp(ltrim($className, '\\'), self::SYMFONY_PROCESS) === 0) {
            return true;
        }

        return $this->reflectionProvider->hasClass($className)
            && $this->reflectionProvider->getClass($className)->is(self::SYMFONY_PROCESS);
    }

    private function isAllowed(Scope $scope): bool {
        $class = $scope->getClassReflection();

        return $class !== null && isset($this->allowedClasses[strtolower($class->getName())]);
    }
}
