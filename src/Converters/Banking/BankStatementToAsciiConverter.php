<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankStatementToAsciiConverter.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Camt\Type52\Document as Camt052Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document as Camt053Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type54\Document as Camt054Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document as Mt940Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type941\Document as Mt941Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type942\Document as Mt942Document;
use CommonToolkit\Enums\CreditDebit;
use DateTimeImmutable;

/**
 * Konvertiert Bankkontoauszüge in ein lesbares ASCII-Textformat.
 * 
 * Unterstützte Eingabeformate:
 * - CAMT.052, CAMT.053, CAMT.054 (ISO 20022)
 * - MT940, MT941, MT942 (SWIFT FIN)
 * 
 * Ausgabeformat:
 * - Kopfzeile mit Kontoinformationen
 * - Tabellarische Transaktionsliste
 * - Saldenzusammenfassung
 * 
 * @package CommonToolkit\Converters\Banking
 */
final class BankStatementToAsciiConverter {
    private const DEFAULT_LINE_WIDTH = 80;
    private const DEFAULT_AMOUNT_WIDTH = 15;
    private const DEFAULT_DATE_WIDTH = 10;
    private const DEFAULT_DESC_WIDTH = 40;

    private int $lineWidth;
    private string $encoding;
    private bool $includeSepaDetails;
    private string $lineBreak;

    public function __construct(
        int $lineWidth = self::DEFAULT_LINE_WIDTH,
        string $encoding = 'UTF-8',
        bool $includeSepaDetails = true,
        string $lineBreak = "\n"
    ) {
        $this->lineWidth = $lineWidth;
        $this->encoding = $encoding;
        $this->includeSepaDetails = $includeSepaDetails;
        $this->lineBreak = $lineBreak;
    }

    /**
     * Konvertiert ein MT940-Dokument in ASCII-Text.
     */
    public function fromMt940(Mt940Document $document): string {
        $lines = [];

        // Header
        $lines[] = $this->createHeader('KONTOAUSZUG (MT940)');
        $lines[] = '';
        $lines[] = $this->formatKeyValue('Konto', $document->getAccountId());
        $lines[] = $this->formatKeyValue('Referenz', $document->getReferenceId());
        $lines[] = $this->formatKeyValue('Auszugsnummer', $document->getStatementNumber());
        $lines[] = $this->formatKeyValue('Währung', $document->getCurrency()->value);
        $lines[] = '';

        // Opening Balance
        $opening = $document->getOpeningBalance();
        $lines[] = $this->formatKeyValue(
            'Anfangssaldo',
            $this->formatAmount($opening->getAmount(), $opening->getCreditDebit()) .
                ' ' . $document->getCurrency()->value .
                ' (' . $opening->getDate()->format('d.m.Y') . ')'
        );
        $lines[] = '';

        // Transactions
        $lines[] = $this->createTransactionHeader();
        $lines[] = $this->createSeparator('-');

        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($document->getTransactions() as $txn) {
            $lines[] = $this->formatMt940Transaction($txn);

            if ($txn->getCreditDebit() === CreditDebit::CREDIT) {
                $totalCredit += $txn->getAmount();
            } else {
                $totalDebit += $txn->getAmount();
            }

            // Verwendungszweck
            if ($txn->getPurpose() !== null) {
                $purpose = $this->formatPurpose($txn->getPurpose());
                foreach ($purpose as $purposeLine) {
                    $lines[] = '    ' . $purposeLine;
                }
            }
        }

        $lines[] = $this->createSeparator('-');
        $lines[] = '';

        // Summary
        $lines[] = $this->formatKeyValue('Summe Gutschriften', $this->formatAmount($totalCredit, CreditDebit::CREDIT) . ' ' . $document->getCurrency()->value);
        $lines[] = $this->formatKeyValue('Summe Belastungen', $this->formatAmount($totalDebit, CreditDebit::DEBIT) . ' ' . $document->getCurrency()->value);
        $lines[] = '';

        // Closing Balance
        $closing = $document->getClosingBalance();
        $lines[] = $this->formatKeyValue(
            'Schlusssaldo',
            $this->formatAmount($closing->getAmount(), $closing->getCreditDebit()) .
                ' ' . $document->getCurrency()->value .
                ' (' . $closing->getDate()->format('d.m.Y') . ')'
        );

        // Available Balance
        $available = $document->getClosingAvailableBalance();
        if ($available !== null) {
            $lines[] = $this->formatKeyValue(
                'Verfügbar',
                $this->formatAmount($available->getAmount(), $available->getCreditDebit()) .
                    ' ' . $document->getCurrency()->value
            );
        }

        $lines[] = '';
        $lines[] = $this->createSeparator('=');

        return $this->finalize($lines);
    }

