<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Purpose.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Mt9;

/**
 * Repräsentiert das strukturierte :86: Mehrzweckfeld einer MT9xx-Transaktion.
 * 
 * Gemäß DATEV-Spezifikation (Dok.-Nr. 9226962) enthält das Mehrzweckfeld:
 * - Geschäftsvorfall-Code (3 Stellen, gem. DFÜ-Abkommen)
 * - Feldschlüssel ?00: Buchungstext (max. 27 Zeichen)
 * - Feldschlüssel ?10: Primanoten-Nr. (max. 10 Zeichen)
 * - Feldschlüssel ?20-?29 und ?60-?63: Verwendungszweck (je max. 27 Zeichen)
 * - Feldschlüssel ?30: BLZ oder BIC Auftraggeber/Zahlungsempfänger (max. 12 Zeichen)
 * - Feldschlüssel ?31: Kontonummer oder IBAN Auftraggeber/Zahlungsempfänger (max. 24 Zeichen)
 * - Feldschlüssel ?32-?33: Name Auftraggeber/Zahlungsempfänger (je max. 27 Zeichen)
 * - Feldschlüssel ?34: Textschlüssel-Ergänzung (3 Zeichen)
 * 
 * @package CommonToolkit\Entities\Common\Banking\Mt9
 */
class Purpose {
    private ?string $gvcCode;           // Geschäftsvorfall-Code (3 Stellen)
    private ?string $bookingText;       // ?00 Buchungstext
    private ?string $primanotenNr;      // ?10 Primanoten-Nr.
    private array $purposeLines;        // ?20-?29, ?60-?63 Verwendungszweck
    private ?string $payerBlz;          // ?30 BLZ/BIC
    private ?string $payerAccount;      // ?31 Kontonummer/IBAN
    private ?string $payerName1;        // ?32 Name Zeile 1
    private ?string $payerName2;        // ?33 Name Zeile 2
    private ?string $textKeyExt;        // ?34 Textschlüssel-Ergänzung
    private ?string $rawText;           // Unstrukturierter Text

    public function __construct(
        ?string $gvcCode = null,
        ?string $bookingText = null,
        ?string $primanotenNr = null,
        array $purposeLines = [],
        ?string $payerBlz = null,
        ?string $payerAccount = null,
        ?string $payerName1 = null,
        ?string $payerName2 = null,
        ?string $textKeyExt = null,
        ?string $rawText = null
    ) {
        $this->gvcCode = $gvcCode;
        $this->bookingText = $bookingText;
        $this->primanotenNr = $primanotenNr;
        $this->purposeLines = $purposeLines;
        $this->payerBlz = $payerBlz;
        $this->payerAccount = $payerAccount;
        $this->payerName1 = $payerName1;
        $this->payerName2 = $payerName2;
        $this->textKeyExt = $textKeyExt;
        $this->rawText = $rawText;
    }

    /**
     * Parst ein strukturiertes :86: Feld.
     */
    public static function fromRawLines(array $lines): self {
        $gvcCode = null;
        $bookingText = null;
        $primanotenNr = null;
        $purposeLines = [];
        $payerBlz = null;
        $payerAccount = null;
        $payerName1 = null;
        $payerName2 = null;
        $textKeyExt = null;
        $rawParts = [];

        foreach ($lines as $line) {
            // Prüfe auf Feldschlüssel ?XX
            if (preg_match('/^\?(\d{2})(.*)$/', $line, $match)) {
                $fieldKey = $match[1];
                $fieldValue = $match[2];

                switch ($fieldKey) {
                    case '00':
                        $bookingText = $fieldValue;
                        break;
                    case '10':
                        $primanotenNr = $fieldValue;
                        break;
                    case '20':
                    case '21':
                    case '22':
                    case '23':
                    case '24':
                    case '25':
                    case '26':
                    case '27':
                    case '28':
                    case '29':
                    case '60':
                    case '61':
                    case '62':
                    case '63':
                        $purposeLines[] = $fieldValue;
                        break;
                    case '30':
                        $payerBlz = $fieldValue;
                        break;
                    case '31':
                        $payerAccount = $fieldValue;
                        break;
                    case '32':
                        $payerName1 = $fieldValue;
                        break;
                    case '33':
                        $payerName2 = $fieldValue;
                        break;
                    case '34':
                        $textKeyExt = $fieldValue;
                        break;
                    default:
                        $rawParts[] = $line;
                }
            } else {
                // Erste Zeile kann GVC-Code + Text enthalten
                if ($gvcCode === null && preg_match('/^(\d{3})(.*)$/', $line, $match)) {
                    $gvcCode = $match[1];
                    if (!empty($match[2])) {
                        $rawParts[] = $match[2];
                    }
                } else {
                    $rawParts[] = $line;
                }
            }
        }

        $rawText = !empty($rawParts) ? implode(' ', $rawParts) : null;

        return new self(
            $gvcCode,
            $bookingText,
            $primanotenNr,
            $purposeLines,
            $payerBlz,
            $payerAccount,
            $payerName1,
            $payerName2,
            $textKeyExt,
            $rawText
        );
    }

