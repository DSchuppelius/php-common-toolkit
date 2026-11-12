<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : OutlookMessageParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Entities\CompoundFile\DirectoryEntry;
use CommonToolkit\Entities\Mail\{Address, Attachment, Message, Recipient};
use CommonToolkit\Enums\Mail\RecipientType;
use CommonToolkit\Exceptions\Parsers\{DocumentLimitExceededException, MalformedDocumentException};
use CommonToolkit\Helper\Data\StringHelper;
use CommonToolkit\Helper\FileSystem\File;
use DateTimeImmutable;
use DateTimeZone;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use Throwable;

/**
 * Liest Outlook-Nachrichten (.msg, MS-OXMSG) in eine formatneutrale
 * {@see Message} — reines PHP auf Basis von {@see CompoundFileReader}.
 *
 * Gelesen werden: Absender (SMTP-Adresse bevorzugt, auch wenn der Absender
 * als Exchange-Adresse gespeichert ist — aus PR_SENT_REPRESENTING_… bzw.
 * PR_SENDER_…, notfalls aus der From-Kopfzeile), Empfänger An/Cc/Bcc, Datum
 * (Sendezeit, sonst Date-Kopfzeile, sonst Zustellzeit; UTC), Betreff,
 * Message-ID, In-Reply-To, References, Kopfzeilen der Zustellung, Text- und
 * HTML-Körper getrennt (HTML auch binär gespeichert, mit Codepage) und
 * Anhänge (langer vor kurzem Dateinamen, MIME-Typ, Inhalt, Content-ID,
 * inline ja/nein). Eingebettete Nachrichten werden rekursiv gelesen und
 * stehen als Anhang mit {@see Attachment::getMessage()} bereit.
 *
 * Zeichensätze: Unicode-Eigenschaften (001F) sind UTF-16LE; 8-Bit-Eigenschaften
 * (001E) nutzen PR_MESSAGE_CODEPAGE, sonst PR_INTERNET_CPID, sonst
 * Windows-1252; der binäre HTML-Körper nutzt PR_INTERNET_CPID zuerst.
 * Eingebettete Nachrichten erben die Codepages, wenn sie keine eigenen nennen.
 *
 * Nicht gewandelt wird ein RTF-komprimierter Körper (PR_RTF_COMPRESSED); ob es
 * ihn gibt, sagt {@see Message::hasRtfBody()}. Anhänge ohne Inhalt in der
 * Datei (Verweise auf Pfade, OLE-Objekte) werden übersprungen.
 *
 * Grenzen (jeweils Parameter mit Vorgabe): Dateigröße, Anzahl der Anhänge
 * über alle Ebenen, Gesamtgröße der Anhänge (Daten und eingebettete
 * Nachrichten, über alle Ebenen) und Verschachtelungstiefe eingebetteter
 * Nachrichten. Überschreitung wirft {@see DocumentLimitExceededException};
 * eine beschädigte Datei {@see MalformedDocumentException}.
 *
 * @example
 * ```php
 * $message = OutlookMessageParser::fromFile('anfrage.msg');
 * $message->getFrom()?->getAddress();   // "max@example.com"
 * foreach ($message->getAttachments() as $attachment) {
 *     $attachment->isMessage() ? $attachment->getMessage() : $attachment->getContent();
 * }
 * ```
 */
final class OutlookMessageParser {
    use ErrorLog;

    /** Vorgabe für die größte angenommene Datei (50 MiB). */
    public const DEFAULT_MAX_FILE_BYTES = 50 * 1024 * 1024;

    /** Vorgabe für die Anzahl der Anhänge über alle Ebenen. */
    public const DEFAULT_MAX_ATTACHMENTS = 100;

    /** Vorgabe für die Gesamtgröße der Anhänge über alle Ebenen (50 MiB). */
    public const DEFAULT_MAX_ATTACHMENT_BYTES = 50 * 1024 * 1024;

    /** Vorgabe für die Tiefe eingebetteter Nachrichten (Nachricht in Nachricht in Nachricht). */
    public const DEFAULT_MAX_DEPTH = 2;

    private const PROPERTIES_STREAM = '__properties_version1.0';

