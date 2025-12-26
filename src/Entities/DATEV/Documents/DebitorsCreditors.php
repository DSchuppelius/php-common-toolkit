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
use CommonToolkit\Enums\DATEV\{
    AddresseeType,
    AddressType,
    CurrencyControl,
    DirectDebitIndicator,
    DunningIndicator,
    InterestCalculationIndicator,
    ItemLock,
    Language,
    OutputTarget,
    PaymentCarrierIndicator,
    StatementIndicator
};
use CommonToolkit\Enums\CountryCode;
use RuntimeException;

/**
 * DATEV-Debitoren/Kreditoren-Dokument.
 * Spezielle Document-Klasse für Debitoren/Kreditoren-Format (Kategorie 16).
 * 
 * Die Spaltenbreiten werden automatisch basierend auf den DATEV-Spezifikationen
 * aus DebitorsCreditorsHeaderField::getMaxLength() angewendet.
 */
final class DebitorsCreditors extends Document {
    public function __construct(?MetaHeaderLine $metaHeader, ?HeaderLine $header, array $rows = []) {
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

    // ---- ADDRESSEE FIELDS ----

    /**
     * Gibt den Adressatentyp eines Debitors/Kreditors zurück (Feld 7).
     * 0 = keine Angabe (Default: Unternehmen), 1 = natürliche Person, 2 = Unternehmen
     */
    public function getAddresseeTypeValue(int $rowIndex): ?AddresseeType {
        return parent::getAddresseeType($rowIndex, DebitorsCreditorsHeaderField::Adressattyp->getPosition());
    }

    /**
     * Setzt den Adressatentyp eines Debitors/Kreditors (Feld 7).
     */
    public function setAddresseeTypeValue(int $rowIndex, AddresseeType $addresseeType): void {
        parent::setAddresseeType($rowIndex, DebitorsCreditorsHeaderField::Adressattyp->getPosition(), $addresseeType);
    }

    /**
     * Gibt die Adressart zurück (Feld 15): STR=Straße, PF=Postfach, GK=Großkunde.
     */
    public function getCorrespondenceAddressType(int $rowIndex): ?AddressType {
        return parent::getAddressType($rowIndex, DebitorsCreditorsHeaderField::Adressart->getPosition());
    }

    /**
     * Setzt die Adressart (Feld 15).
     */
    public function setCorrespondenceAddressType(int $rowIndex, AddressType $addressType): void {
        parent::setAddressType($rowIndex, DebitorsCreditorsHeaderField::Adressart->getPosition(), $addressType);
    }

    /**
     * Gibt die Adressart der Rechnungsadresse zurück (Feld 153).
     */
    public function getInvoiceAddressType(int $rowIndex): ?AddressType {
        return parent::getAddressType($rowIndex, DebitorsCreditorsHeaderField::AdressartRechnungsadresse->getPosition());
    }

    /**
     * Setzt die Adressart der Rechnungsadresse (Feld 153).
     */
    public function setInvoiceAddressType(int $rowIndex, AddressType $addressType): void {
        parent::setAddressType($rowIndex, DebitorsCreditorsHeaderField::AdressartRechnungsadresse->getPosition(), $addressType);
    }

    // ---- COUNTRY FIELDS ----

    /**
     * Gibt das EU-Land eines Debitors/Kreditors zurück (Feld 9).
     */
    public function getEUCountry(int $rowIndex): ?CountryCode {
        return $this->getCountryCode($rowIndex, DebitorsCreditorsHeaderField::EULand->getPosition());
    }

    /**
     * Setzt das EU-Land eines Debitors/Kreditors (Feld 9).
     */
    public function setEUCountry(int $rowIndex, CountryCode $countryCode): void {
        $this->setCountryCode($rowIndex, DebitorsCreditorsHeaderField::EULand->getPosition(), $countryCode);
    }

    /**
     * Gibt das Land eines Debitors/Kreditors zurück (Feld 20).
     */
    public function getCountry(int $rowIndex): ?CountryCode {
        return $this->getCountryCode($rowIndex, DebitorsCreditorsHeaderField::Land->getPosition());
    }

    /**
     * Setzt das Land eines Debitors/Kreditors (Feld 20).
     */
    public function setCountry(int $rowIndex, CountryCode $countryCode): void {
        $this->setCountryCode($rowIndex, DebitorsCreditorsHeaderField::Land->getPosition(), $countryCode);
    }

    /**
     * Gibt das Land der Rechnungsadresse zurück (Feld 158).
     */
    public function getInvoiceCountry(int $rowIndex): ?CountryCode {
        return $this->getCountryCode($rowIndex, DebitorsCreditorsHeaderField::LandRechnungsadresse->getPosition());
    }

    /**
     * Setzt das Land der Rechnungsadresse (Feld 158).
     */
    public function setInvoiceCountry(int $rowIndex, CountryCode $countryCode): void {
        $this->setCountryCode($rowIndex, DebitorsCreditorsHeaderField::LandRechnungsadresse->getPosition(), $countryCode);
    }

    // ---- COMMUNICATION AND OUTPUT FIELDS ----

    /**
     * Gibt die Sprache zurück (Feld 101).
     * 1=deutsch, 4=französisch, 5=englisch, 10=spanisch, 19=italienisch
     */
    public function getLanguageValue(int $rowIndex): ?Language {
        return parent::getLanguage($rowIndex, DebitorsCreditorsHeaderField::Sprache->getPosition());
    }

    /**
     * Setzt die Sprache (Feld 101).
     */
    public function setLanguageValue(int $rowIndex, Language $language): void {
        parent::setLanguage($rowIndex, DebitorsCreditorsHeaderField::Sprache->getPosition(), $language);
    }

    /**
     * Gibt das Ausgabeziel zurück (Feld 106).
     * 1=Druck, 2=Telefax, 3=E-Mail
     */
    public function getOutputTargetValue(int $rowIndex): ?OutputTarget {
        return parent::getOutputTarget($rowIndex, DebitorsCreditorsHeaderField::Ausgabeziel->getPosition());
    }

    /**
     * Setzt das Ausgabeziel (Feld 106).
     */
    public function setOutputTargetValue(int $rowIndex, OutputTarget $outputTarget): void {
        parent::setOutputTarget($rowIndex, DebitorsCreditorsHeaderField::Ausgabeziel->getPosition(), $outputTarget);
    }

    // ---- CURRENCY AND MISCELLANEOUS ACCOUNT ----

    /**
     * Gibt die Währungssteuerung zurück (Feld 107).
     * 0=Zahlungen in Eingabewährung, 2=Ausgabe in EUR
     */
    public function getCurrencyControlValue(int $rowIndex): ?CurrencyControl {
        return parent::getCurrencyControl($rowIndex, DebitorsCreditorsHeaderField::Waehrungssteuerung->getPosition());
    }

    /**
     * Setzt die Währungssteuerung (Feld 107).
     */
    public function setCurrencyControlValue(int $rowIndex, CurrencyControl $currencyControl): void {
        parent::setCurrencyControl($rowIndex, DebitorsCreditorsHeaderField::Waehrungssteuerung->getPosition(), $currencyControl);
    }

    /**
     * Gibt das Diverse-Konto-Kennzeichen zurück (Feld 105).
     * 0=Nein, 1=Ja
     */
    public function getMiscellaneousAccount(int $rowIndex): ?ItemLock {
        return parent::getItemLock($rowIndex, DebitorsCreditorsHeaderField::DiverseKonto->getPosition());
    }

    /**
     * Setzt das Diverse-Konto-Kennzeichen (Feld 105).
     */
    public function setMiscellaneousAccount(int $rowIndex, ItemLock $itemLock): void {
        parent::setItemLock($rowIndex, DebitorsCreditorsHeaderField::DiverseKonto->getPosition(), $itemLock);
    }

    // ---- DUNNING AND STATEMENT ----

    /**
     * Gibt das Mahnungs-Kennzeichen zurück (Feld 121).
     * 0=Keine Angaben, 1=1. Mahnung, 2=2. Mahnung, 3=1.+2. Mahnung,
     * 4=3. Mahnung, 6=2.+3. Mahnung, 7=1.,2.+3. Mahnung, 9=keine Mahnung
     */
    public function getDunningIndicatorValue(int $rowIndex): ?DunningIndicator {
        return parent::getDunningIndicator($rowIndex, DebitorsCreditorsHeaderField::Mahnung->getPosition());
    }

    /**
     * Setzt das Mahnungs-Kennzeichen (Feld 121).
     */
    public function setDunningIndicatorValue(int $rowIndex, DunningIndicator $indicator): void {
        parent::setDunningIndicator($rowIndex, DebitorsCreditorsHeaderField::Mahnung->getPosition(), $indicator);
    }

    /**
     * Gibt das Kontoauszugs-Kennzeichen zurück (Feld 122).
     * 1=alle Posten, 2=nur mahnfähig, 3=alle mahnfälligen, 9=kein Kontoauszug
     */
    public function getStatementIndicatorValue(int $rowIndex): ?StatementIndicator {
        return parent::getStatementIndicator($rowIndex, DebitorsCreditorsHeaderField::Kontoauszug->getPosition());
    }

    /**
     * Setzt das Kontoauszugs-Kennzeichen (Feld 122).
     */
    public function setStatementIndicatorValue(int $rowIndex, StatementIndicator $indicator): void {
        parent::setStatementIndicator($rowIndex, DebitorsCreditorsHeaderField::Kontoauszug->getPosition(), $indicator);
    }

    // ---- INTEREST CALCULATION ----

    /**
     * Gibt das Zinsberechnungs-Kennzeichen zurück (Feld 129).
     * 0=MPD-Schlüsselung, 1=Fester Zinssatz, 2=Zinssatz über Staffel, 9=Keine Berechnung
     */
    public function getInterestCalculationIndicatorValue(int $rowIndex): ?InterestCalculationIndicator {
        return parent::getInterestCalculationIndicator($rowIndex, DebitorsCreditorsHeaderField::Zinsberechnung->getPosition());
    }

    /**
     * Setzt das Zinsberechnungs-Kennzeichen (Feld 129).
     */
    public function setInterestCalculationIndicatorValue(int $rowIndex, InterestCalculationIndicator $indicator): void {
        parent::setInterestCalculationIndicator($rowIndex, DebitorsCreditorsHeaderField::Zinsberechnung->getPosition(), $indicator);
    }

    // ---- DIRECT DEBIT AND PAYMENT CARRIER ----

    /**
     * Gibt das Lastschrift-Kennzeichen zurück (Feld 133).
     * 0=keine Angabe, 7=SEPA-Einzelrechnung, 8=SEPA-Sammelrechnung, 9=kein Lastschriftverfahren
     */
    public function getDirectDebitIndicatorValue(int $rowIndex): ?DirectDebitIndicator {
        return parent::getDirectDebitIndicator($rowIndex, DebitorsCreditorsHeaderField::Lastschrift->getPosition());
    }

    /**
     * Setzt das Lastschrift-Kennzeichen (Feld 133).
     */
    public function setDirectDebitIndicatorValue(int $rowIndex, DirectDebitIndicator $indicator): void {
        parent::setDirectDebitIndicator($rowIndex, DebitorsCreditorsHeaderField::Lastschrift->getPosition(), $indicator);
    }

    /**
     * Gibt das Zahlungsträger-Kennzeichen zurück (Feld 136).
     * 0=keine Angabe, 5=Einzelscheck, 6=Sammelscheck, 7=SEPA-Überweisung einzeln,
     * 8=SEPA-Überweisung Sammel, 9=keine Überweisungen/Schecks
     */
    public function getPaymentCarrierIndicatorValue(int $rowIndex): ?PaymentCarrierIndicator {
        return parent::getPaymentCarrierIndicator($rowIndex, DebitorsCreditorsHeaderField::Zahlungstraeger->getPosition());
    }

    /**
     * Setzt das Zahlungsträger-Kennzeichen (Feld 136).
     */
    public function setPaymentCarrierIndicatorValue(int $rowIndex, PaymentCarrierIndicator $indicator): void {
        parent::setPaymentCarrierIndicator($rowIndex, DebitorsCreditorsHeaderField::Zahlungstraeger->getPosition(), $indicator);
    }


    // ==== CONVENIENCE METHODS ====

    /**
     * Prüft, ob ein Debitor/Kreditor in einem EU-Land ansässig ist.
     */
    public function isEUResident(int $rowIndex): bool {
        $euCountry = $this->getEUCountry($rowIndex);
        $country = $this->getCountry($rowIndex);

        return ($euCountry?->isEU() ?? false) || ($country?->isEU() ?? false);
    }

    /**
     * Prüft, ob SEPA-Lastschrift für diesen Debitor/Kreditor aktiviert ist.
     */
    public function isSepaDirectDebitEnabled(int $rowIndex): bool {
        return $this->getDirectDebitIndicatorValue($rowIndex)?->isSepaDirectDebit() ?? false;
    }

    /**
     * Prüft, ob Mahnung für diesen Debitor/Kreditor aktiviert ist.
     */
    public function isDunningEnabled(int $rowIndex): bool {
        return $this->getDunningIndicatorValue($rowIndex)?->hasDunning() ?? false;
    }

    /**
     * Prüft, ob es sich um eine natürliche Person handelt.
     */
    public function isNaturalPerson(int $rowIndex): bool {
        return $this->getAddresseeTypeValue($rowIndex)?->isNaturalPerson() ?? false;
    }

    /**
     * Prüft, ob es sich um ein Unternehmen handelt.
     */
    public function isCompany(int $rowIndex): bool {
        return $this->getAddresseeTypeValue($rowIndex)?->isCompany() ?? true; // Default ist Unternehmen
    }

    /**
     * Prüft, ob Kontoauszug aktiviert ist.
     */
    public function isStatementEnabled(int $rowIndex): bool {
        return $this->getStatementIndicatorValue($rowIndex)?->isEnabled() ?? false;
    }

    /**
     * Prüft, ob Zinsberechnung aktiviert ist.
     */
    public function isInterestCalculationEnabled(int $rowIndex): bool {
        return $this->getInterestCalculationIndicatorValue($rowIndex)?->isEnabled() ?? false;
    }

    /**
     * Prüft, ob es sich um ein Diverse-Konto handelt.
     */
    public function isMiscellaneousAccount(int $rowIndex): bool {
        return $this->getMiscellaneousAccount($rowIndex)?->isLocked() ?? false;
    }

    /**
     * Gibt alle Debitoren/Kreditoren eines bestimmten Landes zurück.
     */
    public function getRowsByCountry(CountryCode $country): array {
        $result = [];

        foreach ($this->rows as $index => $row) {
            if ($this->getCountry($index) === $country || $this->getEUCountry($index) === $country) {
                $result[] = $index;
            }
        }

        return $result;
    }

    /**
     * Gibt alle natürlichen Personen zurück.
     */
    public function getNaturalPersons(): array {
        $result = [];

        foreach ($this->rows as $index => $row) {
            if ($this->isNaturalPerson($index)) {
                $result[] = $index;
            }
        }

        return $result;
    }

    /**
     * Gibt alle Unternehmen zurück.
     */
    public function getCompanies(): array {
        $result = [];

        foreach ($this->rows as $index => $row) {
            if ($this->isCompany($index)) {
                $result[] = $index;
            }
        }

        return $result;
    }
}