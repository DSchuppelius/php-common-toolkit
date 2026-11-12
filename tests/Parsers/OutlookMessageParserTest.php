<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : OutlookMessageParserTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Parsers;

use CommonToolkit\Entities\Mail\{Attachment, Message, Recipient};
use CommonToolkit\Enums\Mail\RecipientType;
use CommonToolkit\Exceptions\Parsers\{DocumentLimitExceededException, MalformedDocumentException};
use CommonToolkit\Parsers\OutlookMessageParser;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;
use Tests\Support\{CompoundFileWriter, MessageSpec, OutlookMessageWriter};

/**
 * Outlook-Nachrichten (.msg) lesen — ausschließlich mit synthetischen
 * Nachrichten aus dem {@see OutlookMessageWriter} (keine echten Dateien,
 * keine echten Personen).
 */
class OutlookMessageParserTest extends BaseTestCase {
    private const SUBJECT = 0x0037;

    private const CLIENT_SUBMIT_TIME = 0x0039;

    private const SUBJECT_PREFIX = 0x003D;

    private const SENT_REPRESENTING_NAME = 0x0042;

    private const SENT_REPRESENTING_ADDRTYPE = 0x0064;

    private const SENT_REPRESENTING_EMAIL = 0x0065;

    private const TRANSPORT_HEADERS = 0x007D;

    private const RECIPIENT_TYPE = 0x0C15;

    private const SENDER_NAME = 0x0C1A;

    private const SENDER_ADDRTYPE = 0x0C1E;

    private const SENDER_EMAIL = 0x0C1F;

    private const DELIVERY_TIME = 0x0E06;

    private const NORMALIZED_SUBJECT = 0x0E1D;

    private const ATTACH_SIZE = 0x0E20;

    private const BODY = 0x1000;

    private const RTF_COMPRESSED = 0x1009;

    private const HTML = 0x1013;

    private const MESSAGE_ID = 0x1035;

    private const REFERENCES = 0x1039;

    private const IN_REPLY_TO = 0x1042;

    private const DISPLAY_NAME = 0x3001;

    private const ADDRTYPE = 0x3002;

    private const EMAIL_ADDRESS = 0x3003;

    private const ATTACH_DATA = 0x3701;

    private const ATTACH_EXTENSION = 0x3703;

    private const ATTACH_FILENAME = 0x3704;

    private const ATTACH_METHOD = 0x3705;

    private const ATTACH_LONG_FILENAME = 0x3707;

    private const ATTACH_MIME_TAG = 0x370E;

    private const ATTACH_CONTENT_ID = 0x3712;

    private const ATTACH_FLAGS = 0x3714;

    private const SMTP_ADDRESS = 0x39FE;

    private const INTERNET_CPID = 0x3FDE;

    private const MESSAGE_CODEPAGE = 0x3FFD;

    private const SENDER_SMTP = 0x5D01;

    private const SENT_REPRESENTING_SMTP = 0x5D02;

    private const RECIPIENT_DISPLAY_NAME = 0x5FF6;

    private const ATTACHMENT_HIDDEN = 0x7FFE;

    private const EX_MAX = '/O=EXAMPLE/OU=EXCHANGE ADMINISTRATIVE GROUP (FYDIBOHF23SPDLT)/CN=RECIPIENTS/CN=MAX';

    private const EX_ERIKA = '/O=EXAMPLE/OU=EXCHANGE ADMINISTRATIVE GROUP (FYDIBOHF23SPDLT)/CN=RECIPIENTS/CN=ERIKA';

    private const PDF = "%PDF-1.7\n1 0 obj << /Type /Catalog >> endobj\n%%EOF\n";

    private const PNG = "\x89PNG\r\n\x1a\n\0\0\0\rIHDR\0\0\0\x01\0\0\0\x01\x08\x06\0\0\0\x1f\x15\xc4\x89";

    // ------------------------------------------------------------------
    // Bausteine
    // ------------------------------------------------------------------

