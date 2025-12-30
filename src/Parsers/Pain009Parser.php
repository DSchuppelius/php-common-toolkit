<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain009Parser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Entities\Common\Banking\Pain\AccountIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\FinancialInstitution;
use CommonToolkit\Entities\Common\Banking\Pain\Mandate\Mandate;
use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\Type009\Document;
use CommonToolkit\Enums\Common\Banking\LocalInstrument;
use CommonToolkit\Enums\Common\Banking\SequenceType;
use DateTimeImmutable;
use DOMDocument;
use DOMXPath;

/**
 * Parser für pain.009 (Mandate Initiation Request).
 * 
 * @package CommonToolkit\Parsers
 */
class Pain009Parser {
    /**
     * Parst ein pain.009 XML-Dokument.
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

        // Header parsen
        $msgId = $xpath->query("//{$prefix}GrpHdr/{$prefix}MsgId")->item(0)?->textContent ?? 'UNKNOWN';
        $creDtTmStr = $xpath->query("//{$prefix}GrpHdr/{$prefix}CreDtTm")->item(0)?->textContent;
        $creDtTm = $creDtTmStr ? new DateTimeImmutable($creDtTmStr) : new DateTimeImmutable();

        // InitgPty parsen
        $initgPtyNode = $xpath->query("//{$prefix}GrpHdr/{$prefix}InitgPty")->item(0);
        $initiatingParty = self::parsePartyIdentification($xpath, $initgPtyNode, $prefix);

        // Mandate parsen
        $mndtNodes = $xpath->query("//{$prefix}Mndt");
        $mandates = [];

        foreach ($mndtNodes as $mndtNode) {
            $mandates[] = self::parseMandate($xpath, $mndtNode, $prefix);
        }

        return new Document($msgId, $creDtTm, $initiatingParty, $mandates);
    }

    /**
     * Prüft, ob ein XML ein gültiges pain.009 Dokument ist.
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
                return $xpath->query('//p:MndtInitnReq')->length > 0;
            }

            return $xpath->query('//MndtInitnReq')->length > 0;
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
        if ($ns && str_contains($ns, 'pain.009')) {
            return $ns;
        }

        return null;
    }

    private static function parseMandate(DOMXPath $xpath, \DOMNode $node, string $prefix): Mandate {
        $mndtId = $xpath->query("{$prefix}MndtId", $node)->item(0)?->textContent ?? 'UNKNOWN';
        $dtOfSgntrStr = $xpath->query("{$prefix}DtOfSgntr", $node)->item(0)?->textContent;
        $dtOfSgntr = $dtOfSgntrStr ? new DateTimeImmutable($dtOfSgntrStr) : new DateTimeImmutable();

        // Creditor
        $cdtrNode = $xpath->query("{$prefix}Cdtr", $node)->item(0);
        $creditor = self::parsePartyIdentification($xpath, $cdtrNode, $prefix);

        // CreditorAccount
        $cdtrAcctNode = $xpath->query("{$prefix}CdtrAcct", $node)->item(0);
        $creditorAccount = self::parseAccountIdentification($xpath, $cdtrAcctNode, $prefix);

        // CreditorAgent
        $cdtrAgtNode = $xpath->query("{$prefix}CdtrAgt", $node)->item(0);
        $creditorAgent = self::parseFinancialInstitution($xpath, $cdtrAgtNode, $prefix);

        // Debtor
        $dbtrNode = $xpath->query("{$prefix}Dbtr", $node)->item(0);
        $debtor = self::parsePartyIdentification($xpath, $dbtrNode, $prefix);

        // DebtorAccount
        $dbtrAcctNode = $xpath->query("{$prefix}DbtrAcct", $node)->item(0);
        $debtorAccount = self::parseAccountIdentification($xpath, $dbtrAcctNode, $prefix);

        // DebtorAgent
        $dbtrAgtNode = $xpath->query("{$prefix}DbtrAgt", $node)->item(0);
        $debtorAgent = self::parseFinancialInstitution($xpath, $dbtrAgtNode, $prefix);

        // CreditorSchemeId
        $cdtrSchmeId = $xpath->query("{$prefix}CdtrSchmeId/{$prefix}Id/{$prefix}PrvtId/{$prefix}Othr/{$prefix}Id", $node)->item(0)?->textContent;

        // LocalInstrument
        $lclInstrmCd = $xpath->query("{$prefix}MndtTpInf/{$prefix}LclInstrm/{$prefix}Cd", $node)->item(0)?->textContent;
        $localInstrument = $lclInstrmCd ? LocalInstrument::tryFrom($lclInstrmCd) : null;

        // SequenceType
        $seqTp = $xpath->query("{$prefix}MndtTpInf/{$prefix}SeqTp", $node)->item(0)?->textContent;
        $sequenceType = $seqTp ? SequenceType::tryFrom($seqTp) : null;

        return new Mandate(
            mandateId: $mndtId,
            dateOfSignature: $dtOfSgntr,
            creditor: $creditor,
            creditorAccount: $creditorAccount,
            creditorAgent: $creditorAgent,
            debtor: $debtor,
            debtorAccount: $debtorAccount,
            debtorAgent: $debtorAgent,
            creditorSchemeId: $cdtrSchmeId,
            localInstrument: $localInstrument,
            sequenceType: $sequenceType
        );
    }

    private static function parsePartyIdentification(DOMXPath $xpath, ?\DOMNode $node, string $prefix): PartyIdentification {
        if (!$node) {
            return new PartyIdentification(name: 'Unknown');
        }

        $name = $xpath->query("{$prefix}Nm", $node)->item(0)?->textContent;
        return new PartyIdentification(name: $name);
    }

    private static function parseAccountIdentification(DOMXPath $xpath, ?\DOMNode $node, string $prefix): AccountIdentification {
        if (!$node) {
            return new AccountIdentification();
        }

        $iban = $xpath->query("{$prefix}Id/{$prefix}IBAN", $node)->item(0)?->textContent;
        return new AccountIdentification(iban: $iban);
    }

    private static function parseFinancialInstitution(DOMXPath $xpath, ?\DOMNode $node, string $prefix): FinancialInstitution {
        if (!$node) {
            return new FinancialInstitution();
        }

        $bic = $xpath->query("{$prefix}FinInstnId/{$prefix}BICFI", $node)->item(0)?->textContent;
        $bic = $bic ?? $xpath->query("{$prefix}FinInstnId/{$prefix}BIC", $node)->item(0)?->textContent;

        return new FinancialInstitution(bic: $bic);
    }
}
