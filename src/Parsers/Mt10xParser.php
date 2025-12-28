<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt10xParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Entities\Common\Banking\Mt1\Party;
use CommonToolkit\Entities\Common\Banking\Mt1\TransferDetails;
use CommonToolkit\Entities\Common\Banking\Mt1\Type101\Document as Mt101Document;
use CommonToolkit\Entities\Common\Banking\Mt1\Type101\Transaction as Mt101Transaction;
use CommonToolkit\Entities\Common\Banking\Mt1\Type103\Document as Mt103Document;
use CommonToolkit\Entities\Common\Banking\Swift\Message;
use CommonToolkit\Enums\Common\Banking\BankOperationCode;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use CommonToolkit\Enums\Common\Banking\MtType;
use DateTimeImmutable;
use RuntimeException;

/**
 * Parser für MT10x SWIFT-Nachrichten (Zahlungsaufträge).
 * 
 * Unterstützte Formate:
 * - MT101: Request for Transfer (Sammelüberweisung)
 * - MT103: Single Customer Credit Transfer (Einzelüberweisung)
 * 
 * Kann sowohl rohe Text-Blöcke als auch vollständige SWIFT-Messages parsen.
 * 
 * @package CommonToolkit\Parsers
 */
final class Mt10xParser {
    /**
     * Parst einen MT103 Text-Block in ein Document.
     */
    public static function parseMt103(string $content): Mt103Document {
        $fields = self::parseFields($content);

        // Pflichtfelder
        $sendersReference = $fields[':20:'] ?? throw new RuntimeException('Feld :20: (Sender\'s Reference) fehlt');
        $field32A = $fields[':32A:'] ?? null;

        // Datum aus :32A: oder :30:
        $valueDate = null;
        if ($field32A !== null) {
            $transferDetails = TransferDetails::fromField32A($field32A);

            // Prüfe auf Währungsumrechnung (:33B: und :36:)
            $field33B = $fields[':33B:'] ?? null;
            $field36 = $fields[':36:'] ?? null;

            if ($field33B !== null && $field36 !== null) {
                $exchangeRate = (float) str_replace(',', '.', $field36);
                $transferDetails = $transferDetails->withExchangeRate($exchangeRate);
            }
        } else {
            // Fallback: :32B: mit :30: für Datum
            $field32B = $fields[':32B:'] ?? throw new RuntimeException('Feld :32A: oder :32B: fehlt');
            $field30 = $fields[':30:'] ?? null;
            $valueDate = $field30 !== null
                ? DateTimeImmutable::createFromFormat('ymd', $field30)
                : new DateTimeImmutable();
            if (!$valueDate) {
                $valueDate = new DateTimeImmutable();
            }
            $transferDetails = TransferDetails::fromField32B($field32B, $valueDate);
        }

        // Bank Operation Code
        $bankOpCode = isset($fields[':23B:'])
            ? BankOperationCode::fromString($fields[':23B:'])
            : BankOperationCode::CRED;

        // Parteien
        $orderingCustomer = self::parseParty($fields, [':50K:', ':50H:', ':50F:', ':50:']);
        $beneficiary = self::parseParty($fields, [':59:', ':59A:', ':59F:']);

        if ($orderingCustomer === null) {
            $orderingCustomer = new Party();
        }
        if ($beneficiary === null) {
            $beneficiary = new Party();
        }

        // Optionale Felder
        $chargesCode = isset($fields[':71A:']) ? ChargesCode::fromString($fields[':71A:']) : null;
        $remittanceInfo = $fields[':70:'] ?? null;
        $senderToReceiverInfo = $fields[':72:'] ?? null;
        $regulatoryReporting = $fields[':77B:'] ?? null;
        $transactionTypeCode = $fields[':26T:'] ?? null;

        // Beteiligte Banken
        $orderingInstitution = self::parseParty($fields, [':52A:', ':52D:', ':52:']);
        $sendersCorrespondent = self::parseParty($fields, [':53A:', ':53B:', ':53:']);
        $intermediaryInstitution = self::parseParty($fields, [':56A:', ':56D:', ':56:']);
        $accountWithInstitution = self::parseParty($fields, [':57A:', ':57D:', ':57:']);

        return new Mt103Document(
            sendersReference: $sendersReference,
            transferDetails: $transferDetails,
            orderingCustomer: $orderingCustomer,
            beneficiary: $beneficiary,
            bankOperationCode: $bankOpCode,
            chargesCode: $chargesCode,
            remittanceInfo: $remittanceInfo,
            orderingInstitution: $orderingInstitution,
            sendersCorrespondent: $sendersCorrespondent,
            intermediaryInstitution: $intermediaryInstitution,
            accountWithInstitution: $accountWithInstitution,
            senderToReceiverInfo: $senderToReceiverInfo,
            regulatoryReporting: $regulatoryReporting,
            transactionTypeCode: $transactionTypeCode
        );
    }

