<?php
/*
 * Created on   : Thu May 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Reference.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Mt940;

use RuntimeException;

class Reference {
    private string $transactionCode;
    private string $reference;

    public function __construct(string $transactionCode, string $reference) {
        $combined = $transactionCode . $reference;
        if (strlen($combined) > 16) {
            throw new RuntimeException("MT940-Referenzüberschreitung: max. 16 Zeichen erlaubt, übergeben: " . $combined);
        }

        $this->transactionCode = $transactionCode;
        $this->reference = $reference;
    }

    public function getTransactionCode(): string {
        return $this->transactionCode;
    }

    public function getReference(): string {
        return $this->reference;
    }

    public function __toString(): string {
        return $this->transactionCode . $this->reference;
    }
}