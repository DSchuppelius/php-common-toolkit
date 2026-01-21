<?php
/*
 * Created on   : Sun Dec 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CryptoHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use Exception;
use InvalidArgumentException;

/**
 * Helper-Klasse für Kryptographie-Funktionen im Banking/Financial Bereich.
 *
 * Bietet Funktionen für:
 * - AES-Verschlüsselung/Entschlüsselung (PCI DSS konform)
 * - RSA Public/Private Key Operationen
 * - Digitale Signaturen und Verifikation
 * - Sichere Hash-Funktionen (Banking-Grade)
 * - Key-Derivation (PBKDF2, Argon2)
 * - Zufallszahlen-Generierung (CSPRNG)
 * - Base64/Hex Encoding für Banking-APIs
 * - HMAC-basierte Authentifizierung
 */
class CryptoHelper extends HelperAbstract {

    /** @var string Standard-Verschlüsselungsalgorithmus */
    private const DEFAULT_CIPHER = 'aes-256-gcm';

    /** @var string Standard-Hash-Algorithmus */
    private const DEFAULT_HASH = 'sha256';

    /** @var int Standard-IV-Länge */
    private const IV_LENGTH = 12;

    /** @var int Standard-Tag-Länge für GCM */
    private const TAG_LENGTH = 16;

    /** @var array<string> Erlaubte Verschlüsselungsalgorithmen */
    private const ALLOWED_CIPHERS = ['aes-256-gcm', 'aes-256-cbc', 'aes-192-gcm', 'aes-128-gcm'];

    /** @var array<string> Erlaubte Hash-Algorithmen */
    private const ALLOWED_HASHES = ['sha256', 'sha384', 'sha512', 'sha3-256', 'sha3-384', 'sha3-512'];

    /**
     * Verschlüsselt Daten mit AES-GCM (Banking-Grade Encryption).
     *
     * @param string $plaintext Der zu verschlüsselnde Text
     * @param string $key Der Verschlüsselungsschlüssel (32 Bytes für AES-256)
     * @param string $cipher Verschlüsselungsalgorithmus
     * @return array{ciphertext: string, iv: string, tag: string, algorithm: string} Verschlüsselungsresultat
     * @throws InvalidArgumentException Bei ungültigen Parametern
     */
    public static function encrypt(string $plaintext, string $key, string $cipher = self::DEFAULT_CIPHER): array {
        try {
            if (empty($plaintext)) {
                throw new InvalidArgumentException('Plaintext darf nicht leer sein');
            }

            if (!in_array($cipher, self::ALLOWED_CIPHERS)) {
                throw new InvalidArgumentException('Nicht unterstützter Verschlüsselungsalgorithmus: ' . $cipher);
            }

            // Key-Länge validieren
            $expectedKeyLength = match ($cipher) {
                'aes-256-gcm', 'aes-256-cbc' => 32,
                'aes-192-gcm' => 24,
                'aes-128-gcm' => 16,
                default => 32
            };

            if (strlen($key) !== $expectedKeyLength) {
                throw new InvalidArgumentException("Key muss {$expectedKeyLength} Bytes lang sein für {$cipher}");
            }

            // IV generieren
            $ivLength = $cipher === 'aes-256-cbc' ? 16 : self::IV_LENGTH;
            $iv = random_bytes($ivLength);

            $tag = null;
            if (str_contains($cipher, 'gcm')) {
                // GCM Mode mit Authentication Tag
                $ciphertext = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
            } else {
                // CBC Mode
                $ciphertext = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
            }

            if ($ciphertext === false) {
                throw new InvalidArgumentException('Verschlüsselung fehlgeschlagen: ' . openssl_error_string());
            }

            $result = [
                'ciphertext' => base64_encode($ciphertext),
                'iv' => base64_encode($iv),
                'tag' => $tag ? base64_encode($tag) : '',
                'algorithm' => $cipher
            ];

            return self::logDebugAndReturn($result, "Daten erfolgreich verschlüsselt mit {$cipher}");
        } catch (Exception $e) {
            self::logException($e);
            throw $e;
        }
    }

