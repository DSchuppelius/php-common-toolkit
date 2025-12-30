<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain007Parser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\Type007\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type007\GroupHeader;
use CommonToolkit\Entities\Common\Banking\Pain\Type007\OriginalGroupInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type007\OriginalPaymentInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type007\ReversalReason;
use CommonToolkit\Entities\Common\Banking\Pain\Type007\TransactionInformation;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use DOMDocument;
use DOMXPath;

/**
 * Parser für pain.007 (Customer Payment Reversal).
 * 
 * @package CommonToolkit\Parsers
 */
class Pain007Parser {
    /**
     * Parst ein pain.007 XML-Dokument.
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
        $orgnlGrpInfNode = $xpath->query("//{$prefix}OrgnlGrpInf")->item(0);
        $originalGroupInfo = self::parseOriginalGroupInformation($xpath, $orgnlGrpInfNode, $prefix);

        // OriginalPaymentInformation parsen
        $orgnlPmtInfAndRvslNodes = $xpath->query("//{$prefix}OrgnlPmtInfAndRvsl");
        $originalPaymentInfos = [];

        foreach ($orgnlPmtInfAndRvslNodes as $node) {
            $originalPaymentInfos[] = self::parseOriginalPaymentInformation($xpath, $node, $prefix);
        }

        return new Document($groupHeader, $originalGroupInfo, $originalPaymentInfos);
    }

    /**
     * Prüft, ob ein XML ein gültiges pain.007 Dokument ist.
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
                return $xpath->query('//p:CstmrPmtRvsl')->length > 0;
            }

            return $xpath->query('//CstmrPmtRvsl')->length > 0;
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
        if ($ns && str_contains($ns, 'pain.007')) {
            return $ns;
        }

        return null;
    }

    private static function parseGroupHeader(DOMXPath $xpath, ?\DOMNode $node, string $prefix): GroupHeader {
        if (!$node) {
            return GroupHeader::create('UNKNOWN', new PartyIdentification(name: 'Unknown'));
        }

        $msgId = $xpath->query("{$prefix}MsgId", $node)->item(0)?->textContent ?? 'UNKNOWN';
        $creDtTmStr = $xpath->query("{$prefix}CreDtTm", $node)->item(0)?->textContent;
        $creDtTm = $creDtTmStr ? new DateTimeImmutable($creDtTmStr) : new DateTimeImmutable();

        $nbOfTxs = (int) ($xpath->query("{$prefix}NbOfTxs", $node)->item(0)?->textContent ?? '0');
        $ctrlSumStr = $xpath->query("{$prefix}CtrlSum", $node)->item(0)?->textContent;
        $ctrlSum = $ctrlSumStr !== null ? (float) $ctrlSumStr : null;

        $grpRvsl = $xpath->query("{$prefix}GrpRvsl", $node)->item(0)?->textContent;
        $groupReversal = $grpRvsl === 'true';

        // InitgPty parsen
        $initgPtyNode = $xpath->query("{$prefix}InitgPty", $node)->item(0);
        $initiatingParty = self::parsePartyIdentification($xpath, $initgPtyNode, $prefix);

        return new GroupHeader($msgId, $creDtTm, $nbOfTxs, $ctrlSum, $initiatingParty, $groupReversal);
    }

    private static function parseOriginalGroupInformation(DOMXPath $xpath, ?\DOMNode $node, string $prefix): ?OriginalGroupInformation {
        if (!$node) {
            return null;
        }

        $orgnlMsgId = $xpath->query("{$prefix}OrgnlMsgId", $node)->item(0)?->textContent ?? 'UNKNOWN';
        $orgnlMsgNmId = $xpath->query("{$prefix}OrgnlMsgNmId", $node)->item(0)?->textContent ?? 'pain.008.001.11';
        $orgnlCreDtTmStr = $xpath->query("{$prefix}OrgnlCreDtTm", $node)->item(0)?->textContent;
        $orgnlCreDtTm = $orgnlCreDtTmStr ? new DateTimeImmutable($orgnlCreDtTmStr) : null;

        $orgnlNbOfTxs = (int) ($xpath->query("{$prefix}OrgnlNbOfTxs", $node)->item(0)?->textContent ?? '0');
        $orgnlCtrlSumStr = $xpath->query("{$prefix}OrgnlCtrlSum", $node)->item(0)?->textContent;
        $orgnlCtrlSum = $orgnlCtrlSumStr !== null ? (float) $orgnlCtrlSumStr : null;

        // ReversalReason parsen
        $rvslRsnInfNode = $xpath->query("{$prefix}RvslRsnInf", $node)->item(0);
        $reversalReason = $rvslRsnInfNode ? self::parseReversalReason($xpath, $rvslRsnInfNode, $prefix) : null;

        return new OriginalGroupInformation(
            $orgnlMsgId,
            $orgnlMsgNmId,
            $orgnlCreDtTm,
            $orgnlNbOfTxs > 0 ? $orgnlNbOfTxs : null,
            $orgnlCtrlSum,
            $reversalReason
        );
    }

    private static function parseOriginalPaymentInformation(DOMXPath $xpath, \DOMNode $node, string $prefix): OriginalPaymentInformation {
        $orgnlPmtInfId = $xpath->query("{$prefix}OrgnlPmtInfId", $node)->item(0)?->textContent ?? 'UNKNOWN';
        $orgnlNbOfTxs = (int) ($xpath->query("{$prefix}OrgnlNbOfTxs", $node)->item(0)?->textContent ?? '0');
        $orgnlCtrlSumStr = $xpath->query("{$prefix}OrgnlCtrlSum", $node)->item(0)?->textContent;
        $orgnlCtrlSum = $orgnlCtrlSumStr !== null ? (float) $orgnlCtrlSumStr : null;

        $pmtInfRvsl = $xpath->query("{$prefix}PmtInfRvsl", $node)->item(0)?->textContent;
        $paymentInfoReversal = $pmtInfRvsl === 'true';

        // ReversalReason parsen
        $rvslRsnInfNode = $xpath->query("{$prefix}RvslRsnInf", $node)->item(0);
        $reversalReason = $rvslRsnInfNode ? self::parseReversalReason($xpath, $rvslRsnInfNode, $prefix) : null;

        // Transactions parsen
        $txInfNodes = $xpath->query("{$prefix}TxInf", $node);
        $transactions = [];

        foreach ($txInfNodes as $txNode) {
            $transactions[] = self::parseTransactionInformation($xpath, $txNode, $prefix);
        }

        return new OriginalPaymentInformation(
            originalPaymentInformationId: $orgnlPmtInfId,
            originalNumberOfTransactions: $orgnlNbOfTxs > 0 ? $orgnlNbOfTxs : null,
            originalControlSum: $orgnlCtrlSum,
            paymentInformationReversal: $paymentInfoReversal ?: null,
            reversalReason: $reversalReason,
            transactionInformations: $transactions
        );
    }

    private static function parseTransactionInformation(DOMXPath $xpath, \DOMNode $node, string $prefix): TransactionInformation {
        $rvslId = $xpath->query("{$prefix}RvslId", $node)->item(0)?->textContent;
        $orgnlInstrId = $xpath->query("{$prefix}OrgnlInstrId", $node)->item(0)?->textContent;
        $orgnlEndToEndId = $xpath->query("{$prefix}OrgnlEndToEndId", $node)->item(0)?->textContent ?? 'NOTPROVIDED';

        $rvsdInstdAmtStr = $xpath->query("{$prefix}RvsdInstdAmt", $node)->item(0)?->textContent;
        $rvsdInstdAmt = $rvsdInstdAmtStr !== null ? (float) $rvsdInstdAmtStr : null;

        $ccyNode = $xpath->query("{$prefix}RvsdInstdAmt", $node)->item(0);
        $currency = null;
        if ($ccyNode instanceof \DOMElement && $ccyNode->hasAttribute('Ccy')) {
            $currency = CurrencyCode::tryFrom($ccyNode->getAttribute('Ccy'));
        }

        // ReversalReason parsen
        $rvslRsnInfNode = $xpath->query("{$prefix}RvslRsnInf", $node)->item(0);
        $reversalReason = $rvslRsnInfNode ? self::parseReversalReason($xpath, $rvslRsnInfNode, $prefix) : null;

        return new TransactionInformation(
            reversalId: $rvslId,
            originalInstructionId: $orgnlInstrId,
            originalEndToEndId: $orgnlEndToEndId,
            reversedAmount: $rvsdInstdAmt,
            currency: $currency,
            reversalReason: $reversalReason
        );
    }

    private static function parseReversalReason(DOMXPath $xpath, \DOMNode $node, string $prefix): ReversalReason {
        $rsnCd = $xpath->query("{$prefix}Rsn/{$prefix}Cd", $node)->item(0)?->textContent;
        $rsnPrtry = $xpath->query("{$prefix}Rsn/{$prefix}Prtry", $node)->item(0)?->textContent;

        $addtlInf = [];
        $addtlInfNodes = $xpath->query("{$prefix}AddtlInf", $node);
        foreach ($addtlInfNodes as $addtlInfNode) {
            $addtlInf[] = $addtlInfNode->textContent;
        }

        return new ReversalReason($rsnCd, $rsnPrtry, $addtlInf);
    }

    private static function parsePartyIdentification(DOMXPath $xpath, ?\DOMNode $node, string $prefix): PartyIdentification {
        if (!$node) {
            return new PartyIdentification(name: 'Unknown');
        }

        $name = $xpath->query("{$prefix}Nm", $node)->item(0)?->textContent;
        $id = $xpath->query("{$prefix}Id/{$prefix}OrgId/{$prefix}Othr/{$prefix}Id", $node)->item(0)?->textContent;
        $schemeNm = $xpath->query("{$prefix}Id/{$prefix}OrgId/{$prefix}Othr/{$prefix}SchmeNm/{$prefix}Cd", $node)->item(0)?->textContent;
        $issr = $xpath->query("{$prefix}Id/{$prefix}OrgId/{$prefix}Othr/{$prefix}Issr", $node)->item(0)?->textContent;

        return new PartyIdentification(
            name: $name,
            organisationId: $id
        );
    }
}
