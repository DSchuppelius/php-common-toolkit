<?php
/*
 * Created on   : Sun Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GLAccountDescription.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Documents;

use CommonToolkit\Entities\Common\CSV\ColumnWidthConfig;
use CommonToolkit\Entities\Common\CSV\HeaderLine;
use CommonToolkit\Contracts\Abstracts\DATEV\Document;
use CommonToolkit\Entities\DATEV\MetaHeaderLine;
use CommonToolkit\Enums\Common\CSV\TruncationStrategy;
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;
use CommonToolkit\Enums\DATEV\HeaderFields\V700\GLAccountDescriptionHeaderField;
use CommonToolkit\Enums\LanguageCode;
use RuntimeException;

/**
 * DATEV-Kontenbeschriftungen-Dokument.
 * Spezielle Document-Klasse für Kontenbeschriftungen-Format (Kategorie 20).
 * 
 * Die Spaltenbreiten werden automatisch basierend auf den DATEV-Spezifikationen
 * aus GLAccountDescriptionHeaderField::getMaxLength() angewendet.
 */
final class GLAccountDescription extends Document {
    public function __construct(?MetaHeaderLine $metaHeader, ?HeaderLine $header, array $rows = []) {
        parent::__construct($metaHeader, $header, $rows);
    }

    /**
     * Erstellt eine ColumnWidthConfig basierend auf den DATEV-Spezifikationen.
     * Die maximalen Feldlängen werden aus GLAccountDescriptionHeaderField::getMaxLength() abgeleitet.
     * 
     * @param TruncationStrategy $strategy Abschneidungsstrategie (Standard: TRUNCATE für DATEV-Konformität)
     * @return ColumnWidthConfig
     */
    public static function createDatevColumnWidthConfig(TruncationStrategy $strategy = TruncationStrategy::TRUNCATE): ColumnWidthConfig {
        $config = new ColumnWidthConfig(null, $strategy);

        foreach (GLAccountDescriptionHeaderField::ordered() as $index => $field) {
            $maxLength = $field->getMaxLength();
            if ($maxLength !== null) {
                $config->setColumnWidth($index, $maxLength);
            }
        }

        return $config;
    }

    /**
     * Liefert die DATEV-Kategorie für diese Document-Art.
     */
    public function getCategory(): Category {
        return Category::Sachkontenbeschriftungen;
    }

    /**
     * Gibt den DATEV-Format-Typ zurück.
     */
    public function getFormatType(): string {
        return Category::Sachkontenbeschriftungen->nameValue();
    }

    /**
     * Validiert Kontenbeschriftungen-spezifische Regeln.
     */
    public function validate(): void {
        parent::validate();

        $metaFields = $this->getMetaHeader()?->getFields() ?? [];
        if (count($metaFields) > 2 && (int)$metaFields[2]->getValue() !== 20) {
            throw new RuntimeException('Ungültige Kategorie für Kontenbeschriftungen-Dokument. Erwartet: 20');
        }
    }

    // ==== ACCOUNT FIELDS ====

    /**
     * Gibt die Kontonummer zurück (Feld 1).
     */
    public function getAccountNumber(int $rowIndex): ?int {
        $value = $this->getFieldValue($rowIndex, GLAccountDescriptionHeaderField::Konto->getPosition());
        if ($value === null) return null;

        $cleanValue = trim($value, '"');
        if ($cleanValue === '') return null;

        return (int) $cleanValue;
    }

    /**
     * Setzt die Kontonummer (Feld 1).
     */
    public function setAccountNumber(int $rowIndex, int $accountNumber): void {
        $this->setFieldValue($rowIndex, GLAccountDescriptionHeaderField::Konto->getPosition(), (string) $accountNumber);
    }

