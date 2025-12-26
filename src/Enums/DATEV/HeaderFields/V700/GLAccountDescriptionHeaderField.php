<?php
/*
 * Created on   : Sun Dec 15 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GLAccountDescriptionHeaderField.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV\HeaderFields\V700;

use CommonToolkit\Contracts\Interfaces\DATEV\FieldHeaderInterface;
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;

/**
 * DATEV Sachkontenbeschriftung (GL Account Description) - Feldheader V700.
 * Vollständige Implementierung aller 4 DATEV-Felder für Sachkontenbeschriftungen
 * basierend auf der offiziellen DATEV-Spezifikation.
 * 
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/gl-account-description
 */
enum GLAccountDescriptionHeaderField: string implements FieldHeaderInterface {
    // Alle 4 Felder für Sachkontenbeschriftungen
    case Konto                       = 'Konto';                                    // 1
    case Kontenbeschriftung          = 'Kontenbeschriftung';                       // 2
    case SprachID                    = 'Sprach-ID';                               // 3
    case KontenbeschriftungLang      = 'Kontenbeschriftung lang';                 // 4

    /**
     * Liefert alle 4 Felder in der korrekten DATEV-Reihenfolge.
     */
    public static function ordered(): array {
        return [
            self::Konto,                       // 1
            self::Kontenbeschriftung,          // 2
            self::SprachID,                    // 3
            self::KontenbeschriftungLang,      // 4
        ];
    }

    /**
     * Liefert alle verpflichtenden Felder.
     */
    public static function required(): array {
        return [
            self::Konto,                       // Pflichtfeld: Kontonummer muss angegeben werden
            self::Kontenbeschriftung,          // Pflichtfeld: Beschriftung des Kontos
        ];
    }

    /**
     * Liefert alle Sprachfelder.
     */
    public static function languageFields(): array {
        return [
            self::SprachID,
        ];
    }

    /**
     * Liefert alle Beschriftungsfelder.
     */
    public static function descriptionFields(): array {
        return [
            self::Kontenbeschriftung,
            self::KontenbeschriftungLang,
        ];
    }

    /**
     * Prüft, ob ein Feld verpflichtend ist.
     */
    public function isRequired(): bool {
        return in_array($this, self::required(), true);
    }

    /**
     * Prüft, ob ein Feld für Sprachkonfiguration relevant ist.
     */
    public function isLanguageField(): bool {
        return in_array($this, self::languageFields(), true);
    }

    /**
     * Prüft, ob ein Feld für Beschriftungen relevant ist.
     */
    public function isDescriptionField(): bool {
        return in_array($this, self::descriptionFields(), true);
    }

    /**
     * Liefert eine Beschreibung des Feldtyps.
     */
    public function getFieldType(): string {
        return match ($this) {
            self::Konto => 'integer',
            self::SprachID => 'enum',
            self::Kontenbeschriftung, self::KontenbeschriftungLang => 'string',
        };
    }

    /**
     * Liefert die maximale Feldlänge für DATEV.
     */
    public function getMaxLength(): ?int {
        return match ($this) {
            self::Konto => 9,
            self::Kontenbeschriftung => 40,
            self::SprachID => 5,       // "de-DE" oder "en-GB"
            self::KontenbeschriftungLang => 300,
        };
    }

    /**
     * Liefert das Regex-Pattern für DATEV-Validierung.
     */
    public function getValidationPattern(): ?string {
        return match ($this) {
            self::Konto => '^(?!0{1,9}$)(\d{1,9})$',
            self::Kontenbeschriftung => '^("(.){0,40}")$',
            self::SprachID => '^("de-DE"|"en-GB")$',
            self::KontenbeschriftungLang => '^("(.){0,300}")$',
        };
    }

    /**
     * Liefert die unterstützten Sprach-IDs.
     */
    public static function getSupportedLanguages(): array {
        return [
            'de-DE' => 'Deutsch',
            'en-GB' => 'Englisch',
        ];
    }

    /**
     * Prüft, ob eine Sprach-ID gültig ist.
     */
    public static function isValidLanguageId(string $languageId): bool {
        return array_key_exists($languageId, self::getSupportedLanguages());
    }

    /**
     * Liefert die Beschreibung einer Sprach-ID.
     */
    public static function getLanguageDescription(string $languageId): ?string {
        return self::getSupportedLanguages()[$languageId] ?? null;
    }

    /**
     * Liefert die DATEV-Kategorie für dieses Header-Format.
     */
    public static function getCategory(): Category {
        return Category::Sachkontenbeschriftungen;
    }

    /**
     * Liefert die DATEV-Version für dieses Header-Format.
     */
    public static function getVersion(): int {
        return 700;
    }

    /**
     * Liefert die Anzahl der definierten Felder.
     */
    public static function getFieldCount(): int {
        return count(self::ordered());
    }

    /**
     * Prüft, ob ein Feldwert gültig ist (im Enum enthalten).
     */
    public static function isValidFieldValue(string $value): bool {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gibt an, ob der FieldHeader (Spaltenüberschrift) in Anführungszeichen gesetzt wird.
     * DATEV-FieldHeaders werden NICHT gequoted.
     */
    public function isQuotedHeader(): bool {
        return false;
    }

    /**
     * Gibt an, ob der Feldwert in Anführungszeichen gesetzt wird.
     * Basierend auf dem Validierungspattern: Pattern mit ^["]... oder ^(["... = gequotet
     */
    public function isQuotedValue(): bool {
        $pattern = $this->getValidationPattern();
        if ($pattern === null) {
            return true; // Default: gequotet (sicherer für Text)
        }
        // Prüfe ob Pattern mit Anführungszeichen beginnt
        return (bool) preg_match('/^\^(\(?\[?"|\(?")/u', $pattern);
    }

    /**
     * Liefert den tatsächlichen Header-Namen für die CSV-Ausgabe.
     * Weicht ggf. vom Enum-Wert ab, um Kompatibilität mit DATEV-Sample-Dateien zu gewährleisten.
     */
    public function headerName(): string {
        return match ($this) {
            self::Kontenbeschriftung => 'Kontobeschriftung',  // Sample: ohne 'en'
            self::SprachID => 'SprachId',                     // Sample: ohne Bindestrich
            default => $this->value,
        };
    }
}
