<?php
/*
 * Created on   : Mon Oct 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PersonNameHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use ERRORToolkit\Traits\ErrorLog;

/**
 * Zerlegt frei eingegebene Personennamen in Anrede, Titel, Vorname, weitere
 * Vornamen und Nachname und setzt sie wieder zusammen.
 *
 * Erkannt werden Anreden auf Deutsch, Englisch, Französisch, Spanisch und
 * Italienisch, akademische Titel vor dem Namen, Namenszusätze (von, van, de,
 * zu …) als Beginn des Nachnamens und die Form „Nachname, Vorname“. Ohne
 * Zusatz ist das letzte Wort der Nachname — Doppelvornamen wie „Hans Peter“
 * landen daher in Vorname und weiteren Vornamen.
 */
class PersonNameHelper {
    use ErrorLog;

    public const SALUTATION_MR = 'mr';

    public const SALUTATION_MS = 'ms';

    /** Anredewörter (klein, ohne Punkt) → kanonische Anrede. */
    private const SALUTATIONS = [
        'herr' => self::SALUTATION_MR, 'herrn' => self::SALUTATION_MR, 'hr' => self::SALUTATION_MR,
        'frau' => self::SALUTATION_MS, 'fr' => self::SALUTATION_MS,
        'mr' => self::SALUTATION_MR, 'mister' => self::SALUTATION_MR,
        'mrs' => self::SALUTATION_MS, 'ms' => self::SALUTATION_MS, 'miss' => self::SALUTATION_MS,
        'monsieur' => self::SALUTATION_MR,
        'madame' => self::SALUTATION_MS, 'mme' => self::SALUTATION_MS, 'mademoiselle' => self::SALUTATION_MS, 'mlle' => self::SALUTATION_MS,
        'señor' => self::SALUTATION_MR, 'senor' => self::SALUTATION_MR, 'sr' => self::SALUTATION_MR,
        'señora' => self::SALUTATION_MS, 'senora' => self::SALUTATION_MS, 'sra' => self::SALUTATION_MS,
        'señorita' => self::SALUTATION_MS, 'senorita' => self::SALUTATION_MS, 'srta' => self::SALUTATION_MS,
        'signor' => self::SALUTATION_MR, 'signore' => self::SALUTATION_MR, 'sig' => self::SALUTATION_MR,
        'signora' => self::SALUTATION_MS, 'sigra' => self::SALUTATION_MS,
        'signorina' => self::SALUTATION_MS, 'signa' => self::SALUTATION_MS,
    ];

    /** Nur mit Punkt eine Anrede; ohne Punkt eher eine Initiale („M. Dupont“). */
    private const SALUTATIONS_WITH_DOT = ['m' => self::SALUTATION_MR];

    /** Verbindet zwei Anreden („Herr und Frau Müller“). */
    private const COUPLE_JOINERS = ['und', '&', 'and', 'et', 'y', 'e'];

    /** Titelwörter; ohne Punkt zählen nur „Dr“ und „Prof“. */
    private const TITLE_PATTERN = '/^(prof|dr|dipl|mag|ing|lic|habil|med|dent|rer|nat|phil|jur|oec|pol|vet|theol|h\.c|apl|mba|m\.sc|b\.sc|m\.a|b\.a|ll\.m)(\b|[.-])/u';

    /** Namenszusätze, mit denen der Nachname beginnt. */
    private const PARTICLES = [
        'von', 'vom', 'van', 'de', 'der', 'den', 'des', 'del', 'della', 'delle', 'di', 'da', 'du',
        'la', 'le', 'zu', 'zum', 'zur', 'ter', 'ten', 'af', 'av', 'al', 'el', 'bin', 'ibn',
    ];

