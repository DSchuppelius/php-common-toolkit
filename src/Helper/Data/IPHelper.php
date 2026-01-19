<?php
/*
 * Created on   : Sun Jan 19 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : IPHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use InvalidArgumentException;

/**
 * Helper-Klasse für IP-Adressen-Validierung und -Manipulation.
 *
 * Unterstützt IPv4 und IPv6 Adressen sowie CIDR-Notation.
 */
class IPHelper extends HelperAbstract {

    /**
     * Prüft, ob der Wert eine gültige IPv4-Adresse ist.
     *
     * @param string|null $value Die zu prüfende IP-Adresse.
     * @return bool True, wenn es eine gültige IPv4-Adresse ist.
     */
    public static function isIPv4(?string $value): bool {
        if ($value === null || $value === '') {
            return false;
        }
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Prüft, ob der Wert eine gültige IPv6-Adresse ist.
     *
     * @param string|null $value Die zu prüfende IP-Adresse.
     * @return bool True, wenn es eine gültige IPv6-Adresse ist.
     */
    public static function isIPv6(?string $value): bool {
        if ($value === null || $value === '') {
            return false;
        }
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Prüft, ob der Wert eine gültige IP-Adresse (IPv4 oder IPv6) ist.
     *
     * @param string|null $value Die zu prüfende IP-Adresse.
     * @return bool True, wenn es eine gültige IP-Adresse ist.
     */
    public static function isValidIP(?string $value): bool {
        if ($value === null || $value === '') {
            return false;
        }
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Prüft, ob die IP-Adresse eine private Adresse ist.
     *
     * Private Bereiche:
     * - IPv4: 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16
     * - IPv6: fc00::/7 (Unique Local Addresses)
     *
     * @param string|null $value Die zu prüfende IP-Adresse.
     * @return bool True, wenn es eine private IP-Adresse ist.
     */
    public static function isPrivateIP(?string $value): bool {
        if (!self::isValidIP($value)) {
            return false;
        }
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
    }

    /**
     * Prüft, ob die IP-Adresse eine reservierte Adresse ist.
     *
     * @param string|null $value Die zu prüfende IP-Adresse.
     * @return bool True, wenn es eine reservierte IP-Adresse ist.
     */
    public static function isReservedIP(?string $value): bool {
        if (!self::isValidIP($value)) {
            return false;
        }
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Prüft, ob die IP-Adresse eine Loopback-Adresse ist.
     *
     * Loopback-Bereiche:
     * - IPv4: 127.0.0.0/8
     * - IPv6: ::1
     *
     * @param string|null $value Die zu prüfende IP-Adresse.
     * @return bool True, wenn es eine Loopback-Adresse ist.
     */
    public static function isLoopback(?string $value): bool {
        if (!self::isValidIP($value)) {
            return false;
        }

        if (self::isIPv4($value)) {
            return str_starts_with($value, '127.');
        }

        // IPv6: ::1 oder 0:0:0:0:0:0:0:1
        $expanded = self::expandIPv6($value);
        return $expanded === '0000:0000:0000:0000:0000:0000:0000:0001';
    }

    /**
     * Prüft, ob die IP-Adresse eine Link-Local-Adresse ist.
     *
     * Link-Local-Bereiche:
     * - IPv4: 169.254.0.0/16
     * - IPv6: fe80::/10
     *
     * @param string|null $value Die zu prüfende IP-Adresse.
     * @return bool True, wenn es eine Link-Local-Adresse ist.
     */
    public static function isLinkLocal(?string $value): bool {
        if (!self::isValidIP($value)) {
            return false;
        }

        if (self::isIPv4($value)) {
            return str_starts_with($value, '169.254.');
        }

        // IPv6: fe80::/10
        $expanded = self::expandIPv6($value);
        $firstWord = hexdec(substr($expanded, 0, 4));
        return ($firstWord & 0xffc0) === 0xfe80;
    }

    /**
     * Prüft, ob die IP-Adresse eine Multicast-Adresse ist.
     *
     * Multicast-Bereiche:
     * - IPv4: 224.0.0.0/4
     * - IPv6: ff00::/8
     *
     * @param string|null $value Die zu prüfende IP-Adresse.
     * @return bool True, wenn es eine Multicast-Adresse ist.
     */
    public static function isMulticast(?string $value): bool {
        if (!self::isValidIP($value)) {
            return false;
        }

        if (self::isIPv4($value)) {
            $firstOctet = (int) explode('.', $value)[0];
            return $firstOctet >= 224 && $firstOctet <= 239;
        }

        // IPv6: ff00::/8
        return str_starts_with(strtolower($value), 'ff');
    }

    /**
     * Prüft, ob die IP-Adresse öffentlich routbar ist.
     *
     * @param string|null $value Die zu prüfende IP-Adresse.
     * @return bool True, wenn es eine öffentliche IP-Adresse ist.
     */
    public static function isPublicIP(?string $value): bool {
        if (!self::isValidIP($value)) {
            return false;
        }
        return filter_var(
            $value,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * Prüft, ob eine IP-Adresse in einem CIDR-Bereich liegt.
     *
     * @param string $ip Die zu prüfende IP-Adresse.
     * @param string $cidr Der CIDR-Bereich (z.B. "192.168.1.0/24").
     * @return bool True, wenn die IP im Bereich liegt.
     */
    public static function isInRange(string $ip, string $cidr): bool {
        if (!self::isValidIP($ip)) {
            return false;
        }

        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$range, $prefix] = explode('/', $cidr, 2);
        $prefix = (int) $prefix;

        if (!self::isValidIP($range)) {
            return false;
        }

        // IPv4 und IPv6 müssen übereinstimmen
        $ipIsV6 = self::isIPv6($ip);
        $rangeIsV6 = self::isIPv6($range);

        if ($ipIsV6 !== $rangeIsV6) {
            return false;
        }

        if ($ipIsV6) {
            return self::isInRangeIPv6($ip, $range, $prefix);
        }

        return self::isInRangeIPv4($ip, $range, $prefix);
    }

    /**
     * Prüft, ob eine IPv4-Adresse in einem CIDR-Bereich liegt.
     *
     * @param string $ip Die IPv4-Adresse.
     * @param string $range Die Netzwerk-Adresse.
     * @param int $prefix Die Präfix-Länge (0-32).
     * @return bool True, wenn die IP im Bereich liegt.
     */
    private static function isInRangeIPv4(string $ip, string $range, int $prefix): bool {
        if ($prefix < 0 || $prefix > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $rangeLong = ip2long($range);

        if ($ipLong === false || $rangeLong === false) {
            return false;
        }

        if ($prefix === 0) {
            return true;
        }

        $mask = -1 << (32 - $prefix);
        return ($ipLong & $mask) === ($rangeLong & $mask);
    }

    /**
     * Prüft, ob eine IPv6-Adresse in einem CIDR-Bereich liegt.
     *
     * @param string $ip Die IPv6-Adresse.
     * @param string $range Die Netzwerk-Adresse.
     * @param int $prefix Die Präfix-Länge (0-128).
     * @return bool True, wenn die IP im Bereich liegt.
     */
    private static function isInRangeIPv6(string $ip, string $range, int $prefix): bool {
        if ($prefix < 0 || $prefix > 128) {
            return false;
        }

        $ipBin = inet_pton($ip);
        $rangeBin = inet_pton($range);

        if ($ipBin === false || $rangeBin === false) {
            return false;
        }

        // Byte-weise Vergleich basierend auf Präfix
        $fullBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;

        // Volle Bytes vergleichen
        for ($i = 0; $i < $fullBytes; $i++) {
            if ($ipBin[$i] !== $rangeBin[$i]) {
                return false;
            }
        }

        // Restliche Bits vergleichen
        if ($remainingBits > 0 && $fullBytes < 16) {
            $mask = 0xff << (8 - $remainingBits);
            if ((ord($ipBin[$fullBytes]) & $mask) !== (ord($rangeBin[$fullBytes]) & $mask)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Konvertiert eine IPv4-Adresse in eine Ganzzahl.
     *
     * @param string $ip Die IPv4-Adresse.
     * @return int Die IP als vorzeichenlose Ganzzahl.
     * @throws InvalidArgumentException Bei ungültiger IP-Adresse.
     */
    public static function ipToLong(string $ip): int {
        if (!self::isIPv4($ip)) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige IPv4-Adresse: $ip");
        }

        $long = ip2long($ip);

        // Auf 64-Bit-Systemen können negative Werte entstehen
        if ($long < 0) {
            $long += 4294967296; // 2^32
        }

        return $long;
    }

    /**
     * Konvertiert eine Ganzzahl in eine IPv4-Adresse.
     *
     * @param int $long Die Ganzzahl (0 bis 4294967295).
     * @return string Die IPv4-Adresse.
     * @throws InvalidArgumentException Bei ungültigem Wert.
     */
    public static function longToIp(int $long): string {
        if ($long < 0 || $long > 4294967295) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Wert außerhalb des IPv4-Bereichs: $long");
        }

        return long2ip($long);
    }

    /**
     * Expandiert eine IPv6-Adresse zur vollständigen Form.
     *
     * Beispiel: "::1" wird zu "0000:0000:0000:0000:0000:0000:0000:0001"
     *
     * @param string $ip Die IPv6-Adresse.
     * @return string Die expandierte IPv6-Adresse.
     * @throws InvalidArgumentException Bei ungültiger IPv6-Adresse.
     */
    public static function expandIPv6(string $ip): string {
        if (!self::isIPv6($ip)) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige IPv6-Adresse: $ip");
        }

        $packed = inet_pton($ip);
        if ($packed === false) {
            self::logErrorAndThrow(InvalidArgumentException::class, "IPv6-Adresse konnte nicht konvertiert werden: $ip");
        }

        $hex = bin2hex($packed);
        return implode(':', str_split($hex, 4));
    }

    /**
     * Komprimiert eine IPv6-Adresse zur kürzesten Form.
     *
     * Beispiel: "0000:0000:0000:0000:0000:0000:0000:0001" wird zu "::1"
     *
     * @param string $ip Die IPv6-Adresse.
     * @return string Die komprimierte IPv6-Adresse.
     * @throws InvalidArgumentException Bei ungültiger IPv6-Adresse.
     */
    public static function compressIPv6(string $ip): string {
        if (!self::isIPv6($ip)) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige IPv6-Adresse: $ip");
        }

        $packed = inet_pton($ip);
        if ($packed === false) {
            self::logErrorAndThrow(InvalidArgumentException::class, "IPv6-Adresse konnte nicht konvertiert werden: $ip");
        }

        return inet_ntop($packed);
    }

    /**
     * Berechnet die Netzwerk-Adresse für eine IP und Präfix-Länge.
     *
     * @param string $ip Die IP-Adresse.
     * @param int $prefix Die Präfix-Länge.
     * @return string Die Netzwerk-Adresse.
     * @throws InvalidArgumentException Bei ungültiger IP-Adresse.
     */
    public static function getNetworkAddress(string $ip, int $prefix): string {
        if (self::isIPv4($ip)) {
            return self::getNetworkAddressIPv4($ip, $prefix);
        }

        if (self::isIPv6($ip)) {
            return self::getNetworkAddressIPv6($ip, $prefix);
        }

        self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige IP-Adresse: $ip");
    }

    /**
     * Berechnet die Netzwerk-Adresse für eine IPv4-Adresse.
     *
     * @param string $ip Die IPv4-Adresse.
     * @param int $prefix Die Präfix-Länge (0-32).
     * @return string Die Netzwerk-Adresse.
     */
    private static function getNetworkAddressIPv4(string $ip, int $prefix): string {
        if ($prefix < 0 || $prefix > 32) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige Präfix-Länge für IPv4: $prefix");
        }

        $long = ip2long($ip);
        $mask = $prefix === 0 ? 0 : (-1 << (32 - $prefix));
        return long2ip($long & $mask);
    }

    /**
     * Berechnet die Netzwerk-Adresse für eine IPv6-Adresse.
     *
     * @param string $ip Die IPv6-Adresse.
     * @param int $prefix Die Präfix-Länge (0-128).
     * @return string Die Netzwerk-Adresse.
     */
    private static function getNetworkAddressIPv6(string $ip, int $prefix): string {
        if ($prefix < 0 || $prefix > 128) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige Präfix-Länge für IPv6: $prefix");
        }

        $packed = inet_pton($ip);
        $result = str_repeat("\x00", 16);

        $fullBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;

        for ($i = 0; $i < $fullBytes; $i++) {
            $result[$i] = $packed[$i];
        }

        if ($remainingBits > 0 && $fullBytes < 16) {
            $mask = 0xff << (8 - $remainingBits);
            $result[$fullBytes] = chr(ord($packed[$fullBytes]) & $mask);
        }

        return inet_ntop($result);
    }

    /**
     * Berechnet die Broadcast-Adresse für eine IPv4-Adresse und Präfix-Länge.
     *
     * Hinweis: IPv6 hat keine Broadcast-Adressen, nur Multicast.
     *
     * @param string $ip Die IPv4-Adresse.
     * @param int $prefix Die Präfix-Länge (0-32).
     * @return string Die Broadcast-Adresse.
     * @throws InvalidArgumentException Bei ungültiger IPv4-Adresse.
     */
    public static function getBroadcastAddress(string $ip, int $prefix): string {
        if (!self::isIPv4($ip)) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Broadcast-Adressen existieren nur für IPv4: $ip");
        }

        if ($prefix < 0 || $prefix > 32) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige Präfix-Länge für IPv4: $prefix");
        }

        $long = ip2long($ip);
        $mask = $prefix === 0 ? 0 : (-1 << (32 - $prefix));
        $hostMask = ~$mask;

        return long2ip(($long & $mask) | ($hostMask & 0xffffffff));
    }

    /**
     * Berechnet die Start- und End-IP einer CIDR-Range.
     *
     * @param string $cidr Der CIDR-Bereich (z.B. "192.168.1.0/24").
     * @return array{start: string, end: string, network: string, prefix: int, count: string}
     * @throws InvalidArgumentException Bei ungültigem CIDR-Format.
     */
    public static function getCIDRRange(string $cidr): array {
        if (!str_contains($cidr, '/')) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültiges CIDR-Format: $cidr");
        }

        [$ip, $prefix] = explode('/', $cidr, 2);
        $prefix = (int) $prefix;

        if (self::isIPv4($ip)) {
            return self::getCIDRRangeIPv4($ip, $prefix);
        }

        if (self::isIPv6($ip)) {
            return self::getCIDRRangeIPv6($ip, $prefix);
        }

        self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige IP-Adresse im CIDR: $cidr");
    }

