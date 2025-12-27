<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ApplicationHeader.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Swift;

use CommonToolkit\Enums\Common\Banking\MtType;
use DateTimeImmutable;

/**
 * SWIFT FIN Application Header Block (Block 2)
 * 
 * Format Input:  {2:I940BANKDEFFXXXXN}
 * Format Output: {2:O9401200250101BANKDEFF4815162342250101121500N}
 * 
 * Input Header:
 * - I = Input
 * - 940 = Message Type
 * - BANKDEFFXXXX = Receiver BIC (12 chars)
 * - N = Priority (N=Normal, U=Urgent, S=System)
 * 
 * Output Header:
 * - O = Output
 * - 940 = Message Type
 * - 1200 = Input Time (HHMM)
 * - 250101 = Input Date (YYMMDD)
 * - MIR (Message Input Reference): BANKDEFF4815162342
 * - 250101 = Output Date (YYMMDD)
 * - 121500 = Output Time (HHMMSS)
 * - N = Priority
 */
final class ApplicationHeader {
    public function __construct(
        private readonly bool $isOutput,
        private readonly MtType $messageType,
        private readonly ?string $receiverBic = null,
        private readonly ?string $priority = null,
        private readonly ?string $inputTime = null,
        private readonly ?DateTimeImmutable $inputDate = null,
        private readonly ?string $messageInputReference = null,
        private readonly ?DateTimeImmutable $outputDate = null,
        private readonly ?string $outputTime = null,
        private readonly ?string $deliveryMonitor = null,
        private readonly ?string $obsolescencePeriod = null
    ) {
    }

    public function isOutput(): bool {
        return $this->isOutput;
    }

    public function isInput(): bool {
        return !$this->isOutput;
    }

    public function getMessageType(): MtType {
        return $this->messageType;
    }

    public function getReceiverBic(): ?string {
        return $this->receiverBic;
    }

    public function getPriority(): ?string {
        return $this->priority;
    }

    public function getPriorityDescription(): string {
        return match ($this->priority) {
            'N' => 'Normal',
            'U' => 'Urgent',
            'S' => 'System',
            default => 'Unknown'
        };
    }

    public function getInputTime(): ?string {
        return $this->inputTime;
    }

    public function getInputDate(): ?DateTimeImmutable {
        return $this->inputDate;
    }

    public function getMessageInputReference(): ?string {
        return $this->messageInputReference;
    }

    public function getOutputDate(): ?DateTimeImmutable {
        return $this->outputDate;
    }

    public function getOutputTime(): ?string {
        return $this->outputTime;
    }

    public function getDeliveryMonitor(): ?string {
        return $this->deliveryMonitor;
    }

    public function getObsolescencePeriod(): ?string {
        return $this->obsolescencePeriod;
    }

    /**
     * Gibt den vollständigen Block 2 String zurück
     */
    public function __toString(): string {
        if ($this->isOutput) {
            return '{2:O' . $this->messageType->getNumericType() .
                $this->inputTime .
                ($this->inputDate?->format('ymd') ?? '') .
                $this->messageInputReference .
                ($this->outputDate?->format('ymd') ?? '') .
                $this->outputTime .
                ($this->priority ?? 'N') . '}';
        }

        return '{2:I' . $this->messageType->getNumericType() .
            $this->receiverBic .
            ($this->priority ?? 'N') .
            ($this->deliveryMonitor ?? '') .
            ($this->obsolescencePeriod ?? '') . '}';
    }

    /**
     * Parst einen Block 2 String
     * 
     * @param string $raw Roher Block-Inhalt (ohne {2: und })
     */
    public static function parse(string $raw): self {
        $isOutput = str_starts_with($raw, 'O');
        $messageTypeCode = substr($raw, 1, 3);
        $messageType = MtType::fromNumeric((int)$messageTypeCode);

        if ($isOutput) {
            // Output Header: O940HHMM YYMMDD MIR(28) YYMMDD HHMMSS N
            $inputTime = substr($raw, 4, 4);
            $inputDateStr = substr($raw, 8, 6);
            $inputDate = DateTimeImmutable::createFromFormat('ymd', $inputDateStr) ?: null;

            // MIR ist 28 Zeichen lang (BIC 12 + Session 4 + Sequence 6 + Input Date 6)
            $mir = substr($raw, 14, 28);

            $outputDateStr = substr($raw, 42, 6);
            $outputDate = DateTimeImmutable::createFromFormat('ymd', $outputDateStr) ?: null;

            $outputTime = substr($raw, 48, 6);
            $priority = substr($raw, 54, 1) ?: 'N';

            return new self(
                isOutput: true,
                messageType: $messageType,
                priority: $priority,
                inputTime: $inputTime,
                inputDate: $inputDate,
                messageInputReference: $mir,
                outputDate: $outputDate,
                outputTime: $outputTime
            );
        }

        // Input Header: I940BANKDEFFXXXXN3003
        $receiverBic = substr($raw, 4, 12);
        $priority = substr($raw, 16, 1) ?: 'N';
        $deliveryMonitor = strlen($raw) > 17 ? substr($raw, 17, 1) : null;
        $obsolescencePeriod = strlen($raw) > 18 ? substr($raw, 18, 3) : null;

        return new self(
            isOutput: false,
            messageType: $messageType,
            receiverBic: $receiverBic,
            priority: $priority,
            deliveryMonitor: $deliveryMonitor,
            obsolescencePeriod: $obsolescencePeriod
        );
    }
}