    /**
     * Entschlüsselt mit AES verschlüsselte Daten.
     *
     * @param array{ciphertext: string, iv: string, tag: string, algorithm: string} $encryptedData Die verschlüsselten Daten
     * @param string $key Der Entschlüsselungsschlüssel
     * @return string Der entschlüsselte Text
     * @throws InvalidArgumentException Bei ungültigen Daten oder Fehlern
     */
    public static function decrypt(array $encryptedData, string $key): string {
        try {
            $requiredKeys = ['ciphertext', 'iv', 'algorithm'];
            foreach ($requiredKeys as $reqKey) {
                if (!array_key_exists($reqKey, $encryptedData)) {
                    throw new InvalidArgumentException("Fehlender Key in verschlüsselten Daten: {$reqKey}");
                }
            }

            $cipher = $encryptedData['algorithm'];
            if (!in_array($cipher, self::ALLOWED_CIPHERS)) {
                throw new InvalidArgumentException('Nicht unterstützter Verschlüsselungsalgorithmus: ' . $cipher);
            }

            $ciphertext = base64_decode($encryptedData['ciphertext'], true);
            $iv = base64_decode($encryptedData['iv'], true);

            if ($ciphertext === false || $iv === false) {
                throw new InvalidArgumentException('Ungültige Base64-Kodierung in verschlüsselten Daten');
            }

            if (str_contains($cipher, 'gcm')) {
                // GCM Mode mit Tag-Verifikation
                if (empty($encryptedData['tag'])) {
                    throw new InvalidArgumentException('Authentication Tag fehlt für GCM-Mode');
                }

                $tag = base64_decode($encryptedData['tag'], true);
                if ($tag === false) {
                    throw new InvalidArgumentException('Ungültiger Authentication Tag');
                }

                $plaintext = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
            } else {
                // CBC Mode
                $plaintext = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
            }

            if ($plaintext === false) {
                throw new InvalidArgumentException('Entschlüsselung fehlgeschlagen: ' . openssl_error_string());
            }

            return self::logDebugAndReturn($plaintext, "Daten erfolgreich entschlüsselt mit {$cipher}");
        } catch (Exception $e) {
            self::logException($e);
            throw $e;
        }
    }