    /**
     * Konvertiert ein CAMT.053-Dokument in ASCII-Text.
     */
    public function fromCamt053(Camt053Document $document): string {
        $lines = [];

        // Header
        $lines[] = $this->createHeader('KONTOAUSZUG (CAMT.053)');
        $lines[] = '';
        $lines[] = $this->formatKeyValue('Konto (IBAN)', $document->getAccountIdentifier());
        $lines[] = $this->formatKeyValue('Referenz-ID', $document->getId());
        if ($document->getMessageId() !== null) {
            $lines[] = $this->formatKeyValue('Message-ID', $document->getMessageId());
        }
        if ($document->getSequenceNumber() !== null) {
            $lines[] = $this->formatKeyValue('Auszugsnummer', $document->getSequenceNumber());
        }
        if ($document->getAccountOwner() !== null) {
            $lines[] = $this->formatKeyValue('Kontoinhaber', $document->getAccountOwner());
        }
        $lines[] = $this->formatKeyValue('Währung', $document->getCurrency()->value);
        $lines[] = $this->formatKeyValue('Erstellt am', $document->getCreationDateTime()->format('d.m.Y H:i:s'));
        $lines[] = '';

        // Opening Balance
        $opening = $document->getOpeningBalance();
        if ($opening !== null) {
            $lines[] = $this->formatKeyValue(
                'Anfangssaldo',
                $this->formatAmount($opening->getAmount(), $opening->getCreditDebit()) .
                    ' ' . $document->getCurrency()->value .
                    ' (' . $opening->getDate()->format('d.m.Y') . ')'
            );
            $lines[] = '';
        }

        // Transactions
        $lines[] = $this->createTransactionHeader();
        $lines[] = $this->createSeparator('-');

        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($document->getEntries() as $entry) {
            $lines[] = $this->formatCamtTransaction($entry);

            if ($entry->getCreditDebit() === CreditDebit::CREDIT) {
                $totalCredit += $entry->getAmount();
            } else {
                $totalDebit += $entry->getAmount();
            }

            // SEPA Details
            if ($this->includeSepaDetails) {
                $details = $this->formatSepaDetails($entry);
                foreach ($details as $detailLine) {
                    $lines[] = '    ' . $detailLine;
                }
            }
        }

        $lines[] = $this->createSeparator('-');
        $lines[] = '';

        // Summary
        $lines[] = $this->formatKeyValue('Summe Gutschriften', $this->formatAmount($totalCredit, CreditDebit::CREDIT) . ' ' . $document->getCurrency()->value);
        $lines[] = $this->formatKeyValue('Summe Belastungen', $this->formatAmount($totalDebit, CreditDebit::DEBIT) . ' ' . $document->getCurrency()->value);
        $lines[] = '';

        // Closing Balance
        $closing = $document->getClosingBalance();
        if ($closing !== null) {
            $lines[] = $this->formatKeyValue(
                'Schlusssaldo',
                $this->formatAmount($closing->getAmount(), $closing->getCreditDebit()) .
                    ' ' . $document->getCurrency()->value .
                    ' (' . $closing->getDate()->format('d.m.Y') . ')'
            );
        }

        $lines[] = '';
        $lines[] = $this->createSeparator('=');

        return $this->finalize($lines);
    }

