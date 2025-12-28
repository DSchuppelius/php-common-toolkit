<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : WebLinkHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use ERRORToolkit\Traits\ErrorLog;

/**
 * Helper-Klasse für die Validierung und Verarbeitung von URLs/Weblinks.
 *
 * Unterstützt:
 * - URL-Validierung (Schema, Host, Format)
 * - URL-Parsing und Komponenten-Extraktion
 * - URL-Normalisierung
 * - Query-String-Verarbeitung
 * - Domain-Extraktion
 *
 * @package CommonToolkit\Helper\Data
 */
class WebLinkHelper {
    use ErrorLog;

    /**
     * Erlaubte URL-Schemas.
     *
     * @var array<string>
     */
    private const ALLOWED_SCHEMES = [
        'http',
        'https',
        'ftp',
        'ftps',
        'mailto',
        'tel',
        'file',
    ];

    /**
     * Bekannte Top-Level-Domains (Auswahl der häufigsten).
     *
     * @var array<string>
     */
    private const COMMON_TLDS = [
        'com',
        'org',
        'net',
        'edu',
        'gov',
        'mil',
        'int',
        'de',
        'at',
        'ch',
        'uk',
        'fr',
        'it',
        'es',
        'nl',
        'be',
        'pl',
        'cz',
        'sk',
        'hu',
        'eu',
        'io',
        'co',
        'me',
        'info',
        'biz',
        'name',
        'mobi',
        'app',
        'dev',
        'cloud',
    ];

