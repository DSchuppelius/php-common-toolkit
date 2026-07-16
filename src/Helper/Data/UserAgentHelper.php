<?php
/*
 * Created on   : Wed Jul 16 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : UserAgentHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;

/**
 * Leichte, abhängigkeitsfreie Klassifikation von HTTP-User-Agent-Strings in
 * Browser, Betriebssystem und Gerätetyp — für Anzeige-/Protokollzwecke
 * (z. B. Sitzungsübersichten), NICHT für sicherheitskritische Entscheidungen
 * (User-Agents sind fälschbar).
 *
 * Bewusst heuristisch und ohne externe Datenbank: erkannt werden die gängigen
 * Desktop-/Mobil-Browser, die verbreiteten Betriebssysteme sowie typische
 * Bots/Crawler. Nicht Erkanntes wird als "Unknown" zurückgegeben statt zu raten.
 */
class UserAgentHelper extends HelperAbstract {
    public const UNKNOWN = 'Unknown';

    public const DEVICE_DESKTOP = 'desktop';
    public const DEVICE_MOBILE = 'mobile';
    public const DEVICE_TABLET = 'tablet';
    public const DEVICE_BOT = 'bot';
    public const DEVICE_UNKNOWN = 'unknown';

    /**
     * Zerlegt einen User-Agent in seine Anzeige-Bestandteile.
     *
     * @param string|null $ua Roher User-Agent-Header.
     * @return array{browser: string, os: string, device: string, is_bot: bool}
     */
    public static function parse(?string $ua): array {
        $bot = self::isBot($ua);

        return [
            'browser' => self::browser($ua),
            'os' => self::os($ua),
            'device' => self::deviceType($ua),
            'is_bot' => $bot,
        ];
    }

    /**
     * Ermittelt den Browsernamen (ohne Version). Reihenfolge ist relevant, da
     * viele Browser sich als Chrome/Safari ausgeben (Edge/Opera/Samsung zuerst).
     */
    public static function browser(?string $ua): string {
        if ($ua === null || $ua === '') {
            return self::UNKNOWN;
        }

        return match (true) {
            self::contains($ua, ['Edg/', 'Edge/', 'EdgA/', 'EdgiOS/']) => 'Edge',
            self::contains($ua, ['OPR/', 'Opera']) => 'Opera',
            self::contains($ua, ['SamsungBrowser']) => 'Samsung Internet',
            self::contains($ua, ['Firefox/', 'FxiOS/']) => 'Firefox',
            self::contains($ua, ['CriOS/']) => 'Chrome',
            self::contains($ua, ['Chromium/']) => 'Chromium',
            self::contains($ua, ['Chrome/']) => 'Chrome',
            // Safari nur, wenn es sich auch als Safari-Version ausweist —
            // reine "Safari/"-Tokens tauchen sonst in vielen WebViews auf.
            self::contains($ua, ['Version/']) && self::contains($ua, ['Safari/']) => 'Safari',
            self::contains($ua, ['MSIE', 'Trident/']) => 'Internet Explorer',
            self::contains($ua, ['curl/']) => 'curl',
            self::contains($ua, ['Wget/']) => 'Wget',
            self::contains($ua, ['PostmanRuntime/']) => 'Postman',
            default => self::UNKNOWN,
        };
    }

    /**
     * Ermittelt das Betriebssystem. iOS vor macOS prüfen (iPad meldet teils
     * "Mac OS X"); Android vor Linux (Android enthält "Linux").
     */
    public static function os(?string $ua): string {
        if ($ua === null || $ua === '') {
            return self::UNKNOWN;
        }

        return match (true) {
            self::contains($ua, ['Windows NT', 'Windows Phone', 'Windows']) => 'Windows',
            self::contains($ua, ['iPhone', 'iPad', 'iPod', 'iOS', 'FxiOS/', 'CriOS/', 'EdgiOS/']) => 'iOS',
            self::contains($ua, ['Android']) => 'Android',
            self::contains($ua, ['CrOS']) => 'ChromeOS',
            self::contains($ua, ['Mac OS X', 'Macintosh']) => 'macOS',
            self::contains($ua, ['Linux', 'X11']) => 'Linux',
            default => self::UNKNOWN,
        };
    }

    /**
     * Grobe Geräteklasse. Bots gewinnen vor allem anderen; Tablets vor Mobile,
     * da iPad/Android-Tablet sonst als Mobile durchgingen.
     */
    public static function deviceType(?string $ua): string {
        if ($ua === null || $ua === '') {
            return self::DEVICE_UNKNOWN;
        }
        if (self::isBot($ua)) {
            return self::DEVICE_BOT;
        }
        if (self::contains($ua, ['iPad', 'Tablet']) || (self::contains($ua, ['Android']) && !self::contains($ua, ['Mobile']))) {
            return self::DEVICE_TABLET;
        }
        if (self::contains($ua, ['Mobile', 'iPhone', 'iPod', 'Windows Phone'])) {
            return self::DEVICE_MOBILE;
        }
        if (self::contains($ua, ['Windows NT', 'Macintosh', 'X11', 'CrOS', 'Linux'])) {
            return self::DEVICE_DESKTOP;
        }

        return self::DEVICE_UNKNOWN;
    }

    /**
     * Erkennt gängige Bots/Crawler/HTTP-Clients anhand verbreiteter Tokens.
     */
    public static function isBot(?string $ua): bool {
        if ($ua === null || $ua === '') {
            return false;
        }

        return self::contains($ua, [
            'bot', 'Bot', 'crawl', 'Crawl', 'spider', 'Spider', 'slurp', 'Slurp',
            'bingpreview', 'facebookexternalhit', 'WhatsApp', 'Telegram',
            'Googlebot', 'Applebot', 'DuckDuckBot', 'Baiduspider', 'YandexBot',
            'AhrefsBot', 'SemrushBot', 'HeadlessChrome', 'PhantomJS',
            'python-requests', 'Go-http-client', 'okhttp', 'Java/', 'libwww-perl',
            'curl/', 'Wget/', 'PostmanRuntime/',
        ]);
    }

    /**
     * Kompaktes Anzeige-Label, z. B. "Chrome · Windows" oder "Bot". Fällt auf
     * die jeweils bekannten Teile zurück und liefert nie einen leeren String.
     */
    public static function shortLabel(?string $ua): string {
        if ($ua === null || $ua === '') {
            return self::UNKNOWN;
        }
        if (self::isBot($ua)) {
            $browser = self::browser($ua);

            return $browser !== self::UNKNOWN ? $browser : 'Bot';
        }

        $browser = self::browser($ua);
        $os = self::os($ua);

        if ($browser === self::UNKNOWN && $os === self::UNKNOWN) {
            return self::UNKNOWN;
        }
        if ($browser === self::UNKNOWN) {
            return $os;
        }
        if ($os === self::UNKNOWN) {
            return $browser;
        }

        return $browser . ' · ' . $os;
    }

    /**
     * Prüft, ob einer der Nadeln im Heuhaufen vorkommt (case-sensitiv, da die
     * Tokens bewusst mit realer Groß-/Kleinschreibung gepflegt sind).
     *
     * @param list<string> $needles
     */
    private static function contains(string $haystack, array $needles): bool {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