    /**
     * Konvertiert ein CAMT.052-Dokument in ASCII-Text.
     */
    public function fromCamt052(Camt052Document $document): string {
        $lines = [];

        // Header
        $lines[] = $this->createHeader('UNTERTÄGIGER KONTOAUSZUG (CAMT.052)');
        $lines[] = '';
        $lines[] = $this->formatKeyValue('Konto (IBAN)', $document->getAccountIdentifier());
        $lines[] = $this->formatKeyValue('Referenz-ID', $document->getId());
        if ($document->getMessageId() !== null) {
            $lines[] = $this->formatKeyValue('Message-ID', $document->getMessageId());
        }
        $lines[] = $this->formatKeyValue('Währung', $document->getCurrency()->value);
        $lines[] = $this->formatKeyValue('Erstellt am', $document->getCreationDateTime()->format('d.m.Y H:i:s'));
        $lines[] = '';

        // Transactions
        $lines[] = $this->createTransactionHeader();
        $lines[] = $this->createSeparator('-');

        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($document->getEntries() as $entry) {
            $lines[] = $this->formatCamtTransaction($entry);

            if ($entry->getCreditDebit() === CreditDebit::CREDIT) {
                $totalCredit += $entry->getAmount();
            } else {
                $totalDebit += $entry->getAmount();
            }
        }

        $lines[] = $this->createSeparator('-');
        $lines[] = '';

        // Summary
        $lines[] = $this->formatKeyValue('Summe Gutschriften', $this->formatAmount($totalCredit, CreditDebit::CREDIT) . ' ' . $document->getCurrency()->value);
        $lines[] = $this->formatKeyValue('Summe Belastungen', $this->formatAmount($totalDebit, CreditDebit::DEBIT) . ' ' . $document->getCurrency()->value);

        $lines[] = '';
        $lines[] = $this->createSeparator('=');

        return $this->finalize($lines);
    }

    /**
     * Konvertiert ein CAMT.054-Dokument in ASCII-Text.
     */
    public function fromCamt054(Camt054Document $document): string {
        $lines = [];

        // Header
        $lines[] = $this->createHeader('EINZELUMSATZBENACHRICHTIGUNG (CAMT.054)');
        $lines[] = '';
        $lines[] = $this->formatKeyValue('Konto (IBAN)', $document->getAccountIdentifier());
        $lines[] = $this->formatKeyValue('Referenz-ID', $document->getId());
        if ($document->getMessageId() !== null) {
            $lines[] = $this->formatKeyValue('Message-ID', $document->getMessageId());
        }
        $lines[] = $this->formatKeyValue('Währung', $document->getCurrency()->value);
        $lines[] = $this->formatKeyValue('Erstellt am', $document->getCreationDateTime()->format('d.m.Y H:i:s'));
        $lines[] = '';

        // Transactions
        $lines[] = $this->createTransactionHeader();
        $lines[] = $this->createSeparator('-');

        foreach ($document->getEntries() as $entry) {
            $lines[] = $this->formatCamtTransaction($entry);

            if ($this->includeSepaDetails) {
                $details = $this->formatSepaDetails($entry);
                foreach ($details as $detailLine) {
                    $lines[] = '    ' . $detailLine;
                }
            }
        }

        $lines[] = $this->createSeparator('-');
        $lines[] = '';
        $lines[] = $this->createSeparator('=');

        return $this->finalize($lines);
    }

    /**
     * Konvertiert ein MT941-Dokument in ASCII-Text.
     */
    public function fromMt941(Mt941Document $document): string {
        $lines = [];

        // Header
        $lines[] = $this->createHeader('SALDENREPORT (MT941)');
        $lines[] = '';
        $lines[] = $this->formatKeyValue('Konto', $document->getAccountId());
        $lines[] = $this->formatKeyValue('Referenz', $document->getReferenceId());
        $lines[] = $this->formatKeyValue('Auszugsnummer', $document->getStatementNumber());
        $lines[] = $this->formatKeyValue('Währung', $document->getCurrency()->value);
        $lines[] = '';

        // Opening Balance
        $opening = $document->getOpeningBalance();
        $lines[] = $this->formatKeyValue(
            'Anfangssaldo',
            $this->formatAmount($opening->getAmount(), $opening->getCreditDebit()) .
                ' ' . $document->getCurrency()->value .
                ' (' . $opening->getDate()->format('d.m.Y') . ')'
        );

        // Closing Balance
        $closing = $document->getClosingBalance();
        $lines[] = $this->formatKeyValue(
            'Schlusssaldo',
            $this->formatAmount($closing->getAmount(), $closing->getCreditDebit()) .
                ' ' . $document->getCurrency()->value .
                ' (' . $closing->getDate()->format('d.m.Y') . ')'
        );

        $lines[] = '';
        $lines[] = $this->createSeparator('=');

        return $this->finalize($lines);
    }

