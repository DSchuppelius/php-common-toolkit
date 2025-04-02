<?php
/*
 * Created on   : Tue Apr 01 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ShellChardet.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Helper\Shell;

use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;

class ShellChardet {
    use ErrorLog;

    private static $process = null;
    private static $stdin = null;
    private static $stdout = null;

    public static function start(): void {
        if (self::$process !== null) return;

        $descriptorspec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"], // stderr
        ];

        $process = proc_open('chardet -', $descriptorspec, $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException("chardet konnte nicht gestartet werden.");
        }

        self::$process = $process;
        self::$stdin = $pipes[0];
        self::$stdout = $pipes[1];
    }

    public static function detect(string $text): string|false {
        self::start();

        if (!is_resource(self::$stdin) || !is_resource(self::$stdout)) {
            self::logError("ShellChardet: stdin oder stdout ist keine Ressource.");
            return false;
        }

        // Wichtig: Zeilenumbruch als Abschluss für chardet
        fwrite(self::$stdin, $text . "\n");
        fflush(self::$stdin);

        // fgets blockiert, wenn keine \n kommt → chardet muss auch liefern
        $result = fgets(self::$stdout);

        if ($result === false) {
            self::logError("ShellChardet: Keine Antwort von chardet erhalten.");
            return false;
        }

        $result = trim($result);

        if ($result === 'ISO-8859-1' || $result === 'MacRoman') {
            $result = 'ISO-8859-15';
        } elseif ($result === 'None') {
            $result = 'UTF-8';
        }

        if (str_contains($result, 'UTF') || str_contains($result, 'utf')) {
            setlocale(LC_CTYPE, 'de_DE.UTF-8');
        } else {
            setlocale(LC_CTYPE, 'de_DE@euro', 'de_DE');
        }

        return $result;
    }

    public static function stop(): void {
        if (self::$process !== null) {
            fclose(self::$stdin);
            fclose(self::$stdout);
            proc_terminate(self::$process);
            proc_close(self::$process);
            self::$stdin = null;
            self::$stdout = null;
            self::$process = null;
        }
    }

    public function __destruct() {
        self::stop();
    }
}
