<?php
/*
 * Created on   : Mon Dec 15 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingBatch.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Documents;

use CommonToolkit\Entities\Common\CSV\ColumnWidthConfig;
use CommonToolkit\Entities\Common\CSV\HeaderLine;
use CommonToolkit\Contracts\Abstracts\DATEV\Document;
use CommonToolkit\Entities\DATEV\{DocumentInfo, MetaHeaderLine};
use CommonToolkit\Enums\Common\CSV\TruncationStrategy;
use CommonToolkit\Enums\DATEV\{DiscountLock, DiscountType, InterestLock, PostingLock};
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;
use CommonToolkit\Enums\DATEV\HeaderFields\V700\BookingBatchHeaderField;
use CommonToolkit\Enums\{CreditDebit, CurrencyCode, CountryCode};
use RuntimeException;

/**
 * DATEV-BookingBatch-Dokument.
 * Spezielle Document-Klasse für BookingBatch-Format (Kategorie 21).
 * 
 * Die Spaltenbreiten werden automatisch basierend auf den DATEV-Spezifikationen
 * aus BookingBatchHeaderField::getMaxLength() angewendet.
 */
final class BookingBatch extends Document {
    public function __construct(?MetaHeaderLine $metaHeader, ?HeaderLine $header, array $rows = []) {
        parent::__construct($metaHeader, $header, $rows);
    }

    /**
     * Erstellt eine ColumnWidthConfig basierend auf den DATEV-Spezifikationen.
     * Die maximalen Feldlängen werden aus BookingBatchHeaderField::getMaxLength() abgeleitet.
     * 
     * @param TruncationStrategy $strategy Abschneidungsstrategie (Standard: TRUNCATE für DATEV-Konformität)
     * @return ColumnWidthConfig
     */
    public static function createDatevColumnWidthConfig(TruncationStrategy $strategy = TruncationStrategy::TRUNCATE): ColumnWidthConfig {
        $config = new ColumnWidthConfig(null, $strategy);

        foreach (BookingBatchHeaderField::ordered() as $index => $field) {
            $maxLength = $field->getMaxLength();
            if ($maxLength !== null) {
                $config->setColumnWidth($index, $maxLength);
            }
        }

        return $config;
    }

    /**
     * Gibt den DATEV-Format-Typ zurück.
     */
    public function getFormatType(): string {
        return Category::Buchungsstapel->nameValue();
    }

    /**
     * Gibt die Format-Informationen zurück.
     */
    public function getDocumentInfo(): DocumentInfo {
        return new DocumentInfo(Category::Buchungsstapel, 700);
    }

    /**
     * Validiert, dass es sich um ein BookingBatch-Format handelt.
     */
    public function validate(): void {
        parent::validate();

        // Zusätzliche Validierung für BookingBatch
        if ($this->getMetaHeader() !== null) {
            $metaFields = $this->getMetaHeader()->getFields();
            if (count($metaFields) > 2 && $metaFields[2]->getValue() !== '21') {
                throw new RuntimeException('Document ist kein BookingBatch-Format');
            }
        }
    }


    // ==== BOOKINGBATCH-SPEZIFISCHE ENUM GETTER/SETTER ====

    /**
     * Gibt das Soll/Haben-Kennzeichen einer Buchung zurück.
     */
    public function getSollHabenKennzeichen(int $rowIndex): ?CreditDebit {
        return $this->getCreditDebit($rowIndex, BookingBatchHeaderField::SollHabenKennzeichen->getPosition());
    }

    /**
     * Setzt das Soll/Haben-Kennzeichen einer Buchung.
     */
    public function setSollHabenKennzeichen(int $rowIndex, CreditDebit $creditDebit): void {
        $this->setCreditDebit($rowIndex, BookingBatchHeaderField::SollHabenKennzeichen->getPosition(), $creditDebit);
    }

    /**
     * Gibt die Basiswährung einer Buchung zurück.
     */
    public function getWKZBasisUmsatz(int $rowIndex): ?CurrencyCode {
        return $this->getCurrencyCode($rowIndex, BookingBatchHeaderField::WKZBasisUmsatz->getPosition());
    }

    /**
     * Setzt die Basiswährung einer Buchung.
     */
    public function setWKZBasisUmsatz(int $rowIndex, CurrencyCode $currencyCode): void {
        $this->setCurrencyCode($rowIndex, BookingBatchHeaderField::WKZBasisUmsatz->getPosition(), $currencyCode);
    }

    /**
     * Gibt die Umsatzwährung einer Buchung zurück.
     */
    public function getWKZUmsatz(int $rowIndex): ?CurrencyCode {
        return $this->getCurrencyCode($rowIndex, BookingBatchHeaderField::WKZUmsatz->getPosition());
    }