    private const RECIPIENT_PREFIX = '__RECIP_VERSION1.0_#';

    private const ATTACHMENT_PREFIX = '__ATTACH_VERSION1.0_#';

    /** Kopf des Eigenschaften-Streams: Nachricht oben, eingebettete Nachricht, Empfänger/Anhang. */
    private const HEADER_TOP_LEVEL = 32;

    private const HEADER_EMBEDDED = 24;

    private const HEADER_OBJECT = 8;

    private const PROPERTY_ENTRY_SIZE = 16;

    // Eigenschaftstypen (MS-OXCDATA)
    private const TYPE_INTEGER32 = 0x0003;

    private const TYPE_BOOLEAN = 0x000B;

    private const TYPE_OBJECT = 0x000D;

    private const TYPE_STRING8 = 0x001E;

    private const TYPE_UNICODE = 0x001F;

    private const TYPE_TIME = 0x0040;

    private const TYPE_BINARY = 0x0102;

    // Eigenschaften (MS-OXPROPS)
    private const PID_SUBJECT = 0x0037;

    private const PID_CLIENT_SUBMIT_TIME = 0x0039;

    private const PID_SUBJECT_PREFIX = 0x003D;

    private const PID_SENT_REPRESENTING_NAME = 0x0042;

    private const PID_SENT_REPRESENTING_ADDRTYPE = 0x0064;

    private const PID_SENT_REPRESENTING_EMAIL = 0x0065;

    private const PID_TRANSPORT_MESSAGE_HEADERS = 0x007D;

    private const PID_RECIPIENT_TYPE = 0x0C15;

    private const PID_SENDER_NAME = 0x0C1A;

    private const PID_SENDER_ADDRTYPE = 0x0C1E;

    private const PID_SENDER_EMAIL = 0x0C1F;

    private const PID_MESSAGE_DELIVERY_TIME = 0x0E06;

    private const PID_NORMALIZED_SUBJECT = 0x0E1D;

    private const PID_ATTACH_SIZE = 0x0E20;

    private const PID_BODY = 0x1000;

    private const PID_RTF_COMPRESSED = 0x1009;

    private const PID_HTML = 0x1013;

    private const PID_INTERNET_MESSAGE_ID = 0x1035;

    private const PID_INTERNET_REFERENCES = 0x1039;

    private const PID_IN_REPLY_TO = 0x1042;

    private const PID_DISPLAY_NAME = 0x3001;

    private const PID_ADDRTYPE = 0x3002;

    private const PID_EMAIL_ADDRESS = 0x3003;

    private const PID_ATTACH_DATA = 0x3701;

    private const PID_ATTACH_EXTENSION = 0x3703;

    private const PID_ATTACH_FILENAME = 0x3704;

    private const PID_ATTACH_METHOD = 0x3705;

    private const PID_ATTACH_LONG_FILENAME = 0x3707;

    private const PID_ATTACH_MIME_TAG = 0x370E;

    private const PID_ATTACH_CONTENT_ID = 0x3712;

    private const PID_ATTACH_FLAGS = 0x3714;

    private const PID_SMTP_ADDRESS = 0x39FE;

    private const PID_INTERNET_CPID = 0x3FDE;

    private const PID_MESSAGE_CODEPAGE = 0x3FFD;

    private const PID_SENDER_SMTP_ADDRESS = 0x5D01;

    private const PID_SENT_REPRESENTING_SMTP_ADDRESS = 0x5D02;

    private const PID_RECIPIENT_DISPLAY_NAME = 0x5FF6;

    private const PID_ATTACHMENT_HIDDEN = 0x7FFE;

    private const ATTACH_EMBEDDED_MESSAGE = 5;

    /** PR_ATTACH_FLAGS: im HTML-Körper per Content-ID/-Location eingebunden. */
    private const ATTACH_FLAG_MHTML_REFERENCE = 0x4;

    /** Sekunden zwischen 1601-01-01 (FILETIME) und 1970-01-01 (Unix). */
    private const FILETIME_UNIX_OFFSET = 11644473600;

    private const DEFAULT_CODEPAGE_ENCODING = 'Windows-1252';

    private int $attachmentCount = 0;

    private int $attachmentBytes = 0;

