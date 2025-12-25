<?php
/*
 * Created on   : Mon Dec 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankTransactionHeaderLine.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Header\ASCII;

use CommonToolkit\Entities\Common\CSV\HeaderField;
use CommonToolkit\Entities\Common\CSV\HeaderLine;
use CommonToolkit\Entities\DATEV\Header\ASCII\BankTransactionHeaderDefinition;
use CommonToolkit\Enums\DATEV\HeaderFields\ASCII\BankTransactionHeaderField;

/**
 * Header-Zeile für DATEV ASCII-Weiterverarbeitungsdateien (Banktransaktionen).
 * 
 * Diese Klasse erstellt virtuelle Header für ASCII-Dateien mit 17-34 Feldern.
 * ASCII-Dateien haben KEINEN MetaHeader - nur einfache Datenzeilen.
 * 
 * @package CommonToolkit\Entities\DATEV\Header
 */
class BankTransactionHeaderLine extends HeaderLine {

    /**
     * Erstellt eine Standard-Header-Definition für ASCII-Banktransaktionen.
     * 
     * @return BankTransactionHeaderDefinition
     */
    public function getDefinition(): BankTransactionHeaderDefinition {
        return new BankTransactionHeaderDefinition();
    }
}