    /**
     * Berechnet die Range für einen IPv4-CIDR-Bereich.
     *
     * @param string $ip Die IPv4-Adresse.
     * @param int $prefix Die Präfix-Länge.
     * @return array{start: string, end: string, network: string, broadcast: string, prefix: int, count: string}
     */
    private static function getCIDRRangeIPv4(string $ip, int $prefix): array {
        if ($prefix < 0 || $prefix > 32) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige Präfix-Länge für IPv4: $prefix");
        }

        $network = self::getNetworkAddressIPv4($ip, $prefix);
        $broadcast = self::getBroadcastAddress($ip, $prefix);
        $count = bcpow('2', (string)(32 - $prefix));

        return [
            'start'     => $network,
            'end'       => $broadcast,
            'network'   => $network,
            'broadcast' => $broadcast,
            'prefix'    => $prefix,
            'count'     => $count,
        ];
    }

    /**
     * Berechnet die Range für einen IPv6-CIDR-Bereich.
     *
     * @param string $ip Die IPv6-Adresse.
     * @param int $prefix Die Präfix-Länge.
     * @return array{start: string, end: string, network: string, prefix: int, count: string}
     */
    private static function getCIDRRangeIPv6(string $ip, int $prefix): array {
        if ($prefix < 0 || $prefix > 128) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige Präfix-Länge für IPv6: $prefix");
        }

        $network = self::getNetworkAddressIPv6($ip, $prefix);

        // End-Adresse berechnen
        $packed = inet_pton($network);
        $result = $packed;

        $hostBits = 128 - $prefix;
        $fullBytes = intdiv($hostBits, 8);
        $remainingBits = $hostBits % 8;

        // Alle Host-Bits auf 1 setzen
        for ($i = 15; $i >= 16 - $fullBytes; $i--) {
            $result[$i] = "\xff";
        }

        if ($remainingBits > 0) {
            $byteIndex = 15 - $fullBytes;
            $mask = (1 << $remainingBits) - 1;
            $result[$byteIndex] = chr(ord($result[$byteIndex]) | $mask);
        }

        $end = inet_ntop($result);
        $count = bcpow('2', (string)(128 - $prefix));

        return [
            'start'   => $network,
            'end'     => $end,
            'network' => $network,
            'prefix'  => $prefix,
            'count'   => $count,
        ];
    }

    /**
     * Konvertiert eine Subnetzmaske in eine Präfix-Länge.
     *
     * Beispiel: "255.255.255.0" wird zu 24
     *
     * @param string $mask Die Subnetzmaske.
     * @return int Die Präfix-Länge.
     * @throws InvalidArgumentException Bei ungültiger Subnetzmaske.
     */
    public static function maskToPrefix(string $mask): int {
        if (!self::isIPv4($mask)) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige Subnetzmaske: $mask");
        }

        $long = ip2long($mask);
        if ($long < 0) {
            $long += 4294967296;
        }

        // Prüfen ob gültige Maske (nur führende 1en, dann 0en)
        $inverted = ~$long & 0xffffffff;
        if (($inverted & ($inverted + 1)) !== 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige Subnetzmaske (nicht kontiguierlich): $mask");
        }

        $binary = sprintf('%032b', $long);
        return substr_count($binary, '1');
    }

    /**
     * Konvertiert eine Präfix-Länge in eine Subnetzmaske.
     *
     * Beispiel: 24 wird zu "255.255.255.0"
     *
     * @param int $prefix Die Präfix-Länge (0-32).
     * @return string Die Subnetzmaske.
     * @throws InvalidArgumentException Bei ungültiger Präfix-Länge.
     */
    public static function prefixToMask(int $prefix): string {
        if ($prefix < 0 || $prefix > 32) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige Präfix-Länge: $prefix");
        }

        if ($prefix === 0) {
            return '0.0.0.0';
        }

        $mask = (-1 << (32 - $prefix)) & 0xffffffff;
        return long2ip($mask);
    }

    /**
     * Gibt den IP-Typ als String zurück.
     *
     * @param string|null $ip Die IP-Adresse.
     * @return string|null "IPv4", "IPv6" oder null wenn ungültig.
     */
    public static function getIPVersion(?string $ip): ?string {
        if (self::isIPv4($ip)) {
            return 'IPv4';
        }
        if (self::isIPv6($ip)) {
            return 'IPv6';
        }
        return null;
    }

    /**
     * Normalisiert eine IP-Adresse.
     *
     * - IPv4: Entfernt führende Nullen (z.B. "192.168.001.001" -> "192.168.1.1")
     * - IPv6: Komprimiert zur kürzesten Form
     *
     * @param string $ip Die IP-Adresse.
     * @return string Die normalisierte IP-Adresse.
     * @throws InvalidArgumentException Bei ungültiger IP-Adresse.
     */
    public static function normalize(string $ip): string {
        // Versuche IPv4 mit führenden Nullen zu normalisieren
        if (preg_match('/^\d{1,3}(\.\d{1,3}){3}$/', $ip)) {
            $parts = array_map('intval', explode('.', $ip));
            // Prüfen ob alle Oktette gültig sind
            foreach ($parts as $octet) {
                if ($octet < 0 || $octet > 255) {
                    self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige IPv4-Adresse: $ip");
                }
            }
            return implode('.', $parts);
        }

        if (self::isIPv4($ip)) {
            return long2ip(ip2long($ip));
        }

        if (self::isIPv6($ip)) {
            return self::compressIPv6($ip);
        }

        self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige IP-Adresse: $ip");
    }

    /**
     * Vergleicht zwei IP-Adressen.
     *
     * @param string $ip1 Erste IP-Adresse.
     * @param string $ip2 Zweite IP-Adresse.
     * @return int -1 wenn ip1 < ip2, 0 wenn gleich, 1 wenn ip1 > ip2.
     * @throws InvalidArgumentException Bei ungültigen IP-Adressen.
     */
    public static function compare(string $ip1, string $ip2): int {
        if (!self::isValidIP($ip1)) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige IP-Adresse: $ip1");
        }
        if (!self::isValidIP($ip2)) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige IP-Adresse: $ip2");
        }

        $packed1 = inet_pton($ip1);
        $packed2 = inet_pton($ip2);

        // Unterschiedliche IP-Versionen
        if (strlen($packed1) !== strlen($packed2)) {
            return strlen($packed1) <=> strlen($packed2);
        }

        return strcmp($packed1, $packed2);
    }
}