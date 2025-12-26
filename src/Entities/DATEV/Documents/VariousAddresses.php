<?php
/*
 * Created on   : Sun Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VariousAddresses.php
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
use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Enums\DATEV\AddresseeType;
use CommonToolkit\Enums\DATEV\AddressType;
use CommonToolkit\Enums\DATEV\ItemLock;
use CommonToolkit\Enums\DATEV\Language;
use CommonToolkit\Enums\DATEV\OutputTarget;
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;
use CommonToolkit\Enums\DATEV\HeaderFields\V700\VariousAddressesHeaderField;

/**
 * DATEV-Diverse Adressen-Dokument.
 * Spezielle Document-Klasse für Diverse Adressen-Format (Kategorie 48).
 * 
 * Die Spaltenbreiten werden automatisch basierend auf den DATEV-Spezifikationen
 * aus VariousAddressesHeaderField::getMaxLength() angewendet.
 */
final class VariousAddresses extends Document {
    public function __construct(
        ?MetaHeaderLine $metaHeader,
        ?HeaderLine $header,
        array $rows = []
    ) {
        parent::__construct($metaHeader, $header, $rows);
    }

    /**
     * Erstellt eine ColumnWidthConfig basierend auf den DATEV-Spezifikationen.
     * Die maximalen Feldlängen werden aus VariousAddressesHeaderField::getMaxLength() abgeleitet.
     * 
     * @param TruncationStrategy $strategy Abschneidungsstrategie (Standard: TRUNCATE für DATEV-Konformität)
     * @return ColumnWidthConfig
     */
    public static function createDatevColumnWidthConfig(TruncationStrategy $strategy = TruncationStrategy::TRUNCATE): ColumnWidthConfig {
        $config = new ColumnWidthConfig(null, $strategy);

        foreach (VariousAddressesHeaderField::ordered() as $index => $field) {
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
        return Category::DiverseAdressen;
    }

    /**
     * Gibt den DATEV-Format-Typ zurück.
     */
    public function getFormatType(): string {
        return Category::DiverseAdressen->nameValue();
    }

    /**
     * Validiert Diverse Adressen-spezifische Regeln.
     */
    public function validate(): void {
        parent::validate();

        $metaFields = $this->getMetaHeader()?->getFields() ?? [];
        if (count($metaFields) > 2 && (int)$metaFields[2]->getValue() !== 48) {
            throw new \RuntimeException('Ungültige Kategorie für Diverse Adressen-Dokument. Erwartet: 48');
        }
    }

    // ==== ADDRESSEE FIELDS ====

    /**
     * Gibt den Adressatentyp zurück (Feld 10).
     * 0 = keine Angabe (Default: Unternehmen), 1 = natürliche Person, 2 = Unternehmen
     */
    public function getAddresseeTypeValue(int $rowIndex): ?AddresseeType {
        return parent::getAddresseeType($rowIndex, VariousAddressesHeaderField::Adressattyp->getPosition());
    }

    /**
     * Setzt den Adressatentyp (Feld 10).
     */
    public function setAddresseeTypeValue(int $rowIndex, AddresseeType $addresseeType): void {
        parent::setAddresseeType($rowIndex, VariousAddressesHeaderField::Adressattyp->getPosition(), $addresseeType);
    }

    /**
     * Gibt die Adressart zurück (Feld 15): STR=Straße, PF=Postfach, GK=Großkunde.
     */
    public function getCorrespondenceAddressType(int $rowIndex): ?AddressType {
        return parent::getAddressType($rowIndex, VariousAddressesHeaderField::Adressart->getPosition());
    }

    /**
     * Setzt die Adressart (Feld 15).
     */
    public function setCorrespondenceAddressType(int $rowIndex, AddressType $addressType): void {
        parent::setAddressType($rowIndex, VariousAddressesHeaderField::Adressart->getPosition(), $addressType);
    }

    /**
     * Gibt die Adressart der Rechnungsadresse zurück (Feld 29).
     */
    public function getInvoiceAddressType(int $rowIndex): ?AddressType {
        return parent::getAddressType($rowIndex, VariousAddressesHeaderField::AdressartRechnungsadresse->getPosition());
    }

    /**
     * Setzt die Adressart der Rechnungsadresse (Feld 29).
     */
    public function setInvoiceAddressType(int $rowIndex, AddressType $addressType): void {
        parent::setAddressType($rowIndex, VariousAddressesHeaderField::AdressartRechnungsadresse->getPosition(), $addressType);
    }

    // ==== COUNTRY FIELDS ====

    /**
     * Gibt das Land zurück (Feld 20).
     */
    public function getCountry(int $rowIndex): ?CountryCode {
        return parent::getCountryCode($rowIndex, VariousAddressesHeaderField::Land->getPosition());
    }

    /**
     * Setzt das Land (Feld 20).
     */
    public function setCountry(int $rowIndex, CountryCode $countryCode): void {
        parent::setCountryCode($rowIndex, VariousAddressesHeaderField::Land->getPosition(), $countryCode);
    }

    /**
     * Gibt das Land der Rechnungsadresse zurück (Feld 34).
     */
    public function getInvoiceCountry(int $rowIndex): ?CountryCode {
        return parent::getCountryCode($rowIndex, VariousAddressesHeaderField::LandRechnungsadresse->getPosition());
    }

    /**
     * Setzt das Land der Rechnungsadresse (Feld 34).
     */
    public function setInvoiceCountry(int $rowIndex, CountryCode $countryCode): void {
        parent::setCountryCode($rowIndex, VariousAddressesHeaderField::LandRechnungsadresse->getPosition(), $countryCode);
    }

    // ==== CORRESPONDENCE ADDRESS FLAG ====

    /**
     * Gibt das Korrespondenzadresse-Kennzeichen zurück (Feld 25).
     * 1 = Kennzeichnung Korrespondenzadresse
     */
    public function getCorrespondenceAddressFlag(int $rowIndex): ?ItemLock {
        return parent::getItemLock($rowIndex, VariousAddressesHeaderField::KennzKorrespondenzadresse->getPosition());
    }

    /**
     * Setzt das Korrespondenzadresse-Kennzeichen (Feld 25).
     */
    public function setCorrespondenceAddressFlag(int $rowIndex, ItemLock $flag): void {
        parent::setItemLock($rowIndex, VariousAddressesHeaderField::KennzKorrespondenzadresse->getPosition(), $flag);
    }

    /**
     * Prüft, ob die Adresse als Korrespondenzadresse markiert ist.
     */
    public function isCorrespondenceAddress(int $rowIndex): bool {
        $flag = $this->getCorrespondenceAddressFlag($rowIndex);
        return $flag !== null && $flag->isLocked();
    }

    // ==== COMMUNICATION AND OUTPUT FIELDS ====

    /**
     * Gibt die Sprache zurück (Feld 169).
     * 1=deutsch, 4=französisch, 5=englisch, 10=spanisch, 19=italienisch
     */
    public function getLanguageValue(int $rowIndex): ?Language {
        return parent::getLanguage($rowIndex, VariousAddressesHeaderField::Sprache->getPosition());
    }

    /**
     * Setzt die Sprache (Feld 169).
     */
    public function setLanguageValue(int $rowIndex, Language $language): void {
        parent::setLanguage($rowIndex, VariousAddressesHeaderField::Sprache->getPosition(), $language);
    }

    /**
     * Gibt das Ausgabeziel zurück (Feld 170).
     * Bei Diverse Adressen: 1=Druck, 3=E-Mail (kein Fax!)
     */
    public function getOutputTargetValue(int $rowIndex): ?OutputTarget {
        return parent::getOutputTarget($rowIndex, VariousAddressesHeaderField::Ausgabeziel->getPosition());
    }

    /**
     * Setzt das Ausgabeziel (Feld 170).
     * Bei Diverse Adressen nur 1=Druck oder 3=E-Mail erlaubt (kein Fax!).
     *
     * @throws \InvalidArgumentException wenn Fax als Ausgabeziel gewählt wird
     */
    public function setOutputTargetValue(int $rowIndex, OutputTarget $outputTarget): void {
        if ($outputTarget === OutputTarget::FAX) {
            throw new \InvalidArgumentException('Fax ist für Diverse Adressen nicht als Ausgabeziel verfügbar.');
        }
        parent::setOutputTarget($rowIndex, VariousAddressesHeaderField::Ausgabeziel->getPosition(), $outputTarget);
    }

    // ==== MAIN BANK CONNECTION FLAGS (10 banks) ====

    /**
     * Gibt das Hauptbankverbindungs-Kennzeichen für Bank 1 zurück (Feld 61).
     */
    public function getMainBankConnectionFlag1(int $rowIndex): ?ItemLock {
        return parent::getItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb1->getPosition());
    }

