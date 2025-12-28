<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EmailHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use ERRORToolkit\Traits\ErrorLog;

/**
 * Helper-Klasse für die Validierung und Verarbeitung von E-Mail-Adressen.
 *
 * Unterstützt:
 * - RFC 5321/5322 kompatible Validierung
 * - Domain-Extraktion und -Prüfung
 * - Normalisierung
 * - Wegwerf-E-Mail-Erkennung (optional)
 *
 * @package CommonToolkit\Helper\Data
 */
class EmailHelper {
    use ErrorLog;

    /**
     * Bekannte Wegwerf-E-Mail-Domains (Auswahl).
     *
     * @var array<string>
     */
    private const DISPOSABLE_DOMAINS = [
        'tempmail.com',
        'throwaway.email',
        'guerrillamail.com',
        'mailinator.com',
        '10minutemail.com',
        'temp-mail.org',
        'fakeinbox.com',
        'trashmail.com',
        'getnada.com',
        'emailondeck.com',
        'mohmal.com',
        'tempail.com',
        'dispostable.com',
        'mailnesia.com',
        'tempinbox.com',
        'sharklasers.com',
        'yopmail.com',
        'guerrillamail.info',
        'spamgourmet.com',
        'mytemp.email',
    ];

    /**
     * Prüft, ob eine E-Mail-Adresse ein gültiges Format hat.
     *
     * Verwendet PHP's filter_var mit FILTER_VALIDATE_EMAIL.
     *
     * @param string|null $email Die zu prüfende E-Mail-Adresse.
     * @return bool True, wenn das Format gültig ist.
     */
    public static function isEmail(?string $email): bool {
        if ($email === null || $email === '') {
            return false;
        }

        // Grundlegende Formatprüfung
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        return true;
    }

    /**
     * Validiert eine E-Mail-Adresse mit erweiterten Prüfungen.
     *
     * @param string|null $email Die zu validierende E-Mail-Adresse.
     * @param bool $checkDns Bei true wird geprüft, ob die Domain MX-Records hat.
     * @return bool True, wenn die E-Mail-Adresse gültig ist.
     */
    public static function validateEmail(?string $email, bool $checkDns = false): bool {
        if (!self::isEmail($email)) {
            return false;
        }

        $email = self::normalize($email);

        // Prüfe auf ungültige Zeichen im lokalen Teil
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return false;
        }

        [$localPart, $domain] = $parts;

        // Lokaler Teil darf nicht mit Punkt beginnen oder enden
        if (str_starts_with($localPart, '.') || str_ends_with($localPart, '.')) {
            return false;
        }

        // Keine aufeinanderfolgenden Punkte
        if (str_contains($localPart, '..')) {
            return false;
        }

        // Domain-Prüfung
        if (!self::isValidDomain($domain)) {
            return false;
        }

        // Optional: DNS-Prüfung
        if ($checkDns && !self::hasMxRecord($domain)) {
            return false;
        }

