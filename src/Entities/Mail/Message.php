<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Message.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Mail;

use CommonToolkit\Enums\Mail\RecipientType;
use DateTimeImmutable;

/**
 * Gelesene E-Mail-Nachricht, unabhängig vom Quellformat: Kopfdaten,
 * Text- und HTML-Körper getrennt, Anhänge (auch eingebettete Nachrichten).
 * Unveränderlich; alle Texte sind UTF-8.
 *
 * Bewusst ohne Herkunftsdetails (MAPI-Eigenschaften, Codepages, Speicher),
 * damit ein Aufrufer Nachrichten aus verschiedenen Quellen gleich behandeln
 * kann. Erzeugt z. B. von {@see \CommonToolkit\Parsers\OutlookMessageParser}.
 */
final class Message {
    /**
     * @param list<Recipient>  $recipients  An, Cc und Bcc in Quellreihenfolge
     * @param list<string>     $references  Message-IDs der References-Kette
     * @param list<Attachment> $attachments
     */
    public function __construct(
        private readonly string $subject,
        private readonly ?Address $from = null,
        private readonly array $recipients = [],
        private readonly ?DateTimeImmutable $date = null,
        private readonly ?string $messageId = null,
        private readonly ?string $inReplyTo = null,
        private readonly array $references = [],
        private readonly ?string $headers = null,
        private readonly ?string $textBody = null,
        private readonly ?string $htmlBody = null,
        private readonly bool $rtfBody = false,
        private readonly array $attachments = [],
    ) {}

    /** Betreff, '' wenn keiner angegeben ist. */
    public function getSubject(): string {
        return $this->subject;
    }

    /** Absender (From); null wenn die Quelle keinen nennt (z. B. Entwurf). */
    public function getFrom(): ?Address {
        return $this->from;
    }

    /**
     * Alle Empfänger in Quellreihenfolge.
     *
     * @return list<Recipient>
     */
    public function getRecipients(): array {
        return $this->recipients;
    }

    /** @return list<Recipient> */
    public function getTo(): array {
        return $this->recipientsOf(RecipientType::To);
    }

    /** @return list<Recipient> */
    public function getCc(): array {
        return $this->recipientsOf(RecipientType::Cc);
    }

    /** @return list<Recipient> */
    public function getBcc(): array {
        return $this->recipientsOf(RecipientType::Bcc);
    }

    /** Datum in UTC (Sendezeit, sonst Zustellzeit); null wenn keines bekannt ist. */
    public function getDate(): ?DateTimeImmutable {
        return $this->date;
    }

    /** Message-ID wie in der Quelle (meist mit spitzen Klammern). */
    public function getMessageId(): ?string {
        return $this->messageId;
    }

    /** Message-ID der beantworteten Nachricht (In-Reply-To). */
    public function getInReplyTo(): ?string {
        return $this->inReplyTo;
    }

    /**
     * Message-IDs der References-Kette, älteste zuerst.
     *
     * @return list<string>
     */
    public function getReferences(): array {
        return $this->references;
    }

    /** Kopfzeilen der Zustellung (RFC 5322), falls die Quelle sie aufbewahrt. */
    public function getHeaders(): ?string {
        return $this->headers;
    }

    /** Textkörper; null wenn keiner vorliegt. */
    public function getTextBody(): ?string {
        return $this->textBody;
    }

    /** HTML-Körper; null wenn keiner vorliegt. */
    public function getHtmlBody(): ?string {
        return $this->htmlBody;
    }

    /**
     * Liegt (zusätzlich) ein RTF-Körper vor? Er wird nicht in Text gewandelt;
     * fehlen Text und HTML, ist die Nachricht nur als RTF gespeichert.
     */
    public function hasRtfBody(): bool {
        return $this->rtfBody;
    }

    /** @return list<Attachment> */
    public function getAttachments(): array {
        return $this->attachments;
    }

    /** @return list<Recipient> */
    private function recipientsOf(RecipientType $type): array {
        return array_values(array_filter($this->recipients, static fn (Recipient $recipient): bool => $recipient->getType() === $type));
    }
}
