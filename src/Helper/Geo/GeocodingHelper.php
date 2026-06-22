<?php
/*
 * Created on   : Sun Mar 16 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GeocodingHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Geo;

use CommonToolkit\Helper\Data\JsonHelper;
use CommonToolkit\Helper\FileSystem\File;
use ERRORToolkit\Traits\ErrorLog;

/**
 * Reverse Geocoding Helper für Koordinaten → Ortsnamen.
 * Nutzt Nominatim (OpenStreetMap) – konfigurierbar für eigenen Server.
 *
 * Standardmäßig wird das öffentliche Nominatim mit Rate-Limiting verwendet.
 * Eigene Server / abweichende Optionen werden zur Laufzeit injiziert:
 *
 *     GeocodingHelper::configure([
 *         'url' => 'http://nominatim.intern:8080/reverse',
 *         'rate_limit' => false,
 *         'user_agent' => 'MyApp/1.0 (+https://example.org)',
 *     ]);
 *
 * Erkannte Schlüssel: url, rate_limit, rate_limit_interval, user_agent, timeout, language.
 */
final class GeocodingHelper {
    use ErrorLog;

    /** Cache für bereits aufgelöste Koordinaten */
    private static array $cache = [];

    /** Zeitpunkt des letzten Requests (Rate-Limiting) */
    private static float $lastRequestTime = 0;

    /** Aufgelöste Konfiguration (Defaults + Overrides), lazy */
    private static ?array $config = null;

    /** Laufzeit-Overrides via configure() */
    private static array $overrides = [];

    /** Cache-Datei (persistenter Cache) */
    private static ?string $cacheFile = null;

    /** Default-Werte (öffentliches Nominatim) */
    private const DEFAULTS = [
        'url' => 'https://nominatim.openstreetmap.org/reverse',
        'rate_limit' => true,
        'rate_limit_interval' => 1.1,
        'user_agent' => 'php-common-toolkit/1.0 (+https://github.com/Daniel-Jorg-Schuppelius/php-common-toolkit)',
        'timeout' => 5,
        'language' => 'de',
    ];

    /**
     * Injiziert Konfigurations-Overrides (z.B. aus einer App-Config gelesen).
     * Nur bekannte Schlüssel werden übernommen; unbekannte werden ignoriert.
     *
     * @param array<string, mixed> $overrides Teilmenge von url/rate_limit/rate_limit_interval/user_agent/timeout/language
     */
    public static function configure(array $overrides): void {
        $filtered = [];
        foreach (self::DEFAULTS as $key => $_) {
            if (array_key_exists($key, $overrides) && $overrides[$key] !== null) {
                $filtered[$key] = $overrides[$key];
            }
        }
        self::$overrides = $filtered;
        self::$config = null; // Neuauflösung erzwingen

        if (isset($filtered['url']) && $filtered['url'] !== self::DEFAULTS['url']) {
            self::logDebug('Nominatim: Eigener Server konfiguriert', ['url' => $filtered['url']]);
        }
    }

    /**
     * Liefert die aktuelle Konfiguration (Defaults überschrieben durch configure()).
     */
    private static function loadConfig(): array {
        if (self::$config !== null) {
            return self::$config;
        }

        self::$config = array_merge(self::DEFAULTS, self::$overrides);
        return self::$config;
    }

    /**
     * Löst Koordinaten in einen lesbaren Ortsnamen auf.
     *
     * @param float $lat Latitude (Breitengrad)
     * @param float $lng Longitude (Längengrad)
     * @param bool $shortFormat Kurzes Format (Stadt) oder lang (Straße, Stadt)
     * @return string|null Ortsname oder null bei Fehler
     */
    public static function reverseGeocode(float $lat, float $lng, bool $shortFormat = true): ?string {
        $config = self::loadConfig();

        // Runde auf 5 Dezimalstellen für Cache-Effizienz (~1m Genauigkeit)
        $lat = round($lat, 5);
        $lng = round($lng, 5);
        $cacheKey = "{$lat},{$lng}";

        // Cache prüfen
        if (isset(self::$cache[$cacheKey])) {
            return self::formatPlace(self::$cache[$cacheKey], $shortFormat);
        }

        // Persistenten Cache laden (einmalig)
        self::loadPersistentCache();
        if (isset(self::$cache[$cacheKey])) {
            return self::formatPlace(self::$cache[$cacheKey], $shortFormat);
        }

        // Rate-Limiting (nur wenn aktiviert - bei eigenem Server deaktivieren!)
        if ($config['rate_limit']) {
            $now = microtime(true);
            $elapsed = $now - self::$lastRequestTime;
            $interval = (float) $config['rate_limit_interval'];
            if ($elapsed < $interval) {
                usleep((int) (($interval - $elapsed) * 1_000_000));
            }
        }

        // Nominatim Request
        $url = $config['url'] . '?' . http_build_query([
            'lat' => $lat,
            'lon' => $lng,
            'format' => 'json',
            'addressdetails' => 1,
            'zoom' => 16,
            'accept-language' => $config['language'],
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: ' . $config['user_agent'],
                'timeout' => (int) $config['timeout'],
            ],
        ]);