        return true;
    }

    /**
     * Normalisiert eine E-Mail-Adresse.
     *
     * - Entfernt führende/trailing Leerzeichen
     * - Konvertiert zu Kleinbuchstaben
     * - Entfernt optionale Punkte im lokalen Teil (für Gmail-Kompatibilität)
     *
     * @param string $email Die zu normalisierende E-Mail-Adresse.
     * @param bool $removeDots Bei true werden Punkte im lokalen Teil entfernt.
     * @return string Die normalisierte E-Mail-Adresse.
     */
    public static function normalize(string $email, bool $removeDots = false): string {
        $email = strtolower(trim($email));

        if (!$removeDots) {
            return $email;
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        // Gmail und einige andere ignorieren Punkte im lokalen Teil
        $localPart = str_replace('.', '', $parts[0]);

        return $localPart . '@' . $parts[1];
    }

    /**
     * Extrahiert den lokalen Teil einer E-Mail-Adresse.
     *
     * @param string $email Die E-Mail-Adresse.
     * @return string|null Der lokale Teil oder null.
     */
    public static function extractLocalPart(string $email): ?string {
        if (!self::isEmail($email)) {
            return null;
        }

        $parts = explode('@', $email);

        return $parts[0] ?? null;
    }

    /**
     * Extrahiert die Domain einer E-Mail-Adresse.
     *
     * @param string $email Die E-Mail-Adresse.
     * @return string|null Die Domain oder null.
     */
    public static function extractDomain(string $email): ?string {
        if (!self::isEmail($email)) {
            return null;
        }

        $parts = explode('@', $email);

        return $parts[1] ?? null;
    }

    /**
     * Prüft, ob die E-Mail-Domain eine bekannte Wegwerf-Domain ist.
     *
     * @param string $email Die zu prüfende E-Mail-Adresse.
     * @return bool True, wenn es eine Wegwerf-E-Mail ist.
     */
    public static function isDisposableEmail(string $email): bool {
        $domain = self::extractDomain($email);

        if ($domain === null) {
            return false;
        }

        $domain = strtolower($domain);

        return in_array($domain, self::DISPOSABLE_DOMAINS, true);
    }

    /**
     * Prüft, ob die E-Mail von einem kostenlosen E-Mail-Provider stammt.
     *
     * @param string $email Die zu prüfende E-Mail-Adresse.
     * @return bool True, wenn es ein kostenloser Provider ist.
     */
    public static function isFreeEmailProvider(string $email): bool {
        $freeProviders = [
            'gmail.com',
            'googlemail.com',
            'yahoo.com',
            'yahoo.de',
            'hotmail.com',
            'hotmail.de',
            'outlook.com',
            'outlook.de',
            'live.com',
            'live.de',
            'msn.com',
            'aol.com',
            'web.de',
            'gmx.de',
            'gmx.net',
            'gmx.at',
            'gmx.ch',
            't-online.de',
            'freenet.de',
            'arcor.de',
            'vodafone.de',
            'mail.de',
            'email.de',
            'posteo.de',
            'mailbox.org',
            'icloud.com',
            'me.com',
            'mac.com',
            'protonmail.com',
            'proton.me',
            'tutanota.com',
            'tutanota.de',
        ];

        $domain = self::extractDomain($email);

        if ($domain === null) {
            return false;
        }

        return in_array(strtolower($domain), $freeProviders, true);
    }

    /**
     * Generiert eine maskierte Version der E-Mail-Adresse für die Anzeige.
     *
     * @param string $email Die zu maskierende E-Mail-Adresse.
     * @param int $showChars Anzahl der sichtbaren Zeichen am Anfang/Ende des lokalen Teils.
     * @return string Die maskierte E-Mail (z.B. "jo***hn@example.com").
     */
    public static function mask(string $email, int $showChars = 2): string {
        if (!self::isEmail($email)) {
            return $email;
        }

        $parts = explode('@', $email);
        $localPart = $parts[0];
        $domain = $parts[1];

        $length = strlen($localPart);

        if ($length <= $showChars * 2) {
            // Zu kurz zum Maskieren
            return $localPart[0] . str_repeat('*', $length - 1) . '@' . $domain;
        }

        $start = substr($localPart, 0, $showChars);
        $end = substr($localPart, -$showChars);
        $masked = $start . str_repeat('*', $length - $showChars * 2) . $end;

        return $masked . '@' . $domain;
    }

    /**
     * Erstellt einen Mailto-Link.
     *
     * @param string $email Die E-Mail-Adresse.
     * @param string|null $subject Optionaler Betreff.
     * @param string|null $body Optionaler Nachrichtentext.
     * @return string Der Mailto-Link.
     */
    public static function createMailtoLink(string $email, ?string $subject = null, ?string $body = null): string {
        if (!self::isEmail($email)) {
            return '';
        }

        $link = 'mailto:' . rawurlencode($email);
        $params = [];

        if ($subject !== null) {
            $params[] = 'subject=' . rawurlencode($subject);
        }

        if ($body !== null) {
            $params[] = 'body=' . rawurlencode($body);
        }

        if (!empty($params)) {
            $link .= '?' . implode('&', $params);
        }

        return $link;
    }

    /**
     * Vergleicht zwei E-Mail-Adressen (case-insensitive).
     *
     * @param string $email1 Erste E-Mail-Adresse.
     * @param string $email2 Zweite E-Mail-Adresse.
     * @param bool $normalizeGmail Bei true werden Gmail-Punkte ignoriert.
     * @return bool True, wenn die Adressen gleich sind.
     */
    public static function equals(string $email1, string $email2, bool $normalizeGmail = false): bool {
        $e1 = self::normalize($email1, $normalizeGmail && self::isGmailAddress($email1));
        $e2 = self::normalize($email2, $normalizeGmail && self::isGmailAddress($email2));

        return $e1 === $e2;
    }

    /**
     * Prüft, ob es eine Gmail-Adresse ist.
     *
     * @param string $email Die E-Mail-Adresse.
     * @return bool True, wenn Gmail.
     */
    public static function isGmailAddress(string $email): bool {
        $domain = self::extractDomain($email);

        if ($domain === null) {
            return false;
        }

        return in_array(strtolower($domain), ['gmail.com', 'googlemail.com'], true);
    }

    // ========================================
    // Private Methoden
    // ========================================

    /**
     * Prüft, ob eine Domain gültig ist.
     *
     * @param string $domain Die zu prüfende Domain.
     * @return bool True wenn gültig.
     */
    private static function isValidDomain(string $domain): bool {
        // Mindestens ein Punkt erforderlich
        if (!str_contains($domain, '.')) {
            return false;
        }

        // TLD muss mindestens 2 Zeichen haben
        $tld = substr($domain, strrpos($domain, '.') + 1);
        if (strlen($tld) < 2) {
            return false;
        }

        // Keine Bindestriche am Anfang oder Ende
        if (str_starts_with($domain, '-') || str_ends_with($domain, '-')) {
            return false;
        }

        // Gültiges Domain-Format
        return preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/', $domain) === 1;
    }

    /**
     * Prüft, ob die Domain MX-Records hat.
     *
     * @param string $domain Die zu prüfende Domain.
     * @return bool True wenn MX-Records vorhanden.
     */
    private static function hasMxRecord(string $domain): bool {
        // Versuche MX-Records abzufragen
        $mxHosts = [];

        if (getmxrr($domain, $mxHosts)) {
            return !empty($mxHosts);
        }

        // Fallback: Prüfe auf A-Record
        return checkdnsrr($domain, 'A');
    }
}