    private static function fullMessage(): MessageSpec {
        return MessageSpec::create()
            ->string(self::SUBJECT, 'Angebot für Projekt „Nordlicht" 😀')
            ->string(self::SENT_REPRESENTING_NAME, 'Max Mustermann')
            ->string(self::SENT_REPRESENTING_ADDRTYPE, 'EX')
            ->string(self::SENT_REPRESENTING_EMAIL, self::EX_MAX)
            ->string(self::SENT_REPRESENTING_SMTP, 'max.mustermann@example.com')
            ->string(self::SENDER_NAME, 'Max Mustermann')
            ->string(self::SENDER_ADDRTYPE, 'EX')
            ->string(self::SENDER_EMAIL, self::EX_MAX)
            ->string(self::SENDER_SMTP, 'max.mustermann@example.com')
            ->time(self::CLIENT_SUBMIT_TIME, new DateTimeImmutable('2026-11-10 08:15:30 UTC'))
            ->time(self::DELIVERY_TIME, new DateTimeImmutable('2026-11-10 08:16:02 UTC'))
            ->string(self::MESSAGE_ID, '<angebot-4711@example.com>')
            ->string(self::IN_REPLY_TO, '<anfrage-0815@example.org>')
            ->string(self::REFERENCES, '<start-1@example.org> <anfrage-0815@example.org>')
            ->string(self::TRANSPORT_HEADERS, "Received: from mail.example.com\r\nFrom: Max Mustermann <max.mustermann@example.com>\r\nSubject: Angebot\r\n")
            ->string(self::BODY, "Hallo Erika,\r\nanbei das Angebot.\r\n")
            ->integer(self::INTERNET_CPID, 65001)
            ->binary(self::HTML, '<html><body><p>Grüße</p><img src="cid:logo@example"></body></html>')
            ->recipient(MessageSpec::create()
                ->string(self::DISPLAY_NAME, 'Erika Musterfrau')
                ->string(self::ADDRTYPE, 'EX')
                ->string(self::EMAIL_ADDRESS, self::EX_ERIKA)
                ->string(self::SMTP_ADDRESS, 'erika@example.org')
                ->integer(self::RECIPIENT_TYPE, 1))
            ->recipient(MessageSpec::create()
                ->string(self::DISPLAY_NAME, 'Team Vertrieb')
                ->string(self::ADDRTYPE, 'SMTP')
                ->string(self::EMAIL_ADDRESS, 'vertrieb@example.org')
                ->integer(self::RECIPIENT_TYPE, 2))
            ->recipient(MessageSpec::create()
                ->string(self::RECIPIENT_DISPLAY_NAME, 'Archiv')
                ->string(self::ADDRTYPE, 'SMTP')
                ->string(self::EMAIL_ADDRESS, 'archiv@example.net')
                ->integer(self::RECIPIENT_TYPE, 3 | 0x10000000))
            ->attachment(MessageSpec::create()
                ->integer(self::ATTACH_METHOD, 1)
                ->string(self::ATTACH_FILENAME, 'ANGEBO~1.PDF')
                ->string(self::ATTACH_LONG_FILENAME, 'Angebot Nordlicht 2026.pdf')
                ->string(self::ATTACH_MIME_TAG, 'application/pdf')
                ->binary(self::ATTACH_DATA, self::PDF))
            ->attachment(MessageSpec::create()
                ->integer(self::ATTACH_METHOD, 1)
                ->string(self::ATTACH_LONG_FILENAME, 'logo.png')
                ->string(self::ATTACH_CONTENT_ID, 'logo@example')
                ->binary(self::ATTACH_DATA, self::PNG))
            ->attachment(MessageSpec::create()
                ->integer(self::ATTACH_METHOD, 1)
                ->string(self::ATTACH_FILENAME, 'DATEN.CSV')
                ->binary(self::ATTACH_DATA, "a;b\n1;2\n"));
    }

    private static function parse(MessageSpec $spec, bool $unicode = true, string $encoding = 'Windows-1252', int $majorVersion = 3): Message {
        return OutlookMessageParser::fromString(OutlookMessageWriter::build($spec, $unicode, $encoding, $majorVersion));
    }

    private static function pattern(int $length): string {
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= chr(($i * 7 + intdiv($i, 253)) % 256);
        }