    private function __construct(
        private readonly CompoundFileReader $reader,
        private readonly int $maxAttachments,
        private readonly int $maxAttachmentBytes,
        private readonly int $maxDepth,
        private readonly ?string $documentName,
    ) {}

    // ========================================================================
    // Einstieg
    // ========================================================================

    /**
     * Liest eine .msg-Datei. Die Dateigröße wird vor dem Lesen geprüft.
     *
     * @param int $maxFileBytes       Größte angenommene Datei (> 0).
     * @param int $maxAttachments     Höchstzahl der Anhänge über alle Ebenen (>= 0).
     * @param int $maxAttachmentBytes Höchste Gesamtgröße der Anhänge über alle Ebenen (>= 0).
     * @param int $maxDepth           Höchste Tiefe eingebetteter Nachrichten (>= 0; 0 = keine erlaubt).
     * @throws InvalidArgumentException       Bei ungültigen Grenzen.
     * @throws DocumentLimitExceededException Bei Überschreitung einer Grenze ({@see DocumentLimitExceededException::getKind()}).
     * @throws MalformedDocumentException     Bei beschädigter Datei oder wenn sie keine Outlook-Nachricht ist.
     * @throws \ERRORToolkit\Exceptions\FileSystem\FileNotFoundException Wenn die Datei fehlt.
     */
    public static function fromFile(
        string $path,
        int $maxFileBytes = self::DEFAULT_MAX_FILE_BYTES,
        int $maxAttachments = self::DEFAULT_MAX_ATTACHMENTS,
        int $maxAttachmentBytes = self::DEFAULT_MAX_ATTACHMENT_BYTES,
        int $maxDepth = self::DEFAULT_MAX_DEPTH,
    ): Message {
        self::assertLimits($maxFileBytes, $maxAttachments, $maxAttachmentBytes, $maxDepth);

        return (new self(CompoundFileReader::fromFile($path, $maxFileBytes), $maxAttachments, $maxAttachmentBytes, $maxDepth, basename($path)))->readTopLevel();
    }

    /**
     * Wie {@see fromFile()}, aber aus Bytes (Upload, Mail-Anhang, Datenbank).
     *
     * @param string|null $documentName Name für Meldungen und Exceptions (nie ein Serverpfad).
     * @throws InvalidArgumentException       Bei ungültigen Grenzen.
     * @throws DocumentLimitExceededException Bei Überschreitung einer Grenze.
     * @throws MalformedDocumentException     Bei beschädigter Eingabe oder wenn sie keine Outlook-Nachricht ist.
     */
    public static function fromString(
        string $bytes,
        int $maxFileBytes = self::DEFAULT_MAX_FILE_BYTES,
        int $maxAttachments = self::DEFAULT_MAX_ATTACHMENTS,
        int $maxAttachmentBytes = self::DEFAULT_MAX_ATTACHMENT_BYTES,
        int $maxDepth = self::DEFAULT_MAX_DEPTH,
        ?string $documentName = null,
    ): Message {
        self::assertLimits($maxFileBytes, $maxAttachments, $maxAttachmentBytes, $maxDepth);

        return (new self(CompoundFileReader::fromString($bytes, $documentName, $maxFileBytes), $maxAttachments, $maxAttachmentBytes, $maxDepth, $documentName))->readTopLevel();
    }

    // ========================================================================
    // Nachricht
    // ========================================================================

    private function readTopLevel(): Message {
        return $this->readMessage($this->reader->getRoot(), 0, null, null);
    }