    /**
     * Erstellt ein Purpose-Objekt aus einem einfachen Verwendungszweck-String.
     */
    public static function fromString(string $text): self {
        return new self(rawText: $text);
    }

    public function getGvcCode(): ?string {
        return $this->gvcCode;
    }

    public function getBookingText(): ?string {
        return $this->bookingText;
    }

    public function getPrimanotenNr(): ?string {
        return $this->primanotenNr;
    }

    public function getPurposeLines(): array {
        return $this->purposeLines;
    }

    public function getPayerBlz(): ?string {
        return $this->payerBlz;
    }

    public function getPayerAccount(): ?string {
        return $this->payerAccount;
    }

    public function getPayerName(): string {
        return trim(($this->payerName1 ?? '') . ' ' . ($this->payerName2 ?? ''));
    }

    public function getPayerName1(): ?string {
        return $this->payerName1;
    }

    public function getPayerName2(): ?string {
        return $this->payerName2;
    }

    public function getTextKeyExt(): ?string {
        return $this->textKeyExt;
    }

    public function getRawText(): ?string {
        return $this->rawText;
    }

    /**
     * Gibt den vollständigen Verwendungszweck als String zurück.
     */
    public function getPurposeText(): string {
        $parts = $this->purposeLines;
        if (!empty($this->rawText)) {
            $parts[] = $this->rawText;
        }
        return trim(implode(' ', $parts));
    }

    /**
     * Gibt alle Informationen als lesbaren Text zurück.
     */
    public function getFullText(): string {
        $parts = [];

        if (!empty($this->bookingText)) {
            $parts[] = $this->bookingText;
        }

        $purpose = $this->getPurposeText();
        if (!empty($purpose)) {
            $parts[] = $purpose;
        }

        $payerName = $this->getPayerName();
        if (!empty($payerName)) {
            $parts[] = $payerName;
        }

        return trim(implode(' ', $parts));
    }

    /**
     * Konvertiert das Purpose-Objekt in MT940 :86: Format-Zeilen.
     */
    public function toMt940Lines(): array {
        $lines = [];

        // Erste Zeile: GVC-Code + Buchungstext oder Raw-Text
        $firstLine = $this->gvcCode ?? '';
        if (!empty($this->bookingText)) {
            $firstLine .= $this->bookingText;
        } elseif (!empty($this->rawText)) {
            // Raw-Text in 27-Zeichen-Segmente aufteilen
            $segments = str_split($this->rawText, 27);
            $firstLine .= array_shift($segments) ?? '';
            $lines[] = ':86:' . $firstLine;

            // Rest als Verwendungszweck-Zeilen
            $fieldKey = 20;
            foreach ($segments as $segment) {
                if ($fieldKey > 29 && $fieldKey < 60) {
                    $fieldKey = 60;
                }
                if ($fieldKey > 63) {
                    break;
                }
                $lines[] = sprintf('?%02d%s', $fieldKey++, $segment);
            }
            return $lines;
        }

        $lines[] = ':86:' . $firstLine;

        // Primanoten-Nr.
        if (!empty($this->primanotenNr)) {
            $lines[] = '?10' . $this->primanotenNr;
        }

        // Verwendungszweck-Zeilen
        $fieldKey = 20;
        foreach ($this->purposeLines as $purposeLine) {
            if ($fieldKey > 29 && $fieldKey < 60) {
                $fieldKey = 60;
            }
            if ($fieldKey > 63) {
                break;
            }
            $lines[] = sprintf('?%02d%s', $fieldKey++, $purposeLine);
        }

        // Zahlungspartner-Daten
        if (!empty($this->payerBlz)) {
            $lines[] = '?30' . $this->payerBlz;
        }
        if (!empty($this->payerAccount)) {
            $lines[] = '?31' . $this->payerAccount;
        }
        if (!empty($this->payerName1)) {
            $lines[] = '?32' . $this->payerName1;
        }
        if (!empty($this->payerName2)) {
            $lines[] = '?33' . $this->payerName2;
        }
        if (!empty($this->textKeyExt)) {
            $lines[] = '?34' . $this->textKeyExt;
        }

        return $lines;
    }

    public function __toString(): string {
        return $this->getFullText();
    }
}
