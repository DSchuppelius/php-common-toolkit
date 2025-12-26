<?php
/*
 * Created on   : Sun Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DebitorsCreditors.php
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
use CommonToolkit\Enums\DATEV\HeaderFields\V700\DebitorsCreditorsHeaderField;
use CommonToolkit\Enums\{CurrencyCode, CountryCode};
use RuntimeException;

/**
 * DATEV-Debitoren/Kreditoren-Dokument.
 * Spezielle Document-Klasse für Debitoren/Kreditoren-Format (Kategorie 16).
 * 
 * Die Spaltenbreiten werden automatisch basierend auf den DATEV-Spezifikationen
 * aus DebitorsCreditorsHeaderField::getMaxLength() angewendet.
 */
final class DebitorsCreditors extends Document {
    public function __construct(
        ?MetaHeaderLine $metaHeader,
        ?HeaderLine $header,
        array $rows = []
    ) {
        parent::__construct($metaHeader, $header, $rows);
    }

    /**
     * Erstellt eine ColumnWidthConfig basierend auf den DATEV-Spezifikationen.
     * Die maximalen Feldlängen werden aus DebitorsCreditorsHeaderField::getMaxLength() abgeleitet.
     * 
     * @param TruncationStrategy $strategy Abschneidungsstrategie (Standard: TRUNCATE für DATEV-Konformität)
     * @return ColumnWidthConfig
     */
    public static function createDatevColumnWidthConfig(TruncationStrategy $strategy = TruncationStrategy::TRUNCATE): ColumnWidthConfig {
        $config = new ColumnWidthConfig(null, $strategy);

        foreach (DebitorsCreditorsHeaderField::ordered() as $index => $field) {
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
        return Category::DebitorenKreditoren;
    }

    /**
     * Gibt den DATEV-Format-Typ zurück.
     */
    public function getFormatType(): string {
        return Category::DebitorenKreditoren->nameValue();
    }

    /**
     * Validiert Debitoren/Kreditoren-spezifische Regeln.
     */
    public function validate(): void {
        parent::validate();

        $metaFields = $this->getMetaHeader()?->getFields() ?? [];
        if (count($metaFields) > 2 && (int)$metaFields[2]->getValue() !== 16) {
            throw new RuntimeException('Ungültige Kategorie für Debitoren/Kreditoren-Dokument. Erwartet: 16');
        }
    }
    
    // ==== DEBITOREN/KREDITOREN-SPEZIFISCHE ENUM GETTER/SETTER ====

    /**
     * Gibt das EU-Land eines Debitors/Kreditors zurück.
     */
    public function getEULand(int $rowIndex): ?CountryCode {
        return $this->getCountryCode($rowIndex, DebitorsCreditorsHeaderField::EULand->getPosition());
    }

    /**
     * Setzt das EU-Land eines Debitors/Kreditors.
     */
    public function setEULand(int $rowIndex, CountryCode $countryCode): void {
        $this->setCountryCode($rowIndex, DebitorsCreditorsHeaderField::EULand->getPosition(), $countryCode);
    }

    /**
     * Gibt das Land eines Debitors/Kreditors zurück.
     */
    public function getLand(int $rowIndex): ?CountryCode {
        return $this->getCountryCode($rowIndex, DebitorsCreditorsHeaderField::Land->getPosition());
    }

    /**
     * Setzt das Land eines Debitors/Kreditors.
     */
    public function setLand(int $rowIndex, CountryCode $countryCode): void {
        $this->setCountryCode($rowIndex, DebitorsCreditorsHeaderField::Land->getPosition(), $countryCode);
    }

    /**
     * Gibt das Land der Rechnungsadresse zurück.
     */
    public function getLandRechnungsadresse(int $rowIndex): ?CountryCode {
        return $this->getCountryCode($rowIndex, DebitorsCreditorsHeaderField::LandRechnungsadresse->getPosition());
    }

    /**
     * Setzt das Land der Rechnungsadresse.
     */
    public function setLandRechnungsadresse(int $rowIndex, CountryCode $countryCode): void {
        $this->setCountryCode($rowIndex, DebitorsCreditorsHeaderField::LandRechnungsadresse->getPosition(), $countryCode);
    }

    /**
     * Gibt die Währungssteuerung zurück.
     */
    public function getWaehrungssteuerung(int $rowIndex): ?CurrencyCode {
        return $this->getCurrencyCode($rowIndex, DebitorsCreditorsHeaderField::Waehrungssteuerung->getPosition());
    }

    /**
     * Setzt die Währungssteuerung.
     */
    public function setWaehrungssteuerung(int $rowIndex, CurrencyCode $currencyCode): void {
        $this->setCurrencyCode($rowIndex, DebitorsCreditorsHeaderField::Waehrungssteuerung->getPosition(), $currencyCode);
    }

    // ==== CONVENIENCE METHODS ====

    /**
     * Prüft, ob ein Debitor/Kreditor in einem EU-Land ansässig ist.
     */
    public function isEUResident(int $rowIndex): bool {
        $euCountry = $this->getEULand($rowIndex);
        $country = $this->getLand($rowIndex);

        return ($euCountry?->isEU() ?? false) || ($country?->isEU() ?? false);
    }

    /**
     * Gibt alle Debitoren/Kreditoren eines bestimmten Landes zurück.
     */
    public function getRowsByCountry(CountryCode $country): array {
        $result = [];

        foreach ($this->rows as $index => $row) {
            if ($this->getLand($index) === $country || $this->getEULand($index) === $country) {
                $result[] = $index;
            }
        }

        return $result;
    }
}