    /**
     * @param int      $depth             0 = Datei, 1 = eingebettet, …
     * @param int|null $inheritedCodepage PR_MESSAGE_CODEPAGE der umgebenden Nachricht
     * @param int|null $inheritedCpid     PR_INTERNET_CPID der umgebenden Nachricht
     */
    private function readMessage(DirectoryEntry $storage, int $depth, ?int $inheritedCodepage, ?int $inheritedCpid): Message {
        $properties = $this->readProperties($storage, $depth === 0 ? self::HEADER_TOP_LEVEL : self::HEADER_EMBEDDED, $depth, $depth === 0);
        $codepage = self::integer($properties, self::PID_MESSAGE_CODEPAGE) ?? $inheritedCodepage;
        $cpid = self::integer($properties, self::PID_INTERNET_CPID) ?? $inheritedCpid;
        $encoding = self::encodingOf($codepage ?? $cpid);
        $bodyEncoding = self::encodingOf($cpid ?? $codepage);

        $string = fn (int $id): ?string => $this->readString($storage, $id, $depth, $encoding);

        $headers = self::nonBlank($string(self::PID_TRANSPORT_MESSAGE_HEADERS));
        $subject = $string(self::PID_SUBJECT)
            ?? (($string(self::PID_SUBJECT_PREFIX) ?? '') . ($string(self::PID_NORMALIZED_SUBJECT) ?? ''));
        $htmlBody = $this->readHtml($storage, $depth, $bodyEncoding);

        return new Message(
            subject: $subject,
            from: $this->readSender($string, $headers),
            recipients: $this->readRecipients($storage, $depth, $encoding),
            date: self::time($properties, self::PID_CLIENT_SUBMIT_TIME)
                ?? self::headerDate($headers)
                ?? self::time($properties, self::PID_MESSAGE_DELIVERY_TIME),
            messageId: self::nonBlank($string(self::PID_INTERNET_MESSAGE_ID)) ?? self::header($headers, 'Message-ID'),
            inReplyTo: self::nonBlank($string(self::PID_IN_REPLY_TO)) ?? self::header($headers, 'In-Reply-To'),
            references: self::messageIds(self::nonBlank($string(self::PID_INTERNET_REFERENCES)) ?? self::header($headers, 'References')),
            headers: $headers,
            textBody: self::nonEmpty($string(self::PID_BODY)),
            htmlBody: $htmlBody,
            rtfBody: $this->stream($storage, self::PID_RTF_COMPRESSED, self::TYPE_BINARY) !== null,
            attachments: $this->readAttachments($storage, $depth, $codepage, $cpid, $encoding, $htmlBody),
        );
    }

    /**
     * Absender: SMTP-Adresse der vertretenen Person (From), sonst die des
     * tatsächlichen Absenders, wenn es dieselbe Person ist, sonst aus der
     * From-Kopfzeile, zuletzt die gespeicherte Adresse wie sie ist.
     *
     * @param callable(int): ?string $string
     */
    private function readSender(callable $string, ?string $headers): ?Address {
        $representingName = self::nonBlank($string(self::PID_SENT_REPRESENTING_NAME));
        [$representingSmtp, $representingRaw] = self::addressCandidates(
            $string(self::PID_SENT_REPRESENTING_SMTP_ADDRESS),
            $string(self::PID_SENT_REPRESENTING_ADDRTYPE),
            $string(self::PID_SENT_REPRESENTING_EMAIL),
        );
        $senderName = self::nonBlank($string(self::PID_SENDER_NAME));
        [$senderSmtp, $senderRaw] = self::addressCandidates(
            $string(self::PID_SENDER_SMTP_ADDRESS),
            $string(self::PID_SENDER_ADDRTYPE),
            $string(self::PID_SENDER_EMAIL),
        );

        $samePerson = ($representingName === null && $representingRaw === null && $representingSmtp === null)
            || ($representingRaw !== null && $senderRaw !== null && strcasecmp($representingRaw, $senderRaw) === 0)
            || ($representingName !== null && $senderName !== null && mb_strtolower($representingName) === mb_strtolower($senderName));

        $name = $representingName ?? ($samePerson ? $senderName : null);
        $address = $representingSmtp
            ?? ($samePerson ? $senderSmtp : null)
            ?? self::headerAddress($headers, 'From')
            ?? $representingRaw
            ?? ($samePerson ? $senderRaw : null);

        if ($name === null && $address === null) {
            return null;
        }

        return new Address($name ?? '', $address ?? '');
    }

