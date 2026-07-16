<?php
/*
 * Created on   : Wed Jul 16 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : IpLocationHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Geo;

use CommonToolkit\Helper\Data\IPHelper;
use ERRORToolkit\Traits\ErrorLog;
use MaxMind\Db\Reader;
use Throwable;

/**
 * Grobe IP-Geolokalisierung (Land/Stadt) gegen eine LOKALE MaxMind-`.mmdb`-
 * Datenbank — ohne externen Netzwerk-Call, datenschutzfreundlich für den
 * On-Premise-Betrieb. Kompatibel mit MaxMind GeoLite2 **und** DB-IP-Lite, da
 * beide dasselbe `.mmdb`-Format verwenden.
 *
 * Bewusst analog zu {@see GeocodingHelper}: per {@see configure()} injizierbar,
 * mit {@see isAvailable()} zur sauberen Degradation. Fehlt die Reader-Bibliothek
 * ODER die konfigurierte DB-Datei, liefert {@see lookup()} einfach `null` — der
 * Aufrufer zeigt dann nur die rohe IP.
 *
 * Die `.mmdb`-Datei selbst gehört NICHT ins Toolkit (Lizenz/Aktualität): Pfad
 * und Locale werden von der einbettenden Anwendung konfiguriert.
 */
final class IpLocationHelper {
    use ErrorLog;

    /** Aufgelöste Konfiguration (Defaults + Overrides), lazy. */
    private static ?array $config = null;

    /** Laufzeit-Overrides via configure(). */
    private static array $overrides = [];

    /** Geöffneter Reader je Datenbankpfad (Wiederverwendung pro Prozess). */
    private static ?Reader $reader = null;

    /** Pfad, für den {@see $reader} geöffnet wurde. */
    private static ?string $readerPath = null;

    /** Ergebnis-Cache je IP innerhalb des Prozesses. */
    private static array $cache = [];

    private const DEFAULTS = [
        'database' => null,   // absoluter Pfad zur .mmdb
        'locale' => 'en',     // bevorzugte Namenssprache
    ];

    /**
     * Injiziert Konfigurations-Overrides (z. B. aus einer App-Config gelesen).
     * Nur bekannte Schlüssel werden übernommen; unbekannte werden ignoriert.
     *
     * @param array<string, mixed> $overrides Teilmenge von database/locale
     */
    public static function configure(array $overrides): void {
        $filtered = [];
        foreach (self::DEFAULTS as $key => $_) {
            if (array_key_exists($key, $overrides) && $overrides[$key] !== null) {
                $filtered[$key] = $overrides[$key];
            }
        }
        self::$overrides = $filtered;
        self::$config = null;
        // Reader schließen, damit ein geänderter Pfad neu geöffnet wird.
        self::closeReader();
    }

    /**
     * True, wenn eine Auflösung technisch möglich ist: Reader-Bibliothek
     * vorhanden UND eine lesbare DB-Datei konfiguriert.
     */
    public static function isAvailable(): bool {
        if (!class_exists(Reader::class)) {
            return false;
        }
        $path = self::databasePath();

        return $path !== null && is_file($path) && is_readable($path);
    }

    /**
     * Löst eine IP in grobe Standortdaten auf. Liefert `null`, wenn keine DB
     * verfügbar ist, die IP nicht öffentlich/gültig ist oder kein Datensatz
     * gefunden wurde. Fehler werden geschluckt (nur geloggt) — eine
     * Standortanzeige darf nie den Aufrufer sprengen.
     *
     * @return array{country: string|null, country_iso: string|null, city: string|null}|null
     */
    public static function lookup(?string $ip): ?array {
        if ($ip === null || !IPHelper::isPublicIP($ip)) {
            return null;
        }
        if (array_key_exists($ip, self::$cache)) {
            return self::$cache[$ip];
        }
        if (!self::isAvailable()) {
            return null;
        }

        try {
            $reader = self::reader();
            $record = $reader?->get($ip);
            $result = self::mapRecord($record, (string) self::loadConfig()['locale']);
        } catch (Throwable $e) {
            self::logWarning('IP-Geolokalisierung fehlgeschlagen', ['ip' => $ip, 'error' => $e->getMessage()]);
            $result = null;
        }

        return self::$cache[$ip] = $result;
    }

    /**
     * Bildet einen rohen MaxMind-/DB-IP-Datensatz auf die normierte Struktur ab.
     * Defensiv, da Country-Level-DBs kein `city` liefern und einzelne Felder
     * fehlen können. Rückgabe `null`, wenn gar nichts Brauchbares enthalten ist.
     *
     * @param  mixed  $record  Rohdatensatz aus Reader::get()
     * @return array{country: string|null, country_iso: string|null, city: string|null}|null
     */
    public static function mapRecord(mixed $record, string $locale = 'en'): ?array {
        if (!is_array($record)) {
            return null;
        }

        $countryIso = null;
        if (isset($record['country']['iso_code']) && is_string($record['country']['iso_code'])) {
            $countryIso = $record['country']['iso_code'];
        }

        $country = isset($record['country']['names']) && is_array($record['country']['names'])
            ? self::nameFrom($record['country']['names'], $locale)
            : null;

        $city = isset($record['city']['names']) && is_array($record['city']['names'])
            ? self::nameFrom($record['city']['names'], $locale)
            : null;

        if ($countryIso === null && $country === null && $city === null) {
            return null;
        }

        return ['country' => $country, 'country_iso' => $countryIso, 'city' => $city];
    }

    /** Leert den prozessinternen Ergebnis-Cache. */
    public static function clearCache(): void {
        self::$cache = [];
    }

    /** Setzt Konfiguration, Cache und Reader zurück (v. a. für Tests). */
    public static function resetConfig(): void {
        self::$overrides = [];
        self::$config = null;
        self::$cache = [];
        self::closeReader();
    }

    // ───────────────────────── intern ──────────────────────────────────

    /**
     * Wählt aus den lokalisierten Namen den bevorzugten aus: konfigurierte
     * Locale → Englisch → erster verfügbarer.
     *
     * @param array<string, mixed> $names
     */
    private static function nameFrom(array $names, string $locale): ?string {
        foreach ([$locale, 'en'] as $key) {
            if (isset($names[$key]) && is_string($names[$key]) && $names[$key] !== '') {
                return $names[$key];
            }
        }
        foreach ($names as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private static function reader(): ?Reader {
        $path = self::databasePath();
        if ($path === null) {
            return null;
        }
        if (self::$reader !== null && self::$readerPath === $path) {
            return self::$reader;
        }
        self::closeReader();
        self::$reader = new Reader($path);
        self::$readerPath = $path;

        return self::$reader;
    }

    private static function closeReader(): void {
        if (self::$reader !== null) {
            try {
                self::$reader->close();
            } catch (Throwable) {
                // Schließen darf nie werfen.
            }
        }
        self::$reader = null;
        self::$readerPath = null;
    }

    private static function databasePath(): ?string {
        $path = self::loadConfig()['database'];

        return is_string($path) && $path !== '' ? $path : null;
    }

    /**
     * @return array{database: string|null, locale: string}
     */
    private static function loadConfig(): array {
        if (self::$config === null) {
            /** @var array{database: string|null, locale: string} $merged */
            $merged = array_merge(self::DEFAULTS, self::$overrides);
            self::$config = $merged;
        }

        return self::$config;
    }
}
