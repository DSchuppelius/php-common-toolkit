<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : IpAddress.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Helper\Data\IPHelper;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;

/**
 * Immutable, validierte IP-Adresse (IPv4 oder IPv6).
 *
 * Gespeichert wird die über {@see IPHelper::normalize()} kanonisierte Form
 * (IPv6 komprimiert, Kleinschreibung) — "::1" und "0:0:0:0:0:0:0:1" sind
 * damit gleich. Klassifizierung und Bereichsprüfung delegieren an
 * {@see IPHelper}.
 *
 * SENSIBLER WERT: IP-Adressen sind personenbezogene Daten (DSGVO). Die
 * Klasse implementiert deshalb bewusst WEDER `Stringable` NOCH
 * `JsonSerializable` — der Klarwert ist ausschließlich über den bewusst
 * aufgerufenen {@see getValue()}-Getter verfügbar. Die Rolle von `masked()`
 * übernimmt {@see anonymized()}: Es liefert eine echte, gültige IP mit
 * genulltem Host-Teil (übliche Analytics-/DSGVO-Konvention).
 *
 * @example
 * ```php
 * $ip = IpAddress::of('192.168.2.77');
 * $ip->isPrivate();               // true
 * $ip->anonymized()->getValue();  // "192.168.2.0" (/24)
 *
 * IpAddress::of('2001:db8:abcd:1234::5678')->anonymized()->getValue(); // "2001:db8:abcd::" (/48)
 * ```
 */
final class IpAddress {
    use ErrorLog;

    /** Standard-Anonymisierungspräfix für IPv4 (/24). */
    private const ANON_PREFIX_V4 = 24;

    /** Standard-Anonymisierungspräfix für IPv6 (/48). */
    private const ANON_PREFIX_V6 = 48;

    /** Kanonische Darstellung (IPv6 komprimiert, Kleinschreibung). */
    private readonly string $value;

    /** IP-Version: 4 oder 6. */
    private readonly int $version;

    private function __construct(string $value, int $version) {
        $this->value = $value;
        $this->version = $version;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt eine validierte IP-Adresse.
     *
     * Die Fehlermeldung enthält bewusst nicht den Eingabewert — auch eine
     * beinahe gültige IP gehört nicht ins Log.
     *
     * @throws InvalidArgumentException Bei ungültiger Adresse.
     */
    public static function of(string $value): self {
        $ip = self::tryFrom($value);
        if ($ip === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Ungültige IP-Adresse (Länge ' . strlen(trim($value)) . ').');
        }

        return $ip;
    }

    /**
     * Wie {@see of()}, liefert aber bei `null`, leerer oder ungültiger
     * Eingabe `null` statt einer Exception.
     */
    public static function tryFrom(?string $value): ?self {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || !IPHelper::isValidIP($trimmed)) {
            return null;
        }

        return new self(IPHelper::normalize($trimmed), IPHelper::isIPv4($trimmed) ? 4 : 6);
    }

    // ========================================================================
    // Zugriff / Klassifizierung
    // ========================================================================

    /**
     * Klarwert (kanonisch, z.B. "192.168.2.77" oder "2001:db8::1") — nur
     * bewusst abrufen; für Logs/Auswertungen {@see anonymized()} verwenden.
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * IP-Version: 4 oder 6.
     */
    public function getVersion(): int {
        return $this->version;
    }

    public function isIpv4(): bool {
        return $this->version === 4;
    }

    public function isIpv6(): bool {
        return $this->version === 6;
    }

    /**
     * Privater Adressbereich (RFC 1918 bzw. ULA)?
     */
    public function isPrivate(): bool {
        return IPHelper::isPrivateIP($this->value);
    }

    /**
     * Öffentlich routbare Adresse?
     */
    public function isPublic(): bool {
        return IPHelper::isPublicIP($this->value);
    }

    public function isLoopback(): bool {
        return IPHelper::isLoopback($this->value);
    }

    public function isLinkLocal(): bool {
        return IPHelper::isLinkLocal($this->value);
    }

    public function isMulticast(): bool {
        return IPHelper::isMulticast($this->value);
    }

    public function isReserved(): bool {
        return IPHelper::isReservedIP($this->value);
    }

    /**
     * Liegt die Adresse im CIDR-Bereich (z.B. "192.168.2.0/24")?
     */
    public function isInRange(string $cidr): bool {
        return IPHelper::isInRange($this->value, $cidr);
    }

    // ========================================================================
    // Anonymisierung
    // ========================================================================

    /**
     * DSGVO-taugliche Anonymisierung: nullt den Host-Teil und liefert eine
     * echte, gültige IP-Adresse (delegiert an
     * {@see IPHelper::getNetworkAddress()}). Standardpräfix: /24 für IPv4,
     * /48 für IPv6.
     *
     * @param int|null $prefix Netzpräfix in Bits (null = Standard je Version).
     * @throws InvalidArgumentException Bei ungültiger Präfix-Länge (Verhalten des Helpers).
     */
    public function anonymized(?int $prefix = null): self {
        $prefix ??= $this->isIpv4() ? self::ANON_PREFIX_V4 : self::ANON_PREFIX_V6;

        return new self(IPHelper::normalize(IPHelper::getNetworkAddress($this->value, $prefix)), $this->version);
    }

    /**
     * Gleichheit der kanonischen Darstellung — expandierte und komprimierte
     * IPv6-Formen derselben Adresse sind gleich.
     */
    public function equals(self $other): bool {
        return $this->value === $other->value;
    }
}
