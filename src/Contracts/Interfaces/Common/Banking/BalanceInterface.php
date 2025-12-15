<?php
/*
 * Created on   : Thu May 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BalanceInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Contracts\Interfaces\Common\Banking;

use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;

interface BalanceInterface {
    public function getCreditDebit(): CreditDebit;
    public function getDate(): DateTimeImmutable;
    public function getCurrency(): CurrencyCode;
    public function getAmount(): float;

    public function __toString(): string;
}