    /** @return list<Recipient> */
    private function readRecipients(DirectoryEntry $storage, int $depth, string $encoding): array {
        $recipients = [];
        foreach ($this->objectStorages($storage, self::RECIPIENT_PREFIX) as $recipientStorage) {
            $properties = $this->readProperties($recipientStorage, self::HEADER_OBJECT, $depth, false);
            $string = fn (int $id): ?string => $this->readString($recipientStorage, $id, $depth, $encoding);

            [$smtp, $raw] = self::addressCandidates($string(self::PID_SMTP_ADDRESS), $string(self::PID_ADDRTYPE), $string(self::PID_EMAIL_ADDRESS));
            $type = match ((self::integer($properties, self::PID_RECIPIENT_TYPE) ?? 1) & 0xFF) {
                2 => RecipientType::Cc,
                3 => RecipientType::Bcc,
                default => RecipientType::To,
            };

            $recipients[] = new Recipient(
                self::nonBlank($string(self::PID_DISPLAY_NAME)) ?? self::nonBlank($string(self::PID_RECIPIENT_DISPLAY_NAME)) ?? '',
                $smtp ?? $raw ?? '',
                $type,
            );
        }

        return $recipients;
    }

    // ========================================================================
    // Anhänge
    // ========================================================================

    /** @return list<Attachment> */
    private function readAttachments(DirectoryEntry $storage, int $depth, ?int $codepage, ?int $cpid, string $encoding, ?string $htmlBody): array {
        $attachments = [];
        foreach ($this->objectStorages($storage, self::ATTACHMENT_PREFIX) as $attachmentStorage) {
            $this->attachmentCount++;
            if ($this->attachmentCount > $this->maxAttachments) {
                $this->limitExceeded(
                    DocumentLimitExceededException::KIND_ENTRIES,
                    $this->maxAttachments,
                    $this->attachmentCount,
                    sprintf('Nachricht hat mehr als %d Anhänge.', $this->maxAttachments),
                );
            }

            $attachment = $this->readAttachment($attachmentStorage, $depth, $codepage, $cpid, $encoding, $htmlBody);
            if ($attachment !== null) {
                $attachments[] = $attachment;
            }
        }

        return $attachments;
    }

    private function readAttachment(DirectoryEntry $storage, int $depth, ?int $codepage, ?int $cpid, string $encoding, ?string $htmlBody): ?Attachment {
        $properties = $this->readProperties($storage, self::HEADER_OBJECT, $depth, false);
        $string = fn (int $id): ?string => self::nonBlank($this->readString($storage, $id, $depth, $encoding));

        $method = self::integer($properties, self::PID_ATTACH_METHOD);
        $filename = $string(self::PID_ATTACH_LONG_FILENAME) ?? $string(self::PID_ATTACH_FILENAME) ?? $string(self::PID_DISPLAY_NAME) ?? '';
        $mimeType = $string(self::PID_ATTACH_MIME_TAG);
        $contentId = self::nonBlank(trim((string) $string(self::PID_ATTACH_CONTENT_ID), '<> '));
        $inline = (self::boolean($properties, self::PID_ATTACHMENT_HIDDEN) ?? false)
            || ((self::integer($properties, self::PID_ATTACH_FLAGS) ?? 0) & self::ATTACH_FLAG_MHTML_REFERENCE) !== 0
            || ($contentId !== null && $htmlBody !== null && str_contains($htmlBody, 'cid:' . $contentId));

        $embedded = $this->reader->getChild($storage, self::substgName(self::PID_ATTACH_DATA, self::TYPE_OBJECT));
        if ($method === self::ATTACH_EMBEDDED_MESSAGE || ($method === null && $embedded?->isStorage() === true)) {
            if ($embedded === null || !$embedded->isStorage()) {
                self::logWarning('Outlook-Nachricht: eingebettete Nachricht ohne Inhalt übersprungen.');

                return null;
            }
            if ($depth + 1 > $this->maxDepth) {
                $this->limitExceeded(
                    DocumentLimitExceededException::KIND_DEPTH,
                    $this->maxDepth,
                    $depth + 1,
                    sprintf('Eingebettete Nachrichten sind tiefer als %d Ebenen verschachtelt.', $this->maxDepth),
                );
            }

            return new Attachment(
                filename: $filename,
                mimeType: $mimeType,
                content: '',
                size: self::integer($properties, self::PID_ATTACH_SIZE) ?? 0,
                contentId: $contentId,
                inline: $inline,
                message: $this->readMessage($embedded, $depth + 1, $codepage, $cpid),
            );
        }

        $data = $this->stream($storage, self::PID_ATTACH_DATA, self::TYPE_BINARY);
        if ($data === null) {
            self::logDebug('Outlook-Nachricht: Anhang ohne Daten in der Datei übersprungen (Methode ' . ($method ?? 'unbekannt') . ').');

            return null;
        }
        $content = $this->read($data, $depth, true);

        return new Attachment(
            filename: $filename,
            mimeType: $mimeType ?? self::mimeTypeOf(File::extension($filename)) ?? self::mimeTypeOf($string(self::PID_ATTACH_EXTENSION) ?? ''),
            content: $content,
            size: strlen($content),
            contentId: $contentId,
            inline: $inline,
        );
    }

