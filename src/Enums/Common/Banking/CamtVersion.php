<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CamtVersion.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking;

/**
 * CAMT Nachrichtenversionen gemäß ISO 20022.
 * 
 * Die Versionsnummern entsprechen dem Schema camt.0XX.001.VV
 * wobei VV die hier aufgeführte Versionsnummer ist.
 * 
 * @package CommonToolkit\Enums\Common\Banking
 */
enum CamtVersion: string {
    /**
     * Version 02 - Initiale weit verbreitete Version
     */
    case V02 = '02';

    /**
     * Version 04 - Erweiterungen für SEPA
     */
    case V04 = '04';

    /**
     * Version 06 - Zwischenversion (CAMT.052)
     */
    case V06 = '06';

    /**
     * Version 08 - Aktuelle Standardversion
     */
    case V08 = '08';

    /**
     * Version 10 - Neuere Erweiterungen
     */
    case V10 = '10';

    /**
     * Version 12 - Aktuellste Version
     */
    case V12 = '12';

    /**
     * Gibt den vollständigen Namespace für einen CAMT-Typ zurück.
     */
    public function getNamespace(CamtType $type): string {
        return "urn:iso:std:iso:20022:tech:xsd:{$type->value}.001.{$this->value}";
    }

    /**
     * Gibt die Versionsnummer als Integer zurück.
     */
    public function toInt(): int {
        return (int) $this->value;
    }

    /**
     * Erstellt eine Version aus einem Integer.
     */
    public static function fromInt(int $version): ?self {
        $padded = str_pad((string) $version, 2, '0', STR_PAD_LEFT);
        return self::tryFrom($padded);
    }

    /**
     * Erstellt eine Version aus einem String (mit oder ohne führende Null).
     */
    public static function fromString(string $version): ?self {
        $padded = str_pad(ltrim($version, '0') ?: '0', 2, '0', STR_PAD_LEFT);
        return self::tryFrom($padded);
    }

    /**
     * Gibt die Beschreibung der Version zurück.
     */
    public function getDescription(): string {
        return match ($this) {
            self::V02 => 'ISO 20022 Version 02 (Initial)',
            self::V04 => 'ISO 20022 Version 04 (SEPA)',
            self::V06 => 'ISO 20022 Version 06',
            self::V08 => 'ISO 20022 Version 08 (Standard)',
            self::V10 => 'ISO 20022 Version 10',
            self::V12 => 'ISO 20022 Version 12 (Aktuell)',
        };
    }

    /**
     * Prüft ob diese Version neuer ist als eine andere.
     */
    public function isNewerThan(self $other): bool {
        return $this->toInt() > $other->toInt();
    }

    /**
     * Prüft ob diese Version älter ist als eine andere.
     */
    public function isOlderThan(self $other): bool {
        return $this->toInt() < $other->toInt();
    }

    /**
     * Gibt die Standard-Version zurück (V02 für maximale Kompatibilität).
     */
    public static function default(): self {
        return self::V02;
    }
}