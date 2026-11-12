<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Attachment.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Mail;

/**
 * Anhang einer Nachricht: entweder Daten (Datei, Bild) oder eine eingebettete
 * Nachricht ({@see isMessage()}), deren Inhalt bereits gelesen in
 * {@see getMessage()} steht. Unveränderlich.
 */
final class Attachment {
    public function __construct(
        private readonly string $filename,
        private readonly ?string $mimeType,
        private readonly string $content,
        private readonly int $size,
        private readonly ?string $contentId = null,
        private readonly bool $inline = false,
        private readonly ?Message $message = null,
    ) {}

    /** Dateiname wie angegeben (langer Name vor kurzem); '' wenn keiner bekannt ist. Nicht bereinigt. */
    public function getFilename(): string {
        return $this->filename;
    }

    /** MIME-Typ, falls die Quelle einen nennt oder er sich aus der Endung ergibt; sonst null. */
    public function getMimeType(): ?string {
        return $this->mimeType;
    }

    /** Rohbytes des Anhangs; bei eingebetteten Nachrichten ''. */
    public function getContent(): string {
        return $this->content;
    }

    /**
     * Größe in Byte: bei Daten die Länge des Inhalts, bei eingebetteten
     * Nachrichten die von der Quelle gemeldete Größe (0, wenn keine).
     */
    public function getSize(): int {
        return $this->size;
    }

    /** Content-ID ohne spitze Klammern (für "cid:"-Verweise im HTML), sonst null. */
    public function getContentId(): ?string {
        return $this->contentId;
    }

    /** Im Text eingebettet (Logo, Signaturbild) statt angehängt. */
    public function isInline(): bool {
        return $this->inline;
    }

    /** Eingebettete Nachricht statt Daten? */
    public function isMessage(): bool {
        return $this->message !== null;
    }

    /** Die eingebettete Nachricht, bereits gelesen; null bei Datenanhängen. */
    public function getMessage(): ?Message {
        return $this->message;
    }
}