    /**
     * Konvertiert ein MT942-Dokument in ASCII-Text.
     */
    public function fromMt942(Mt942Document $document): string {
        $lines = [];

        // Header
        $lines[] = $this->createHeader('UNTERTÄGIGER KONTOAUSZUG (MT942)');
        $lines[] = '';
        $lines[] = $this->formatKeyValue('Konto', $document->getAccountId());
        $lines[] = $this->formatKeyValue('Referenz', $document->getReferenceId());
        $lines[] = $this->formatKeyValue('Auszugsnummer', $document->getStatementNumber());
        $lines[] = $this->formatKeyValue('Währung', $document->getCurrency()->value);
        $lines[] = '';

        // Transactions
        $lines[] = $this->createTransactionHeader();
        $lines[] = $this->createSeparator('-');

        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($document->getTransactions() as $txn) {
            $lines[] = $this->formatMt940Transaction($txn);

            if ($txn->getCreditDebit() === CreditDebit::CREDIT) {
                $totalCredit += $txn->getAmount();
            } else {
                $totalDebit += $txn->getAmount();
            }

            if ($txn->getPurpose() !== null) {
                $purpose = $this->formatPurpose($txn->getPurpose());
                foreach ($purpose as $purposeLine) {
                    $lines[] = '    ' . $purposeLine;
                }
            }
        }

        $lines[] = $this->createSeparator('-');
        $lines[] = '';

        // Summary
        $lines[] = $this->formatKeyValue('Summe Gutschriften', $this->formatAmount($totalCredit, CreditDebit::CREDIT) . ' ' . $document->getCurrency()->value);
        $lines[] = $this->formatKeyValue('Summe Belastungen', $this->formatAmount($totalDebit, CreditDebit::DEBIT) . ' ' . $document->getCurrency()->value);

        $lines[] = '';
        $lines[] = $this->createSeparator('=');

        return $this->finalize($lines);
    }

    /**
     * Erstellt eine zentrierte Header-Zeile.
     */
    private function createHeader(string $title): string {
        $padding = max(0, (int) (($this->lineWidth - mb_strlen($title) - 4) / 2));
        return str_repeat('=', $padding) . '[ ' . $title . ' ]' . str_repeat('=', $padding);
    }

    /**
     * Erstellt eine Trennlinie.
     */
    private function createSeparator(string $char): string {
        return str_repeat($char, $this->lineWidth);
    }

    /**
     * Formatiert ein Schlüssel-Wert-Paar.
     */
    private function formatKeyValue(string $key, string $value): string {
        return str_pad($key . ':', 20) . $value;
    }

    /**
     * Erstellt den Transaktions-Tabellenkopf.
     */
    private function createTransactionHeader(): string {
        return sprintf(
            '%-10s %-10s %-3s %15s  %-s',
            'Buchung',
            'Valuta',
            'C/D',
            'Betrag',
            'Referenz'
        );
    }

    /**
     * Formatiert eine MT940-Transaktion.
     */
    private function formatMt940Transaction(mixed $txn): string {
        $direction = $txn->getCreditDebit() === CreditDebit::CREDIT ? '+' : '-';

        return sprintf(
            '%-10s %-10s  %s  %15s  %-s',
            $txn->getBookingDate()->format('d.m.Y'),
            $txn->getValutaDate()?->format('d.m.Y') ?? '',
            $direction,
            $this->formatAmountNumber($txn->getAmount()),
            $this->truncate($txn->getReference()->getReference() ?? '', 30)
        );
    }