        self::$lastRequestTime = microtime(true);

        try {
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                self::logDebug('Nominatim request failed', ['lat' => $lat, 'lng' => $lng, 'url' => $config['url']]);
                return null;
            }

            $data = JsonHelper::decode($response, true);
            if (!$data || !isset($data['address'])) {
                return null;
            }

            // In Cache speichern
            self::$cache[$cacheKey] = $data['address'];
            self::savePersistentCache();

            return self::formatPlace($data['address'], $shortFormat);
        } catch (\Throwable $e) {
            self::logDebug('Geocoding error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Batch-Geocoding für mehrere Koordinaten.
     * Nutzt Cache effizient und respektiert Rate-Limits.
     *
     * @param array<int, array{lat: float, lng: float}> $coordinates
     * @param bool $shortFormat
     * @return array<int, string|null> Ortsnamen (gleiche Indizes wie Input)
     */
    public static function batchReverseGeocode(array $coordinates, bool $shortFormat = true): array {
        $results = [];
        foreach ($coordinates as $idx => $coord) {
            $results[$idx] = self::reverseGeocode($coord['lat'], $coord['lng'], $shortFormat);
        }
        return $results;
    }

    /**
     * Formatiert Adress-Daten als lesbaren String.
     */
    private static function formatPlace(array $address, bool $shortFormat): string {
        if ($shortFormat) {
            // Kurzes Format: Stadt/Ort
            return $address['city']
                ?? $address['town']
                ?? $address['village']
                ?? $address['municipality']
                ?? $address['suburb']
                ?? $address['county']
                ?? $address['state']
                ?? 'Unbekannt';
        }

        // Langes Format: Straße, Stadt
        $parts = [];

        // Straße + Hausnummer
        if (isset($address['road'])) {
            $road = $address['road'];
            if (isset($address['house_number'])) {
                $road .= ' ' . $address['house_number'];
            }
            $parts[] = $road;
        }

        // Stadtteil (optional)
        if (isset($address['suburb'])) {
            $parts[] = $address['suburb'];
        }

        // Stadt
        $city = $address['city']
            ?? $address['town']
            ?? $address['village']
            ?? $address['municipality']
            ?? null;

        if ($city) {
            $parts[] = $city;
        }

        return $parts ? implode(', ', $parts) : 'Unbekannt';
    }

    /**
     * Lädt persistenten Cache aus Datei.
     */
    private static function loadPersistentCache(): void {
        if (self::$cacheFile !== null) {
            return; // Bereits geladen
        }

        self::$cacheFile = sys_get_temp_dir() . '/commontoolkit_geocache.json';

        if (file_exists(self::$cacheFile)) {
            try {
                $content = File::read(self::$cacheFile);
                $data = JsonHelper::decode($content, true);
                if (is_array($data)) {
                    self::$cache = array_merge($data, self::$cache);
                    self::logDebug('Geocache loaded', ['entries' => count($data)]);
                }
            } catch (\Throwable) {
                self::logDebug('Geocache konnte nicht geladen werden');
            }
        }
    }

    /**
     * Speichert Cache persistent.
     */
    private static function savePersistentCache(): void {
        if (self::$cacheFile === null) {
            return;
        }

        // Begrenze Cache-Größe (max 10.000 Einträge)
        if (count(self::$cache) > 10000) {
            self::$cache = array_slice(self::$cache, -5000, null, true);
        }

        try {
            File::write(
                self::$cacheFile,
                JsonHelper::encode(self::$cache, JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable) {
            self::logDebug('Geocache konnte nicht gespeichert werden');
        }
    }

    /**
     * Leert den Cache (für Tests).
     */
    public static function clearCache(): void {
        self::$cache = [];
        if (self::$cacheFile && file_exists(self::$cacheFile)) {
            try {
                File::delete(self::$cacheFile);
            } catch (\Throwable) {
                // Ignorieren – Cache-Datei ist nicht kritisch
            }
        }
        self::$cacheFile = null;
    }

    /**
     * Setzt Config & Overrides zurück (für Tests).
     */
    public static function resetConfig(): void {
        self::$config = null;
        self::$overrides = [];
    }

    /**
     * Gibt die konfigurierte Nominatim-URL zurück.
     */
    public static function getConfiguredUrl(): string {
        $config = self::loadConfig();
        return $config['url'];
    }

    /**
     * Prüft ob Rate-Limiting aktiviert ist.
     */
    public static function isRateLimitEnabled(): bool {
        $config = self::loadConfig();
        return (bool) $config['rate_limit'];
    }

    /**
     * Prüft ob Geocoding verfügbar ist (Server erreichbar).
     */
    public static function isAvailable(): bool {
        $config = self::loadConfig();

        // Status-URL ableiten (bei /reverse → /status probieren)
        $baseUrl = preg_replace('#/reverse$#', '', $config['url']);
        $statusUrl = $baseUrl . '/status';

        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'header' => 'User-Agent: ' . $config['user_agent'],
                'timeout' => 2,
            ],
        ]);

        $headers = @get_headers($statusUrl, context: $context);
        return $headers !== false;
    }
}
