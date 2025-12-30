<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt10xConverter.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Mt1\Party;
use CommonToolkit\Entities\Common\Banking\Mt1\TransferDetails;
use CommonToolkit\Entities\Common\Banking\Mt1\Type101\Document as Mt101Document;
use CommonToolkit\Entities\Common\Banking\Mt1\Type101\Transaction as Mt101Transaction;
use CommonToolkit\Entities\Common\Banking\Mt1\Type103\Document as Mt103Document;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use DateTimeImmutable;

/**
 * Konverter für MT10x Formate (Zahlungsaufträge).
 * 
 * MT10x sind Zahlungsaufträge, keine Kontoauszüge:
 * - MT101: Request for Transfer (Sammelüberweisung mit mehreren Transaktionen)
 * - MT103: Single Customer Credit Transfer (Einzelüberweisung)
 * 
 * Hinweis: Die Konvertierung zwischen MT10x und MT940/CAMT.053 ist nicht sinnvoll,
 * da MT10x Aufträge sind und MT940/CAMT.053 Kontoauszüge.
 * Das XML-Äquivalent zu MT101/MT103 wäre pain.001.
 * 
 * @package CommonToolkit\Converters\Banking
 */
final class Mt10xConverter {
    /**
     * Extrahiert einzelne MT103-Dokumente aus einem MT101.
     * 
     * Jede Transaktion im MT101 wird zu einem separaten MT103-Dokument.
     * Nützlich für die Verarbeitung in Systemen, die nur Einzelüberweisungen akzeptieren.
     * 
     * @return Mt103Document[]
     */
    public static function mt101ToMt103Array(Mt101Document $mt101): array {
        $mt103Documents = [];

        foreach ($mt101->getTransactions() as $index => $transaction) {
            $mt103Documents[] = new Mt103Document(
                sendersReference: sprintf('%s-%03d', $mt101->getSendersReference(), $index + 1),
                transferDetails: $transaction->getTransferDetails(),
                orderingCustomer: $mt101->getOrderingCustomer(),
                beneficiary: $transaction->getBeneficiary(),
                bankOperationCode: null, // Default CRED
                chargesCode: $transaction->getChargesCode(),
                remittanceInfo: $transaction->getRemittanceInfo(),
                orderingInstitution: $mt101->getOrderingInstitution(),
                sendersCorrespondent: null,
                intermediaryInstitution: null,
                accountWithInstitution: $transaction->getAccountWithInstitution(),
                senderToReceiverInfo: null,
                regulatoryReporting: null,
                transactionTypeCode: null,
                creationDateTime: $mt101->getCreationDateTime()
            );
        }

        return $mt103Documents;
    }

    /**
     * Fasst mehrere MT103-Dokumente zu einem MT101 zusammen.
     * 
     * Voraussetzung: Alle MT103 müssen vom gleichen Auftraggeber stammen.
     * Nützlich für Batch-Verarbeitung.
     * 
     * @param Mt103Document[] $mt103Documents
     */
    public static function mt103ArrayToMt101(
        array $mt103Documents,
        string $sendersReference,
        ?DateTimeImmutable $requestedExecutionDate = null
    ): Mt101Document {
        if (empty($mt103Documents)) {
            throw new \InvalidArgumentException('Mindestens ein MT103-Dokument erforderlich');
        }

        // Ersten MT103 als Referenz für gemeinsame Daten verwenden
        $first = $mt103Documents[0];
        $executionDate = $requestedExecutionDate ?? $first->getTransferDetails()->getValueDate();

        $transactions = [];

        foreach ($mt103Documents as $index => $mt103) {
            $transactions[] = new Mt101Transaction(
                transactionReference: sprintf('%s-%03d', $sendersReference, $index + 1),
                transferDetails: $mt103->getTransferDetails(),
                beneficiary: $mt103->getBeneficiary(),
                accountWithInstitution: $mt103->getAccountWithInstitution(),
                remittanceInfo: $mt103->getRemittanceInfo(),
                chargesCode: $mt103->getChargesCode()
            );
        }

        return new Mt101Document(
            sendersReference: $sendersReference,
            orderingCustomer: $first->getOrderingCustomer(),
            requestedExecutionDate: $executionDate,
            transactions: $transactions,
            orderingInstitution: $first->getOrderingInstitution(),
            customerReference: null,
            messageIndex: '1/1',
            creationDateTime: new DateTimeImmutable()
        );
    }

    /**
     * Berechnet die Gesamtsumme aller Transaktionen in einem MT101.
     * 
     * Achtung: Bei unterschiedlichen Währungen wird nur die Stückzahl zurückgegeben,
     * nicht die Summe. Für eine echte Summe müssen alle Transaktionen die gleiche
     * Währung haben.
     * 
     * @return array{total: float, currency: string, count: int, mixed_currencies: bool}
     */
    public static function calculateMt101Totals(Mt101Document $mt101): array {
        $transactions = $mt101->getTransactions();
        $count = count($transactions);

        if ($count === 0) {
            return [
                'total' => 0.0,
                'currency' => 'EUR',
                'count' => 0,
                'mixed_currencies' => false,
            ];
        }

        $firstCurrency = $transactions[0]->getTransferDetails()->getCurrency();
        $total = 0.0;
        $mixedCurrencies = false;

        foreach ($transactions as $txn) {
            $currency = $txn->getTransferDetails()->getCurrency();
            if ($currency !== $firstCurrency) {
                $mixedCurrencies = true;
            }
            $total += $txn->getTransferDetails()->getAmount();
        }

        return [
            'total' => $mixedCurrencies ? 0.0 : $total,
            'currency' => $firstCurrency->value,
            'count' => $count,
            'mixed_currencies' => $mixedCurrencies,
        ];
    }

