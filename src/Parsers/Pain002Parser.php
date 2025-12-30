<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain002Parser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\GroupHeader;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\OriginalGroupInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\OriginalPaymentInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\StatusReason;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\TransactionInformationAndStatus;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\TransactionStatus;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Parser für pain.002 (Customer Payment Status Report).
 * 
 * @package CommonToolkit\Parsers
 */
class Pain002Parser {
    /**
     * Parst ein pain.002 XML-Dokument.
     */
    public static function fromXml(string $xml): Document {
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $xpath = new DOMXPath($dom);

        // Namespace ermitteln
        $namespace = self::detectNamespace($dom);
        if ($namespace) {
            $xpath->registerNamespace('p', $namespace);
            $prefix = 'p:';
        } else {
            $prefix = '';
        }

        // GroupHeader parsen
        $grpHdrNode = $xpath->query("//{$prefix}GrpHdr")->item(0);
        $groupHeader = self::parseGroupHeader($xpath, $grpHdrNode, $prefix);

        // OriginalGroupInformation parsen
        $orgnlGrpNode = $xpath->query("//{$prefix}OrgnlGrpInfAndSts")->item(0);
        $originalGroupInfo = self::parseOriginalGroupInformation($xpath, $orgnlGrpNode, $prefix);

        // OriginalPaymentInformations parsen
        $pmtInfoNodes = $xpath->query("//{$prefix}OrgnlPmtInfAndSts");
        $paymentInfos = [];

        foreach ($pmtInfoNodes as $pmtInfoNode) {
            $paymentInfos[] = self::parseOriginalPaymentInformation($xpath, $pmtInfoNode, $prefix);
        }

        return new Document($groupHeader, $originalGroupInfo, $paymentInfos);
    }

