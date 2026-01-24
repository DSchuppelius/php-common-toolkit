<?php
/*
 * Created on   : Sun Dec 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SecurityHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Helper\FileSystem\File;
use Exception;
use InvalidArgumentException;

/**
 * Helper-Klasse für Sicherheitsfunktionen im Banking/Financial Bereich.
 *
 * Bietet Funktionen für:
 * - Sichere Password-Verarbeitung (OWASP-konform)
 * - Token-Generierung und -Validierung
 * - Input-Sanitization für Banking-Daten
 * - Security-Header-Verwaltung
 * - CSRF-Schutz
 * - Rate-Limiting-Utilities
 * - PCI DSS konforme Sicherheitsfunktionen
 */
class SecurityHelper extends HelperAbstract {

    /** @var array<string, string> Default Security Headers */
    private const DEFAULT_SECURITY_HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy' => "default-src 'self'",
        'Referrer-Policy' => 'strict-origin-when-cross-origin'
    ];

    /** @var array<string> Gefährliche Zeichen für Input-Sanitization */
    private const DANGEROUS_CHARS = ['<', '>', '"', "'", '&', '%', ';', '(', ')', '+', 'script', 'javascript', 'vbscript'];

    /**
     * Erstellt einen sicheren Password-Hash (OWASP-konform).
     *
     * @param string $password Das zu hashende Password
     * @param array<string, mixed> $options Hash-Optionen (cost, etc.)
     * @return string Der sichere Hash
     * @throws InvalidArgumentException Bei ungültigen Parametern
     */
    public static function hashPassword(string $password, array $options = []): string {
        try {
            if (strlen($password) < 8) {
                throw new InvalidArgumentException('Password muss mindestens 8 Zeichen lang sein');
            }

            if (strlen($password) > 4096) {
                throw new InvalidArgumentException('Password zu lang (max. 4096 Zeichen)');
            }

            $cost = $options['cost'] ?? 12;
            if ($cost < 10 || $cost > 15) {
                throw new InvalidArgumentException('Cost-Parameter muss zwischen 10 und 15 liegen');
            }

            $hash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3]);

            if ($hash === false) {
                throw new InvalidArgumentException('Password-Hashing fehlgeschlagen');
            }

            return self::logDebugAndReturn($hash, "Sicherer Password-Hash erstellt");
        } catch (Exception $e) {
            self::logException($e);
            throw $e;
        }
    }

    /**
     * Verifiziert ein Password gegen einen Hash.
     *
     * @param string $password Das zu prüfende Password
     * @param string $hash Der gespeicherte Hash
     * @return bool True wenn das Password korrekt ist
     */
    public static function verifyPassword(string $password, string $hash): bool {
        try {
            if (empty($password) || empty($hash)) {
                return false;
            }

            $result = password_verify($password, $hash);

            return $result
                ? self::logDebugAndReturn(true, "Password-Verifikation erfolgreich")
                : self::logWarningAndReturn(false, "Password-Verifikation fehlgeschlagen");
        } catch (Exception $e) {
            return self::logErrorAndReturn(false, "Fehler bei Password-Verifikation: " . $e->getMessage());
        }
    }

    /**
     * Prüft ob ein Password-Hash neu gehashed werden sollte.
     *
     * @param string $hash Der zu prüfende Hash
     * @param array<string, mixed> $options Hash-Optionen
     * @return bool True wenn Rehash nötig ist
     */
    public static function needsRehash(string $hash, array $options = []): bool {
        try {
            $cost = $options['cost'] ?? 12;
            return password_needs_rehash($hash, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3]);
        } catch (Exception $e) {
            return self::logErrorAndReturn(true, "Fehler bei Rehash-Prüfung: " . $e->getMessage()); // Im Zweifelsfall rehash durchführen
        }
    }

    /**
     * Generiert einen kryptographisch sicheren Token.
     *
     * @param int $length Token-Länge in Bytes
     * @param bool $base64 Wenn true, wird Base64-codiert zurückgegeben
     * @return string Der generierte Token
     * @throws InvalidArgumentException Bei ungültiger Länge
     */
    public static function generateSecureToken(int $length = 32, bool $base64 = true): string {
        try {
            if ($length < 16 || $length > 256) {
                throw new InvalidArgumentException('Token-Länge muss zwischen 16 und 256 Bytes liegen');
            }

            $randomBytes = random_bytes($length);
            $token = $base64 ? base64_encode($randomBytes) : bin2hex($randomBytes);

            return self::logDebugAndReturn($token, "Sicherer Token generiert (Länge: {$length} Bytes)");
        } catch (Exception $e) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Token-Generierung fehlgeschlagen: ' . $e->getMessage());
        }
    }

    /**
     * Generiert einen CSRF-Token für Formular-Schutz.
     *
     * @param string $sessionId Session-ID für Token-Bindung
     * @param string $action Spezifische Aktion (optional)
     * @return string Der CSRF-Token
     */
    public static function generateCsrfToken(string $sessionId, string $action = 'default'): string {
        try {
            $data = $sessionId . '|' . $action . '|' . time();
            $key = hash('sha256', 'csrf_key_' . $sessionId, true);
            $token = hash_hmac('sha256', $data, $key);

            return self::logDebugAndReturn(base64_encode($data . '|' . $token), "CSRF-Token für Aktion '{$action}' generiert");
        } catch (Exception $e) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'CSRF-Token-Generierung fehlgeschlagen: ' . $e->getMessage());
        }
    }

    /**
     * Validiert einen CSRF-Token.
     *
     * @param string $token Der zu prüfende Token
     * @param string $sessionId Aktuelle Session-ID
     * @param string $action Erwartete Aktion
     * @param int $maxAge Maximale Token-Gültigkeit in Sekunden
     * @return bool True wenn Token gültig ist
     */
    public static function validateCsrfToken(string $token, string $sessionId, string $action = 'default', int $maxAge = 3600): bool {
        try {
            $decoded = base64_decode($token, true);
            if ($decoded === false) {
                return false;
            }

            $parts = explode('|', $decoded);
            if (count($parts) !== 4) {
                return false;
            }

            [$tokenSessionId, $tokenAction, $timestamp, $signature] = $parts;

            // Zeitvalidierung
            if (time() - (int)$timestamp > $maxAge) {
                return self::logWarningAndReturn(false, "CSRF-Token abgelaufen");
            }

            // Session- und Aktion-Validierung
            if ($tokenSessionId !== $sessionId || $tokenAction !== $action) {
                return self::logWarningAndReturn(false, "CSRF-Token Session/Aktion mismatch");
            }

            // Signatur-Validierung
            $expectedData = $tokenSessionId . '|' . $tokenAction . '|' . $timestamp;
            $key = hash('sha256', 'csrf_key_' . $sessionId, true);
            $expectedSignature = hash_hmac('sha256', $expectedData, $key);

            if (!hash_equals($expectedSignature, $signature)) {
                return self::logWarningAndReturn(false, "CSRF-Token Signatur ungültig");
            }

            return self::logDebugAndReturn(true, "CSRF-Token erfolgreich validiert");
        } catch (Exception $e) {
            return self::logErrorAndReturn(false, "Fehler bei CSRF-Token-Validierung: " . $e->getMessage());
        }
    }

    /**
     * Sanitisiert Input-Daten für Banking-Anwendungen.
     *
     * @param string $input Der zu bereinigende Input
     * @param bool $allowHtml Ob HTML-Tags erlaubt sind
     * @return string Der bereinigte Input
     */
    public static function sanitizeInput(string $input, bool $allowHtml = false): string {
        try {
            // Trim und Normalisierung
            $input = trim($input);
            $input = preg_replace('/\s+/', ' ', $input);

            if (!$allowHtml) {
                // HTML-Tags entfernen
                $input = strip_tags($input);

                // Gefährliche Zeichen und Wörter aggressiv neutralisieren  
                $input = preg_replace('/\balert\b/i', '', $input);
                $input = preg_replace('/\bscript\b/i', '', $input);
                $input = preg_replace('/javascript:/i', '', $input);
                $input = preg_replace('/vbscript:/i', '', $input);
                $input = preg_replace('/on\w+\s*=/i', '', $input);

                // Einzelne gefährliche Zeichen entfernen
                foreach (self::DANGEROUS_CHARS as $char) {
                    if (!in_array($char, ['script', 'javascript', 'vbscript'])) { // Diese wurden schon behandelt
                        $input = str_ireplace($char, '', $input);
                    }
                }
            } else {
                // Nur Script-Tags und JavaScript entfernen
                $input = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $input);
                $input = preg_replace('/javascript:/i', '', $input);
                $input = preg_replace('/vbscript:/i', '', $input);
                $input = preg_replace('/on\w+\s*=/i', '', $input);
            }

            // HTML-Entities encoding
            $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return self::logDebugAndReturn($input, "Input sanitisiert: " . substr($input, 0, 50) . "...");
        } catch (Exception $e) {
            return self::logErrorAndReturn('', "Fehler bei Input-Sanitization: " . $e->getMessage()); // Im Fehlerfall leeren String zurückgeben
        }
    }

    /**
     * Validiert und bereitet Banking-relevante Daten sicher auf.
     *
     * @param string $data Die zu bereinigenden Banking-Daten
     * @param string $type Datentyp ('iban', 'bic', 'account', 'amount', etc.)
     * @return string Die bereinigten Daten
     * @throws InvalidArgumentException Bei ungültigen Daten
     */
    public static function sanitizeBankingData(string $data, string $type): string {
        try {
            $data = trim($data);

            switch (strtolower($type)) {
                case 'iban':
                    // Nur Alphanumerisch, max 34 Zeichen
                    $data = preg_replace('/[^A-Z0-9]/', '', strtoupper($data));
                    if (strlen($data) > 34) {
                        throw new InvalidArgumentException('IBAN zu lang');
                    }
                    break;

                case 'bic':
                    // Nur Alphanumerisch, 8 oder 11 Zeichen
                    $data = preg_replace('/[^A-Z0-9]/', '', strtoupper($data));
                    if (!in_array(strlen($data), [8, 11])) {
                        throw new InvalidArgumentException('BIC muss 8 oder 11 Zeichen haben');
                    }
                    break;

                case 'account':
                    // Nur Zahlen und Bindestriche
                    $data = preg_replace('/[^0-9\-]/', '', $data);
                    break;

                case 'amount':
                    // Nur Zahlen, Punkt und Komma
                    $data = preg_replace('/[^0-9\.,\-]/', '', $data);
                    // Komma durch Punkt ersetzen
                    $data = str_replace(',', '.', $data);
                    break;

                case 'name':
                    // Alphanumerisch, Leerzeichen, Bindestriche, Punkte
                    $data = preg_replace('/[^a-zA-Z0-9\s\-\.\säöüÄÖÜß]/', '', $data);
                    // Gefährliche Javascript-Keywords entfernen
                    $data = preg_replace('/\b(script|alert|javascript|eval|onerror|onload|onclick)\b/i', '', $data);
                    // Mehrfache Leerzeichen normalisieren
                    $data = preg_replace('/\s+/', ' ', $data);
                    $data = trim($data);
                    break;

                default:
                    // Standard-Sanitization
                    $data = self::sanitizeInput($data, false);
            }

            return self::logDebugAndReturn($data, "Banking-Daten sanitisiert: Typ '{$type}', Länge: " . strlen($data));
        } catch (Exception $e) {
            self::logException($e);
            throw $e;
        }
    }

    /**
     * Setzt Standard-Security-Header für Banking-Anwendungen.
     *
     * @param array<string, string> $customHeaders Zusätzliche Header
     * @param bool $strictMode Ob strenge CSP-Regeln angewendet werden
     * @return array<string, string> Die gesetzten Header
     */
    public static function setSecurityHeaders(array $customHeaders = [], bool $strictMode = true): array {
        try {
            $headers = self::DEFAULT_SECURITY_HEADERS;

            if ($strictMode) {
                $headers['Content-Security-Policy'] = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; font-src 'self'";
                $headers['X-Permitted-Cross-Domain-Policies'] = 'none';
                $headers['X-Download-Options'] = 'noopen';
            }

            // Custom Headers hinzufügen
            $headers = array_merge($headers, $customHeaders);

            // Header senden (falls nicht im CLI-Mode)
            if (!headers_sent() && php_sapi_name() !== 'cli') {
                foreach ($headers as $name => $value) {
                    header("{$name}: {$value}");
                }
            }

            return self::logDebugAndReturn($headers, "Security-Header gesetzt: " . count($headers) . " Header");
        } catch (Exception $e) {
            return self::logErrorAndReturn([], "Fehler bei Security-Header-Setup: " . $e->getMessage());
        }
    }

    /**
     * Implementiert einfaches Rate-Limiting basierend auf IP/User.
     *
     * @param string $identifier Eindeutiger Identifier (IP, User-ID, etc.)
     * @param int $maxAttempts Maximale Versuche
     * @param int $timeWindow Zeitfenster in Sekunden
     * @param string $action Beschreibung der Aktion
     * @return bool True wenn Aktion erlaubt ist
     */
    public static function checkRateLimit(string $identifier, int $maxAttempts, int $timeWindow, string $action = 'default'): bool {
        try {
            $key = 'rate_limit_' . hash('sha256', $identifier . '_' . $action);
            $currentTime = time();

            // Simple File-basierte Rate-Limiting (für Produktionsumgebung Redis/Memcached verwenden)
            $rateLimitFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $key . '.tmp';

            $attempts = [];
            if (File::exists($rateLimitFile)) {
                try {
                    $data = File::read($rateLimitFile);
                    $attempts = JsonHelper::decode($data);
                } catch (Exception) {
                    $attempts = [];
                }
            }

            // Alte Versuche bereinigen
            $attempts = array_filter($attempts, function ($timestamp) use ($currentTime, $timeWindow) {
                return ($currentTime - $timestamp) < $timeWindow;
            });

            // Prüfen ob Limit überschritten
            if (count($attempts) >= $maxAttempts) {
                return self::logWarningAndReturn(false, "Rate-Limit überschritten für: {$identifier}, Aktion: {$action}");
            }

            // Aktuellen Versuch hinzufügen
            $attempts[] = $currentTime;
            File::write($rateLimitFile, JsonHelper::encode($attempts));

            return self::logDebugAndReturn(true, "Rate-Limit geprüft: {$identifier}, Versuche: " . count($attempts) . "/{$maxAttempts}");
        } catch (Exception $e) {
            return self::logErrorAndReturn(true, "Fehler bei Rate-Limiting: " . $e->getMessage()); // Im Fehlerfall erlauben
        }
    }

    /**
     * Generiert eine sichere Session-ID für Banking-Anwendungen.
     *
     * @param string $userAgent User-Agent String für zusätzliche Entropie
     * @param string $ipAddress IP-Adresse für Binding
     * @return string Die sichere Session-ID
     */
    public static function generateSecureSessionId(string $userAgent = '', string $ipAddress = ''): string {
        try {
            $entropy = [
                microtime(true),
                random_bytes(32),
                $userAgent,
                $ipAddress,
                getmypid(),
                $_SERVER['REQUEST_TIME_FLOAT'] ?? time()
            ];

            $sessionData = implode('|', $entropy);
            $sessionId = hash('sha256', $sessionData);

            return self::logDebugAndReturn($sessionId, "Sichere Session-ID generiert");
        } catch (Exception $e) {
            return self::logErrorAndReturn(bin2hex(random_bytes(32)), "Fehler bei Session-ID-Generierung: " . $e->getMessage()); // Fallback
        }
    }

    /**
     * Maskiert sensible Daten für Logging/Display.
     *
     * @param string $data Die zu maskierenden Daten
     * @param string $type Datentyp (zur spezifischen Maskierung)
     * @param int $visibleChars Anzahl sichtbarer Zeichen am Anfang/Ende
     * @return string Die maskierten Daten
     */
    public static function maskSensitiveData(string $data, string $type = 'generic', int $visibleChars = 2): string {
        try {
            if (empty($data)) {
                return $data;
            }

            $length = strlen($data);

            switch (strtolower($type)) {
                case 'iban':
                    // IBAN: DE89 37** **** **** ***0 00
                    if ($length > 8) {
                        return substr($data, 0, 4) . str_repeat('*', $length - 8) . substr($data, -4);
                    }
                    break;

                case 'creditcard':
                    // Kreditkarte: 1234 **** **** 5678
                    if ($length > 8) {
                        return substr($data, 0, 4) . str_repeat('*', $length - 8) . substr($data, -4);
                    }
                    break;

                case 'email':
                    // Email: jo**@ex*****.com
                    $parts = explode('@', $data);
                    if (count($parts) === 2) {
                        $username = $parts[0];
                        $domain = $parts[1];
                        $maskedUsername = substr($username, 0, 2) . str_repeat('*', max(0, strlen($username) - 2));
                        $domainParts = explode('.', $domain);
                        if (count($domainParts) > 1) {
                            $maskedDomain = substr($domainParts[0], 0, 2) . str_repeat('*', max(0, strlen($domainParts[0]) - 2)) . '.' . end($domainParts);
                        } else {
                            $maskedDomain = $domain;
                        }
                        return $maskedUsername . '@' . $maskedDomain;
                    }
                    break;

                case 'phone':
                    // Telefon: +49 *** *** **89
                    if ($length > 6) {
                        return substr($data, 0, 3) . str_repeat('*', $length - 5) . substr($data, -2);
                    }
                    break;

                default:
                    // Generisch: ab****yz
                    if ($length > ($visibleChars * 2)) {
                        return substr($data, 0, $visibleChars) . str_repeat('*', $length - ($visibleChars * 2)) . substr($data, -$visibleChars);
                    }
            }

            // Fallback für kurze Strings
            return str_repeat('*', $length);
        } catch (Exception $e) {
            return self::logErrorAndReturn(str_repeat('*', strlen($data)), "Fehler bei Daten-Maskierung: " . $e->getMessage());
        }
    }
}
