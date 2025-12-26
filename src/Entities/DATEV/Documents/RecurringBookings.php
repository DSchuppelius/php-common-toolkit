<?php
/*
 * Created on   : Sun Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : RecurringBookings.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Documents;

use CommonToolkit\Entities\Common\CSV\ColumnWidthConfig;
use CommonToolkit\Entities\Common\CSV\HeaderLine;
use CommonToolkit\Contracts\Abstracts\DATEV\Document;
use CommonToolkit\Entities\DATEV\MetaHeaderLine;
use CommonToolkit\Enums\{CreditDebit, CurrencyCode, Weekday};
use CommonToolkit\Enums\Common\CSV\TruncationStrategy;
use CommonToolkit\Enums\DATEV\{
    DunningSubject,
    EndType,
    InterestLock,
    ItemLock,
    ReceiptFieldHandling,
    TimeIntervalType,
    WeekdayOrdinal
};
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;
use CommonToolkit\Enums\DATEV\HeaderFields\V700\RecurringBookingsHeaderField;

/**
 * DATEV-Wiederkehrende Buchungen-Dokument.
 * Spezielle Document-Klasse für Wiederkehrende Buchungen-Format (Kategorie 65).
 * 
 * Die Spaltenbreiten werden automatisch basierend auf den DATEV-Spezifikationen
 * aus RecurringBookingsHeaderField::getMaxLength() angewendet.
 * 
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/recurring-bookings
 */
final class RecurringBookings extends Document {
    public function __construct(?MetaHeaderLine $metaHeader, ?HeaderLine $header, array $rows = []) {
        parent::__construct($metaHeader, $header, $rows);
    }

