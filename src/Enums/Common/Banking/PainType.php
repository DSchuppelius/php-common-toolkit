<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PainType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking;

/**
 * ISO 20022 Payment Initiation (pain) Message Types.
 * 
 * @package CommonToolkit\Enums\Common\Banking
 */
enum PainType: string {
    /** Customer Credit Transfer Initiation - Überweisungsauftrag */
    case PAIN_001 = 'pain.001';

    /** Customer Payment Status Report - Statusbericht */
    case PAIN_002 = 'pain.002';

    /** Customer Payment Reversal - Rückrufauftrag */
    case PAIN_007 = 'pain.007';

    /** Customer Direct Debit Initiation - Lastschriftauftrag */
    case PAIN_008 = 'pain.008';

    /** Mandate Initiation Request - SEPA-Mandatsanfrage */
    case PAIN_009 = 'pain.009';

    /** Mandate Amendment Request - SEPA-Mandatsänderung */
    case PAIN_010 = 'pain.010';

    /** Mandate Cancellation Request - SEPA-Mandatskündigung */
    case PAIN_011 = 'pain.011';

    /** Mandate Acceptance Report - SEPA-Mandatsakzeptanz */
    case PAIN_012 = 'pain.012';

    /** Creditor Payment Activation Request - Creditor-initiated Payment */
    case PAIN_013 = 'pain.013';

    /** Creditor Payment Activation Request Status Report */
    case PAIN_014 = 'pain.014';

    /** Mandate Copy Request */
    case PAIN_017 = 'pain.017';

    /** Mandate Suspension Request */
    case PAIN_018 = 'pain.018';

    /**
     * Gibt den Namespace-Präfix zurück.
     */
    public function namespacePrefix(): string {
        return match ($this) {
            self::PAIN_001 => 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.12',
            self::PAIN_002 => 'urn:iso:std:iso:20022:tech:xsd:pain.002.001.14',
            self::PAIN_007 => 'urn:iso:std:iso:20022:tech:xsd:pain.007.001.12',
            self::PAIN_008 => 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.11',
            self::PAIN_009 => 'urn:iso:std:iso:20022:tech:xsd:pain.009.001.08',
            self::PAIN_010 => 'urn:iso:std:iso:20022:tech:xsd:pain.010.001.08',
            self::PAIN_011 => 'urn:iso:std:iso:20022:tech:xsd:pain.011.001.08',
            self::PAIN_012 => 'urn:iso:std:iso:20022:tech:xsd:pain.012.001.08',
            self::PAIN_013 => 'urn:iso:std:iso:20022:tech:xsd:pain.013.001.10',
            self::PAIN_014 => 'urn:iso:std:iso:20022:tech:xsd:pain.014.001.10',
            self::PAIN_017 => 'urn:iso:std:iso:20022:tech:xsd:pain.017.001.04',
            self::PAIN_018 => 'urn:iso:std:iso:20022:tech:xsd:pain.018.001.04',
        };
    }

    /**
     * Gibt den deutschen Beschreibungstext zurück.
     */
    public function description(): string {
        return match ($this) {
            self::PAIN_001 => 'Überweisungsauftrag',
            self::PAIN_002 => 'Zahlungsstatusbericht',
            self::PAIN_007 => 'Rückrufauftrag',
            self::PAIN_008 => 'Lastschriftauftrag',
            self::PAIN_009 => 'Mandatsanfrage',
            self::PAIN_010 => 'Mandatsänderung',
            self::PAIN_011 => 'Mandatskündigung',
            self::PAIN_012 => 'Mandatsakzeptanz',
            self::PAIN_013 => 'Creditor-initiierte Zahlungsanfrage',
            self::PAIN_014 => 'Statusbericht Zahlungsanfrage',
            self::PAIN_017 => 'Mandatskopie-Anfrage',
            self::PAIN_018 => 'Mandatssuspendierung',
        };
    }

    /**
     * Gibt das Root-Element zurück.
     */
    public function rootElement(): string {
        return match ($this) {
            self::PAIN_001 => 'CstmrCdtTrfInitn',
            self::PAIN_002 => 'CstmrPmtStsRpt',
            self::PAIN_007 => 'CstmrPmtRvsl',
            self::PAIN_008 => 'CstmrDrctDbtInitn',
            self::PAIN_009 => 'MndtInitnReq',
            self::PAIN_010 => 'MndtAmdmntReq',
            self::PAIN_011 => 'MndtCxlReq',
            self::PAIN_012 => 'MndtAccptncRpt',
            self::PAIN_013 => 'CdtrPmtActvtnReq',
            self::PAIN_014 => 'CdtrPmtActvtnReqStsRpt',
            self::PAIN_017 => 'MndtCpyReq',
            self::PAIN_018 => 'MndtSspnsnReq',
        };
    }

    /**
     * Erstellt PainType aus XML-Namespace.
     */
    public static function fromNamespace(string $namespace): ?self {
        foreach (self::cases() as $case) {
            if (str_contains($namespace, $case->value)) {
                return $case;
            }
        }
        return null;
    }

    /**
     * Erstellt PainType aus Root-Element.
     */
    public static function fromRootElement(string $element): ?self {
        foreach (self::cases() as $case) {
            if ($case->rootElement() === $element) {
                return $case;
            }
        }
        return null;
    }

    /**
     * Prüft, ob es sich um ein Überweisungsformat handelt.
     */
    public function isCreditTransfer(): bool {
        return $this === self::PAIN_001;
    }

    /**
     * Prüft, ob es sich um ein Lastschriftformat handelt.
     */
    public function isDirectDebit(): bool {
        return $this === self::PAIN_008;
    }

    /**
     * Prüft, ob es sich um ein Mandatsformat handelt.
     */
    public function isMandate(): bool {
        return in_array($this, [
            self::PAIN_009,
            self::PAIN_010,
            self::PAIN_011,
            self::PAIN_012,
            self::PAIN_017,
            self::PAIN_018,
        ]);
    }

    /**
     * Prüft, ob es sich um einen Statusbericht handelt.
     */
    public function isStatusReport(): bool {
        return in_array($this, [self::PAIN_002, self::PAIN_014]);
    }
}