    /**
     * Setzt das Hauptbankverbindungs-Kennzeichen für Bank 1 (Feld 61).
     */
    public function setMainBankConnectionFlag1(int $rowIndex, ItemLock $flag): void {
        parent::setItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb1->getPosition(), $flag);
    }

    /**
     * Prüft, ob Bank 1 die Hauptbankverbindung ist.
     */
    public function isMainBankConnection1(int $rowIndex): bool {
        $flag = $this->getMainBankConnectionFlag1($rowIndex);
        return $flag !== null && $flag->isLocked();
    }

    /**
     * Gibt das Hauptbankverbindungs-Kennzeichen für Bank 2 zurück (Feld 72).
     */
    public function getMainBankConnectionFlag2(int $rowIndex): ?ItemLock {
        return parent::getItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb2->getPosition());
    }

    /**
     * Setzt das Hauptbankverbindungs-Kennzeichen für Bank 2 (Feld 72).
     */
    public function setMainBankConnectionFlag2(int $rowIndex, ItemLock $flag): void {
        parent::setItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb2->getPosition(), $flag);
    }

    /**
     * Prüft, ob Bank 2 die Hauptbankverbindung ist.
     */
    public function isMainBankConnection2(int $rowIndex): bool {
        $flag = $this->getMainBankConnectionFlag2($rowIndex);
        return $flag !== null && $flag->isLocked();
    }

    /**
     * Gibt das Hauptbankverbindungs-Kennzeichen für Bank 3 zurück (Feld 83).
     */
    public function getMainBankConnectionFlag3(int $rowIndex): ?ItemLock {
        return parent::getItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb3->getPosition());
    }

    /**
     * Setzt das Hauptbankverbindungs-Kennzeichen für Bank 3 (Feld 83).
     */
    public function setMainBankConnectionFlag3(int $rowIndex, ItemLock $flag): void {
        parent::setItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb3->getPosition(), $flag);
    }

    /**
     * Prüft, ob Bank 3 die Hauptbankverbindung ist.
     */
    public function isMainBankConnection3(int $rowIndex): bool {
        $flag = $this->getMainBankConnectionFlag3($rowIndex);
        return $flag !== null && $flag->isLocked();
    }

    /**
     * Gibt das Hauptbankverbindungs-Kennzeichen für Bank 4 zurück (Feld 94).
     */
    public function getMainBankConnectionFlag4(int $rowIndex): ?ItemLock {
        return parent::getItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb4->getPosition());
    }

    /**
     * Setzt das Hauptbankverbindungs-Kennzeichen für Bank 4 (Feld 94).
     */
    public function setMainBankConnectionFlag4(int $rowIndex, ItemLock $flag): void {
        parent::setItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb4->getPosition(), $flag);
    }

    /**
     * Prüft, ob Bank 4 die Hauptbankverbindung ist.
     */
    public function isMainBankConnection4(int $rowIndex): bool {
        $flag = $this->getMainBankConnectionFlag4($rowIndex);
        return $flag !== null && $flag->isLocked();
    }

    /**
     * Gibt das Hauptbankverbindungs-Kennzeichen für Bank 5 zurück (Feld 105).
     */
    public function getMainBankConnectionFlag5(int $rowIndex): ?ItemLock {
        return parent::getItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb5->getPosition());
    }

    /**
     * Setzt das Hauptbankverbindungs-Kennzeichen für Bank 5 (Feld 105).
     */
    public function setMainBankConnectionFlag5(int $rowIndex, ItemLock $flag): void {
        parent::setItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb5->getPosition(), $flag);
    }

    /**
     * Prüft, ob Bank 5 die Hauptbankverbindung ist.
     */
    public function isMainBankConnection5(int $rowIndex): bool {
        $flag = $this->getMainBankConnectionFlag5($rowIndex);
        return $flag !== null && $flag->isLocked();
    }

    /**
     * Gibt das Hauptbankverbindungs-Kennzeichen für Bank 6 zurück (Feld 116).
     */
    public function getMainBankConnectionFlag6(int $rowIndex): ?ItemLock {
        return parent::getItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb6->getPosition());
    }

    /**
     * Setzt das Hauptbankverbindungs-Kennzeichen für Bank 6 (Feld 116).
     */
    public function setMainBankConnectionFlag6(int $rowIndex, ItemLock $flag): void {
        parent::setItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb6->getPosition(), $flag);
    }

    /**
     * Prüft, ob Bank 6 die Hauptbankverbindung ist.
     */
    public function isMainBankConnection6(int $rowIndex): bool {
        $flag = $this->getMainBankConnectionFlag6($rowIndex);
        return $flag !== null && $flag->isLocked();
    }

    /**
     * Gibt das Hauptbankverbindungs-Kennzeichen für Bank 7 zurück (Feld 127).
     */
    public function getMainBankConnectionFlag7(int $rowIndex): ?ItemLock {
        return parent::getItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb7->getPosition());
    }

    /**
     * Setzt das Hauptbankverbindungs-Kennzeichen für Bank 7 (Feld 127).
     */
    public function setMainBankConnectionFlag7(int $rowIndex, ItemLock $flag): void {
        parent::setItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb7->getPosition(), $flag);
    }

    /**
     * Prüft, ob Bank 7 die Hauptbankverbindung ist.
     */
    public function isMainBankConnection7(int $rowIndex): bool {
        $flag = $this->getMainBankConnectionFlag7($rowIndex);
        return $flag !== null && $flag->isLocked();
    }

    /**
     * Gibt das Hauptbankverbindungs-Kennzeichen für Bank 8 zurück (Feld 138).
     */
    public function getMainBankConnectionFlag8(int $rowIndex): ?ItemLock {
        return parent::getItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb8->getPosition());
    }

    /**
     * Setzt das Hauptbankverbindungs-Kennzeichen für Bank 8 (Feld 138).
     */
    public function setMainBankConnectionFlag8(int $rowIndex, ItemLock $flag): void {
        parent::setItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb8->getPosition(), $flag);
    }

    /**
     * Prüft, ob Bank 8 die Hauptbankverbindung ist.
     */
    public function isMainBankConnection8(int $rowIndex): bool {
        $flag = $this->getMainBankConnectionFlag8($rowIndex);
        return $flag !== null && $flag->isLocked();
    }

    /**
     * Gibt das Hauptbankverbindungs-Kennzeichen für Bank 9 zurück (Feld 149).
     */
    public function getMainBankConnectionFlag9(int $rowIndex): ?ItemLock {
        return parent::getItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb9->getPosition());
    }

    /**
     * Setzt das Hauptbankverbindungs-Kennzeichen für Bank 9 (Feld 149).
     */
    public function setMainBankConnectionFlag9(int $rowIndex, ItemLock $flag): void {
        parent::setItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb9->getPosition(), $flag);
    }

    /**
     * Prüft, ob Bank 9 die Hauptbankverbindung ist.
     */
    public function isMainBankConnection9(int $rowIndex): bool {
        $flag = $this->getMainBankConnectionFlag9($rowIndex);
        return $flag !== null && $flag->isLocked();
    }

    /**
     * Gibt das Hauptbankverbindungs-Kennzeichen für Bank 10 zurück (Feld 160).
     */
    public function getMainBankConnectionFlag10(int $rowIndex): ?ItemLock {
        return parent::getItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb10->getPosition());
    }

    /**
     * Setzt das Hauptbankverbindungs-Kennzeichen für Bank 10 (Feld 160).
     */
    public function setMainBankConnectionFlag10(int $rowIndex, ItemLock $flag): void {
        parent::setItemLock($rowIndex, VariousAddressesHeaderField::KennzHauptbankverb10->getPosition(), $flag);
    }

    /**
     * Prüft, ob Bank 10 die Hauptbankverbindung ist.
     */
    public function isMainBankConnection10(int $rowIndex): bool {
        $flag = $this->getMainBankConnectionFlag10($rowIndex);
        return $flag !== null && $flag->isLocked();
    }

    // ==== CONVENIENCE METHODS ====

    /**
     * Prüft, ob eine diverse Adresse einer natürlichen Person gehört.
     */
    public function isNaturalPerson(int $rowIndex): bool {
        $type = $this->getAddresseeTypeValue($rowIndex);
        return $type !== null && $type->isNaturalPerson();
    }

    /**
     * Prüft, ob eine diverse Adresse einem Unternehmen gehört.
     */
    public function isCompany(int $rowIndex): bool {
        $type = $this->getAddresseeTypeValue($rowIndex);
        return $type !== null && $type->isCompany();
    }

    /**
     * Ermittelt die Hauptbankverbindung für eine diverse Adresse.
     * Gibt die Bank-Nummer (1-10) zurück oder null, wenn keine Hauptbank gesetzt ist.
     */
    public function getMainBankNumber(int $rowIndex): ?int {
        if ($this->isMainBankConnection1($rowIndex)) return 1;
        if ($this->isMainBankConnection2($rowIndex)) return 2;
        if ($this->isMainBankConnection3($rowIndex)) return 3;
        if ($this->isMainBankConnection4($rowIndex)) return 4;
        if ($this->isMainBankConnection5($rowIndex)) return 5;
        if ($this->isMainBankConnection6($rowIndex)) return 6;
        if ($this->isMainBankConnection7($rowIndex)) return 7;
        if ($this->isMainBankConnection8($rowIndex)) return 8;
        if ($this->isMainBankConnection9($rowIndex)) return 9;
        if ($this->isMainBankConnection10($rowIndex)) return 10;
        return null;
    }

    /**
     * Gibt alle diversen Adressen zurück, die natürlichen Personen gehören.
     *
     * @return int[] Array der Row-Indices
     */
    public function getNaturalPersons(): array {
        $result = [];
        $count = count($this->getRows());
        for ($i = 0; $i < $count; $i++) {
            if ($this->isNaturalPerson($i)) {
                $result[] = $i;
            }
        }
        return $result;
    }

    /**
     * Gibt alle diversen Adressen zurück, die Unternehmen gehören.
     *
     * @return int[] Array der Row-Indices
     */
    public function getCompanies(): array {
        $result = [];
        $count = count($this->getRows());
        for ($i = 0; $i < $count; $i++) {
            if ($this->isCompany($i)) {
                $result[] = $i;
            }
        }
        return $result;
    }
}
