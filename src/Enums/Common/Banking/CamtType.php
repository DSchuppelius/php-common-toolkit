<?php
/*
 * Created on   : Sun Jul 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CamtType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking;

/**
 * CAMT Nachrichtentypen gemäß ISO 20022.
 * 
 * @package CommonToolkit\Enums\Common\Banking
 */
enum CamtType: string {
    /**
     * CAMT.052 - Bank to Customer Account Report (Intraday)
     * Untertägige Kontobewegungsinformation
     */
    case CAMT052 = 'camt.052';

    /**
     * CAMT.053 - Bank to Customer Statement (End of Day)
     * Täglicher Kontoauszug
     */
    case CAMT053 = 'camt.053';

    /**
     * CAMT.054 - Bank to Customer Debit Credit Notification
     * Soll/Haben-Avis (Einzelumsatzbenachrichtigung)
     */
    case CAMT054 = 'camt.054';

    /**
     * Gibt den deutschen Beschreibungstext zurück.
     */
    public function getDescription(): string {
        return match ($this) {
            self::CAMT052 => 'Untertägige Kontobewegungsinformation',
            self::CAMT053 => 'Täglicher Kontoauszug',
            self::CAMT054 => 'Soll/Haben-Avis',
        };
    }

    /**
     * Gibt den ISO 20022 Nachrichtennamen zurück.
     */
    public function getMessageName(): string {
        return match ($this) {
            self::CAMT052 => 'BankToCustomerAccountReport',
            self::CAMT053 => 'BankToCustomerStatement',
            self::CAMT054 => 'BankToCustomerDebitCreditNotification',
        };
    }

    /**
     * Gibt das Root-Element im XML zurück.
     */
    public function getRootElement(): string {
        return match ($this) {
            self::CAMT052 => 'BkToCstmrAcctRpt',
            self::CAMT053 => 'BkToCstmrStmt',
            self::CAMT054 => 'BkToCstmrDbtCdtNtfctn',
        };
    }

    /**
     * Gibt das Statement/Report/Notification Element zurück.
     */
    public function getStatementElement(): string {
        return match ($this) {
            self::CAMT052 => 'Rpt',
            self::CAMT053 => 'Stmt',
            self::CAMT054 => 'Ntfctn',
        };
    }

    /**
     * Ermittelt den CAMT-Typ aus einem XML-Dokument.
     */
    public static function fromXml(string $xmlContent): ?self {
        if (str_contains($xmlContent, 'camt.052')) {
            return self::CAMT052;
        }
        if (str_contains($xmlContent, 'camt.053')) {
            return self::CAMT053;
        }
        if (str_contains($xmlContent, 'camt.054')) {
            return self::CAMT054;
        }

        // Fallback: Nach Root-Element suchen
        if (str_contains($xmlContent, '<BkToCstmrAcctRpt>') || str_contains($xmlContent, '<BkToCstmrAcctRpt ')) {
            return self::CAMT052;
        }
        if (str_contains($xmlContent, '<BkToCstmrStmt>') || str_contains($xmlContent, '<BkToCstmrStmt ')) {
            return self::CAMT053;
        }
        if (str_contains($xmlContent, '<BkToCstmrDbtCdtNtfctn>') || str_contains($xmlContent, '<BkToCstmrDbtCdtNtfctn ')) {
            return self::CAMT054;
        }

        return null;
    }

    /**
     * Gibt die unterstützten Versionen für diesen CAMT-Typ zurück.
     * @return CamtVersion[]
     */
    public function getSupportedVersions(): array {
        return match ($this) {
            self::CAMT052 => [
                CamtVersion::V02,
                CamtVersion::V06,
                CamtVersion::V08,
                CamtVersion::V10,
                CamtVersion::V12,
            ],
            self::CAMT053 => [
                CamtVersion::V02,
                CamtVersion::V04,
                CamtVersion::V08,
                CamtVersion::V10,
                CamtVersion::V12,
            ],
            self::CAMT054 => [
                CamtVersion::V02,
                CamtVersion::V08,
            ],
        };
    }

    /**
     * Gibt den Namespace für eine bestimmte Version zurück.
     */
    public function getNamespace(CamtVersion $version): string {
        return $version->getNamespace($this);
    }

    /**
     * Prüft ob eine Version für diesen CAMT-Typ unterstützt wird.
     */
    public function supportsVersion(CamtVersion $version): bool {
        return in_array($version, $this->getSupportedVersions(), true);
    }

    /**
     * Gibt die unterstützten Namespace-URIs zurück.
     * @return array<string, string> Version => Namespace-URI
     * @deprecated Verwende getSupportedVersions() und getNamespace() stattdessen
     */
    public function getNamespaces(): array {
        $namespaces = [];
        foreach ($this->getSupportedVersions() as $version) {
            $namespaces[$version->value] = $version->getNamespace($this);
        }
        return $namespaces;
    }
}