    /**
     * Setzt die Umsatzwährung einer Buchung.
     */
    public function setWKZUmsatz(int $rowIndex, CurrencyCode $currencyCode): void {
        $this->setCurrencyCode($rowIndex, BookingBatchHeaderField::WKZUmsatz->getPosition(), $currencyCode);
    }

    /**
     * Gibt das EU-Land einer Buchung zurück.
     */
    public function getEULandUStID(int $rowIndex): ?CountryCode {
        return $this->getCountryCode($rowIndex, BookingBatchHeaderField::EULandUStID->getPosition());
    }

    /**
     * Setzt das EU-Land einer Buchung.
     */
    public function setEULandUStID(int $rowIndex, CountryCode $countryCode): void {
        $this->setCountryCode($rowIndex, BookingBatchHeaderField::EULandUStID->getPosition(), $countryCode);
    }

    /**
     * Gibt das Land einer Buchung zurück.
     */
    public function getLand(int $rowIndex): ?CountryCode {
        return $this->getCountryCode($rowIndex, BookingBatchHeaderField::Land->getPosition());
    }

    /**
     * Setzt das Land einer Buchung.
     */
    public function setLand(int $rowIndex, CountryCode $countryCode): void {
        $this->setCountryCode($rowIndex, BookingBatchHeaderField::Land->getPosition(), $countryCode);
    }


    // ==== CONVENIENCE METHODS ====

    /**
     * Prüft, ob eine Buchung ein EU-Land hat.
     */
    public function isEUBooking(int $rowIndex): bool {
        $country = $this->getEULandUStID($rowIndex) ?? $this->getLand($rowIndex);
        return $country?->isEU() ?? false;
    }

    /**
     * Prüft, ob eine Buchung Euro als Währung nutzt.
     */
    public function isEuroCurrency(int $rowIndex): bool {
        $currency = $this->getWKZUmsatz($rowIndex) ?? $this->getWKZBasisUmsatz($rowIndex);
        return $currency === CurrencyCode::Euro;
    }

    /**
     * Gibt die Zinssperre einer Buchung zurück.
     */
    public function getZinssperre(int $rowIndex): ?InterestLock {
        return $this->getInterestLock($rowIndex, BookingBatchHeaderField::Zinssperre->getPosition());
    }

    /**
     * Setzt die Zinssperre einer Buchung.
     */
    public function setZinssperre(int $rowIndex, InterestLock $interestLock): void {
        $this->setInterestLock($rowIndex, BookingBatchHeaderField::Zinssperre->getPosition(), $interestLock);
    }

    /**
     * Gibt die Skontosperre einer Buchung zurück.
     */
    public function getSkontosperre(int $rowIndex): ?DiscountLock {
        return $this->getDiscountLock($rowIndex, BookingBatchHeaderField::Skontosperre->getPosition());
    }

    /**
     * Setzt die Skontosperre einer Buchung.
     */
    public function setSkontosperre(int $rowIndex, DiscountLock $discountLock): void {
        $this->setDiscountLock($rowIndex, BookingBatchHeaderField::Skontosperre->getPosition(), $discountLock);
    }

    /**
     * Gibt den Skontotyp einer Buchung zurück.
     */
    public function getSkontoTyp(int $rowIndex): ?DiscountType {
        return $this->getDiscountType($rowIndex, BookingBatchHeaderField::SkontoTyp->getPosition());
    }

    /**
     * Setzt den Skontotyp einer Buchung.
     */
    public function setSkontoTyp(int $rowIndex, DiscountType $discountType): void {
        $this->setDiscountType($rowIndex, BookingBatchHeaderField::SkontoTyp->getPosition(), $discountType);
    }

    /**
     * Gibt die Festschreibung einer Buchung zurück.
     */
    public function getFestschreibung(int $rowIndex): ?PostingLock {
        return $this->getPostingLock($rowIndex, BookingBatchHeaderField::Festschreibung->getPosition());
    }

    /**
     * Setzt die Festschreibung einer Buchung.
     */
    public function setFestschreibung(int $rowIndex, PostingLock $postingLock): void {
        $this->setPostingLock($rowIndex, BookingBatchHeaderField::Festschreibung->getPosition(), $postingLock);
    }

    /**
     * Prüft, ob eine Buchung festgeschrieben ist.
     */
    public function isLocked(int $rowIndex): bool {
        return $this->getFestschreibung($rowIndex)?->isLocked() ?? false;
    }

    /**
     * Gibt alle Buchungen mit einem bestimmten Soll/Haben-Kennzeichen zurück.
     */
    public function getRowsByCreditDebit(CreditDebit $creditDebit): array {
        $result = [];

        foreach ($this->rows as $index => $row) {
            if ($this->getSollHabenKennzeichen($index) === $creditDebit) {
                $result[] = $index;
            }
        }

        return $result;
    }
}