        return $out;
    }

    // ------------------------------------------------------------------
    // Inhalt
    // ------------------------------------------------------------------

    public function test_reads_header_fields_of_a_unicode_message(): void {
        $message = self::parse(self::fullMessage());

        $this->assertSame('Angebot für Projekt „Nordlicht" 😀', $message->getSubject());
        $this->assertNotNull($message->getFrom());
        $this->assertSame('Max Mustermann', $message->getFrom()->getName());
        $this->assertSame('max.mustermann@example.com', $message->getFrom()->getAddress(), 'SMTP statt Exchange-Adresse');
        $this->assertSame('2026-11-10T08:15:30+00:00', $message->getDate()?->format(DATE_ATOM));
        $this->assertSame('UTC', $message->getDate()->getTimezone()->getName());
        $this->assertSame('<angebot-4711@example.com>', $message->getMessageId());
        $this->assertSame('<anfrage-0815@example.org>', $message->getInReplyTo());
        $this->assertSame(['<start-1@example.org>', '<anfrage-0815@example.org>'], $message->getReferences());
        $this->assertStringStartsWith('Received: from mail.example.com', (string) $message->getHeaders());
    }

    public function test_reads_recipients_with_type_and_smtp_address(): void {
        $message = self::parse(self::fullMessage());

        $summary = array_map(
            static fn (Recipient $recipient): array => [$recipient->getName(), $recipient->getAddress(), $recipient->getType()],
            $message->getRecipients(),
        );
        $this->assertSame([
            ['Erika Musterfrau', 'erika@example.org', RecipientType::To],
            ['Team Vertrieb', 'vertrieb@example.org', RecipientType::Cc],
            ['Archiv', 'archiv@example.net', RecipientType::Bcc],
        ], $summary);
        $this->assertCount(1, $message->getTo());
        $this->assertSame('vertrieb@example.org', $message->getCc()[0]->getAddress());
        $this->assertSame('Archiv', $message->getBcc()[0]->getName());
    }

    public function test_reads_text_and_html_bodies_separately(): void {
        $message = self::parse(self::fullMessage());

        $this->assertSame("Hallo Erika,\r\nanbei das Angebot.\r\n", $message->getTextBody());
        $this->assertSame('<html><body><p>Grüße</p><img src="cid:logo@example"></body></html>', $message->getHtmlBody());
        $this->assertFalse($message->hasRtfBody());
    }

    public function test_reads_attachments(): void {
        $attachments = self::parse(self::fullMessage())->getAttachments();
        $this->assertCount(3, $attachments);

        [$pdf, $logo, $csv] = $attachments;
        $this->assertSame('Angebot Nordlicht 2026.pdf', $pdf->getFilename(), 'langer Name vor kurzem');
        $this->assertSame('application/pdf', $pdf->getMimeType());
        $this->assertSame(self::PDF, $pdf->getContent());
        $this->assertSame(strlen(self::PDF), $pdf->getSize());
        $this->assertNull($pdf->getContentId());
        $this->assertFalse($pdf->isInline());
        $this->assertFalse($pdf->isMessage());
        $this->assertNull($pdf->getMessage());

        $this->assertSame('logo.png', $logo->getFilename());
        $this->assertSame('image/png', $logo->getMimeType(), 'MIME-Typ aus der Endung');
        $this->assertSame('logo@example', $logo->getContentId());
        $this->assertTrue($logo->isInline(), 'im HTML per cid: verwendet');
        $this->assertSame(self::PNG, $logo->getContent());

        $this->assertSame('DATEN.CSV', $csv->getFilename(), 'kurzer Name, wenn kein langer da ist');
        $this->assertSame('text/csv', $csv->getMimeType());
        $this->assertFalse($csv->isInline());
    }

    public function test_inline_flags_and_extension_property(): void {
        $message = self::parse(MessageSpec::create()
            ->string(self::SUBJECT, 'Inline')
            ->attachment(MessageSpec::create()
                ->string(self::ATTACH_LONG_FILENAME, 'unterschrift')
                ->string(self::ATTACH_EXTENSION, '.gif')
                ->string(self::ATTACH_CONTENT_ID, '<sig@example>')
                ->boolean(self::ATTACHMENT_HIDDEN, true)
                ->binary(self::ATTACH_DATA, 'GIF89a'))
            ->attachment(MessageSpec::create()
                ->string(self::ATTACH_LONG_FILENAME, 'bild.jpg')
                ->integer(self::ATTACH_FLAGS, 0x4)
                ->binary(self::ATTACH_DATA, "\xFF\xD8\xFF")));

        [$signature, $picture] = $message->getAttachments();
        $this->assertTrue($signature->isInline(), 'versteckt = inline');
        $this->assertSame('sig@example', $signature->getContentId(), 'ohne spitze Klammern');
        $this->assertSame('image/gif', $signature->getMimeType(), 'MIME-Typ aus PR_ATTACH_EXTENSION');
        $this->assertTrue($picture->isInline(), 'ATT_MHTML_REF = inline');
        $this->assertNull($message->getHtmlBody());
    }

    /**
     * @return array<string, array{int}>
     */
    public static function versionProvider(): array {
        return ['512-Byte-Sektoren' => [3], '4096-Byte-Sektoren' => [4]];
    }

    #[DataProvider('versionProvider')]
    public function test_large_message_with_mini_and_regular_streams(int $majorVersion): void {
        $big = self::pattern(300_000);
        $body = str_repeat("Zeile mit Umlauten äöü\r\n", 400);
        $spec = MessageSpec::create()
            ->string(self::SUBJECT, 'Groß')
            ->string(self::BODY, $body)
            ->attachment(MessageSpec::create()->string(self::ATTACH_LONG_FILENAME, 'gross.bin')->binary(self::ATTACH_DATA, $big))
            ->attachment(MessageSpec::create()->string(self::ATTACH_LONG_FILENAME, 'klein.txt')->binary(self::ATTACH_DATA, 'klein'));

        $message = self::parse($spec, true, 'Windows-1252', $majorVersion);

        $this->assertSame($body, $message->getTextBody());
        $this->assertSame($big, $message->getAttachments()[0]->getContent());
        $this->assertSame(300_000, $message->getAttachments()[0]->getSize());
        $this->assertSame('klein', $message->getAttachments()[1]->getContent());
        $this->assertSame('text/plain', $message->getAttachments()[1]->getMimeType());
    }

    // ------------------------------------------------------------------
    // Zeichensätze
    // ------------------------------------------------------------------

    public function test_eight_bit_strings_use_the_message_codepage(): void {
        $spec = MessageSpec::create()
            ->integer(self::MESSAGE_CODEPAGE, 1250)
            ->integer(self::INTERNET_CPID, 1252)
            ->string(self::SUBJECT, 'Zażółć gęślą jaźń')
            ->string(self::SENDER_NAME, 'Łukasz Żółć')
            ->string(self::SENDER_ADDRTYPE, 'SMTP')
            ->string(self::SENDER_EMAIL, 'lukasz@example.com')
            ->string(self::BODY, 'Dzień dobry')
            ->binary(self::HTML, (string) iconv('UTF-8', 'Windows-1252', '<p>Grüße aus Köln – „Test"</p>'))
            ->recipient(MessageSpec::create()->string(self::DISPLAY_NAME, 'Paweł')->string(self::EMAIL_ADDRESS, 'pawel@example.org'))
            ->attachment(MessageSpec::create()
                ->integer(self::ATTACH_METHOD, 5)
                ->string(self::ATTACH_LONG_FILENAME, 'Wiadomość.msg')
                ->embed(MessageSpec::create()->string(self::SUBJECT, 'Odpowiedź źródłowa')));

        $message = self::parse($spec, false, 'Windows-1250');

        $this->assertSame('Zażółć gęślą jaźń', $message->getSubject());
        $this->assertSame('Łukasz Żółć', $message->getFrom()?->getName());
        $this->assertSame('lukasz@example.com', $message->getFrom()->getAddress());
        $this->assertSame('Dzień dobry', $message->getTextBody());
        $this->assertSame('<p>Grüße aus Köln – „Test"</p>', $message->getHtmlBody(), 'HTML binär in PR_INTERNET_CPID');
        $this->assertSame('Paweł', $message->getTo()[0]->getName());
        $this->assertSame('pawel@example.org', $message->getTo()[0]->getAddress(), 'ohne Adresstyp, aber SMTP-förmig');
        $this->assertSame('Wiadomość.msg', $message->getAttachments()[0]->getFilename());
        $this->assertSame('Odpowiedź źródłowa', $message->getAttachments()[0]->getMessage()?->getSubject(), 'Codepage geerbt');
    }

    public function test_eight_bit_strings_fall_back_to_windows_1252(): void {
        $message = self::parse(
            MessageSpec::create()->string(self::SUBJECT, 'Größenänderung – „Entwurf"')->binary(self::HTML, "<p>Gr\xFC\xDFe</p>"),
            false,
            'Windows-1252',
        );

        $this->assertSame('Größenänderung – „Entwurf"', $message->getSubject());
        $this->assertSame('<p>Grüße</p>', $message->getHtmlBody());
    }

    public function test_eight_bit_strings_use_the_internet_codepage_without_message_codepage(): void {
        $message = self::parse(
            MessageSpec::create()->integer(self::INTERNET_CPID, 1251)->string(self::SUBJECT, 'Привет, мир'),
            false,
            'Windows-1251',
        );

        $this->assertSame('Привет, мир', $message->getSubject());
    }

    public function test_html_body_stored_as_string_or_with_bom(): void {
        $asString = self::parse(MessageSpec::create()->string(self::SUBJECT, 'x')->string(self::HTML, '<p>Text € ✓</p>'));
        $this->assertSame('<p>Text € ✓</p>', $asString->getHtmlBody());

        $withBom = self::parse(MessageSpec::create()->integer(self::INTERNET_CPID, 1252)->binary(self::HTML, "\xEF\xBB\xBF<p>Grüße</p>"));
        $this->assertSame('<p>Grüße</p>', $withBom->getHtmlBody(), 'BOM schlägt die Codepage');
    }

    // ------------------------------------------------------------------
    // Eingebettete Nachrichten
    // ------------------------------------------------------------------

    private static function nested(int $levels): MessageSpec {
        $message = MessageSpec::create()
            ->string(self::SUBJECT, "Ebene $levels")
            ->string(self::BODY, "Text der Ebene $levels")
            ->attachment(MessageSpec::create()->string(self::ATTACH_LONG_FILENAME, "datei-$levels.txt")->binary(self::ATTACH_DATA, "Inhalt $levels"));
        if ($levels > 0) {
            $message->attachment(MessageSpec::create()
                ->integer(self::ATTACH_METHOD, 5)
                ->integer(self::ATTACH_SIZE, 1234)
                ->string(self::ATTACH_LONG_FILENAME, 'Weitergeleitet.msg')
                ->embed(self::nested($levels - 1)));
        }

        return $message;
    }

    public function test_embedded_messages_are_read_recursively(): void {
        $message = self::parse(self::nested(2));

        $this->assertSame('Ebene 2', $message->getSubject());
        [$file, $forward] = $message->getAttachments();
        $this->assertSame('Inhalt 2', $file->getContent());
        $this->assertTrue($forward->isMessage());
        $this->assertSame('Weitergeleitet.msg', $forward->getFilename());
        $this->assertSame('', $forward->getContent());
        $this->assertSame(1234, $forward->getSize());

        $inner = $forward->getMessage();
        $this->assertNotNull($inner);
        $this->assertSame('Ebene 1', $inner->getSubject());
        $this->assertSame('Text der Ebene 1', $inner->getTextBody());
        $this->assertSame('Inhalt 1', $inner->getAttachments()[0]->getContent());

        $innermost = $inner->getAttachments()[1]->getMessage();
        $this->assertNotNull($innermost);
        $this->assertSame('Ebene 0', $innermost->getSubject());
        $this->assertCount(1, $innermost->getAttachments());
    }

    public function test_embedded_message_without_method_is_recognized_by_its_storage(): void {
        $message = self::parse(MessageSpec::create()
            ->string(self::SUBJECT, 'außen')
            ->attachment(MessageSpec::create()->embed(MessageSpec::create()->string(self::SUBJECT, 'innen'))));

        $this->assertSame('innen', $message->getAttachments()[0]->getMessage()?->getSubject());
    }

    public function test_nesting_deeper_than_the_limit_is_refused(): void {
        $bytes = OutlookMessageWriter::build(self::nested(3));

        $exception = $this->limitException(static fn () => OutlookMessageParser::fromString($bytes));
        $this->assertSame(DocumentLimitExceededException::KIND_DEPTH, $exception->getKind());
        $this->assertSame(OutlookMessageParser::DEFAULT_MAX_DEPTH, $exception->getLimit());
        $this->assertSame(3, $exception->getActual());

        $this->assertSame('Ebene 3', OutlookMessageParser::fromString($bytes, maxDepth: 3)->getSubject());

        $flat = OutlookMessageWriter::build(self::nested(1));
        $exception = $this->limitException(static fn () => OutlookMessageParser::fromString($flat, maxDepth: 0));
        $this->assertSame(DocumentLimitExceededException::KIND_DEPTH, $exception->getKind());
    }

    // ------------------------------------------------------------------
    // Grenzen
    // ------------------------------------------------------------------

    public function test_attachment_count_limit_counts_all_levels(): void {
        $threeAttachments = OutlookMessageWriter::build(self::fullMessage());
        $exception = $this->limitException(static fn () => OutlookMessageParser::fromString($threeAttachments, maxAttachments: 2));
        $this->assertSame(DocumentLimitExceededException::KIND_ENTRIES, $exception->getKind());
        $this->assertSame(2, $exception->getLimit());
        $this->assertSame(3, $exception->getActual());

        // nested(1): oben eine Datei + eine Nachricht, darin eine Datei = 3
        $nested = OutlookMessageWriter::build(self::nested(1));
        $this->assertCount(2, OutlookMessageParser::fromString($nested, maxAttachments: 3)->getAttachments());
        $exception = $this->limitException(static fn () => OutlookMessageParser::fromString($nested, maxAttachments: 2));
        $this->assertSame(DocumentLimitExceededException::KIND_ENTRIES, $exception->getKind());
    }

    public function test_attachment_bytes_limit(): void {
        $bytes = OutlookMessageWriter::build(MessageSpec::create()
            ->string(self::SUBJECT, 'Anhang')
            ->attachment(MessageSpec::create()->string(self::ATTACH_LONG_FILENAME, 'a.bin')->binary(self::ATTACH_DATA, str_repeat('a', 6000)))
            ->attachment(MessageSpec::create()->string(self::ATTACH_LONG_FILENAME, 'b.bin')->binary(self::ATTACH_DATA, str_repeat('b', 6000))));

        $this->assertCount(2, OutlookMessageParser::fromString($bytes, maxAttachmentBytes: 12_000)->getAttachments());

        $exception = $this->limitException(static fn () => OutlookMessageParser::fromString($bytes, maxAttachmentBytes: 11_999));
        $this->assertSame(DocumentLimitExceededException::KIND_BYTES, $exception->getKind());
        $this->assertSame(11_999, $exception->getLimit());
        $this->assertSame(12_000, $exception->getActual());
    }

    public function test_embedded_message_counts_against_the_attachment_bytes(): void {
        $bytes = OutlookMessageWriter::build(MessageSpec::create()
            ->string(self::SUBJECT, 'außen')
            ->attachment(MessageSpec::create()
                ->integer(self::ATTACH_METHOD, 5)
                ->embed(MessageSpec::create()->string(self::BODY, str_repeat('x', 5000)))));

        $exception = $this->limitException(static fn () => OutlookMessageParser::fromString($bytes, maxAttachmentBytes: 5000));
        $this->assertSame(DocumentLimitExceededException::KIND_BYTES, $exception->getKind());
    }

    public function test_file_size_limit(): void {
        $bytes = OutlookMessageWriter::build(self::fullMessage());

        $exception = $this->limitException(static fn () => OutlookMessageParser::fromString($bytes, maxFileBytes: 1024, documentName: 'gross.msg'));
        $this->assertSame(DocumentLimitExceededException::KIND_BYTES, $exception->getKind());
        $this->assertSame(strlen($bytes), $exception->getActual());
        $this->assertSame('gross.msg', $exception->getDocument());
    }

    public function test_invalid_limits_are_refused(): void {
        $bytes = OutlookMessageWriter::build(self::fullMessage());

        foreach ([['maxFileBytes' => 0], ['maxAttachments' => -1], ['maxAttachmentBytes' => -1], ['maxDepth' => -1]] as $limits) {
            try {
                OutlookMessageParser::fromString($bytes, ...$limits);
                $this->fail('InvalidArgumentException erwartet für ' . (string) array_key_first($limits));
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    // ------------------------------------------------------------------
    // Rückfälle
    // ------------------------------------------------------------------

    public function test_date_falls_back_to_the_date_header_then_delivery_time(): void {
        $fromHeader = self::parse(MessageSpec::create()
            ->string(self::TRANSPORT_HEADERS, "Date: Tue, 10 Nov 2026 09:15:30 +0100 (CET)\r\nSubject: x\r\n")
            ->time(self::DELIVERY_TIME, new DateTimeImmutable('2026-11-11 00:00:00 UTC')));
        $this->assertSame('2026-11-10T08:15:30+00:00', $fromHeader->getDate()?->format(DATE_ATOM));

        $fromDelivery = self::parse(MessageSpec::create()->time(self::DELIVERY_TIME, new DateTimeImmutable('2026-11-11 07:00:00 UTC')));
        $this->assertSame('2026-11-11T07:00:00+00:00', $fromDelivery->getDate()?->format(DATE_ATOM));
    }

    public function test_header_fields_fall_back_to_transport_headers(): void {
        $headers = "Message-ID: <aus-kopf@example.com>\r\nIn-Reply-To:\r\n <vorher@example.com>\r\nReferences: <a@example.com>\r\n\t<b@example.com>\r\n\r\nX-Body: nicht im Kopf\r\n";
        $message = self::parse(MessageSpec::create()->string(self::TRANSPORT_HEADERS, $headers));

        $this->assertSame('<aus-kopf@example.com>', $message->getMessageId());
        $this->assertSame('<vorher@example.com>', $message->getInReplyTo(), 'gefaltete Kopfzeile');
        $this->assertSame(['<a@example.com>', '<b@example.com>'], $message->getReferences());
    }

    public function test_exchange_sender_falls_back_to_the_from_header(): void {
        $message = self::parse(MessageSpec::create()
            ->string(self::SENDER_NAME, 'Max Mustermann')
            ->string(self::SENDER_ADDRTYPE, 'EX')
            ->string(self::SENDER_EMAIL, self::EX_MAX)
            ->string(self::TRANSPORT_HEADERS, "From: \"Mustermann, Max\" <max@example.com>\r\n"));

        $this->assertSame('Max Mustermann', $message->getFrom()?->getName());
        $this->assertSame('max@example.com', $message->getFrom()->getAddress());
    }

    public function test_exchange_sender_without_smtp_source_keeps_the_stored_address(): void {
        $message = self::parse(MessageSpec::create()
            ->string(self::SENDER_NAME, 'Max Mustermann')
            ->string(self::SENDER_ADDRTYPE, 'EX')
            ->string(self::SENDER_EMAIL, self::EX_MAX));

        $this->assertSame(self::EX_MAX, $message->getFrom()?->getAddress());
    }

    public function test_delegate_sender_does_not_replace_the_represented_person(): void {
        // Assistenz sendet im Auftrag: From ist die vertretene Person, nicht die Assistenz.
        $message = self::parse(MessageSpec::create()
            ->string(self::SENT_REPRESENTING_NAME, 'Erika Musterfrau')
            ->string(self::SENT_REPRESENTING_ADDRTYPE, 'EX')
            ->string(self::SENT_REPRESENTING_EMAIL, self::EX_ERIKA)
            ->string(self::SENDER_NAME, 'Max Mustermann')
            ->string(self::SENDER_SMTP, 'max@example.com'));

        $this->assertSame('Erika Musterfrau', $message->getFrom()?->getName());
        $this->assertSame(self::EX_ERIKA, $message->getFrom()->getAddress());
    }

    public function test_recipient_without_smtp_keeps_the_stored_address(): void {
        $message = self::parse(MessageSpec::create()
            ->recipient(MessageSpec::create()->string(self::DISPLAY_NAME, 'Erika')->string(self::ADDRTYPE, 'EX')->string(self::EMAIL_ADDRESS, self::EX_ERIKA))
            ->recipient(MessageSpec::create()->string(self::EMAIL_ADDRESS, 'ohne-name@example.org')));

        $this->assertSame(self::EX_ERIKA, $message->getTo()[0]->getAddress());
        $this->assertSame('', $message->getTo()[1]->getName());
        $this->assertSame(RecipientType::To, $message->getTo()[1]->getType(), 'ohne Art = An');
    }

    public function test_subject_from_prefix_and_normalized_subject(): void {
        $message = self::parse(MessageSpec::create()->string(self::SUBJECT_PREFIX, 'AW: ')->string(self::NORMALIZED_SUBJECT, 'Termin'));

        $this->assertSame('AW: Termin', $message->getSubject());
    }

    public function test_draft_without_sender_date_and_recipients(): void {
        $message = self::parse(MessageSpec::create()->string(self::BODY, 'Entwurf'));

        $this->assertSame('', $message->getSubject());
        $this->assertNull($message->getFrom());
        $this->assertNull($message->getDate());
        $this->assertNull($message->getMessageId());
        $this->assertNull($message->getHeaders());
        $this->assertSame([], $message->getRecipients());
        $this->assertSame([], $message->getReferences());
        $this->assertSame('Entwurf', $message->getTextBody());
        $this->assertNull($message->getHtmlBody());
    }

    public function test_rtf_only_message_reports_the_rtf_body(): void {
        $message = self::parse(MessageSpec::create()->string(self::SUBJECT, 'RTF')->binary(self::RTF_COMPRESSED, "\x2d\0\0\0\x2b\0\0\0LZFu\0\0\0\0"));

        $this->assertTrue($message->hasRtfBody());
        $this->assertNull($message->getTextBody());
        $this->assertNull($message->getHtmlBody());
    }

    public function test_attachment_without_data_in_the_file_is_skipped(): void {
        $message = self::parse(MessageSpec::create()
            ->attachment(MessageSpec::create()->integer(self::ATTACH_METHOD, 2)->string(self::ATTACH_LONG_FILENAME, '\\\\server\\freigabe\\datei.pdf'))
            ->attachment(MessageSpec::create()->string(self::ATTACH_LONG_FILENAME, 'echt.txt')->binary(self::ATTACH_DATA, 'da')));

        $this->assertSame(['echt.txt'], array_map(static fn (Attachment $attachment): string => $attachment->getFilename(), $message->getAttachments()));
    }

    // ------------------------------------------------------------------
    // Datei, falsche und beschädigte Eingaben
    // ------------------------------------------------------------------

    public function test_reads_from_file(): void {
        $path = sys_get_temp_dir() . '/msg-test-' . bin2hex(random_bytes(6)) . '.msg';
        file_put_contents($path, OutlookMessageWriter::build(self::fullMessage()));
        try {
            $this->assertSame('max.mustermann@example.com', OutlookMessageParser::fromFile($path)->getFrom()?->getAddress());

            $exception = $this->limitException(static fn () => OutlookMessageParser::fromFile($path, maxFileBytes: 512));
            $this->assertSame(basename($path), $exception->getDocument());
        } finally {
            unlink($path);
        }
    }

    public function test_compound_file_without_message_properties_is_refused(): void {
        $other = (new CompoundFileWriter)->addStream('WordDocument', str_repeat('w', 100))->build();

        $this->assertMalformed($other, 'keine Outlook-Nachricht');
    }

    public function test_other_formats_are_refused(): void {
        $this->assertMalformed("From: max@example.com\r\nSubject: Ich bin eine .eml\r\n\r\nText\r\n");
        $this->assertMalformed('');
        $this->assertMalformed("PK\x03\x04" . str_repeat("\0", 600));
    }

    public function test_truncated_message_is_refused(): void {
        $bytes = OutlookMessageWriter::build(self::fullMessage());

        $this->assertMalformed(substr($bytes, 0, intdiv(strlen($bytes), 3)));
        $this->assertMalformed(substr($bytes, 0, strlen($bytes) - 1));
    }

    public function test_sector_cycle_in_attachment_data_is_refused(): void {
        $writer = OutlookMessageWriter::writer(MessageSpec::create()
            ->string(self::SUBJECT, 'Zyklus')
            ->attachment(MessageSpec::create()->string(self::ATTACH_LONG_FILENAME, 'gross.bin')->binary(self::ATTACH_DATA, self::pattern(10_000))));
        $bytes = $writer->build();
        $start = $writer->layout()['starts']['__attach_version1.0_#00000000/__substg1.0_37010102'];

        $this->assertMalformed($writer->patchFat($bytes, $start + 3, $start), 'Zyklus');
    }

    public function test_shared_attachment_sectors_are_refused(): void {
        // Zwei Anhänge auf denselben Sektoren würden den Inhalt vervielfachen.
        $data = self::pattern(10_000);
        $writer = OutlookMessageWriter::writer(MessageSpec::create()
            ->attachment(MessageSpec::create()->string(self::ATTACH_LONG_FILENAME, 'a.bin')->binary(self::ATTACH_DATA, $data))
            ->attachment(MessageSpec::create()->string(self::ATTACH_LONG_FILENAME, 'b.bin')->binary(self::ATTACH_DATA, $data)));
        $bytes = $writer->build();
        $first = $writer->layout()['starts']['__attach_version1.0_#00000000/__substg1.0_37010102'];
        $shared = $writer->patchEntry($bytes, '__attach_version1.0_#00000001/__substg1.0_37010102', CompoundFileWriter::ENTRY_START, $first);

        $this->assertMalformed($shared, 'zwei Strukturen');
    }

    private function assertMalformed(string $bytes, ?string $messagePart = null): void {
        try {
            OutlookMessageParser::fromString($bytes, documentName: 'eingang.msg');
            $this->fail('MalformedDocumentException erwartet.');
        } catch (MalformedDocumentException $exception) {
            $this->assertSame('eingang.msg', $exception->getDocument());
            if ($messagePart !== null) {
                $this->assertStringContainsString($messagePart, $exception->getMessage());
            }
        }
    }

    /** @param callable(): mixed $call */
    private function limitException(callable $call): DocumentLimitExceededException {
        try {
            $call();
        } catch (DocumentLimitExceededException $exception) {
            return $exception;
        }
        $this->fail('DocumentLimitExceededException erwartet.');
    }
}