    /**
     * Gibt die Kontenbeschriftung zurück (Feld 2, max 40 Zeichen).
     */
    public function getAccountDescription(int $rowIndex): ?string {
        $value = $this->getFieldValue($rowIndex, GLAccountDescriptionHeaderField::Kontenbeschriftung->getPosition());
        if ($value === null) return null;

        return trim($value, '"');
    }

    /**
     * Setzt die Kontenbeschriftung (Feld 2, max 40 Zeichen).
     */
    public function setAccountDescription(int $rowIndex, string $description): void {
        $this->setFieldValue($rowIndex, GLAccountDescriptionHeaderField::Kontenbeschriftung->getPosition(), '"' . $description . '"');
    }

    /**
     * Gibt die lange Kontenbeschriftung zurück (Feld 4, max 300 Zeichen).
     */
    public function getLongAccountDescription(int $rowIndex): ?string {
        $value = $this->getFieldValue($rowIndex, GLAccountDescriptionHeaderField::KontenbeschriftungLang->getPosition());
        if ($value === null) return null;

        return trim($value, '"');
    }

    /**
     * Setzt die lange Kontenbeschriftung (Feld 4, max 300 Zeichen).
     */
    public function setLongAccountDescription(int $rowIndex, string $description): void {
        $this->setFieldValue($rowIndex, GLAccountDescriptionHeaderField::KontenbeschriftungLang->getPosition(), '"' . $description . '"');
    }

    // ==== LANGUAGE FIELD ====

    /**
     * Gibt die Sprach-ID zurück (Feld 3).
     * de-DE = Deutsch, en-GB = Englisch
     */
    public function getLanguageCodeValue(int $rowIndex): ?LanguageCode {
        return parent::getLanguageCode($rowIndex, GLAccountDescriptionHeaderField::SprachID->getPosition());
    }

    /**
     * Setzt die Sprach-ID (Feld 3).
     */
    public function setLanguageCodeValue(int $rowIndex, LanguageCode $languageCode): void {
        parent::setLanguageCode($rowIndex, GLAccountDescriptionHeaderField::SprachID->getPosition(), $languageCode);
    }

    // ==== CONVENIENCE METHODS ====

    /**
     * Prüft, ob eine Kontenbeschriftung auf Deutsch ist.
     */
    public function isGerman(int $rowIndex): bool {
        $lang = $this->getLanguageCodeValue($rowIndex);
        return $lang !== null && $lang->isGerman();
    }

    /**
     * Prüft, ob eine Kontenbeschriftung auf Englisch ist.
     */
    public function isEnglish(int $rowIndex): bool {
        $lang = $this->getLanguageCodeValue($rowIndex);
        return $lang !== null && $lang->isEnglish();
    }

    /**
     * Gibt alle deutschen Kontenbeschriftungen zurück.
     *
     * @return int[] Array der Row-Indices
     */
    public function getGermanDescriptions(): array {
        $result = [];
        $count = count($this->getRows());
        for ($i = 0; $i < $count; $i++) {
            if ($this->isGerman($i)) {
                $result[] = $i;
            }
        }
        return $result;
    }

    /**
     * Gibt alle englischen Kontenbeschriftungen zurück.
     *
     * @return int[] Array der Row-Indices
     */
    public function getEnglishDescriptions(): array {
        $result = [];
        $count = count($this->getRows());
        for ($i = 0; $i < $count; $i++) {
            if ($this->isEnglish($i)) {
                $result[] = $i;
            }
        }
        return $result;
    }

    /**
     * Findet eine Kontenbeschriftung für eine bestimmte Kontonummer und Sprache.
     *
     * @return int|null Row-Index oder null, wenn nicht gefunden
     */
    public function findByAccountAndLanguage(int $accountNumber, LanguageCode $languageCode): ?int {
        $count = count($this->getRows());
        for ($i = 0; $i < $count; $i++) {
            if ($this->getAccountNumber($i) === $accountNumber && $this->getLanguageCodeValue($i) === $languageCode) {
                return $i;
            }
        }
        return null;
    }
}