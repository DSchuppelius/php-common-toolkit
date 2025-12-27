<?php
/*
 * Created on   : Wed Nov 05 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Abstracts\DATEV;

use CommonToolkit\Entities\Common\CSV\ColumnWidthConfig;
use CommonToolkit\Entities\Common\CSV\Document as CSVDocument;
use CommonToolkit\Entities\Common\CSV\HeaderLine;
use CommonToolkit\Entities\DATEV\{DataLine, MetaHeaderLine};
use CommonToolkit\Enums\DATEV\{
    AddresseeType,
    AddressType,
    CurrencyControl,
    DirectDebitIndicator,
    DiscountLock,
    DiscountType,
    DunningIndicator,
    DunningSubject,
    EndType,
    InterestCalculationIndicator,
    InterestLock,
    ItemLock,
    Language,
    OutputTarget,
    PaymentCarrierIndicator,
    PaymentMethod,
    PostingLock,
    ReceiptFieldHandling,
    StatementIndicator,
    TimeIntervalType,
    WeekdayOrdinal
};
use CommonToolkit\Enums\{CreditDebit, CurrencyCode, CountryCode, LanguageCode};
use CommonToolkit\Helper\Data\StringHelper;
use InvalidArgumentException;
use RuntimeException;

abstract class Document extends CSVDocument {
    public const DEFAULT_DELIMITER = ';';

    private ?MetaHeaderLine $metaHeader = null;

    /** @param DataLine[] $rows */
    public function __construct(?MetaHeaderLine $metaHeader, ?HeaderLine $header, array $rows = [], ?ColumnWidthConfig $columnWidthConfig = null, string $encoding = CSVDocument::DEFAULT_ENCODING) {
        // Falls keine ColumnWidthConfig übergeben wurde, erstelle eine basierend auf DATEV-Spezifikation
        $columnWidthConfig ??= static::createDatevColumnWidthConfig();

        parent::__construct($header, $rows, ';', '"', $columnWidthConfig, $encoding);
        $this->metaHeader  = $metaHeader;
    }

    /**
     * Erstellt eine ColumnWidthConfig basierend auf den DATEV-Spezifikationen.
     * Muss von abgeleiteten Klassen überschrieben werden, um die spezifischen Feldbreiten zu definieren.
     * 
     * @return ColumnWidthConfig|null
     */
    public static function createDatevColumnWidthConfig(): ?ColumnWidthConfig {
        // Standardimplementierung gibt null zurück
        // Abgeleitete Klassen sollten dies überschreiben
        return null;
    }

    public function getMetaHeader(): ?MetaHeaderLine {
        return $this->metaHeader;
    }

    public function validate(): void {
        if (!$this->metaHeader) {
            throw new RuntimeException('DATEV-Metadatenheader fehlt.');
        }
        if (!$this->header) {
            throw new RuntimeException('DATEV-Feldheader fehlt.');
        }

        $metaValues = array_map(fn($f) => trim($f->getValue(), "\"'"), $this->metaHeader->getFields());
        if ($metaValues[0] !== 'EXTF') {
            throw new RuntimeException('Ungültiger DATEV-Metadatenheader – "EXTF" erwartet.');
        }
    }

    public function toAssoc(): array {
        $rows = parent::toAssoc();

        return [
            'meta' => [
                'format' => 'DATEV',
                'formatType' => $this->getFormatType(),
                'metaHeader' => $this->metaHeader?->toAssoc(),
                'columns' => $this->header?->countFields() ?? 0,
                'rows' => count($rows),
            ],
            'data' => $rows,
        ];
    }

    /**
     * Gibt den DATEV-Format-Typ zurück.
     * Muss von abgeleiteten Klassen implementiert werden.
     */
    abstract public function getFormatType(): string;

    /**
     * Wandelt das gesamte DATEV-Dokument in eine rohe CSV-Zeichenkette um.
     * Überschreibt die Parent-Methode, um den MetaHeader mit einzubeziehen.
     *
     * @param string|null $delimiter Das Trennzeichen. Wenn null, wird das Standard-Trennzeichen verwendet.
     * @param string|null $enclosure Das Einschlusszeichen. Wenn null, wird das Standard-Einschlusszeichen verwendet.
     * @param int|null $enclosureRepeat Die Anzahl der Enclosure-Wiederholungen.
     * @param string|null $targetEncoding Das Ziel-Encoding. Wenn null, wird das Dokument-Encoding verwendet.
     * @return string
     */
    public function toString(?string $delimiter = null, ?string $enclosure = null, ?int $enclosureRepeat = null, ?string $targetEncoding = null): string {
        $delimiter ??= $this->delimiter;
        $enclosure ??= $this->enclosure;
        $targetEncoding ??= $this->encoding;

        $lines = [];

        // MetaHeader als erste Zeile
        if ($this->metaHeader) {
            $lines[] = $this->metaHeader->toString($delimiter, $enclosure);
        }

        // Parent-Logik für Header und Datenzeilen (immer UTF-8, Konvertierung am Ende)
        $parentContent = parent::toString($delimiter, $enclosure, $enclosureRepeat, CSVDocument::DEFAULT_ENCODING);
        if ($parentContent !== '') {
            $lines[] = $parentContent;
        }

        $result = implode("\n", $lines);

        // Encoding-Konvertierung falls nötig - nutze StringHelper
        if ($targetEncoding !== CSVDocument::DEFAULT_ENCODING) {
            return StringHelper::convertEncoding($result, CSVDocument::DEFAULT_ENCODING, $targetEncoding);
        }

        return $result;
    }

    /**
     * Gibt das Dokument als String zurück (DATEV-CSV-Format).
     */
    public function __toString(): string {
        return $this->toString();
    }

    /**
     * Gibt einen Feldwert als CreditDebit-Enum zurück.
     */
    protected function getCreditDebit(int $rowIndex, int $fieldIndex): ?CreditDebit {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if (!$value) return null;

        $cleanValue = trim($value, '"');
        return match ($cleanValue) {
            'S' => CreditDebit::CREDIT,
            'H' => CreditDebit::DEBIT,
            default => null
        };
    }

    /**
     * Setzt einen Feldwert aus einem CreditDebit-Enum.
     */
    protected function setCreditDebit(int $rowIndex, int $fieldIndex, CreditDebit $creditDebit): void {
        $datevValue = match ($creditDebit) {
            CreditDebit::CREDIT => '"S"',
            CreditDebit::DEBIT => '"H"'
        };
        $this->setFieldValue($rowIndex, $fieldIndex, $datevValue);
    }

    /**
     * Gibt einen Feldwert als CurrencyCode-Enum zurück.
     */
    protected function getCurrencyCode(int $rowIndex, int $fieldIndex): ?CurrencyCode {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if (!$value) return null;

        $cleanValue = trim($value, '"');
        try {
            return CurrencyCode::fromCode($cleanValue);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Setzt einen Feldwert aus einem CurrencyCode-Enum.
     */
    protected function setCurrencyCode(int $rowIndex, int $fieldIndex, CurrencyCode $currencyCode): void {
        $datevValue = '"' . $currencyCode->value . '"';
        $this->setFieldValue($rowIndex, $fieldIndex, $datevValue);
    }

    /**
     * Gibt einen Feldwert als CountryCode-Enum zurück.
     */
    protected function getCountryCode(int $rowIndex, int $fieldIndex): ?CountryCode {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if (!$value) return null;

        $cleanValue = trim($value, '"');
        try {
            return CountryCode::fromStringValue($cleanValue);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Setzt einen Feldwert aus einem CountryCode-Enum.
     */
    protected function setCountryCode(int $rowIndex, int $fieldIndex, CountryCode $countryCode): void {
        $datevValue = '"' . $countryCode->value . '"';
        $this->setFieldValue($rowIndex, $fieldIndex, $datevValue);
    }

    /**
     * Gibt einen Feldwert als PostingLock-Enum zurück.
     */
    protected function getPostingLock(int $rowIndex, int $fieldIndex): ?PostingLock {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        try {
            return PostingLock::fromStringValue(trim($value, '"'));
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Setzt einen Feldwert aus einem PostingLock-Enum.
     */
    protected function setPostingLock(int $rowIndex, int $fieldIndex, PostingLock $postingLock): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$postingLock->value);
    }

    /**
     * Gibt einen Feldwert als InterestLock-Enum zurück.
     */
    protected function getInterestLock(int $rowIndex, int $fieldIndex): ?InterestLock {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        $cleanValue = trim($value, '"');
        if ($cleanValue === '') return null;

        return InterestLock::fromInt((int)$cleanValue);
    }

    /**
     * Setzt einen Feldwert aus einem InterestLock-Enum.
     */
    protected function setInterestLock(int $rowIndex, int $fieldIndex, InterestLock $interestLock): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$interestLock->value);
    }

    /**
     * Gibt einen Feldwert als DiscountLock-Enum zurück.
     */
    protected function getDiscountLock(int $rowIndex, int $fieldIndex): ?DiscountLock {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        $cleanValue = trim($value, '"');
        if ($cleanValue === '') return null;

        return DiscountLock::fromInt((int)$cleanValue);
    }

    /**
     * Setzt einen Feldwert aus einem DiscountLock-Enum.
     */
    protected function setDiscountLock(int $rowIndex, int $fieldIndex, DiscountLock $discountLock): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$discountLock->value);
    }

    /**
     * Gibt einen Feldwert als DiscountType-Enum zurück.
     */
    protected function getDiscountType(int $rowIndex, int $fieldIndex): ?DiscountType {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        $cleanValue = trim($value, '"');
        if ($cleanValue === '') return null;

        try {
            return DiscountType::fromInt((int)$cleanValue);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Setzt einen Feldwert aus einem DiscountType-Enum.
     */
    protected function setDiscountType(int $rowIndex, int $fieldIndex, DiscountType $discountType): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$discountType->value);
    }

    /**
     * Gibt einen Feldwert als ItemLock-Enum zurück (0/1-Sperre).
     */
    protected function getItemLock(int $rowIndex, int $fieldIndex): ?ItemLock {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        $cleanValue = trim($value, '"');
        if ($cleanValue === '') return null;

        return ItemLock::fromInt((int)$cleanValue);
    }

    /**
     * Setzt einen Feldwert aus einem ItemLock-Enum.
     */
    protected function setItemLock(int $rowIndex, int $fieldIndex, ItemLock $itemLock): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$itemLock->value);
    }

    /**
     * Gibt einen Feldwert als PaymentMethod-Enum zurück.
     */
    protected function getPaymentMethod(int $rowIndex, int $fieldIndex): ?PaymentMethod {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        $cleanValue = trim($value, '"');
        if ($cleanValue === '') return null;

        try {
            return PaymentMethod::fromInt((int)$cleanValue);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Setzt einen Feldwert aus einem PaymentMethod-Enum.
     */
    protected function setPaymentMethod(int $rowIndex, int $fieldIndex, PaymentMethod $paymentMethod): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$paymentMethod->value);
    }

    // ==================== DEBITOREN/KREDITOREN-SPEZIFISCHE ENUMS ====================

    /**
     * Gibt einen Feldwert als AddresseeType-Enum zurück.
     */
    protected function getAddresseeType(int $rowIndex, int $fieldIndex): ?AddresseeType {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return AddresseeType::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem AddresseeType-Enum.
     */
    protected function setAddresseeType(int $rowIndex, int $fieldIndex, AddresseeType $addresseeType): void {
        $this->setFieldValue($rowIndex, $fieldIndex, '"' . $addresseeType->value . '"');
    }

    /**
     * Gibt einen Feldwert als Language-Enum zurück.
     */
    protected function getLanguage(int $rowIndex, int $fieldIndex): ?Language {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return Language::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem Language-Enum.
     */
    protected function setLanguage(int $rowIndex, int $fieldIndex, Language $language): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$language->value);
    }

    /**
     * Gibt einen Feldwert als OutputTarget-Enum zurück.
     */
    protected function getOutputTarget(int $rowIndex, int $fieldIndex): ?OutputTarget {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return OutputTarget::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem OutputTarget-Enum.
     */
    protected function setOutputTarget(int $rowIndex, int $fieldIndex, OutputTarget $outputTarget): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$outputTarget->value);
    }

    /**
     * Gibt einen Feldwert als AddressType-Enum zurück.
     */
    protected function getAddressType(int $rowIndex, int $fieldIndex): ?AddressType {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return AddressType::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem AddressType-Enum.
     */
    protected function setAddressType(int $rowIndex, int $fieldIndex, AddressType $addressType): void {
        $this->setFieldValue($rowIndex, $fieldIndex, '"' . $addressType->value . '"');
    }

    /**
     * Gibt einen Feldwert als DirectDebitIndicator-Enum zurück.
     */
    protected function getDirectDebitIndicator(int $rowIndex, int $fieldIndex): ?DirectDebitIndicator {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return DirectDebitIndicator::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem DirectDebitIndicator-Enum.
     */
    protected function setDirectDebitIndicator(int $rowIndex, int $fieldIndex, DirectDebitIndicator $indicator): void {
        $this->setFieldValue($rowIndex, $fieldIndex, '"' . $indicator->value . '"');
    }

    /**
     * Gibt einen Feldwert als PaymentCarrierIndicator-Enum zurück.
     */
    protected function getPaymentCarrierIndicator(int $rowIndex, int $fieldIndex): ?PaymentCarrierIndicator {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return PaymentCarrierIndicator::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem PaymentCarrierIndicator-Enum.
     */
    protected function setPaymentCarrierIndicator(int $rowIndex, int $fieldIndex, PaymentCarrierIndicator $indicator): void {
        $this->setFieldValue($rowIndex, $fieldIndex, '"' . $indicator->value . '"');
    }

    /**
     * Gibt einen Feldwert als DunningIndicator-Enum zurück.
     */
    protected function getDunningIndicator(int $rowIndex, int $fieldIndex): ?DunningIndicator {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return DunningIndicator::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem DunningIndicator-Enum.
     */
    protected function setDunningIndicator(int $rowIndex, int $fieldIndex, DunningIndicator $indicator): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$indicator->value);
    }

    /**
     * Gibt einen Feldwert als StatementIndicator-Enum zurück.
     */
    protected function getStatementIndicator(int $rowIndex, int $fieldIndex): ?StatementIndicator {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return StatementIndicator::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem StatementIndicator-Enum.
     */
    protected function setStatementIndicator(int $rowIndex, int $fieldIndex, StatementIndicator $indicator): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$indicator->value);
    }

    /**
     * Gibt einen Feldwert als InterestCalculationIndicator-Enum zurück.
     */
    protected function getInterestCalculationIndicator(int $rowIndex, int $fieldIndex): ?InterestCalculationIndicator {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return InterestCalculationIndicator::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem InterestCalculationIndicator-Enum.
     */
    protected function setInterestCalculationIndicator(int $rowIndex, int $fieldIndex, InterestCalculationIndicator $indicator): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$indicator->value);
    }

    /**
     * Gibt einen Feldwert als CurrencyControl-Enum zurück.
     */
    protected function getCurrencyControl(int $rowIndex, int $fieldIndex): ?CurrencyControl {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return CurrencyControl::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem CurrencyControl-Enum.
     */
    protected function setCurrencyControl(int $rowIndex, int $fieldIndex, CurrencyControl $currencyControl): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$currencyControl->value);
    }

    /**
     * Gibt einen Feldwert als LanguageCode-Enum zurück (String-basierte Sprach-ID).
     */
    protected function getLanguageCode(int $rowIndex, int $fieldIndex): ?LanguageCode {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return LanguageCode::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem LanguageCode-Enum.
     */
    protected function setLanguageCode(int $rowIndex, int $fieldIndex, LanguageCode $languageCode): void {
        $this->setFieldValue($rowIndex, $fieldIndex, $languageCode->toCsvValue());
    }

    // ==================== WIEDERKEHRENDE BUCHUNGEN-SPEZIFISCHE ENUMS ====================

    /**
     * Gibt einen Feldwert als ReceiptFieldHandling-Enum zurück.
     */
    protected function getReceiptFieldHandling(int $rowIndex, int $fieldIndex): ?ReceiptFieldHandling {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return ReceiptFieldHandling::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem ReceiptFieldHandling-Enum.
     */
    protected function setReceiptFieldHandling(int $rowIndex, int $fieldIndex, ReceiptFieldHandling $handling): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$handling->value);
    }

    /**
     * Gibt einen Feldwert als DunningSubject-Enum zurück.
     */
    protected function getDunningSubject(int $rowIndex, int $fieldIndex): ?DunningSubject {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return DunningSubject::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem DunningSubject-Enum.
     */
    protected function setDunningSubject(int $rowIndex, int $fieldIndex, DunningSubject $subject): void {
        $value = $subject->value === 0 ? '' : (string)$subject->value;
        $this->setFieldValue($rowIndex, $fieldIndex, $value);
    }

    /**
     * Gibt einen Feldwert als TimeIntervalType-Enum zurück.
     */
    protected function getTimeIntervalType(int $rowIndex, int $fieldIndex): ?TimeIntervalType {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return TimeIntervalType::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem TimeIntervalType-Enum.
     */
    protected function setTimeIntervalType(int $rowIndex, int $fieldIndex, TimeIntervalType $intervalType): void {
        $this->setFieldValue($rowIndex, $fieldIndex, $intervalType->toCsvValue());
    }

    /**
     * Gibt einen Feldwert als Wochentag-Bitmaske zurück (Weekday::toBitmask()).
     */
    protected function getWeekdayMask(int $rowIndex, int $fieldIndex): ?int {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        $cleanValue = trim($value, '"');
        if ($cleanValue === '' || !is_numeric($cleanValue)) return null;

        return (int)$cleanValue;
    }

    /**
     * Setzt einen Feldwert aus einer Wochentag-Bitmaske (Weekday::createMask()).
     */
    protected function setWeekdayMask(int $rowIndex, int $fieldIndex, int $mask): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$mask);
    }

    /**
     * Gibt einen Feldwert als WeekdayOrdinal-Enum zurück.
     */
    protected function getWeekdayOrdinal(int $rowIndex, int $fieldIndex): ?WeekdayOrdinal {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return WeekdayOrdinal::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem WeekdayOrdinal-Enum.
     */
    protected function setWeekdayOrdinal(int $rowIndex, int $fieldIndex, WeekdayOrdinal $ordinal): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$ordinal->value);
    }

    /**
     * Gibt einen Feldwert als EndType-Enum zurück.
     */
    protected function getEndType(int $rowIndex, int $fieldIndex): ?EndType {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if ($value === null) return null;

        return EndType::tryFromString($value);
    }

    /**
     * Setzt einen Feldwert aus einem EndType-Enum.
     */
    protected function setEndType(int $rowIndex, int $fieldIndex, EndType $endType): void {
        $this->setFieldValue($rowIndex, $fieldIndex, (string)$endType->value);
    }
}
