<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Message.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Swift;

use CommonToolkit\Contracts\Abstracts\Common\Banking\Mt1\MtDocumentAbstract as Mt1DocumentAbstract;
use CommonToolkit\Contracts\Abstracts\Common\Banking\Mt9\MtDocumentAbstract as Mt9DocumentAbstract;
use CommonToolkit\Entities\Common\Banking\Mt1\Type101\Document as Mt101Document;
use CommonToolkit\Enums\Common\Banking\MtType;
use CommonToolkit\Parsers\Mt10xParser;
use CommonToolkit\Parsers\Mt940DocumentParser;
use RuntimeException;

/**
 * SWIFT FIN Message - Vollständige SWIFT-Nachricht mit allen 5 Blöcken
 * 
 * Format:
 * {1:F01BANKBEBB2222123456}           - Basic Header
 * {2:O9401200250101BANKDEFF...}       - Application Header
 * {3:{108:MUR12345}}                   - User Header (optional)
 * {4:                                  - Text Block (MT-Daten)
 * :20:REFERENCE
 * :25:ACCOUNTID
 * ...
 * -}
 * {5:{CHK:123456789ABC}}               - Trailer
 */
final class Message {
    public function __construct(
        private readonly BasicHeader $basicHeader,
        private readonly ApplicationHeader $applicationHeader,
        private readonly string $textBlock,
        private readonly ?UserHeader $userHeader = null,
        private readonly ?Trailer $trailer = null
    ) {
    }

    public function getBasicHeader(): BasicHeader {
        return $this->basicHeader;
    }

    public function getApplicationHeader(): ApplicationHeader {
        return $this->applicationHeader;
    }

    public function getUserHeader(): ?UserHeader {
        return $this->userHeader;
    }

    /**
     * Gibt den Text-Block (Block 4) zurück - die eigentlichen MT-Daten
     */
    public function getTextBlock(): string {
        return $this->textBlock;
    }

    public function getTrailer(): ?Trailer {
        return $this->trailer;
    }

    /**
     * Gibt den Message Type zurück
     */
    public function getMessageType(): MtType {
        return $this->applicationHeader->getMessageType();
    }

    /**
     * Gibt den BIC des Senders zurück
     */
    public function getSenderBic(): string {
        return $this->basicHeader->getBic();
    }

    /**
     * Gibt den BIC des Empfängers zurück (nur bei Input-Nachrichten)
     */
    public function getReceiverBic(): ?string {
        return $this->applicationHeader->getReceiverBic();
    }

    /**
     * Prüft ob die Nachricht eine Output-Nachricht ist
     */
    public function isOutput(): bool {
        return $this->applicationHeader->isOutput();
    }

    /**
     * Prüft ob die Nachricht eine Input-Nachricht ist
     */
    public function isInput(): bool {
        return $this->applicationHeader->isInput();
    }

    /**
     * Prüft ob die Nachricht eine Training-Nachricht ist
     */
    public function isTraining(): bool {
        return $this->trailer?->isTraining() ?? false;
    }

    /**
     * Gibt die Checksum zurück
     */
    public function getChecksum(): ?string {
        return $this->trailer?->getChecksum();
    }

    /**
     * Gibt die Message User Reference (MUR) zurück
     */
    public function getMur(): ?string {
        return $this->userHeader?->getMur();
    }

    /**
     * Gibt die UETR zurück (für gpi-Tracking)
     */
    public function getUetr(): ?string {
        return $this->userHeader?->getUetr();
    }

    /**
     * Prüft ob STP aktiviert ist
     */
    public function isStp(): bool {
        return $this->userHeader?->isStp() ?? false;
    }

    /**
     * Parst den Text-Block automatisch basierend auf dem MT-Typ.
     * 
     * Unterstützte Typen:
     * - MT101 → Mt101Document (Sammelüberweisung)
     * - MT103 → Mt1DocumentAbstract (Einzelüberweisung)
     * - MT940, MT941, MT942 → Mt9DocumentAbstract (Kontoauszüge)
     * 
     * @return Mt101Document|Mt1DocumentAbstract|Mt9DocumentAbstract Das geparste Dokument
     * @throws RuntimeException Wenn der MT-Typ nicht unterstützt wird
     */
    public function parseDocument(): Mt101Document|Mt1DocumentAbstract|Mt9DocumentAbstract {
        return match ($this->getMessageType()) {
            MtType::MT101 => Mt10xParser::parseMt101($this->textBlock),
            MtType::MT103 => Mt10xParser::parseMt103($this->textBlock),
            MtType::MT940, MtType::MT941, MtType::MT942 => Mt940DocumentParser::parse($this->textBlock),
            default => throw new RuntimeException(
                "Nicht unterstützter MT-Typ für automatisches Parsing: {$this->getMessageType()->value}"
            ),
        };
    }

    /**
     * Prüft ob der MT-Typ ein Zahlungsauftrag ist (MT10x).
     */
    public function isPaymentOrder(): bool {
        return in_array($this->getMessageType(), [MtType::MT101, MtType::MT103], true);
    }

    /**
     * Prüft ob der MT-Typ ein Kontoauszug ist (MT9xx).
     */
    public function isStatement(): bool {
        return in_array($this->getMessageType(), [MtType::MT940, MtType::MT941, MtType::MT942], true);
    }

    /**
     * Gibt die vollständige SWIFT-Nachricht als String zurück
     */
    public function __toString(): string {
        $result = (string) $this->basicHeader;
        $result .= (string) $this->applicationHeader;

        if ($this->userHeader !== null) {
            $userHeaderStr = (string) $this->userHeader;
            if ($userHeaderStr !== '') {
                $result .= $userHeaderStr;
            }
        }

        // Text Block mit Zeilenumbrüchen
        $result .= "{4:\n" . $this->textBlock . "\n-}";

        if ($this->trailer !== null) {
            $trailerStr = (string) $this->trailer;
            if ($trailerStr !== '') {
                $result .= $trailerStr;
            }
        }

        return $result;
    }
}
