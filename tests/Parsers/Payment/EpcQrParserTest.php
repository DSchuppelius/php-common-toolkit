<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EpcQrParserTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Parsers\Payment;

use CommonToolkit\Builders\Payment\EpcQrBuilder;
use CommonToolkit\Enums\Payment\EpcQrCharset;
use CommonToolkit\Exceptions\Parsers\{InvalidEpcQrPayloadException, MalformedDocumentException};
use CommonToolkit\Parsers\Payment\EpcQrParser;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

/**
 * EPC-QR-Nutzlast lesen (Girocode, EPC069-12) — Gegenstück zum EpcQrBuilder.
 */
class EpcQrParserTest extends BaseTestCase {
    private const IBAN = 'DE02120300000000202051';

    private const BIC = 'BYLADEM1001';

    // ------------------------------------------------------------------
    // Rundreise Builder -> Parser
    // ------------------------------------------------------------------

    public function test_round_trip_minimal_payload(): void {
        $payment = EpcQrParser::parse(EpcQrBuilder::to('Muster GmbH', self::IBAN)->build());

        $this->assertSame('002', $payment->getVersion());
        $this->assertSame(EpcQrCharset::Utf8, $payment->getCharset());
        $this->assertNull($payment->getBic());
        $this->assertSame('Muster GmbH', $payment->getName());
        $this->assertSame(self::IBAN, $payment->getIban());
        $this->assertTrue($payment->isIbanValid());
        $this->assertNull($payment->getAmount());
        $this->assertNull($payment->getPurpose());
        $this->assertNull($payment->getReference());
        $this->assertNull($payment->getRemittance());
        $this->assertNull($payment->getNote());
    }

    public function test_round_trip_full_payload_with_remittance(): void {
        $payload = EpcQrBuilder::to('Muster GmbH', self::IBAN)
            ->bic(self::BIC)
            ->amount('1234.5')
            ->purpose('GDDS')
            ->remittance('Rechnung 2026-0815')
            ->note('Bitte bis 31.08. zahlen')
            ->build();

        $payment = EpcQrParser::parse($payload);

        $this->assertSame(self::BIC, $payment->getBic());
        $this->assertSame('1234.50', $payment->getAmount());
        $this->assertSame('GDDS', $payment->getPurpose());
        $this->assertNull($payment->getReference());
        $this->assertSame('Rechnung 2026-0815', $payment->getRemittance());
        $this->assertSame('Bitte bis 31.08. zahlen', $payment->getNote());
    }

    public function test_round_trip_with_structured_reference(): void {
        $payload = EpcQrBuilder::to('Muster GmbH', self::IBAN)->amount(99.99)->reference('RF18539007547034')->build();

        $payment = EpcQrParser::parse($payload);

        $this->assertSame('99.99', $payment->getAmount());
        $this->assertSame('RF18539007547034', $payment->getReference());
        $this->assertNull($payment->getRemittance());
    }

    public function test_round_trip_latin1_charset(): void {
        $payload = EpcQrBuilder::to('Grüße & Söhne GmbH', self::IBAN)->remittance('Maß 1/2')->charset(false)->build();

        $payment = EpcQrParser::parse($payload);

        $this->assertSame(EpcQrCharset::Latin1, $payment->getCharset());
        $this->assertSame('Grüße & Söhne GmbH', $payment->getName());
        $this->assertSame('Maß 1/2', $payment->getRemittance());
    }

    public function test_round_trip_at_the_byte_limit(): void {
        $payload = EpcQrBuilder::to(str_repeat('A', 70), self::IBAN)
            ->bic(self::BIC)
            ->amount('999999999.99')
            ->purpose('GDDS')
            ->remittance(str_repeat('B', 140))
            ->note(str_repeat('C', 48))
            ->build();
        $this->assertSame(EpcQrBuilder::MAX_BYTES, strlen($payload));

        $payment = EpcQrParser::parse($payload);

        $this->assertSame('999999999.99', $payment->getAmount());
        $this->assertSame(str_repeat('C', 48), $payment->getNote());
    }

    // ------------------------------------------------------------------
    // Versionen, Zeilenenden, Zeichensätze
    // ------------------------------------------------------------------

