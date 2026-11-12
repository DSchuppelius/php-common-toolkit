<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : OutlookMessageWriter.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

/**
 * Test-Hilfe: schreibt synthetische Outlook-Nachrichten (MS-OXMSG) aus einer
 * {@see MessageSpec} — Eigenschaften-Streams mit Kopf (32/24/8 Byte),
 * `__substg1.0_`-Streams, Empfänger- und Anhang-Speicher, eingebettete
 * Nachrichten und den (leeren) Speicher für benannte Eigenschaften.
 *
 * Zeichenketten werden als Unicode (001F, UTF-16LE) oder als 8-Bit (001E)
 * in der angegebenen Kodierung geschrieben.
 */
final class OutlookMessageWriter {
    private const KIND_TOP_LEVEL = 0;

    private const KIND_EMBEDDED = 1;

    private const KIND_OBJECT = 2;

    private const FILETIME_UNIX_OFFSET = 11644473600;

    private function __construct(
        private readonly bool $unicode,
        private readonly string $encoding,
    ) {}

    /** Nachricht als .msg-Bytes. */
    public static function build(MessageSpec $message, bool $unicode = true, string $encoding = 'Windows-1252', int $majorVersion = 3): string {
        return self::writer($message, $unicode, $encoding, $majorVersion)->build();
    }

    /** Der befüllte CFB-Schreiber, damit Tests nach dem Bauen das Layout kennen. */
    public static function writer(MessageSpec $message, bool $unicode = true, string $encoding = 'Windows-1252', int $majorVersion = 3): CompoundFileWriter {
        $cfb = new CompoundFileWriter($majorVersion);
        (new self($unicode, $encoding))->writeObject($cfb, '', $message, self::KIND_TOP_LEVEL);
        $cfb->addStream('__nameid_version1.0/__substg1.0_00020102', '');
        $cfb->addStream('__nameid_version1.0/__substg1.0_00030102', '');
        $cfb->addStream('__nameid_version1.0/__substg1.0_00040102', '');

        return $cfb;
    }

    private function writeObject(CompoundFileWriter $cfb, string $path, MessageSpec $spec, int $kind): void {
        $prefix = $path === '' ? '' : $path . '/';
        $entries = '';

        foreach ($spec->strings as $id => $value) {
            $type = $this->unicode ? 0x001F : 0x001E;
            $data = $this->unicode ? mb_convert_encoding($value, 'UTF-16LE', 'UTF-8') . "\0\0" : self::encode($value, $this->encoding) . "\0";
            $cfb->addStream($prefix . self::substg($id, $type), $data);
            $entries .= pack('VVVV', ($id << 16) | $type, 6, strlen($data), 0);
        }
        foreach ($spec->binaries as $id => $value) {
            $cfb->addStream($prefix . self::substg($id, 0x0102), $value);
            $entries .= pack('VVVV', ($id << 16) | 0x0102, 6, strlen($value), 0);
        }
        foreach ($spec->integers as $id => $value) {
            $entries .= pack('VVVV', ($id << 16) | 0x0003, 6, $value, 0);
        }
        foreach ($spec->booleans as $id => $value) {
            $entries .= pack('VVVV', ($id << 16) | 0x000B, 6, $value ? 1 : 0, 0);
        }
        foreach ($spec->times as $id => $value) {
            $ticks = ($value->getTimestamp() + self::FILETIME_UNIX_OFFSET) * 10_000_000;
            $entries .= pack('VVVV', ($id << 16) | 0x0040, 6, $ticks & 0xFFFFFFFF, $ticks >> 32);
        }

        foreach ($spec->recipients as $index => $recipient) {
            $recipientPath = $prefix . sprintf('__recip_version1.0_#%08X', $index);
            $cfb->addStorage($recipientPath);
            $this->writeObject($cfb, $recipientPath, $recipient, self::KIND_OBJECT);
        }
        foreach ($spec->attachments as $index => $attachment) {
            $attachmentPath = $prefix . sprintf('__attach_version1.0_#%08X', $index);
            $cfb->addStorage($attachmentPath);
            $this->writeObject($cfb, $attachmentPath, $attachment, self::KIND_OBJECT);
        }
        if ($spec->embedded !== null) {
            $embeddedPath = $prefix . self::substg(0x3701, 0x000D);
            $cfb->addStorage($embeddedPath);
            $this->writeObject($cfb, $embeddedPath, $spec->embedded, self::KIND_EMBEDDED);
            $entries .= pack('VVVV', (0x3701 << 16) | 0x000D, 6, 0xFFFFFFFF, 0);
        }

        $recipients = count($spec->recipients);
        $attachments = count($spec->attachments);
        $header = match ($kind) {
            self::KIND_TOP_LEVEL => str_repeat("\0", 8) . pack('VVVV', $recipients, $attachments, $recipients, $attachments) . str_repeat("\0", 8),
            self::KIND_EMBEDDED => str_repeat("\0", 8) . pack('VVVV', $recipients, $attachments, $recipients, $attachments),
            default => str_repeat("\0", 8),
        };
        $cfb->addStream($prefix . '__properties_version1.0', $header . $entries);
    }

    private static function substg(int $id, int $type): string {
        return sprintf('__substg1.0_%04X%04X', $id, $type);
    }

    private static function encode(string $value, string $encoding): string {
        $encoded = @iconv('UTF-8', $encoding, $value);
        if ($encoded === false) {
            throw new RuntimeException("Testdaten nicht in $encoding darstellbar.");
        }

        return $encoded;
    }
}