    /**
     * Erstellt eine ColumnWidthConfig basierend auf den DATEV-Spezifikationen.
     * Die maximalen Feldlängen werden aus RecurringBookingsHeaderField::getMaxLength() abgeleitet.
     * 
     * @param TruncationStrategy $strategy Abschneidungsstrategie (Standard: TRUNCATE für DATEV-Konformität)
     * @return ColumnWidthConfig
     */
    public static function createDatevColumnWidthConfig(TruncationStrategy $strategy = TruncationStrategy::TRUNCATE): ColumnWidthConfig {
        $config = new ColumnWidthConfig(null, $strategy);

        foreach (RecurringBookingsHeaderField::ordered() as $index => $field) {
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
        return Category::WiederkehrendeBuchungen;
    }

    /**
     * Gibt den DATEV-Format-Typ zurück.
     */
    public function getFormatType(): string {
        return Category::WiederkehrendeBuchungen->nameValue();
    }

    /**
     * Validiert Wiederkehrende Buchungen-spezifische Regeln.
     */
    public function validate(): void {
        parent::validate();

        $metaFields = $this->getMetaHeader()?->getFields() ?? [];
        if (count($metaFields) > 2 && (int)$metaFields[2]->getValue() !== 65) {
            throw new \RuntimeException('Ungültige Kategorie für Wiederkehrende Buchungen-Dokument. Erwartet: 65');
        }
    }

    // ==================== BELEGFELD-BEHANDLUNG (FELD 1) ====================

    /**
     * Gibt die Belegfeld1-Behandlung einer Buchungszeile zurück.
     */
    public function getReceiptFieldHandlingValue(int $rowIndex): ?ReceiptFieldHandling {
        return $this->getReceiptFieldHandling($rowIndex, RecurringBookingsHeaderField::B1->getPosition());
    }

    /**
     * Setzt die Belegfeld1-Behandlung einer Buchungszeile.
     */
    public function setReceiptFieldHandlingValue(int $rowIndex, ReceiptFieldHandling $handling): void {
        $this->setReceiptFieldHandling($rowIndex, RecurringBookingsHeaderField::B1->getPosition(), $handling);
    }

    // ==================== UMSATZ (FELD 2-5) ====================

    /**
     * Gibt den Umsatzbetrag einer Buchungszeile zurück.
     */
    public function getAmount(int $rowIndex): ?string {
        return $this->getFieldValue($rowIndex, RecurringBookingsHeaderField::Umsatz->getPosition());
    }

    /**
     * Gibt Soll/Haben einer Buchungszeile zurück.
     */
    public function getCreditDebitValue(int $rowIndex): ?CreditDebit {
        return $this->getCreditDebit($rowIndex, RecurringBookingsHeaderField::SollHabenKennzeichen->getPosition());
    }

    /**
     * Setzt Soll/Haben einer Buchungszeile.
     */
    public function setCreditDebitValue(int $rowIndex, CreditDebit $creditDebit): void {
        $this->setCreditDebit($rowIndex, RecurringBookingsHeaderField::SollHabenKennzeichen->getPosition(), $creditDebit);
    }

    /**
     * Gibt den Währungscode einer Buchungszeile zurück.
     */
    public function getCurrencyCodeValue(int $rowIndex): ?CurrencyCode {
        return $this->getCurrencyCode($rowIndex, RecurringBookingsHeaderField::WKZUmsatz->getPosition());
    }

    /**
     * Setzt den Währungscode einer Buchungszeile.
     */
    public function setCurrencyCodeValue(int $rowIndex, CurrencyCode $currencyCode): void {
        $this->setCurrencyCode($rowIndex, RecurringBookingsHeaderField::WKZUmsatz->getPosition(), $currencyCode);
    }

    // ==================== POSTENSPERRE (FELD 21) ====================

    /**
     * Gibt die Postensperre einer Buchungszeile zurück.
     */
    public function getItemLockValue(int $rowIndex): ?ItemLock {
        return $this->getItemLock($rowIndex, RecurringBookingsHeaderField::Postensperre->getPosition());
    }

    /**
     * Setzt die Postensperre einer Buchungszeile.
     */
    public function setItemLockValue(int $rowIndex, ItemLock $itemLock): void {
        $this->setItemLock($rowIndex, RecurringBookingsHeaderField::Postensperre->getPosition(), $itemLock);
    }

    // ==================== SACHVERHALT (FELD 24) ====================

    /**
     * Gibt den Sachverhalt einer Buchungszeile zurück (Mahnzins/Mahngebühr).
     */
    public function getDunningSubjectValue(int $rowIndex): ?DunningSubject {
        return $this->getDunningSubject($rowIndex, RecurringBookingsHeaderField::Sachverhalt->getPosition());
    }

    /**
     * Setzt den Sachverhalt einer Buchungszeile.
     */
    public function setDunningSubjectValue(int $rowIndex, DunningSubject $subject): void {
        $this->setDunningSubject($rowIndex, RecurringBookingsHeaderField::Sachverhalt->getPosition(), $subject);
    }

    // ==================== ZINSSPERRE (FELD 25) ====================

    /**
     * Gibt die Zinssperre einer Buchungszeile zurück.
     */
    public function getInterestLockValue(int $rowIndex): ?InterestLock {
        return $this->getInterestLock($rowIndex, RecurringBookingsHeaderField::Zinssperre->getPosition());
    }

    /**
     * Setzt die Zinssperre einer Buchungszeile.
     */
    public function setInterestLockValue(int $rowIndex, InterestLock $interestLock): void {
        $this->setInterestLock($rowIndex, RecurringBookingsHeaderField::Zinssperre->getPosition(), $interestLock);
    }

    // ==================== ZEITINTERVALL (FELDER 81-82) ====================

    /**
     * Gibt die Zeitintervallart einer Buchungszeile zurück (TAG/MON).
     */
    public function getTimeIntervalTypeValue(int $rowIndex): ?TimeIntervalType {
        return $this->getTimeIntervalType($rowIndex, RecurringBookingsHeaderField::Zeitintervallart->getPosition());
    }

    /**
     * Setzt die Zeitintervallart einer Buchungszeile.
     */
    public function setTimeIntervalTypeValue(int $rowIndex, TimeIntervalType $intervalType): void {
        $this->setTimeIntervalType($rowIndex, RecurringBookingsHeaderField::Zeitintervallart->getPosition(), $intervalType);
    }

    /**
     * Gibt das Zeitintervall (alle n Tage/Monate) zurück.
     */
    public function getTimeInterval(int $rowIndex): ?int {
        $value = $this->getFieldValue($rowIndex, RecurringBookingsHeaderField::Zeitabstand->getPosition());
        if ($value === null) return null;

        $cleanValue = trim($value, '"');
        return $cleanValue !== '' && is_numeric($cleanValue) ? (int)$cleanValue : null;
    }

    /**
     * Setzt das Zeitintervall.
     */
    public function setTimeInterval(int $rowIndex, int $interval): void {
        $this->setFieldValue($rowIndex, RecurringBookingsHeaderField::Zeitabstand->getPosition(), (string)$interval);
    }

    // ==================== WOCHENTAG (FELD 83) ====================

    /**
     * Gibt die Wochentag-Bitmaske einer Buchungszeile zurück.
     */
    public function getWeekdayMaskValue(int $rowIndex): ?int {
        return $this->getWeekdayMask($rowIndex, RecurringBookingsHeaderField::Wochentag->getPosition());
    }

    /**
     * Gibt die Wochentage als Array von Weekday-Enums zurück.
     *
     * @return array<Weekday>
     */
    public function getWeekdays(int $rowIndex): array {
        $mask = $this->getWeekdayMaskValue($rowIndex);
        return $mask !== null ? Weekday::fromMask($mask) : [];
    }

    /**
     * Setzt die Wochentag-Bitmaske einer Buchungszeile.
     */
    public function setWeekdayMaskValue(int $rowIndex, int $mask): void {
        $this->setWeekdayMask($rowIndex, RecurringBookingsHeaderField::Wochentag->getPosition(), $mask);
    }

    /**
     * Setzt die Wochentage aus Weekday-Enums.
     *
     * @param Weekday ...$weekdays
     */
    public function setWeekdays(int $rowIndex, Weekday ...$weekdays): void {
        $mask = Weekday::createMask(...$weekdays);
        $this->setWeekdayMaskValue($rowIndex, $mask);
    }

    // ==================== ORDNUNGSZAHL TAG IM MONAT (FELD 85) ====================

    /**
     * Gibt die Ordnungszahl Tag im Monat zurück (1-31).
     * Bei Zeitintervallart MON: Tag des Monats für die Buchung.
     */
    public function getDayOfMonth(int $rowIndex): ?int {
        $value = $this->getFieldValue($rowIndex, RecurringBookingsHeaderField::OrdnungszahlTagImMonat->getPosition());
        if ($value === null) return null;

        $cleanValue = trim($value, '"');
        return $cleanValue !== '' && is_numeric($cleanValue) ? (int)$cleanValue : null;
    }

    /**
     * Setzt die Ordnungszahl Tag im Monat (1-31).
     */
    public function setDayOfMonth(int $rowIndex, int $day): void {
        $this->setFieldValue($rowIndex, RecurringBookingsHeaderField::OrdnungszahlTagImMonat->getPosition(), (string)$day);
    }

    // ==================== ORDNUNGSZAHL WOCHENTAG (FELD 86) ====================

    /**
     * Gibt die Ordnungszahl des Wochentags zurück (1=erster, 5=letzter).
     */
    public function getWeekdayOrdinalValue(int $rowIndex): ?WeekdayOrdinal {
        return $this->getWeekdayOrdinal($rowIndex, RecurringBookingsHeaderField::OrdnungszahlWochentag->getPosition());
    }

    /**
     * Setzt die Ordnungszahl des Wochentags.
     */
    public function setWeekdayOrdinalValue(int $rowIndex, WeekdayOrdinal $ordinal): void {
        $this->setWeekdayOrdinal($rowIndex, RecurringBookingsHeaderField::OrdnungszahlWochentag->getPosition(), $ordinal);
    }

    // ==================== ENDETYP UND ENDDATUM (FELDER 80, 87) ====================

    /**
     * Gibt das Enddatum der Buchungsserie zurück (Feld 80).
     * Muss nach dem Beginndatum liegen. Format: TTMMJJJJ
     */
    public function getEndDate(int $rowIndex): ?string {
        return $this->getFieldValue($rowIndex, RecurringBookingsHeaderField::Enddatum->getPosition());
    }

    /**
     * Setzt das Enddatum der Buchungsserie (Feld 80).
     */
    public function setEndDate(int $rowIndex, string $endDate): void {
        $this->setFieldValue($rowIndex, RecurringBookingsHeaderField::Enddatum->getPosition(), $endDate);
    }

    /**
     * Gibt den Endetyp einer Buchungszeile zurück (Feld 87).
     * 1 = kein Enddatum, 2 = Endzeitpunkt bei Anzahl Ereignissen, 3 = Endet am
     */
    public function getEndTypeValue(int $rowIndex): ?EndType {
        return $this->getEndType($rowIndex, RecurringBookingsHeaderField::Endetyp->getPosition());
    }

    /**
     * Setzt den Endetyp einer Buchungszeile.
     */
    public function setEndTypeValue(int $rowIndex, EndType $endType): void {
        $this->setEndType($rowIndex, RecurringBookingsHeaderField::Endetyp->getPosition(), $endType);
    }

    // ==================== GESELLSCHAFTER (FELDER 88-89) ====================

    /**
     * Gibt den Gesellschafternamen zurück (Feld 88).
     * Muss mit dem zugeordneten Gesellschafter in den zentralen Stammdaten übereinstimmen.
     */
    public function getPartnerName(int $rowIndex): ?string {
        $value = $this->getFieldValue($rowIndex, RecurringBookingsHeaderField::Gesellschaftername->getPosition());
        if ($value === null) return null;

        return trim($value, '"');
    }

    /**
     * Setzt den Gesellschafternamen (Feld 88).
     */
    public function setPartnerName(int $rowIndex, string $name): void {
        $this->setFieldValue($rowIndex, RecurringBookingsHeaderField::Gesellschaftername->getPosition(), '"' . $name . '"');
    }

    /**
     * Gibt die Beteiligtennummer zurück (Feld 89).
     * Muss-Feld für die Zuordnung von Gesellschaftern zum Buchungssatz.
     * Die Beteiligtennummer muss der amtlichen Nummer aus der Feststellungserklärung entsprechen.
     */
    public function getPartnerNumber(int $rowIndex): ?int {
        $value = $this->getFieldValue($rowIndex, RecurringBookingsHeaderField::Beteiligtennummer->getPosition());
        if ($value === null) return null;

        $cleanValue = trim($value, '"');
        return $cleanValue !== '' && is_numeric($cleanValue) ? (int)$cleanValue : null;
    }

    /**
     * Setzt die Beteiligtennummer (Feld 89).
     */
    public function setPartnerNumber(int $rowIndex, int $number): void {
        $this->setFieldValue($rowIndex, RecurringBookingsHeaderField::Beteiligtennummer->getPosition(), (string)$number);
    }

    // ==================== SOBIL / GENERALUMKEHR (FELDER 96-97) ====================

    /**
     * Gibt den SoBil-Buchung-Wert zurück.
     */
    public function getSoBilValue(int $rowIndex): ?ItemLock {
        return $this->getItemLock($rowIndex, RecurringBookingsHeaderField::KennzeichenSoBilBuchung->getPosition());
    }

    /**
     * Setzt den SoBil-Buchung-Wert.
     */
    public function setSoBilValue(int $rowIndex, ItemLock $soBil): void {
        $this->setItemLock($rowIndex, RecurringBookingsHeaderField::KennzeichenSoBilBuchung->getPosition(), $soBil);
    }

    /**
     * Gibt den Generalumkehr-Wert zurück.
     */
    public function getGeneralReversalValue(int $rowIndex): ?string {
        return $this->getFieldValue($rowIndex, RecurringBookingsHeaderField::Generalumkehr->getPosition());
    }

    /**
     * Setzt den Generalumkehr-Wert (0, 1, oder G).
     */
    public function setGeneralReversalValue(int $rowIndex, string $value): void {
        $this->setFieldValue($rowIndex, RecurringBookingsHeaderField::Generalumkehr->getPosition(), $value);
    }

    // ==================== HILFSMETHODEN ====================

    /**
     * Prüft, ob eine Buchungszeile als tägliches Intervall konfiguriert ist.
     */
    public function isDailyInterval(int $rowIndex): bool {
        $intervalType = $this->getTimeIntervalTypeValue($rowIndex);
        return $intervalType?->isDaily() ?? false;
    }

    /**
     * Prüft, ob eine Buchungszeile als monatliches Intervall konfiguriert ist.
     */
    public function isMonthlyInterval(int $rowIndex): bool {
        $intervalType = $this->getTimeIntervalTypeValue($rowIndex);
        return $intervalType?->isMonthly() ?? false;
    }

    /**
     * Prüft, ob eine Buchungszeile unbegrenzt läuft.
     */
    public function isUnlimitedRepetition(int $rowIndex): bool {
        $endType = $this->getEndTypeValue($rowIndex);
        return $endType?->isUnlimited() ?? false;
    }

    /**
     * Gibt eine lesbare Beschreibung des Wiederholungsintervalls zurück.
     */
    public function getIntervalDescription(int $rowIndex): string {
        $intervalType = $this->getTimeIntervalTypeValue($rowIndex);
        $interval = $this->getTimeInterval($rowIndex);

        if ($intervalType === null || $interval === null) {
            return 'Kein Intervall definiert';
        }

        $unit = $intervalType->isDaily() ? 'Tag(e)' : 'Monat(e)';
        return "Alle $interval $unit";
    }

    /**
     * Gibt eine lesbare Beschreibung des Endtyps zurück.
     */
    public function getEndDescription(int $rowIndex): string {
        $endType = $this->getEndTypeValue($rowIndex);

        if ($endType === null) {
            return 'Kein Endetyp definiert';
        }

        return match ($endType) {
            EndType::NO_END => 'Unbegrenzt',
            EndType::BY_COUNT => 'Nach Anzahl Ereignissen',
            EndType::BY_DATE => sprintf('Am %s', $this->getEndDate($rowIndex) ?? 'unbekannt'),
        };
    }
}