    // ========================================================================
    // Eigenschaften und Streams
    // ========================================================================

    /**
     * Eigenschaften fester Länge aus `__properties_version1.0`, nach Tag
     * (ID << 16 | Typ) mit ihrem 8-Byte-Wert.
     *
     * @return array<int, string>
     */
    private function readProperties(DirectoryEntry $storage, int $headerSize, int $depth, bool $required): array {
        $stream = $this->reader->getChild($storage, self::PROPERTIES_STREAM);
        if ($stream === null || !$stream->isStream()) {
            if ($required) {
                $this->malformed('keine Outlook-Nachricht (__properties_version1.0 fehlt).');
            }

            return [];
        }

        $data = $this->read($stream, $depth);
        $length = strlen($data);
        if ($length < $headerSize && $required) {
            $this->malformed("Eigenschaften-Stream ist $length Byte lang, sein Kopf allein hat $headerSize.");
        }

        $properties = [];
        for ($offset = $headerSize; $offset + self::PROPERTY_ENTRY_SIZE <= $length; $offset += self::PROPERTY_ENTRY_SIZE) {
            $properties[self::uint32($data, $offset)] = substr($data, $offset + 8, 8);
        }

        return $properties;
    }

    /** Zeichenkette: Unicode-Variante bevorzugt, sonst 8 Bit in $encoding. */
    private function readString(DirectoryEntry $storage, int $id, int $depth, string $encoding): ?string {
        $unicode = $this->stream($storage, $id, self::TYPE_UNICODE);
        if ($unicode !== null) {
            return self::decode($this->read($unicode, $depth), 'UTF-16LE');
        }
        $ansi = $this->stream($storage, $id, self::TYPE_STRING8);

        return $ansi === null ? null : self::decode($this->read($ansi, $depth), $encoding);
    }

    /** HTML-Körper: binär (in der Codepage des Körpers) oder als Zeichenkette. */
    private function readHtml(DirectoryEntry $storage, int $depth, string $bodyEncoding): ?string {
        $binary = $this->stream($storage, self::PID_HTML, self::TYPE_BINARY);
        if ($binary === null) {
            return self::nonEmpty($this->readString($storage, self::PID_HTML, $depth, $bodyEncoding));
        }

        $data = rtrim($this->read($binary, $depth), "\0");
        if (str_starts_with($data, "\xEF\xBB\xBF")) {
            return self::nonEmpty(mb_scrub(substr($data, 3), 'UTF-8'));
        }

        return self::nonEmpty(mb_scrub(StringHelper::convertToUtf8($data, $bodyEncoding), 'UTF-8'));
    }

    private function stream(DirectoryEntry $storage, int $id, int $type): ?DirectoryEntry {
        $entry = $this->reader->getChild($storage, self::substgName($id, $type));

        return $entry !== null && $entry->isStream() ? $entry : null;
    }

    /**
     * Stream lesen. Alles unterhalb einer eingebetteten Nachricht und alle
     * Anhangsdaten zählen gegen die Gesamtgröße der Anhänge — geprüft vor dem
     * Lesen anhand der Verzeichnisgröße.
     */
    private function read(DirectoryEntry $entry, int $depth, bool $attachmentData = false): string {
        if ($depth > 0 || $attachmentData) {
            $total = $this->attachmentBytes + $entry->getSize();
            if ($total > $this->maxAttachmentBytes) {
                $this->limitExceeded(
                    DocumentLimitExceededException::KIND_BYTES,
                    $this->maxAttachmentBytes,
                    $total,
                    sprintf('Anhänge sind zusammen größer als %d Byte.', $this->maxAttachmentBytes),
                );
            }
            $this->attachmentBytes = $total;
        }

        return $this->reader->readStream($entry);
    }