    public function test_version_001_requires_and_reads_the_bic(): void {
        $payment = EpcQrParser::parse(self::payload(['BCD', '001', '1', 'SCT', 'bylade m1001', 'Muster GmbH', self::IBAN]));

        $this->assertSame('001', $payment->getVersion());
        $this->assertSame(self::BIC, $payment->getBic());
    }

    public function test_version_001_without_bic_is_refused(): void {
        $this->assertInvalid(self::payload(['BCD', '001', '1', 'SCT', '', 'Muster GmbH', self::IBAN]), InvalidEpcQrPayloadException::FIELD_BIC);
    }

    public function test_crlf_line_endings_are_accepted(): void {
        $payment = EpcQrParser::parse(implode("\r\n", ['BCD', '002', '1', 'SCT', '', 'Muster GmbH', self::IBAN, 'EUR12.3', '', '', 'Miete']));

        $this->assertSame('12.30', $payment->getAmount());
        $this->assertSame('Miete', $payment->getRemittance());
    }

    public function test_one_trailing_line_break_is_tolerated(): void {
        $lines = ['BCD', '002', '1', 'SCT', '', 'Muster GmbH', self::IBAN, 'EUR1', '', '', 'Miete', 'Danke'];

        $this->assertSame('Danke', EpcQrParser::parse(implode("\n", $lines) . "\n")->getNote());
        $this->assertSame('Danke', EpcQrParser::parse(implode("\r\n", $lines) . "\r\n")->getNote());
    }

    public function test_a_second_trailing_line_break_counts_as_a_thirteenth_line(): void {
        $lines = ['BCD', '002', '1', 'SCT', '', 'Muster GmbH', self::IBAN, 'EUR1', '', '', 'Miete', 'Danke'];

        $this->assertInvalid(implode("\n", $lines) . "\n\n", InvalidEpcQrPayloadException::FIELD_PAYLOAD);
    }

    public function test_spaces_around_fields_are_trimmed(): void {
        $payment = EpcQrParser::parse(self::payload(['BCD', '002', '1', 'SCT', '', '  Muster GmbH ', ' DE02 1203 0000 0000 2020 51 ', ' EUR5 ']));

        $this->assertSame('Muster GmbH', $payment->getName());
        $this->assertSame(self::IBAN, $payment->getIban());
        $this->assertSame('5.00', $payment->getAmount());
    }

    /**
     * @return array<string, array{int, string, EpcQrCharset}>
     */
    public static function charsetProvider(): array {
        return [
            'UTF-8' => [1, 'Žluťoučký kůň €', EpcQrCharset::Utf8],
            'ISO-8859-1' => [2, 'Müller Ärzte', EpcQrCharset::Latin1],
            'ISO-8859-2' => [3, 'Łukasz Żółć', EpcQrCharset::Latin2],
            'ISO-8859-4' => [4, 'Ģirts Ķēniņš', EpcQrCharset::Latin4],
            'ISO-8859-5' => [5, 'Иван Петров', EpcQrCharset::Cyrillic],
            'ISO-8859-7' => [6, 'Γιώργος Παπαδόπουλος', EpcQrCharset::Greek],
            'ISO-8859-10' => [7, 'Þórður Ŋáhkir', EpcQrCharset::Latin6],
            'ISO-8859-15' => [8, 'Œuvre € GmbH', EpcQrCharset::Latin9],
        ];
    }

    #[DataProvider('charsetProvider')]
    public function test_every_charset_is_converted_to_utf8(int $digit, string $name, EpcQrCharset $charset): void {
        $encodedName = $charset === EpcQrCharset::Utf8 ? $name : iconv('UTF-8', $charset->encoding(), $name);
        $this->assertIsString($encodedName);
        if ($charset !== EpcQrCharset::Utf8) {
            $this->assertNotSame($name, $encodedName, 'Testdaten müssen im Zielzeichensatz anders kodiert sein.');
        }

        $payment = EpcQrParser::parse(self::payload(['BCD', '002', (string) $digit, 'SCT', '', $encodedName, self::IBAN]));

        $this->assertSame($charset, $payment->getCharset());
        $this->assertSame($name, $payment->getName());
    }