    /**
     * Zerlegt einen Personennamen.
     *
     * @return array{salutation: ?string, title: ?string, first_name: ?string, middle_names: ?string, last_name: ?string}
     */
    public static function split(?string $name): array {
        $parts = ['salutation' => null, 'title' => null, 'first_name' => null, 'middle_names' => null, 'last_name' => null];
        $name = StringHelper::normalizeWhitespace(trim((string) $name));
        if ($name === '') {
            return $parts;
        }

        $lastName = null;
        if (substr_count($name, ',') === 1) {
            [$before, $after] = array_map('trim', explode(',', $name));
            if ($before !== '' && $after !== '') {
                $lastName = $before;
                $name = $after;
            }
        }

        $tokens = explode(' ', $name);
        [$parts['salutation'], $tokens] = self::takeSalutation($tokens);
        [$parts['title'], $tokens] = self::takeTitles($tokens);

        if ($lastName !== null) {
            $parts['last_name'] = $lastName;
            $parts['first_name'] = array_shift($tokens);
            $parts['middle_names'] = $tokens !== [] ? implode(' ', $tokens) : null;

            return $parts;
        }

        $count = count($tokens);
        if ($count === 0) {
            return $parts;
        }
        if ($count === 1 || in_array(mb_strtolower($tokens[0]), self::PARTICLES, true)) {
            $parts['last_name'] = implode(' ', $tokens);

            return $parts;
        }

        $lastStart = $count - 1;
        for ($i = 1; $i < $count - 1; $i++) {
            if (in_array(mb_strtolower($tokens[$i]), self::PARTICLES, true)) {
                $lastStart = $i;

                break;
            }
        }

        $parts['first_name'] = $tokens[0];
        $parts['middle_names'] = $lastStart > 1 ? implode(' ', array_slice($tokens, 1, $lastStart - 1)) : null;
        $parts['last_name'] = implode(' ', array_slice($tokens, $lastStart));

        return $parts;
    }

    /**
     * Setzt den Namen ohne Anrede zusammen: Titel, Vorname, weitere Vornamen,
     * Nachname. Leere Teile entfallen.
     *
     * @param array{salutation?: ?string, title?: ?string, first_name?: ?string, middle_names?: ?string, last_name?: ?string} $parts
     */
    public static function join(array $parts): string {
        $present = [];
        foreach (['title', 'first_name', 'middle_names', 'last_name'] as $key) {
            $value = StringHelper::normalizeWhitespace(trim((string) ($parts[$key] ?? '')));
            if ($value !== '') {
                $present[] = $value;
            }
        }

        return implode(' ', $present);
    }

    /**
     * Trennt eine Anredezeile wie „Herr Dr.“ oder „Frau“ in kanonische Anrede
     * und Titel; unbekannte Wörter entfallen.
     *
     * @return array{salutation: ?string, title: ?string}
     */
    public static function splitSalutation(?string $text): array {
        $text = StringHelper::normalizeWhitespace(trim((string) $text));
        if ($text === '') {
            return ['salutation' => null, 'title' => null];
        }

        $tokens = explode(' ', $text);
        $salutation = self::salutationOf($tokens[0]);
        if ($salutation !== null) {
            array_shift($tokens);
        }
        $titles = array_values(array_filter($tokens, self::isTitle(...)));

        return ['salutation' => $salutation, 'title' => $titles !== [] ? implode(' ', $titles) : null];
    }

    /**
     * @param list<string> $tokens
     * @return array{0: ?string, 1: list<string>}
     */
    private static function takeSalutation(array $tokens): array {
        $salutation = self::salutationOf($tokens[0] ?? '');
        if ($salutation === null || count($tokens) < 2) {
            return [null, $tokens];
        }
        array_shift($tokens);

        // „Herr und Frau Müller“: zwei Personen, keine gemeinsame Anrede.
        if (count($tokens) >= 3 && in_array(mb_strtolower($tokens[0]), self::COUPLE_JOINERS, true) && self::salutationOf($tokens[1]) !== null) {
            return [null, array_slice($tokens, 2)];
        }

        return [$salutation, $tokens];
    }

    /**
     * @param list<string> $tokens
     * @return array{0: ?string, 1: list<string>}
     */
    private static function takeTitles(array $tokens): array {
        $titles = [];
        while (count($tokens) > 1 && self::isTitle($tokens[0])) {
            $titles[] = array_shift($tokens);
        }

        return [$titles !== [] ? implode(' ', $titles) : null, $tokens];
    }

    private static function salutationOf(string $token): ?string {
        $lower = mb_strtolower($token);
        $bare = str_replace('.', '', $lower);
        if (str_ends_with($lower, '.') && isset(self::SALUTATIONS_WITH_DOT[$bare])) {
            return self::SALUTATIONS_WITH_DOT[$bare];
        }

        return self::SALUTATIONS[$bare] ?? null;
    }

    private static function isTitle(string $token): bool {
        $lower = mb_strtolower($token);
        if (preg_match(self::TITLE_PATTERN, $lower) !== 1) {
            return false;
        }

        return str_contains($lower, '.') || in_array($lower, ['dr', 'prof'], true);
    }
}