    /**
     * Filtert Transaktionen in einem MT101 nach Währung.
     * 
     * @return Mt101Transaction[]
     */
    public static function filterMt101ByChargesCode(Mt101Document $mt101, ChargesCode $chargesCode): array {
        return array_filter(
            $mt101->getTransactions(),
            fn(Mt101Transaction $txn) => $txn->getChargesCode() === $chargesCode
        );
    }

    /**
     * Validiert ein MT103-Dokument auf Vollständigkeit.
     * 
     * @return array{valid: bool, errors: string[]}
     */
    public static function validateMt103(Mt103Document $mt103): array {
        $errors = [];

        // Pflichtfelder prüfen
        if (empty($mt103->getSendersReference())) {
            $errors[] = 'Sender\'s Reference (:20:) fehlt';
        }

        $transferDetails = $mt103->getTransferDetails();
        if ($transferDetails->getAmount() <= 0) {
            $errors[] = 'Betrag muss größer als 0 sein (:32A:/:32B:)';
        }

        $orderingCustomer = $mt103->getOrderingCustomer();
        if ($orderingCustomer->getName() === null && $orderingCustomer->getAccount() === null) {
            $errors[] = 'Ordering Customer (:50a:) muss Name oder Konto enthalten';
        }

        $beneficiary = $mt103->getBeneficiary();
        if ($beneficiary->getName() === null && $beneficiary->getAccount() === null) {
            $errors[] = 'Beneficiary (:59a:) muss Name oder Konto enthalten';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validiert ein MT101-Dokument auf Vollständigkeit.
     * 
     * @return array{valid: bool, errors: string[], transaction_errors: array<int, string[]>}
     */
    public static function validateMt101(Mt101Document $mt101): array {
        $errors = [];
        $transactionErrors = [];

        // Sequence A - Pflichtfelder
        if (empty($mt101->getSendersReference())) {
            $errors[] = 'Sender\'s Reference (:20:) fehlt';
        }

        $orderingCustomer = $mt101->getOrderingCustomer();
        if ($orderingCustomer->getName() === null && $orderingCustomer->getAccount() === null) {
            $errors[] = 'Ordering Customer (:50:) muss Name oder Konto enthalten';
        }

        // Sequence B - Transaktionen prüfen
        $transactions = $mt101->getTransactions();
        if (empty($transactions)) {
            $errors[] = 'Mindestens eine Transaktion erforderlich';
        }

        foreach ($transactions as $index => $txn) {
            $txnErrors = [];

            if ($txn->getTransferDetails()->getAmount() <= 0) {
                $txnErrors[] = 'Betrag muss größer als 0 sein (:32B:)';
            }

            $beneficiary = $txn->getBeneficiary();
            if ($beneficiary->getName() === null && $beneficiary->getAccount() === null) {
                $txnErrors[] = 'Beneficiary (:59a:) muss Name oder Konto enthalten';
            }

            if (!empty($txnErrors)) {
                $transactionErrors[$index] = $txnErrors;
            }
        }

        return [
            'valid' => empty($errors) && empty($transactionErrors),
            'errors' => $errors,
            'transaction_errors' => $transactionErrors,
        ];
    }

    /**
     * Erstellt eine Zusammenfassung eines MT101-Dokuments.
     * 
     * @return array{
     *     reference: string,
     *     execution_date: string,
     *     ordering_customer: string,
     *     transaction_count: int,
     *     totals_by_currency: array<string, float>,
     *     charges_summary: array<string, int>
     * }
     */
    public static function summarizeMt101(Mt101Document $mt101): array {
        $totalsByCurrency = [];
        $chargesSummary = [];

        foreach ($mt101->getTransactions() as $txn) {
            $currency = $txn->getTransferDetails()->getCurrency()->value;
            $amount = $txn->getTransferDetails()->getAmount();

            $totalsByCurrency[$currency] = ($totalsByCurrency[$currency] ?? 0.0) + $amount;

            $charges = $txn->getChargesCode()?->value ?? 'NONE';
            $chargesSummary[$charges] = ($chargesSummary[$charges] ?? 0) + 1;
        }

        return [
            'reference' => $mt101->getSendersReference(),
            'execution_date' => $mt101->getRequestedExecutionDate()->format('Y-m-d'),
            'ordering_customer' => $mt101->getOrderingCustomer()->getName() ?? 'Unbekannt',
            'transaction_count' => $mt101->countTransactions(),
            'totals_by_currency' => $totalsByCurrency,
            'charges_summary' => $chargesSummary,
        ];
    }

    /**
     * Erstellt eine Zusammenfassung eines MT103-Dokuments.
     * 
     * @return array{
     *     reference: string,
     *     value_date: string,
     *     amount: float,
     *     currency: string,
     *     ordering_customer: string,
     *     beneficiary: string,
     *     bank_operation: string,
     *     charges: string
     * }
     */
    public static function summarizeMt103(Mt103Document $mt103): array {
        $transferDetails = $mt103->getTransferDetails();

        return [
            'reference' => $mt103->getSendersReference(),
            'value_date' => $transferDetails->getValueDate()->format('Y-m-d'),
            'amount' => $transferDetails->getAmount(),
            'currency' => $transferDetails->getCurrency()->value,
            'ordering_customer' => $mt103->getOrderingCustomer()->getName() ?? 'Unbekannt',
            'beneficiary' => $mt103->getBeneficiary()->getName() ?? 'Unbekannt',
            'bank_operation' => $mt103->getBankOperationCode()->value,
            'charges' => $mt103->getChargesCode()?->value ?? 'NONE',
        ];
    }
}