    /**
     * Formatiert eine CAMT-Transaktion.
     */
    private function formatCamtTransaction(mixed $entry): string {
        $direction = $entry->getCreditDebit() === CreditDebit::CREDIT ? '+' : '-';
        $reference = $entry->getReference()?->getEndToEndId() ?? $entry->getReference()?->getAccountServicerRef() ?? '';

        return sprintf(
            '%-10s %-10s  %s  %15s  %-s',
            $entry->getBookingDate()->format('d.m.Y'),
            $entry->getValutaDate()?->format('d.m.Y') ?? '',
            $direction,
            $this->formatAmountNumber($entry->getAmount()),
            $this->truncate($reference, 30)
        );
    }

    /**
     * Formatiert SEPA-Details einer CAMT-Transaktion.
     * 
     * @return string[]
     */
    private function formatSepaDetails(mixed $entry): array {
        $details = [];
        $ref = $entry->getReference();

        if ($ref !== null) {
            if ($ref->getEndToEndId() !== null && $ref->getEndToEndId() !== 'NOTPROVIDED') {
                $details[] = 'End-to-End-ID: ' . $ref->getEndToEndId();
            }
            if ($ref->getMandateId() !== null) {
                $details[] = 'Mandats-ID: ' . $ref->getMandateId();
            }
            if ($ref->getCreditorId() !== null) {
                $details[] = 'Gläubiger-ID: ' . $ref->getCreditorId();
            }
        }

        if (method_exists($entry, 'getRemittanceInfo') && $entry->getRemittanceInfo() !== null) {
            $lines = $this->wrapText($entry->getRemittanceInfo(), $this->lineWidth - 8);
            foreach ($lines as $line) {
                $details[] = 'Zweck: ' . $line;
            }
        }

        if (method_exists($entry, 'getDebtor') && $entry->getDebtor() !== null) {
            $details[] = 'Auftraggeber: ' . $entry->getDebtor();
        }

        if (method_exists($entry, 'getCreditor') && $entry->getCreditor() !== null) {
            $details[] = 'Empfänger: ' . $entry->getCreditor();
        }

        return $details;
    }

    /**
     * Formatiert den Verwendungszweck (MT940 :86:).
     * 
     * @return string[]
     */
    private function formatPurpose(string $purpose): array {
        // Entferne strukturierte Codes und extrahiere lesbaren Text
        $readable = preg_replace('/\?[\d]{2}/', ' ', $purpose);
        $readable = preg_replace('/\s+/', ' ', $readable ?? $purpose);
        $readable = trim($readable ?? '');

        return $this->wrapText($readable, $this->lineWidth - 8);
    }

    /**
     * Formatiert einen Betrag mit Vorzeichen.
     */
    private function formatAmount(float $amount, CreditDebit $direction): string {
        $sign = $direction === CreditDebit::CREDIT ? '+' : '-';
        return $sign . ' ' . number_format($amount, 2, ',', '.');
    }

    /**
     * Formatiert einen Betrag ohne Vorzeichen.
     */
    private function formatAmountNumber(float $amount): string {
        return number_format($amount, 2, ',', '.');
    }

    /**
     * Kürzt einen String auf maximale Länge.
     */
    private function truncate(string $text, int $maxLength): string {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }
        return mb_substr($text, 0, $maxLength - 3) . '...';
    }

    /**
     * Bricht Text in Zeilen um.
     * 
     * @return string[]
     */
    private function wrapText(string $text, int $maxWidth): array {
        $lines = [];
        $words = explode(' ', $text);
        $currentLine = '';

        foreach ($words as $word) {
            if ($currentLine === '') {
                $currentLine = $word;
            } elseif (mb_strlen($currentLine . ' ' . $word) <= $maxWidth) {
                $currentLine .= ' ' . $word;
            } else {
                $lines[] = $currentLine;
                $currentLine = $word;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines ?: [''];
    }

    /**
     * Finalisiert die Ausgabe (Encoding, Line Breaks).
     */
    private function finalize(array $lines): string {
        $output = implode($this->lineBreak, $lines);

        if ($this->encoding !== 'UTF-8') {
            $converted = mb_convert_encoding($output, $this->encoding, 'UTF-8');
            if ($converted !== false) {
                $output = $converted;
            }
        }

        return $output;
    }
}