    /**
     * Prüft, ob eine URL ein gültiges Format hat.
     *
     * @param string|null $url Die zu prüfende URL.
     * @return bool True, wenn das Format gültig ist.
     */
    public static function isUrl(?string $url): bool {
        if ($url === null || $url === '') {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Prüft, ob eine URL ein gültiges HTTP(S)-Format hat.
     *
     * @param string|null $url Die zu prüfende URL.
     * @return bool True, wenn die URL eine gültige HTTP(S)-URL ist.
     */
    public static function isHttpUrl(?string $url): bool {
        if (!self::isUrl($url)) {
            return false;
        }

        $scheme = self::getScheme($url);
        return $scheme === 'http' || $scheme === 'https';
    }

    /**
     * Prüft, ob eine URL HTTPS verwendet.
     *
     * @param string|null $url Die zu prüfende URL.
     * @return bool True, wenn die URL HTTPS verwendet.
     */
    public static function isSecure(?string $url): bool {
        if (!self::isUrl($url)) {
            return false;
        }

        return self::getScheme($url) === 'https';
    }

    /**
     * Validiert eine URL mit erweiterten Prüfungen.
     *
     * @param string|null $url Die zu validierende URL.
     * @param bool $checkDns Bei true wird geprüft, ob der Host existiert.
     * @param array<string>|null $allowedSchemes Erlaubte Schemas (null = alle Standardschemas).
     * @return bool True, wenn die URL gültig ist.
     */
    public static function validateUrl(?string $url, bool $checkDns = false, ?array $allowedSchemes = null): bool {
        if (!self::isUrl($url)) {
            return false;
        }

        $scheme = self::getScheme($url);
        $allowedSchemes = $allowedSchemes ?? self::ALLOWED_SCHEMES;

        if (!in_array($scheme, $allowedSchemes, true)) {
            self::logDebug("URL-Schema '$scheme' ist nicht erlaubt.");
            return false;
        }

        $host = self::getHost($url);

        // mailto: und tel: haben keinen Host
        if (in_array($scheme, ['mailto', 'tel'], true)) {
            return true;
        }

        if ($host === null || $host === '') {
            self::logDebug("URL hat keinen gültigen Host: $url");
            return false;
        }

        if ($checkDns && function_exists('checkdnsrr')) {
            if (!@checkdnsrr($host, 'A') && !@checkdnsrr($host, 'AAAA')) {
                self::logDebug("DNS-Lookup für Host '$host' fehlgeschlagen.");
                return false;
            }
        }

        return true;
    }

    /**
     * Normalisiert eine URL.
     *
     * - Konvertiert Schema und Host zu Kleinbuchstaben
     * - Entfernt Standard-Ports (80 für HTTP, 443 für HTTPS)
     * - Entfernt trailing slashes (außer bei Root-Pfad)
     * - Sortiert Query-Parameter alphabetisch
     *
     * @param string|null $url Die zu normalisierende URL.
     * @return string|null Die normalisierte URL oder null bei ungültiger URL.
     */
    public static function normalize(?string $url): ?string {
        if (!self::isUrl($url)) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        // Schema und Host zu Kleinbuchstaben
        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : 'http';
        $host = isset($parts['host']) ? strtolower($parts['host']) : '';

        // Port nur wenn nicht Standard
        $port = '';
        if (isset($parts['port'])) {
            $standardPorts = ['http' => 80, 'https' => 443, 'ftp' => 21];
            if (!isset($standardPorts[$scheme]) || $parts['port'] !== $standardPorts[$scheme]) {
                $port = ':' . $parts['port'];
            }
        }

        // User/Pass
        $userInfo = '';
        if (isset($parts['user'])) {
            $userInfo = $parts['user'];
            if (isset($parts['pass'])) {
                $userInfo .= ':' . $parts['pass'];
            }
            $userInfo .= '@';
        }

        // Pfad normalisieren
        $path = $parts['path'] ?? '/';
        if ($path === '') {
            $path = '/';
        }
        // Trailing slash entfernen (außer bei Root)
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        // Query-Parameter sortieren
        $query = '';
        if (isset($parts['query']) && $parts['query'] !== '') {
            parse_str($parts['query'], $queryParams);
            ksort($queryParams);
            $query = '?' . http_build_query($queryParams);
        }

        // Fragment
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $scheme . '://' . $userInfo . $host . $port . $path . $query . $fragment;
    }

    /**
     * Extrahiert das Schema einer URL.
     *
     * @param string|null $url Die URL.
     * @return string|null Das Schema (z.B. 'https') oder null.
     */
    public static function getScheme(?string $url): ?string {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        return $parts['scheme'] ?? null;
    }

    /**
     * Extrahiert den Host einer URL.
     *
     * @param string|null $url Die URL.
     * @return string|null Der Host oder null.
     */
    public static function getHost(?string $url): ?string {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        return $parts['host'] ?? null;
    }

    /**
     * Extrahiert die Domain (ohne Subdomain) einer URL.
     *
     * @param string|null $url Die URL.
     * @return string|null Die Domain oder null.
     */
    public static function getDomain(?string $url): ?string {
        $host = self::getHost($url);
        if ($host === null) {
            return null;
        }

        // IP-Adresse direkt zurückgeben
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $host;
        }

        $parts = explode('.', $host);
        $count = count($parts);

        if ($count < 2) {
            return $host;
        }

        // Bekannte zweiteilige TLDs (co.uk, com.au, etc.)
        $twoPartTlds = ['co.uk', 'com.au', 'co.nz', 'org.uk', 'com.br', 'co.jp'];
        $lastTwo = $parts[$count - 2] . '.' . $parts[$count - 1];

        if (in_array($lastTwo, $twoPartTlds, true) && $count >= 3) {
            return $parts[$count - 3] . '.' . $lastTwo;
        }

        return $parts[$count - 2] . '.' . $parts[$count - 1];
    }

    /**
     * Extrahiert die Subdomain einer URL.
     *
     * @param string|null $url Die URL.
     * @return string|null Die Subdomain oder null.
     */
    public static function getSubdomain(?string $url): ?string {
        $host = self::getHost($url);
        $domain = self::getDomain($url);

        if ($host === null || $domain === null || $host === $domain) {
            return null;
        }

        $subdomain = substr($host, 0, strlen($host) - strlen($domain) - 1);
        return $subdomain !== '' ? $subdomain : null;
    }

    /**
     * Extrahiert den Port einer URL.
     *
     * @param string|null $url Die URL.
     * @return int|null Der Port oder null (bei Standard-Port).
     */
    public static function getPort(?string $url): ?int {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        return isset($parts['port']) ? (int) $parts['port'] : null;
    }

    /**
     * Extrahiert den Pfad einer URL.
     *
     * @param string|null $url Die URL.
     * @return string|null Der Pfad oder null.
     */
    public static function getPath(?string $url): ?string {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        return $parts['path'] ?? null;
    }

    /**
     * Extrahiert den Query-String einer URL.
     *
     * @param string|null $url Die URL.
     * @return string|null Der Query-String (ohne '?') oder null.
     */
    public static function getQueryString(?string $url): ?string {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        return $parts['query'] ?? null;
    }

    /**
     * Extrahiert die Query-Parameter als Array.
     *
     * @param string|null $url Die URL.
     * @return array<string, string|array<string>> Die Query-Parameter.
     */
    public static function getQueryParams(?string $url): array {
        $query = self::getQueryString($url);
        if ($query === null) {
            return [];
        }

        parse_str($query, $params);
        return $params;
    }

    /**
     * Extrahiert einen einzelnen Query-Parameter.
     *
     * @param string|null $url Die URL.
     * @param string $key Der Parameter-Name.
     * @return string|array<string>|null Der Wert oder null.
     */
    public static function getQueryParam(?string $url, string $key): string|array|null {
        $params = self::getQueryParams($url);
        return $params[$key] ?? null;
    }

    /**
     * Extrahiert das Fragment (Anker) einer URL.
     *
     * @param string|null $url Die URL.
     * @return string|null Das Fragment (ohne '#') oder null.
     */
    public static function getFragment(?string $url): ?string {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        return $parts['fragment'] ?? null;
    }

    /**
     * Fügt Query-Parameter zu einer URL hinzu oder ersetzt sie.
     *
     * @param string $url Die Basis-URL.
     * @param array<string, string|int|float|bool|null> $params Die hinzuzufügenden Parameter.
     * @param bool $replace Bei true werden existierende Parameter überschrieben.
     * @return string|null Die URL mit den neuen Parametern oder null bei ungültiger URL.
     */
    public static function addQueryParams(string $url, array $params, bool $replace = true): ?string {
        if (!self::isUrl($url)) {
            return null;
        }

        $existingParams = self::getQueryParams($url);

        if ($replace) {
            $mergedParams = array_merge($existingParams, $params);
        } else {
            $mergedParams = array_merge($params, $existingParams);
        }

        // Null-Werte entfernen
        $mergedParams = array_filter($mergedParams, fn($v) => $v !== null);

        // URL ohne Query-String
        $baseUrl = strtok($url, '?');
        $fragment = self::getFragment($url);

        $queryString = http_build_query($mergedParams);
        $result = $baseUrl;

        if ($queryString !== '') {
            $result .= '?' . $queryString;
        }

        if ($fragment !== null) {
            $result .= '#' . $fragment;
        }

        return $result;
    }

    /**
     * Entfernt Query-Parameter aus einer URL.
     *
     * @param string $url Die URL.
     * @param array<string> $keys Die zu entfernenden Parameter-Namen.
     * @return string|null Die URL ohne die angegebenen Parameter oder null bei ungültiger URL.
     */
    public static function removeQueryParams(string $url, array $keys): ?string {
        if (!self::isUrl($url)) {
            return null;
        }

        $params = self::getQueryParams($url);

        foreach ($keys as $key) {
            unset($params[$key]);
        }

        // URL ohne Query-String
        $baseUrl = strtok($url, '?');
        $fragment = self::getFragment($url);

        $queryString = http_build_query($params);
        $result = $baseUrl;

        if ($queryString !== '') {
            $result .= '?' . $queryString;
        }

        if ($fragment !== null) {
            $result .= '#' . $fragment;
        }

        return $result;
    }

    /**
     * Kombiniert eine Basis-URL mit einem relativen Pfad.
     *
     * @param string $baseUrl Die Basis-URL.
     * @param string $relativePath Der relative Pfad.
     * @return string|null Die kombinierte URL oder null bei ungültiger Basis-URL.
     */
    public static function resolveRelative(string $baseUrl, string $relativePath): ?string {
        if (!self::isUrl($baseUrl)) {
            return null;
        }

        // Absoluter Pfad
        if (self::isUrl($relativePath)) {
            return $relativePath;
        }

        $parts = parse_url($baseUrl);
        if ($parts === false) {
            return null;
        }

        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $basePath = $parts['path'] ?? '/';

        // Protocol-relative URL (//example.com/path)
        if (str_starts_with($relativePath, '//')) {
            return $scheme . ':' . $relativePath;
        }

        // Absoluter Pfad vom Root
        if (str_starts_with($relativePath, '/')) {
            return $scheme . '://' . $host . $port . $relativePath;
        }

        // Relativer Pfad
        $basePath = dirname($basePath);
        if ($basePath === '\\' || $basePath === '.') {
            $basePath = '/';
        }

        $fullPath = rtrim($basePath, '/') . '/' . $relativePath;

        // Pfad normalisieren (. und .. auflösen)
        $segments = explode('/', $fullPath);
        $resolved = [];

        foreach ($segments as $segment) {
            if ($segment === '..') {
                array_pop($resolved);
            } elseif ($segment !== '.' && $segment !== '') {
                $resolved[] = $segment;
            }
        }

        return $scheme . '://' . $host . $port . '/' . implode('/', $resolved);
    }

    /**
     * Extrahiert alle URLs aus einem Text.
     *
     * @param string $text Der Text, in dem nach URLs gesucht wird.
     * @param bool $unique Bei true werden nur eindeutige URLs zurückgegeben.
     * @return array<string> Die gefundenen URLs.
     */
    public static function extractUrls(string $text, bool $unique = true): array {
        $pattern = '/\b(?:https?|ftp):\/\/[^\s<>\[\]{}|\\^`"\']+/i';

        if (preg_match_all($pattern, $text, $matches)) {
            $urls = $matches[0];

            // Trailing-Zeichen bereinigen (Satzzeichen am Ende)
            $urls = array_map(function ($url) {
                return rtrim($url, '.,;:!?)\'">');
            }, $urls);

            return $unique ? array_unique($urls) : $urls;
        }

        return [];
    }

    /**
     * Prüft, ob eine URL zu einer bestimmten Domain gehört.
     *
     * @param string|null $url Die zu prüfende URL.
     * @param string $domain Die erwartete Domain.
     * @param bool $includeSubdomains Bei true werden auch Subdomains akzeptiert.
     * @return bool True, wenn die URL zur Domain gehört.
     */
    public static function belongsToDomain(?string $url, string $domain, bool $includeSubdomains = true): bool {
        $host = self::getHost($url);
        if ($host === null) {
            return false;
        }

        $host = strtolower($host);
        $domain = strtolower($domain);

        if ($host === $domain) {
            return true;
        }

        if ($includeSubdomains) {
            return str_ends_with($host, '.' . $domain);
        }

        return false;
    }

    /**
     * Kodiert eine URL (Pfad und Query-String).
     *
     * @param string $url Die zu kodierende URL.
     * @return string|null Die kodierte URL oder null bei ungültiger URL.
     */
    public static function encode(string $url): ?string {
        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        $result = '';

        if (isset($parts['scheme'])) {
            $result .= $parts['scheme'] . '://';
        }

        if (isset($parts['user'])) {
            $result .= rawurlencode($parts['user']);
            if (isset($parts['pass'])) {
                $result .= ':' . rawurlencode($parts['pass']);
            }
            $result .= '@';
        }

        if (isset($parts['host'])) {
            $result .= $parts['host'];
        }

        if (isset($parts['port'])) {
            $result .= ':' . $parts['port'];
        }

        if (isset($parts['path'])) {
            $encodedPath = implode('/', array_map('rawurlencode', explode('/', $parts['path'])));
            $result .= $encodedPath;
        }

        if (isset($parts['query'])) {
            $result .= '?' . $parts['query'];
        }

        if (isset($parts['fragment'])) {
            $result .= '#' . rawurlencode($parts['fragment']);
        }

        return $result;
    }

    /**
     * Dekodiert eine URL.
     *
     * @param string $url Die zu dekodierende URL.
     * @return string Die dekodierte URL.
     */
    public static function decode(string $url): string {
        return rawurldecode($url);
    }

    /**
     * Prüft, ob eine URL erreichbar ist (HTTP HEAD Request).
     *
     * @param string $url Die zu prüfende URL.
     * @param int $timeout Timeout in Sekunden.
     * @return bool True, wenn die URL erreichbar ist.
     */
    public static function isReachable(string $url, int $timeout = 5): bool {
        if (!self::isHttpUrl($url)) {
            return false;
        }

        $headers = @get_headers($url, context: stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => $timeout,
            ],
        ]));

        if ($headers === false || !isset($headers[0])) {
            return false;
        }

        // HTTP Status-Code extrahieren
        if (preg_match('/HTTP\/\d+\.?\d*\s+(\d{3})/', $headers[0], $matches)) {
            $statusCode = (int) $matches[1];
            return $statusCode >= 200 && $statusCode < 400;
        }

        return false;
    }

    /**
     * Prüft, ob der Host einer URL online ist.
     *
     * Verwendet DNS-Lookup und optional einen Socket-Verbindungstest.
     * Im Gegensatz zu isReachable() wird nur geprüft, ob der Server erreichbar ist,
     * nicht ob die spezifische URL antwortet.
     *
     * @param string|null $url Die zu prüfende URL.
     * @param int $timeout Timeout in Sekunden für den Socket-Test.
     * @param bool $checkSocket Bei true wird zusätzlich ein Socket-Verbindungstest durchgeführt.
     * @return bool True, wenn der Host online ist.
     */
    public static function isOnline(?string $url, int $timeout = 3, bool $checkSocket = true): bool {
        $host = self::getHost($url);
        if ($host === null || $host === '') {
            return false;
        }

        // DNS-Lookup prüfen
        if (!self::hasValidDns($host)) {
            return false;
        }

        if (!$checkSocket) {
            return true;
        }

        // Socket-Verbindungstest
        $port = self::getPort($url);
        if ($port === null) {
            $scheme = self::getScheme($url);
            $port = match ($scheme) {
                'https' => 443,
                'http' => 80,
                'ftp' => 21,
                'ftps' => 990,
                default => 80,
            };
        }

        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($socket === false) {
            self::logDebug("Socket-Verbindung zu $host:$port fehlgeschlagen: $errstr ($errno)");
            return false;
        }

        fclose($socket);
        return true;
    }

    /**
     * Prüft, ob ein Host gültige DNS-Einträge hat.
     *
     * @param string $host Der zu prüfende Hostname.
     * @return bool True, wenn DNS-Einträge vorhanden sind.
     */
    public static function hasValidDns(string $host): bool {
        // IP-Adressen sind immer "gültig" für DNS-Zwecke
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }

        // Prüfe auf A oder AAAA Records
        if (function_exists('checkdnsrr')) {
            return @checkdnsrr($host, 'A') || @checkdnsrr($host, 'AAAA');
        }

        // Fallback: gethostbyname
        $ip = @gethostbyname($host);
        return $ip !== $host;
    }

    /**
     * Generiert eine Slug-URL aus einem Text.
     *
     * @param string $text Der Text.
     * @param string $separator Das Trennzeichen (Standard: '-').
     * @return string Die Slug-URL.
     */
    public static function slugify(string $text, string $separator = '-'): string {
        // Transliteration für Umlaute
        $transliterations = [
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            'ß' => 'ss',
            'Ä' => 'Ae',
            'Ö' => 'Oe',
            'Ü' => 'Ue',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'á' => 'a',
            'à' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ñ' => 'n',
            'ç' => 'c',
        ];

        $text = strtr($text, $transliterations);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', $separator, $text);
        $text = trim($text, $separator);

        return $text;
    }

    /**
     * Extrahiert die Dateierweiterung aus einer URL.
     *
     * @param string|null $url Die URL.
     * @return string|null Die Dateierweiterung (ohne Punkt) oder null.
     */
    public static function getFileExtension(?string $url): ?string {
        $path = self::getPath($url);
        if ($path === null) {
            return null;
        }

        $filename = basename($path);
        $pos = strrpos($filename, '.');

        if ($pos === false || $pos === 0) {
            return null;
        }

        return strtolower(substr($filename, $pos + 1));
    }

    /**
     * Extrahiert den Dateinamen aus einer URL.
     *
     * @param string|null $url Die URL.
     * @param bool $withExtension Bei true wird die Erweiterung beibehalten.
     * @return string|null Der Dateiname oder null.
     */
    public static function getFilename(?string $url, bool $withExtension = true): ?string {
        $path = self::getPath($url);
        if ($path === null) {
            return null;
        }

        $filename = basename($path);

        if (!$withExtension) {
            $pos = strrpos($filename, '.');
            if ($pos !== false && $pos !== 0) {
                $filename = substr($filename, 0, $pos);
            }
        }

        return $filename !== '' ? $filename : null;
    }
}