    /**
     * Prüft, ob ein XML ein gültiges pain.002 Dokument ist.
     */
    public static function isValid(string $xml): bool {
        try {
            $dom = new DOMDocument();
            if (!@$dom->loadXML($xml)) {
                return false;
            }

            $xpath = new DOMXPath($dom);
            $namespace = self::detectNamespace($dom);

            if ($namespace) {
                $xpath->registerNamespace('p', $namespace);
                return $xpath->query('//p:CstmrPmtStsRpt')->length > 0;
            }

            return $xpath->query('//CstmrPmtStsRpt')->length > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function detectNamespace(DOMDocument $dom): ?string {
        $root = $dom->documentElement;

        if ($root && $root->hasAttribute('xmlns')) {
            return $root->getAttribute('xmlns');
        }

        $ns = $root?->namespaceURI;
        if ($ns && str_contains($ns, 'pain.002')) {
            return $ns;
        }

        return null;
    }

    private static function parseGroupHeader(DOMXPath $xpath, ?\DOMNode $node, string $prefix): GroupHeader {
        if (!$node) {
            return GroupHeader::create('UNKNOWN');
        }

        $msgId = $xpath->query("{$prefix}MsgId", $node)->item(0)?->textContent ?? 'UNKNOWN';
        $creDtTmStr = $xpath->query("{$prefix}CreDtTm", $node)->item(0)?->textContent;
        $creDtTm = $creDtTmStr ? new DateTimeImmutable($creDtTmStr) : new DateTimeImmutable();

        // InitgPty parsen
        $initgPtyNode = $xpath->query("{$prefix}InitgPty", $node)->item(0);
        $initiatingParty = null;
        if ($initgPtyNode) {
            $name = $xpath->query("{$prefix}Nm", $initgPtyNode)->item(0)?->textContent;
            if ($name) {
                $initiatingParty = new PartyIdentification(name: $name);
            }
        }

        return new GroupHeader($msgId, $creDtTm, $initiatingParty);
    }

    private static function parseOriginalGroupInformation(DOMXPath $xpath, ?\DOMNode $node, string $prefix): OriginalGroupInformation {
        if (!$node) {
            return new OriginalGroupInformation('UNKNOWN', 'pain.001.001.12');
        }

        $orgnlMsgId = $xpath->query("{$prefix}OrgnlMsgId", $node)->item(0)?->textContent ?? 'UNKNOWN';
        $orgnlMsgNmId = $xpath->query("{$prefix}OrgnlMsgNmId", $node)->item(0)?->textContent ?? 'pain.001.001.12';

        $orgnlCreDtTmStr = $xpath->query("{$prefix}OrgnlCreDtTm", $node)->item(0)?->textContent;
        $orgnlCreDtTm = $orgnlCreDtTmStr ? new DateTimeImmutable($orgnlCreDtTmStr) : null;

        $orgnlNbOfTxs = $xpath->query("{$prefix}OrgnlNbOfTxs", $node)->item(0)?->textContent;
        $orgnlCtrlSum = $xpath->query("{$prefix}OrgnlCtrlSum", $node)->item(0)?->textContent;

        $grpStsStr = $xpath->query("{$prefix}GrpSts", $node)->item(0)?->textContent;
        $grpSts = $grpStsStr ? TransactionStatus::tryFrom($grpStsStr) : null;

        // StsRsnInf parsen
        $statusReasons = self::parseStatusReasons($xpath, $node, $prefix);

        return new OriginalGroupInformation(
            originalMessageId: $orgnlMsgId,
            originalMessageNameId: $orgnlMsgNmId,
            originalCreationDateTime: $orgnlCreDtTm,
            originalNumberOfTransactions: $orgnlNbOfTxs !== null ? (int) $orgnlNbOfTxs : null,
            originalControlSum: $orgnlCtrlSum !== null ? (float) $orgnlCtrlSum : null,
            groupStatus: $grpSts,
            statusReasons: $statusReasons
        );
    }

    private static function parseOriginalPaymentInformation(DOMXPath $xpath, \DOMNode $node, string $prefix): OriginalPaymentInformation {
        $orgnlPmtInfId = $xpath->query("{$prefix}OrgnlPmtInfId", $node)->item(0)?->textContent ?? 'UNKNOWN';

        $pmtInfStsStr = $xpath->query("{$prefix}PmtInfSts", $node)->item(0)?->textContent;
        $pmtInfSts = $pmtInfStsStr ? TransactionStatus::tryFrom($pmtInfStsStr) : null;

        $statusReasons = self::parseStatusReasons($xpath, $node, $prefix);

        // TxInfAndSts parsen
        $txNodes = $xpath->query("{$prefix}TxInfAndSts", $node);
        $txStatuses = [];

        foreach ($txNodes as $txNode) {
            $txStatuses[] = self::parseTransactionStatus($xpath, $txNode, $prefix);
        }

        return new OriginalPaymentInformation(
            originalPaymentInformationId: $orgnlPmtInfId,
            status: $pmtInfSts,
            statusReasons: $statusReasons,
            transactionStatuses: $txStatuses
        );
    }

    private static function parseTransactionStatus(DOMXPath $xpath, \DOMNode $node, string $prefix): TransactionInformationAndStatus {
        $stsId = $xpath->query("{$prefix}StsId", $node)->item(0)?->textContent;
        $orgnlInstrId = $xpath->query("{$prefix}OrgnlInstrId", $node)->item(0)?->textContent;
        $orgnlEndToEndId = $xpath->query("{$prefix}OrgnlEndToEndId", $node)->item(0)?->textContent;
        $orgnlUetr = $xpath->query("{$prefix}OrgnlUETR", $node)->item(0)?->textContent;

        $txStsStr = $xpath->query("{$prefix}TxSts", $node)->item(0)?->textContent;
        $txSts = $txStsStr ? TransactionStatus::tryFrom($txStsStr) : null;

        $statusReasons = self::parseStatusReasons($xpath, $node, $prefix);

        // AccptncDtTm
        $accptncDtTmStr = $xpath->query("{$prefix}AccptncDtTm", $node)->item(0)?->textContent;
        $accptncDtTm = $accptncDtTmStr ? new DateTimeImmutable($accptncDtTmStr) : null;

        // OrgnlTxRef/Amt
        $amtNode = $xpath->query("{$prefix}OrgnlTxRef/{$prefix}Amt/{$prefix}InstdAmt", $node)->item(0);
        $amount = null;
        $currency = null;

        if ($amtNode instanceof DOMElement) {
            $amount = (float) $amtNode->textContent;
            $currencyStr = $amtNode->getAttribute('Ccy') ?: 'EUR';
            $currency = CurrencyCode::tryFrom($currencyStr);
        }

        return new TransactionInformationAndStatus(
            statusId: $stsId,
            originalInstructionId: $orgnlInstrId,
            originalEndToEndId: $orgnlEndToEndId,
            originalUetr: $orgnlUetr,
            status: $txSts,
            statusReasons: $statusReasons,
            originalAmount: $amount,
            originalCurrency: $currency,
            acceptanceDateTime: $accptncDtTm
        );
    }

    /**
     * @return StatusReason[]
     */
    private static function parseStatusReasons(DOMXPath $xpath, \DOMNode $node, string $prefix): array {
        $reasons = [];
        $stsRsnNodes = $xpath->query("{$prefix}StsRsnInf", $node);

        foreach ($stsRsnNodes as $rsnNode) {
            $code = $xpath->query("{$prefix}Rsn/{$prefix}Cd", $rsnNode)->item(0)?->textContent;
            $prtry = $xpath->query("{$prefix}Rsn/{$prefix}Prtry", $rsnNode)->item(0)?->textContent;

            $addtlInf = [];
            $addtlInfNodes = $xpath->query("{$prefix}AddtlInf", $rsnNode);
            foreach ($addtlInfNodes as $infoNode) {
                $addtlInf[] = $infoNode->textContent;
            }

            $reasons[] = new StatusReason($code, $prtry, $addtlInf);
        }

        return $reasons;
    }
}
