<?php
/*
 * Created on   : Mon Dec 22 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankTransactionHeaderDefinition.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Header\ASCII;

use CommonToolkit\Enums\DATEV\HeaderFields\ASCII\BankTransactionHeaderField;

/**
 * Definiert die Struktur der DATEV ASCII-Weiterverarbeitungsdatei Header
 * 
 * Diese Klasse definiert alle 34 Felder für Banktransaktionen im ASCII Format.
 * ASCII-Weiterverarbeitungsdateien haben KEINEN MetaHeader - nur reine Datenzeilen.
 * Basiert auf der offiziellen DATEV-Dokumentation (Dok.-Nr. 9226961).
 * 
 * @since 1.0.0
 * @package CommonToolkit\Entities\DATEV\Header\ASCII
 */
class BankTransactionHeaderDefinition {
    public function __construct() {
        // ASCII-Weiterverarbeitungsdateien haben laut DATEV-Spezifikation 34 Felder
        // Kann-Felder können leer sein, müssen aber das Semikolon enthalten
    }

    /**
     * Gibt die erwartete Anzahl der Felder zurück.
     * 
     * @return int
     */
    public function getExpectedFieldCount(): int {
        return 34; // DATEV-Standard: 34 Felder
    }

    /**
     * Prüft ob eine gegebene Datenzeile gegen die Feldstruktur passt
     * Akzeptiert auch Dateien mit weniger Feldern (fehlende Felder gelten als leer)
     */
    public function matches(array $values): bool {
        $fieldCount = count($values);

        // Mindestens die Muss-Felder müssen vorhanden sein (bis Feld 7)
        if ($fieldCount < 7) {
            return false;
        }

        // Maximal 34 Felder erlaubt
        if ($fieldCount > 34) {
            return false;
        }

        // Prüfe Muss-Felder:
        // Feld 1: BLZ/BIC des Kontoinhabers (Muss-Feld)
        $blz = trim($values[0] ?? '', '"');
        if (empty($blz)) {
            return false;
        }

        // Feld 2: Kontonummer/IBAN des Kontoinhabers (Muss-Feld)
        $kontonummer = trim($values[1] ?? '', '"');
        if (empty($kontonummer)) {
            return false;
        }

        // Feld 6: Buchungsdatum (Muss-Feld) - Index 5
        if (isset($values[5])) {
            $buchungsdatum = trim($values[5], '"');
            if (empty($buchungsdatum)) {
                return false;
            }
        }

        // Feld 7: Umsatz (Muss-Feld) - Index 6
        if (isset($values[6])) {
            $umsatz = trim($values[6], '"');
            if (empty($umsatz) || !is_numeric(str_replace([',', '+'], ['.', ''], $umsatz))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Prüft ob die gegebene Feldanzahl der erwarteten Struktur entspricht
     */
    public function isValidFieldCount(int $fieldCount): bool {
        // Mindestens 7 Felder (bis zum Umsatz), maximal 34 Felder
        return $fieldCount >= 7 && $fieldCount <= 34;
    }

    /**
     * Gibt alle Felder zurück (für 17-Feld Version)
     */
    public function getFields(): array {
        return BankTransactionHeaderField::ordered();
    }

    /**
     * Gibt die Pflichtfelder zurück
     */
    public function getRequiredFields(): array {
        return BankTransactionHeaderField::required();
    }

    /**
     * Validiert eine gegebene Datenzeile gegen die DATEV-Feldstruktur
     */
    public function validateDataLine(array $fields): array {
        $errors = [];
        $fieldCount = count($fields);

        if ($fieldCount < 7) {
            $errors[] = sprintf(
                'Erwartete mindestens 7 Felder (bis Umsatz), erhielt %d',
                $fieldCount
            );
            return $errors;
        }

        if ($fieldCount > 34) {
            $errors[] = sprintf(
                'Erwartete maximal 34 Felder, erhielt %d',
                $fieldCount
            );
            return $errors;
        }

        // Validiere Muss-Felder
        $requiredFields = $this->getRequiredFieldsValidation();

        foreach ($requiredFields as $index => $fieldInfo) {
            if ($index >= $fieldCount) {
                $errors[] = sprintf(
                    'Muss-Feld %s (Position %d) fehlt',
                    $fieldInfo['name'],
                    $index + 1
                );
                continue;
            }

            $value = trim($fields[$index] ?? '', '"');

            if (empty($value)) {
                $errors[] = sprintf(
                    'Muss-Feld %s (Position %d) darf nicht leer sein',
                    $fieldInfo['name'],
                    $index + 1
                );
            } elseif (isset($fieldInfo['pattern']) && !preg_match($fieldInfo['pattern'], $value)) {
                $errors[] = sprintf(
                    'Muss-Feld %s (Position %d) entspricht nicht dem erwarteten Format: %s',
                    $fieldInfo['name'],
                    $index + 1,
                    $value
                );
            }
        }

        return $errors;
    }

    /**
     * Gibt Validierungsregeln für Muss-Felder zurück
     */
    private function getRequiredFieldsValidation(): array {
        return [
            0 => ['name' => 'BLZ/BIC Kontoinhaber', 'pattern' => '/^.+$/'], // Nicht leer
            1 => ['name' => 'Kontonummer/IBAN Kontoinhaber', 'pattern' => '/^.+$/'], // Nicht leer
            5 => ['name' => 'Buchungsdatum', 'pattern' => '/^\d{2}\.\d{2}\.(\d{2}|\d{4})$/'], // TT.MM.JJ oder TT.MM.JJJJ
            6 => ['name' => 'Umsatz', 'pattern' => '/^[+-]?\d+([.,]\d{2})?$/'], // Betrag mit Vorzeichen
        ];
    }

    /**
     * Gibt Beispieldaten für eine DATEV ASCII-Banktransaktion zurück.
     * 
     * @return array<string>
     */
    public function getSampleData(): array {
        return [
            '70030000',                    // BLZ der Bank
            '1234567',                     // Kontonummer
            '433',                         // Auszugsnummer (nicht BIC!)
            '29.12.15',                    // Auszugsdatum
            '29.12.15',                    // Valutadatum
            '29.12.15',                    // Buchungsdatum
            '10.00',                       // Betrag
            'HANS MUSTERMANN',             // Empfänger/Zahlungspflichtiger
            '',                            // Empfänger 2 (leer)
            '80550000',                    // BLZ Empfänger
            '7654321',                     // Konto Empfänger
            'Kd.Nr. 12345',               // Verwendungszweck 1
            'RECHNUNG v. 12.12.15',       // Verwendungszweck 2
            '',                           // Verwendungszweck 3 (leer)
            '',                           // Verwendungszweck 4 (leer)
            '051',                        // Textschlüssel
            '',                           // Leerfeld 17
        ];
    }
}