    /**
     * Parst einen MT101 Text-Block in ein Document.
     */
    public static function parseMt101(string $content): Mt101Document {
        // Teile in Sequence A und Sequence B auf
        $lines = preg_split('/\r?\n/', trim($content));
        $sequenceA = [];
        $sequencesBLines = [];
        $currentSequenceB = [];
        $inSequenceB = false;

        foreach ($lines as $line) {
            // :21: startet eine neue Sequence B (Transaktion)
            if (preg_match('/^:21:/', $line)) {
                if ($inSequenceB && !empty($currentSequenceB)) {
                    $sequencesBLines[] = implode("\n", $currentSequenceB);
                }
                $currentSequenceB = [$line];
                $inSequenceB = true;
            } elseif ($inSequenceB) {
                $currentSequenceB[] = $line;
            } else {
                $sequenceA[] = $line;
            }
        }

        // Letzte Sequence B speichern
        if (!empty($currentSequenceB)) {
            $sequencesBLines[] = implode("\n", $currentSequenceB);
        }

        // Sequence A parsen
        $fieldsA = self::parseFields(implode("\n", $sequenceA));

        // Sequence A - General Information
        $sendersReference = $fieldsA[':20:'] ?? throw new RuntimeException('Feld :20: (Sender\'s Reference) fehlt');
        $customerReference = $fieldsA[':21R:'] ?? null;
        $messageIndex = $fieldsA[':28D:'] ?? '1/1';

        // Execution Date
        $field30 = $fieldsA[':30:'] ?? null;
        $executionDate = $field30 !== null
            ? DateTimeImmutable::createFromFormat('ymd', $field30)
            : new DateTimeImmutable();
        if (!$executionDate) {
            $executionDate = new DateTimeImmutable();
        }

        // Ordering Customer und Institution
        $orderingCustomer = self::parseParty($fieldsA, [':50H:', ':50K:', ':50F:', ':50:']);
        $orderingInstitution = self::parseParty($fieldsA, [':52A:', ':52C:', ':52:']);

        if ($orderingCustomer === null) {
            $orderingCustomer = new Party();
        }

        // Sequence B - Transaktionen parsen
        $transactions = [];

        foreach ($sequencesBLines as $seqBContent) {
            $fieldsB = self::parseFields($seqBContent);

            $transactionRef = $fieldsB[':21:'] ?? $sendersReference;
            $field32B = $fieldsB[':32B:'] ?? null;

            if ($field32B !== null) {
                $transferDetails = TransferDetails::fromField32B($field32B, $executionDate);
                $beneficiary = self::parseParty($fieldsB, [':59:', ':59A:', ':59F:']);
                $accountWithInstitution = self::parseParty($fieldsB, [':57A:', ':57D:', ':57:']);
                $remittanceInfo = $fieldsB[':70:'] ?? null;
                $chargesCode = isset($fieldsB[':71A:']) ? ChargesCode::fromString($fieldsB[':71A:']) : null;

                $transactions[] = new Mt101Transaction(
                    transactionReference: $transactionRef,
                    transferDetails: $transferDetails,
                    beneficiary: $beneficiary ?? new Party(),
                    accountWithInstitution: $accountWithInstitution,
                    remittanceInfo: $remittanceInfo,
                    chargesCode: $chargesCode
                );
            }
        }

        return new Mt101Document(
            sendersReference: $sendersReference,
            orderingCustomer: $orderingCustomer,
            requestedExecutionDate: $executionDate,
            transactions: $transactions,
            orderingInstitution: $orderingInstitution,
            customerReference: $customerReference,
            messageIndex: $messageIndex
        );
    }

    /**
     * Parst eine SWIFT Message basierend auf dem Typ.
     */
    public static function parse(Message $message): Mt101Document|Mt103Document {
        $textBlock = $message->getTextBlock();

        return match ($message->getMessageType()) {
            MtType::MT101 => self::parseMt101($textBlock),
            MtType::MT103 => self::parseMt103($textBlock),
            default => throw new RuntimeException(
                'Nicht unterstützter MT-Typ: ' . $message->getMessageType()->value
            ),
        };
    }

    /**
     * Parst SWIFT-Felder aus einem Text-Block.
     * 
     * @return array<string, string>
     */
    private static function parseFields(string $content): array {
        $fields = [];
        $lines = preg_split('/\r?\n/', trim($content));

        $currentTag = null;
        $currentValue = '';

        foreach ($lines as $line) {
            // Neues Feld beginnt mit :XX: oder :XXx:
            if (preg_match('/^(:[\d]{2}[A-Za-z]?:)(.*)$/', $line, $matches)) {
                // Vorheriges Feld speichern
                if ($currentTag !== null) {
                    $fields[$currentTag] = trim($currentValue);
                }

                $currentTag = $matches[1];
                $currentValue = $matches[2];
            } elseif ($currentTag !== null) {
                // Fortsetzung des aktuellen Feldes
                $currentValue .= "\n" . $line;
            }
        }

        // Letztes Feld speichern
        if ($currentTag !== null) {
            $fields[$currentTag] = trim($currentValue);
        }

        return $fields;
    }

    /**
     * Parst eine Party aus möglichen Feldvarianten.
     * 
     * @param array<string, string> $fields
     * @param string[] $possibleTags
     */
    private static function parseParty(array $fields, array $possibleTags): ?Party {
        foreach ($possibleTags as $tag) {
            if (isset($fields[$tag])) {
                return Party::fromSwiftField($fields[$tag]);
            }
        }
        return null;
    }
}