    /**
     * Generiert einen kryptographisch sicheren Schlüssel.
     *
     * @param int $length Schlüssellänge in Bytes
     * @param bool $base64 Wenn true, wird Base64-kodiert zurückgegeben
     * @return string Der generierte Schlüssel
     * @throws InvalidArgumentException Bei ungültiger Länge
     */
    public static function generateKey(int $length = 32, bool $base64 = false): string {
        try {
            if ($length < 16 || $length > 256) {
                throw new InvalidArgumentException('Schlüssellänge muss zwischen 16 und 256 Bytes liegen');
            }

            $key = random_bytes($length);
            $result = $base64 ? base64_encode($key) : $key;

            return self::logDebugAndReturn($result, "Kryptographischer Schlüssel generiert ({$length} Bytes)");
        } catch (Exception $e) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Schlüsselgenerierung fehlgeschlagen: ' . $e->getMessage());
        }
    }

    /**
     * Leitet einen Schlüssel aus einem Password ab (PBKDF2).
     *
     * @param string $password Das Password
     * @param string $salt Der Salt (mindestens 16 Bytes)
     * @param int $iterations Anzahl Iterationen (mindestens 10000)
     * @param int $length Ausgabelänge in Bytes
     * @param string $algorithm Hash-Algorithmus
     * @return string Der abgeleitete Schlüssel (Base64-kodiert)
     * @throws InvalidArgumentException Bei ungültigen Parametern
     */
    public static function deriveKey(string $password, string $salt, int $iterations = 100000, int $length = 32, string $algorithm = 'sha256'): string {
        try {
            if (strlen($password) < 1) {
                throw new InvalidArgumentException('Password darf nicht leer sein');
            }

            if (strlen($salt) < 16) {
                throw new InvalidArgumentException('Salt muss mindestens 16 Bytes lang sein');
            }

            if ($iterations < 10000) {
                throw new InvalidArgumentException('Mindestens 10000 Iterationen erforderlich');
            }

            if (!in_array($algorithm, self::ALLOWED_HASHES)) {
                throw new InvalidArgumentException('Nicht unterstützter Hash-Algorithmus: ' . $algorithm);
            }

            $derivedKey = hash_pbkdf2($algorithm, $password, $salt, $iterations, $length, true);
            $result = base64_encode($derivedKey);

            return self::logDebugAndReturn($result, "Schlüssel erfolgreich abgeleitet mit PBKDF2 ({$iterations} Iterationen)");
        } catch (Exception $e) {
            self::logException($e);
            throw $e;
        }
    }

    /**
     * Erstellt einen sicheren Hash mit Salt.
     *
     * @param string $data Die zu hashenden Daten
     * @param string $salt Der Salt (wird generiert wenn leer)
     * @param string $algorithm Hash-Algorithmus
     * @return array{hash: string, salt: string, algorithm: string} Hash-Ergebnis
     * @throws InvalidArgumentException Bei ungültigen Parametern
     */
    public static function secureHash(string $data, string $salt = '', string $algorithm = self::DEFAULT_HASH): array {
        try {
            if (empty($data)) {
                throw new InvalidArgumentException('Zu hashende Daten dürfen nicht leer sein');
            }

            if (!in_array($algorithm, self::ALLOWED_HASHES)) {
                throw new InvalidArgumentException('Nicht unterstützter Hash-Algorithmus: ' . $algorithm);
            }

            if (empty($salt)) {
                $salt = random_bytes(32);
            } else {
                $salt = base64_decode($salt, true) ?: $salt;
            }

            $hash = hash($algorithm, $data . $salt, true);

            $result = [
                'hash' => base64_encode($hash),
                'salt' => base64_encode($salt),
                'algorithm' => $algorithm
            ];

            return self::logDebugAndReturn($result, "Sicherer Hash erstellt mit {$algorithm}");
        } catch (Exception $e) {
            self::logException($e);
            throw $e;
        }
    }

    /**
     * Verifiziert einen Hash gegen Daten.
     *
     * @param string $data Die ursprünglichen Daten
     * @param array{hash: string, salt: string, algorithm: string} $hashData Die Hash-Daten
     * @return bool True wenn Hash korrekt ist
     */
    public static function verifyHash(string $data, array $hashData): bool {
        try {
            $requiredKeys = ['hash', 'salt', 'algorithm'];
            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $hashData)) {
                    return false;
                }
            }

            $expectedHashData = self::secureHash($data, $hashData['salt'], $hashData['algorithm']);
            $result = hash_equals($expectedHashData['hash'], $hashData['hash']);

            if ($result) {
                self::logDebug("Hash-Verifikation erfolgreich");
            } else {
                self::logWarning("Hash-Verifikation fehlgeschlagen");
            }

            return $result;
        } catch (Exception $e) {
            return self::logErrorAndReturn(false, "Fehler bei Hash-Verifikation: " . $e->getMessage());
        }
    }

    /**
     * Erstellt eine HMAC-Signatur für API-Authentifizierung.
     *
     * @param string $data Die zu signierenden Daten
     * @param string $key Der HMAC-Schlüssel
     * @param string $algorithm Hash-Algorithmus
     * @return string Die HMAC-Signatur (Base64-kodiert)
     * @throws InvalidArgumentException Bei ungültigen Parametern
     */
    public static function createHmac(string $data, string $key, string $algorithm = self::DEFAULT_HASH): string {
        try {
            if (empty($data)) {
                throw new InvalidArgumentException('Zu signierende Daten dürfen nicht leer sein');
            }

            if (empty($key)) {
                throw new InvalidArgumentException('HMAC-Schlüssel darf nicht leer sein');
            }

            if (!in_array($algorithm, self::ALLOWED_HASHES)) {
                throw new InvalidArgumentException('Nicht unterstützter Hash-Algorithmus: ' . $algorithm);
            }

            $hmac = hash_hmac($algorithm, $data, $key, true);
            $result = base64_encode($hmac);

            return self::logDebugAndReturn($result, "HMAC-Signatur erstellt mit {$algorithm}");
        } catch (Exception $e) {
            self::logException($e);
            throw $e;
        }
    }

    /**
     * Verifiziert eine HMAC-Signatur.
     *
     * @param string $data Die ursprünglichen Daten
     * @param string $signature Die zu verifizierende Signatur
     * @param string $key Der HMAC-Schlüssel
     * @param string $algorithm Hash-Algorithmus
     * @return bool True wenn Signatur gültig ist
     */
    public static function verifyHmac(string $data, string $signature, string $key, string $algorithm = self::DEFAULT_HASH): bool {
        try {
            $expectedSignature = self::createHmac($data, $key, $algorithm);
            $result = hash_equals($expectedSignature, $signature);

            return $result
                ? self::logDebugAndReturn(true, "HMAC-Verifikation erfolgreich")
                : self::logWarningAndReturn(false, "HMAC-Verifikation fehlgeschlagen");
        } catch (Exception $e) {
            return self::logErrorAndReturn(false, "Fehler bei HMAC-Verifikation: " . $e->getMessage());
        }
    }

    /**
     * Generiert ein RSA-Schlüsselpaar für asymmetrische Verschlüsselung.
     *
     * @param int $keySize Schlüsselgröße in Bits (2048, 3072, 4096)
     * @return array{private_key: string, public_key: string} Das Schlüsselpaar
     * @throws InvalidArgumentException Bei ungültigen Parametern
     */
    public static function generateRsaKeyPair(int $keySize = 2048): array {
        try {
            if (!in_array($keySize, [2048, 3072, 4096])) {
                throw new InvalidArgumentException('RSA-Schlüsselgröße muss 2048, 3072 oder 4096 Bits sein');
            }

            $config = [
                'digest_alg' => 'sha256',
                'private_key_bits' => $keySize,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ];

            $resource = openssl_pkey_new($config);
            if ($resource === false) {
                throw new InvalidArgumentException('RSA-Schlüsselgenerierung fehlgeschlagen: ' . openssl_error_string());
            }

            if (!openssl_pkey_export($resource, $privateKey)) {
                throw new InvalidArgumentException('Export des privaten Schlüssels fehlgeschlagen: ' . openssl_error_string());
            }

            $publicKeyDetails = openssl_pkey_get_details($resource);
            if ($publicKeyDetails === false) {
                throw new InvalidArgumentException('Extraktion des öffentlichen Schlüssels fehlgeschlagen');
            }

            $result = [
                'private_key' => $privateKey,
                'public_key' => $publicKeyDetails['key']
            ];

            return self::logDebugAndReturn($result, "RSA-Schlüsselpaar generiert ({$keySize} Bits)");
        } catch (Exception $e) {
            self::logException($e);
            throw $e;
        }
    }

    /**
     * Verschlüsselt Daten mit RSA Public Key.
     *
     * @param string $data Die zu verschlüsselnden Daten
     * @param string $publicKey Der öffentliche RSA-Schlüssel
     * @return string Die verschlüsselten Daten (Base64-kodiert)
     * @throws InvalidArgumentException Bei Fehlern
     */
    public static function rsaEncrypt(string $data, string $publicKey): string {
        try {
            if (empty($data)) {
                throw new InvalidArgumentException('Zu verschlüsselnde Daten dürfen nicht leer sein');
            }

            $key = openssl_pkey_get_public($publicKey);
            if ($key === false) {
                throw new InvalidArgumentException('Ungültiger öffentlicher RSA-Schlüssel: ' . openssl_error_string());
            }

            if (!openssl_public_encrypt($data, $encrypted, $key, OPENSSL_PKCS1_OAEP_PADDING)) {
                throw new InvalidArgumentException('RSA-Verschlüsselung fehlgeschlagen: ' . openssl_error_string());
            }

            $result = base64_encode($encrypted);
            return self::logDebugAndReturn($result, "Daten erfolgreich RSA-verschlüsselt");
        } catch (Exception $e) {
            self::logException($e);
            throw $e;
        }
    }

    /**
     * Entschlüsselt RSA-verschlüsselte Daten mit Private Key.
     *
     * @param string $encryptedData Die verschlüsselten Daten (Base64-kodiert)
     * @param string $privateKey Der private RSA-Schlüssel
     * @return string Die entschlüsselten Daten
     * @throws InvalidArgumentException Bei Fehlern
     */
    public static function rsaDecrypt(string $encryptedData, string $privateKey): string {
        try {
            $encrypted = base64_decode($encryptedData, true);
            if ($encrypted === false) {
                throw new InvalidArgumentException('Ungültige Base64-kodierte Daten');
            }

            $key = openssl_pkey_get_private($privateKey);
            if ($key === false) {
                throw new InvalidArgumentException('Ungültiger privater RSA-Schlüssel: ' . openssl_error_string());
            }

            if (!openssl_private_decrypt($encrypted, $decrypted, $key, OPENSSL_PKCS1_OAEP_PADDING)) {
                throw new InvalidArgumentException('RSA-Entschlüsselung fehlgeschlagen: ' . openssl_error_string());
            }

            return self::logDebugAndReturn($decrypted, "Daten erfolgreich RSA-entschlüsselt");
        } catch (Exception $e) {
            self::logException($e);
            throw $e;
        }
    }

    /**
     * Erstellt eine digitale Signatur mit RSA Private Key.
     *
     * @param string $data Die zu signierenden Daten
     * @param string $privateKey Der private RSA-Schlüssel
     * @param string $algorithm Signatur-Algorithmus
     * @return string Die digitale Signatur (Base64-kodiert)
     * @throws InvalidArgumentException Bei Fehlern
     */
    public static function rsaSign(string $data, string $privateKey, string $algorithm = 'SHA256'): string {
        try {
            if (empty($data)) {
                throw new InvalidArgumentException('Zu signierende Daten dürfen nicht leer sein');
            }

            $key = openssl_pkey_get_private($privateKey);
            if ($key === false) {
                throw new InvalidArgumentException('Ungültiger privater RSA-Schlüssel: ' . openssl_error_string());
            }

            if (!openssl_sign($data, $signature, $key, $algorithm)) {
                throw new InvalidArgumentException('RSA-Signierung fehlgeschlagen: ' . openssl_error_string());
            }

            $result = base64_encode($signature);
            return self::logDebugAndReturn($result, "Digitale RSA-Signatur erstellt mit {$algorithm}");
        } catch (Exception $e) {
            self::logException($e);
            throw $e;
        }
    }

    /**
     * Verifiziert eine digitale RSA-Signatur.
     *
     * @param string $data Die ursprünglichen Daten
     * @param string $signature Die digitale Signatur (Base64-kodiert)
     * @param string $publicKey Der öffentliche RSA-Schlüssel
     * @param string $algorithm Signatur-Algorithmus
     * @return bool True wenn Signatur gültig ist
     */
    public static function rsaVerify(string $data, string $signature, string $publicKey, string $algorithm = 'SHA256'): bool {
        try {
            $signatureData = base64_decode($signature, true);
            if ($signatureData === false) {
                throw new InvalidArgumentException('Ungültige Base64-kodierte Signatur');
            }

            $key = openssl_pkey_get_public($publicKey);
            if ($key === false) {
                throw new InvalidArgumentException('Ungültiger öffentlicher RSA-Schlüssel: ' . openssl_error_string());
            }

            $result = openssl_verify($data, $signatureData, $key, $algorithm);

            if ($result === 1) {
                return self::logDebugAndReturn(true, "RSA-Signatur erfolgreich verifiziert");
            } elseif ($result === 0) {
                return self::logWarningAndReturn(false, "RSA-Signatur-Verifikation fehlgeschlagen");
            } else {
                throw new InvalidArgumentException('RSA-Verifikation Fehler: ' . openssl_error_string());
            }
        } catch (Exception $e) {
            self::logException($e);
            throw $e;
        }
    }

    /**
     * Konvertiert binäre Daten zu Base64 (URL-safe).
     *
     * @param string $data Binäre Daten
     * @return string Base64-kodierte Daten (URL-safe)
     */
    public static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Konvertiert Base64 (URL-safe) zurück zu binären Daten.
     *
     * @param string $data Base64-kodierte Daten (URL-safe)
     * @return string|false Binäre Daten oder false bei Fehler
     */
    public static function base64UrlDecode(string $data): string|false {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT), true);
    }

    /**
     * Konvertiert binäre Daten zu Hexadezimal.
     *
     * @param string $data Binäre Daten
     * @return string Hexadezimal-String
     */
    public static function binToHex(string $data): string {
        return bin2hex($data);
    }

    /**
     * Konvertiert Hexadezimal zurück zu binären Daten.
     *
     * @param string $hex Hexadezimal-String
     * @return string|false Binäre Daten oder false bei Fehler
     */
    public static function hexToBin(string $hex): string|false {
        $result = @hex2bin($hex);
        if ($result === false) {
            self::logWarning("Ungültiger Hexadezimal-String bei Konvertierung");
        }
        return $result;
    }

    /**
     * Generiert kryptographisch sichere Zufallszahlen.
     *
     * @param int $min Minimalwert
     * @param int $max Maximalwert
     * @return int Sichere Zufallszahl
     * @throws InvalidArgumentException Bei ungültigen Parametern
     */
    public static function secureRandomInt(int $min, int $max): int {
        try {
            if ($min >= $max) {
                throw new InvalidArgumentException('Min-Wert muss kleiner als Max-Wert sein');
            }

            $result = random_int($min, $max);
            return self::logDebugAndReturn($result, "Sichere Zufallszahl generiert: {$result}");
        } catch (Exception $e) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Sichere Zufallszahlgenerierung fehlgeschlagen: ' . $e->getMessage());
        }
    }

    /**
     * Überprüft ob OpenSSL-Extension verfügbar und korrekt konfiguriert ist.
     *
     * @return array{available: bool, version: string, ciphers: array<string>, hashes: array<string>} OpenSSL-Status
     */
    public static function getOpenSslStatus(): array {
        try {
            $available = extension_loaded('openssl');
            $version = $available ? OPENSSL_VERSION_TEXT : 'N/A';
            $ciphers = $available ? openssl_get_cipher_methods() : [];
            $hashes = $available ? hash_algos() : [];

            $result = [
                'available' => $available,
                'version' => $version,
                'ciphers' => array_intersect($ciphers, self::ALLOWED_CIPHERS),
                'hashes' => array_intersect($hashes, self::ALLOWED_HASHES)
            ];

            return self::logDebugAndReturn($result, "OpenSSL-Status abgerufen - Verfügbar: " . ($available ? 'Ja' : 'Nein'));
        } catch (Exception $e) {
            return self::logErrorAndReturn([
                'available' => false,
                'version' => 'Error',
                'ciphers' => [],
                'hashes' => []
            ], "Fehler bei OpenSSL-Status-Abfrage: " . $e->getMessage());
        }
    }
}