    public function test_charset_encoding_names(): void {
        $this->assertSame('UTF-8', EpcQrCharset::Utf8->encoding());
        $this->assertSame('ISO-8859-15', EpcQrCharset::Latin9->encoding());
        $this->assertSame(EpcQrCharset::Latin6, EpcQrCharset::from(7));
    }

    // ------------------------------------------------------------------
    // Betrag
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{string, string}>
     */
    public static function validAmountProvider(): array {
        return [
            'integer' => ['EUR1', '1.00'],
            'one decimal' => ['EUR0.5', '0.50'],
            'leading zeros' => ['EUR007.10', '7.10'],
            'minimum' => ['EUR0.01', '0.01'],
            'maximum' => ['EUR999999999.99', '999999999.99'],
        ];
    }

    #[DataProvider('validAmountProvider')]
    public function test_valid_amounts_become_two_decimal_strings(string $line, string $expected): void {
        $payment = EpcQrParser::parse(self::payload(['BCD', '002', '1', 'SCT', '', 'Muster GmbH', self::IBAN, $line]));

        $this->assertSame($expected, $payment->getAmount());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidAmountProvider(): array {
        return [
            'zero' => ['EUR0.00'],
            'zero integer' => ['EUR0'],
            'too large' => ['EUR1000000000.00'],
            'other currency' => ['USD10.00'],
            'decimal comma' => ['EUR10,00'],
            'three decimals' => ['EUR10.001'],
            'no integer digit' => ['EUR.50'],
            'trailing dot' => ['EUR10.'],
            'negative' => ['EUR-5.00'],
            'currency missing' => ['10.00'],
        ];
    }

    #[DataProvider('invalidAmountProvider')]
    public function test_invalid_amounts_are_refused(string $line): void {
        $this->assertInvalid(self::payload(['BCD', '002', '1', 'SCT', '', 'Muster GmbH', self::IBAN, $line]), InvalidEpcQrPayloadException::FIELD_AMOUNT);
    }

    // ------------------------------------------------------------------
    // IBAN
    // ------------------------------------------------------------------

    public function test_iban_with_wrong_check_digits_is_reported_not_hidden(): void {
        $payment = EpcQrParser::parse(self::payload(['BCD', '002', '1', 'SCT', '', 'Muster GmbH', 'DE03120300000000202051']));

        $this->assertSame('DE03120300000000202051', $payment->getIban());
        $this->assertFalse($payment->isIbanValid());
    }

    public function test_iban_with_wrong_country_length_is_reported_invalid(): void {
        $payment = EpcQrParser::parse(self::payload(['BCD', '002', '1', 'SCT', '', 'Muster GmbH', 'DE0212030000000020205']));

        $this->assertFalse($payment->isIbanValid());
    }

    public function test_text_without_iban_shape_is_refused(): void {
        $this->assertInvalid(self::payload(['BCD', '002', '1', 'SCT', '', 'Muster GmbH', 'Kontonummer 123']), InvalidEpcQrPayloadException::FIELD_IBAN);
        $this->assertInvalid(self::payload(['BCD', '002', '1', 'SCT', '', 'Muster GmbH', 'DE02' . str_repeat('1', 31)]), InvalidEpcQrPayloadException::FIELD_IBAN);
        $this->assertInvalid(self::payload(['BCD', '002', '1', 'SCT', '', 'Muster GmbH', '', 'EUR1']), InvalidEpcQrPayloadException::FIELD_IBAN);
    }

    // ------------------------------------------------------------------
    // Feldgrenzen
    // ------------------------------------------------------------------

    public function test_name_is_required_and_limited(): void {
        $this->assertInvalid(self::payload(['BCD', '002', '1', 'SCT', '', '', self::IBAN]), InvalidEpcQrPayloadException::FIELD_NAME);
        $this->assertInvalid(self::payload(['BCD', '002', '1', 'SCT', '', str_repeat('Ä', 71), self::IBAN]), InvalidEpcQrPayloadException::FIELD_NAME);
        $this->assertSame(str_repeat('Ä', 70), EpcQrParser::parse(self::payload(['BCD', '002', '1', 'SCT', '', str_repeat('Ä', 70), self::IBAN]))->getName());
    }

    public function test_reference_remittance_and_note_lengths(): void {
        $base = ['BCD', '002', '1', 'SCT', '', 'Muster GmbH', self::IBAN, '', ''];

        $this->assertInvalid(self::payload([...$base, str_repeat('R', 36)]), InvalidEpcQrPayloadException::FIELD_REFERENCE);
        $this->assertInvalid(self::payload([...$base, '', str_repeat('V', 141)]), InvalidEpcQrPayloadException::FIELD_REMITTANCE);
        $this->assertInvalid(self::payload([...$base, '', '', str_repeat('H', 71)]), InvalidEpcQrPayloadException::FIELD_NOTE);
    }

    public function test_reference_and_remittance_are_mutually_exclusive(): void {
        $this->assertInvalid(
            self::payload(['BCD', '002', '1', 'SCT', '', 'Muster GmbH', self::IBAN, '', '', 'RF18539007547034', 'Rechnung 1']),
            InvalidEpcQrPayloadException::FIELD_REMITTANCE,
        );
    }

    public function test_purpose_code_is_uppercased_and_checked(): void {
        $payment = EpcQrParser::parse(self::payload(['BCD', '002', '1', 'SCT', '', 'Muster GmbH', self::IBAN, '', 'gdds']));
        $this->assertSame('GDDS', $payment->getPurpose());

        $this->assertInvalid(self::payload(['BCD', '002', '1', 'SCT', '', 'Muster GmbH', self::IBAN, '', 'GD']), InvalidEpcQrPayloadException::FIELD_PURPOSE);
    }

    public function test_invalid_bic_is_refused(): void {
        $this->assertInvalid(self::payload(['BCD', '002', '1', 'SCT', 'BUCHUNGSTAG', 'Muster GmbH', self::IBAN]), InvalidEpcQrPayloadException::FIELD_BIC);
    }

    // ------------------------------------------------------------------
    // Kopf, Zeilenzahl, Bytes, Kodierung
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{list<string>, string}>
     */
    public static function invalidHeaderProvider(): array {
        $tail = ['', 'Muster GmbH', self::IBAN];

        return [
            'service tag' => [['XYZ', '002', '1', 'SCT', ...$tail], InvalidEpcQrPayloadException::FIELD_SERVICE_TAG],
            'service tag lowercase' => [['bcd', '002', '1', 'SCT', ...$tail], InvalidEpcQrPayloadException::FIELD_SERVICE_TAG],
            'version' => [['BCD', '003', '1', 'SCT', ...$tail], InvalidEpcQrPayloadException::FIELD_VERSION],
            'charset zero' => [['BCD', '002', '0', 'SCT', ...$tail], InvalidEpcQrPayloadException::FIELD_CHARSET],
            'charset nine' => [['BCD', '002', '9', 'SCT', ...$tail], InvalidEpcQrPayloadException::FIELD_CHARSET],
            'charset word' => [['BCD', '002', 'UTF-8', 'SCT', ...$tail], InvalidEpcQrPayloadException::FIELD_CHARSET],
            'identification' => [['BCD', '002', '1', 'INST', ...$tail], InvalidEpcQrPayloadException::FIELD_IDENTIFICATION],
            'ends before iban' => [['BCD', '002', '1', 'SCT', '', 'Muster GmbH'], InvalidEpcQrPayloadException::FIELD_PAYLOAD],
            'thirteen lines' => [['BCD', '002', '1', 'SCT', '', 'Muster GmbH', self::IBAN, '', '', '', 'Miete', 'Danke', 'zu viel'], InvalidEpcQrPayloadException::FIELD_PAYLOAD],
        ];
    }

    /**
     * @param list<string> $lines
     */
    #[DataProvider('invalidHeaderProvider')]
    public function test_invalid_structure_is_refused(array $lines, string $field): void {
        $this->assertInvalid(self::payload($lines), $field);
    }

    public function test_payload_above_331_bytes_is_refused(): void {
        $lines = ['BCD', '002', '1', 'SCT', self::BIC, str_repeat('A', 70), self::IBAN, 'EUR999999999.99', 'GDDS', '', str_repeat('B', 140), str_repeat('C', 49)];
        $this->assertSame(EpcQrBuilder::MAX_BYTES + 1, strlen(self::payload($lines)));

        $this->assertInvalid(self::payload($lines), InvalidEpcQrPayloadException::FIELD_PAYLOAD);
    }

    public function test_byte_limit_counts_bytes_not_characters(): void {
        // 70 + 75 Zeichen, aber 290 Byte: zusammen über 331 Byte, obwohl jedes Feld in seiner Zeichengrenze bleibt.
        $lines = ['BCD', '002', '1', 'SCT', '', str_repeat('Ä', 70), self::IBAN, '', '', '', str_repeat('Ö', 75)];
        $this->assertGreaterThan(EpcQrBuilder::MAX_BYTES, strlen(self::payload($lines)));

        $this->assertInvalid(self::payload($lines), InvalidEpcQrPayloadException::FIELD_PAYLOAD);
    }

    public function test_invalid_utf8_is_refused(): void {
        $this->assertInvalid(self::payload(['BCD', '002', '1', 'SCT', '', "M\xFCller GmbH", self::IBAN]), InvalidEpcQrPayloadException::FIELD_CHARSET);
    }

    public function test_control_characters_and_stray_carriage_returns_are_refused(): void {
        $this->assertInvalid(self::payload(['BCD', '002', '1', 'SCT', '', "Muster\x07GmbH", self::IBAN]), InvalidEpcQrPayloadException::FIELD_NAME);
        $this->assertInvalid(self::payload(['BCD', '002', '1', 'SCT', '', "Muster\rGmbH", self::IBAN]), InvalidEpcQrPayloadException::FIELD_NAME);
    }

    public function test_empty_payload_is_refused(): void {
        $this->assertInvalid('', InvalidEpcQrPayloadException::FIELD_PAYLOAD);
    }

    public function test_exception_message_does_not_leak_field_content(): void {
        try {
            EpcQrParser::parse(self::payload(['BCD', '002', '1', 'SCT', '', 'Geheime Firma', 'KONTO 4711']));
            $this->fail('Exception erwartet.');
        } catch (InvalidEpcQrPayloadException $exception) {
            $this->assertInstanceOf(MalformedDocumentException::class, $exception);
            $this->assertStringNotContainsString('4711', $exception->getMessage());
            $this->assertStringNotContainsString('Geheime', $exception->getMessage());
        }
    }

    // ------------------------------------------------------------------
    // tryParse
    // ------------------------------------------------------------------

    public function test_try_parse_returns_null_for_foreign_or_broken_codes(): void {
        $this->assertNull(EpcQrParser::tryParse(null));
        $this->assertNull(EpcQrParser::tryParse(''));
        $this->assertNull(EpcQrParser::tryParse('https://example.org/'));
        $this->assertNull(EpcQrParser::tryParse(self::payload(['BCD', '002', '1', 'SCT', '', 'Muster GmbH', self::IBAN, 'EUR0'])));
    }

    public function test_try_parse_returns_the_payment_for_valid_codes(): void {
        $payment = EpcQrParser::tryParse(EpcQrBuilder::to('Muster GmbH', self::IBAN)->amount('10')->build());

        $this->assertNotNull($payment);
        $this->assertSame('10.00', $payment->getAmount());
    }

    // ------------------------------------------------------------------
    // Builder: Zeilenumbrüche in Feldern
    // ------------------------------------------------------------------

    public function test_builder_refuses_line_breaks_that_would_shift_fields(): void {
        $this->expectException(InvalidArgumentException::class);

        EpcQrBuilder::to("Muster GmbH\nDE89370400440532013000", self::IBAN);
    }

    public function test_builder_refuses_line_breaks_in_remittance(): void {
        $this->expectException(InvalidArgumentException::class);

        EpcQrBuilder::to('Muster GmbH', self::IBAN)->remittance("Rechnung 1\r\nDanke");
    }

    /**
     * @param list<string> $lines
     */
    private static function payload(array $lines): string {
        return implode("\n", $lines);
    }

    private function assertInvalid(string $payload, string $field): void {
        try {
            EpcQrParser::parse($payload);
            $this->fail("Exception für Feld '$field' erwartet.");
        } catch (InvalidEpcQrPayloadException $exception) {
            $this->assertSame($field, $exception->getField(), $exception->getMessage());
        }
        $this->assertNull(EpcQrParser::tryParse($payload));
    }
}