    /**
     * Unterspeicher für Empfänger oder Anhänge, nach Nummer sortiert.
     *
     * @return list<DirectoryEntry>
     */
    private function objectStorages(DirectoryEntry $storage, string $prefix): array {
        $storages = array_values(array_filter(
            $this->reader->getChildren($storage),
            static fn (DirectoryEntry $child): bool => $child->isStorage() && str_starts_with(strtoupper($child->getName()), $prefix),
        ));
        usort($storages, static fn (DirectoryEntry $a, DirectoryEntry $b): int => strcmp(strtoupper($a->getName()), strtoupper($b->getName())));

        return $storages;
    }

    // ========================================================================
    // Werte
    // ========================================================================

    /** @param array<int, string> $properties */
    private static function integer(array $properties, int $id): ?int {
        $raw = $properties[($id << 16) | self::TYPE_INTEGER32] ?? null;

        return $raw === null ? null : self::uint32($raw, 0);
    }

    /** @param array<int, string> $properties */
    private static function boolean(array $properties, int $id): ?bool {
        $raw = $properties[($id << 16) | self::TYPE_BOOLEAN] ?? null;

        return $raw === null ? null : ($raw[0] !== "\0" || $raw[1] !== "\0");
    }

    /**
     * FILETIME (100-ns-Schritte seit 1601-01-01 UTC) als UTC-Zeitpunkt, sekundengenau.
     *
     * @param array<int, string> $properties
     */
    private static function time(array $properties, int $id): ?DateTimeImmutable {
        $raw = $properties[($id << 16) | self::TYPE_TIME] ?? null;
        if ($raw === null) {
            return null;
        }
        $low = self::uint32($raw, 0);
        $high = self::uint32($raw, 4);
        if (($low === 0 && $high === 0) || $high >= 0x80000000) {
            return null;
        }

        $seconds = intdiv($high * 0x100000000 + $low, 10_000_000) - self::FILETIME_UNIX_OFFSET;
        try {
            return (new DateTimeImmutable('@' . $seconds))->setTimezone(new DateTimeZone('UTC'));
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * SMTP-Adresse und gespeicherte Adresse: die SMTP-Eigenschaft, sonst die
     * Adresse, wenn ihr Typ SMTP ist (oder ohne Typ wie eine aussieht).
     *
     * @return array{0: ?string, 1: ?string}
     */
    private static function addressCandidates(?string $smtpAddress, ?string $addressType, ?string $address): array {
        $smtpAddress = self::nonBlank($smtpAddress);
        $address = self::nonBlank($address);
        $addressType = strtoupper((string) self::nonBlank($addressType));

        if ($smtpAddress === null && $address !== null) {
            $looksSmtp = $addressType === 'SMTP'
                || ($addressType === '' && str_contains($address, '@') && !str_starts_with($address, '/'));
            if ($looksSmtp) {
                $smtpAddress = $address;
            }
        }

        return [$smtpAddress, $address];
    }

    /** Eigenschaftsdaten nach UTF-8; endet am ersten NUL (Abschluss der Zeichenkette). */
    private static function decode(string $data, string $encoding): string {
        if ($encoding === 'UTF-16LE') {
            $data = substr($data, 0, strlen($data) - strlen($data) % 2);
            $end = 0;
            while (($end = strpos($data, "\0\0", $end)) !== false && $end % 2 !== 0) {
                $end++;
            }
            if ($end !== false) {
                $data = substr($data, 0, $end);
            }
        } else {
            $end = strpos($data, "\0");
            if ($end !== false) {
                $data = substr($data, 0, $end);
            }
        }

        return mb_scrub(StringHelper::convertToUtf8($data, $encoding), 'UTF-8');
    }

    private static function encodingOf(?int $codepage): string {
        return ($codepage !== null ? StringHelper::encodingForCodepage($codepage) : null) ?? self::DEFAULT_CODEPAGE_ENCODING;
    }

    private static function mimeTypeOf(string $extension): ?string {
        return trim($extension, '. ') === '' ? null : File::mimeTypeForExtension($extension);
    }

    // ========================================================================
    // Kopfzeilen der Zustellung (Rückfall, wenn MAPI-Eigenschaften fehlen)
    // ========================================================================

    /** Erste Kopfzeile dieses Namens, entfaltet und getrimmt. */
    private static function header(?string $headers, string $name): ?string {
        if ($headers === null) {
            return null;
        }
        $section = preg_split('/\r?\n\r?\n/', $headers, 2)[0] ?? $headers;
        $unfolded = preg_replace('/\r?\n[ \t]+/', ' ', $section) ?? $section;
        if (preg_match('/^' . preg_quote($name, '/') . '[ \t]*:(.*)$/mi', $unfolded, $match) !== 1) {
            return null;
        }

        return self::nonBlank($match[1]);
    }

    private static function headerDate(?string $headers): ?DateTimeImmutable {
        $value = self::header($headers, 'Date');
        if ($value === null) {
            return null;
        }
        try {
            return (new DateTimeImmutable(preg_replace('/\([^)]*\)/', '', $value) ?? $value))->setTimezone(new DateTimeZone('UTC'));
        } catch (Throwable) {
            return null;
        }
    }

    private static function headerAddress(?string $headers, string $name): ?string {
        $value = self::header($headers, $name);
        if ($value === null) {
            return null;
        }
        if (preg_match('/<([^<>\s]+@[^<>\s]+)>/', $value, $match) === 1 || preg_match('/([^\s<>"(),;:]+@[^\s<>"(),;:]+)/', $value, $match) === 1) {
            return $match[1];
        }

        return null;
    }

    /** @return list<string> */
    private static function messageIds(?string $value): array {
        if ($value === null) {
            return [];
        }
        if (preg_match_all('/<[^<>\s]+>/', $value, $matches) > 0) {
            return $matches[0];
        }

        return preg_split('/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    // ========================================================================
    // Hilfen
    // ========================================================================

    /** Getrimmter Wert, null wenn leer. */
    private static function nonBlank(?string $value): ?string {
        $value = $value === null ? '' : trim($value);

        return $value === '' ? null : $value;
    }

    /** Ungekürzter Wert, null wenn leer (Körper behalten ihren Leerraum). */
    private static function nonEmpty(?string $value): ?string {
        return $value === null || $value === '' ? null : $value;
    }

    private static function substgName(int $id, int $type): string {
        return sprintf('__substg1.0_%04X%04X', $id, $type);
    }

    private static function uint32(string $data, int $offset): int {
        return ord($data[$offset]) | (ord($data[$offset + 1]) << 8) | (ord($data[$offset + 2]) << 16) | (ord($data[$offset + 3]) << 24);
    }

    /** @throws InvalidArgumentException */
    private static function assertLimits(int $maxFileBytes, int $maxAttachments, int $maxAttachmentBytes, int $maxDepth): void {
        if ($maxFileBytes <= 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "maxFileBytes muss > 0 sein, erhalten: $maxFileBytes");
        }
        if ($maxAttachments < 0 || $maxAttachmentBytes < 0 || $maxDepth < 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Grenzen dürfen nicht negativ sein (maxAttachments $maxAttachments, maxAttachmentBytes $maxAttachmentBytes, maxDepth $maxDepth).");
        }
    }

    /** @throws DocumentLimitExceededException */
    private function limitExceeded(string $kind, int $limit, int $actual, string $message): never {
        $text = 'Outlook-Nachricht' . ($this->documentName !== null ? ' ' . $this->documentName : '') . ': ' . $message;
        self::logError($text);

        throw new DocumentLimitExceededException($text, $kind, $limit, $actual, $this->documentName);
    }

    /** @throws MalformedDocumentException */
    private function malformed(string $message): never {
        $text = 'Outlook-Nachricht' . ($this->documentName !== null ? ' ' . $this->documentName : '') . ': ' . $message;
        self::logError($text);

        throw new MalformedDocumentException($text, $this->documentName);
    }
}
