<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CheckDigitHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\CheckDigitHelper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

/**
 * ISO/IEC 7064 MOD 97-10. Die Referenzwerte sind Rechenbeispiele der Normen
 * bzw. Spezifikationen oder erfunden und unabhängig nachgerechnet.
 */
class CheckDigitHelperTest extends BaseTestCase {
    /**
     * Nutzdaten in der Reihenfolge, in der das Format rechnet, und die Prüfziffern.
     *
     * @return array<string, array{string, string}>
     */
    public static function references(): array {
        return [
            'Leitweg-ID, Beispiel der Formatspezifikation 2.0.2' => ['040110001234512345', '06'],
            'Leitweg-ID, kurze Feinadressierung' => ['0401100012345', '03'],
            'Leitweg-ID mit Buchstaben' => ['99133333TEST', '33'],
            'IBAN DE89 (Bundesbank-Beispiel), umgestellt' => ['370400440532013000DE', '89'],
            'IBAN GB29 (ISO 13616), umgestellt' => ['NWBK60161331926819GB', '29'],
            'IBAN erfunden, Prüfziffer mit führender Null' => ['123456780001234567DE', '09'],
            'RF-Referenz (ISO 11649), umgestellt' => ['539007547034RF', '18'],
            'Gläubiger-ID DE98ZZZ09999999999 (Bundesbank), umgestellt' => ['09999999999DE', '98'],
            'LEI erfunden' => ['984500ABCDEF12345X', '24'],
        ];
    }

    #[DataProvider('references')]
    public function test_check_digits_match_the_reference(string $payload, string $checkDigits): void {
        $this->assertSame($checkDigits, CheckDigitHelper::mod97CheckDigits($payload));
        $this->assertTrue(CheckDigitHelper::isValidMod97($payload . $checkDigits));
        $this->assertSame(1, CheckDigitHelper::mod97Remainder($payload . $checkDigits));
    }

    #[DataProvider('references')]
    public function test_wrong_check_digits_are_rejected(string $payload, string $checkDigits): void {
        $wrong = sprintf('%02d', ((int) $checkDigits + 1) % 100);

        $this->assertFalse(CheckDigitHelper::isValidMod97($payload . $wrong));
        $this->assertNotSame(1, CheckDigitHelper::mod97Remainder($payload . $wrong));
    }

    public function test_transposed_digits_are_detected(): void {
        $this->assertTrue(CheckDigitHelper::isValidMod97('04011000123451234506'));
        $this->assertFalse(CheckDigitHelper::isValidMod97('04011000123451235406'));
        $this->assertFalse(CheckDigitHelper::isValidMod97('04011000123451234560'));
    }

    public function test_letters_count_as_ten_to_thirty_five_in_either_case(): void {
        $this->assertSame(10, CheckDigitHelper::mod97Remainder('A'));
        $this->assertSame(35, CheckDigitHelper::mod97Remainder('Z'));
        $this->assertSame(10, CheckDigitHelper::mod97Remainder('a'));
        $this->assertSame(1011 % 97, CheckDigitHelper::mod97Remainder('AB'));
        $this->assertSame(0, CheckDigitHelper::mod97Remainder('97'));
        $this->assertSame(1, CheckDigitHelper::mod97Remainder('98'));

        $this->assertSame('29', CheckDigitHelper::mod97CheckDigits('nwbk60161331926819gb'));
        $this->assertTrue(CheckDigitHelper::isValidMod97('99133333test33'));
    }

    public function test_every_computed_check_digit_validates(): void {
        mt_srand(7064);
        $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i < 300; $i++) {
            $payload = '';
            for ($length = mt_rand(1, 40); $length > 0; $length--) {
                $payload .= $alphabet[mt_rand(0, 35)];
            }

            $checkDigits = CheckDigitHelper::mod97CheckDigits($payload);
            $this->assertMatchesRegularExpression('/^(0[2-9]|[1-8][0-9]|9[0-8])$/', $checkDigits, $payload);
            $this->assertTrue(CheckDigitHelper::isValidMod97($payload . $checkDigits), $payload);
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidInputs(): array {
        return [
            'leer' => [''],
            'Leerzeichen' => ['DE89 3704'],
            'Bindestrich' => ['04011000-12345'],
            'Umlaut' => ['ÄMT'],
            'Vorzeichen' => ['+12'],
            'Dezimalpunkt' => ['1.5'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_other_characters_are_not_valid(string $value): void {
        $this->assertFalse(CheckDigitHelper::isValidMod97($value));
    }

    #[DataProvider('invalidInputs')]
    public function test_remainder_rejects_other_characters(string $value): void {
        $this->expectException(InvalidArgumentException::class);
        CheckDigitHelper::mod97Remainder($value);
    }

    #[DataProvider('invalidInputs')]
    public function test_check_digits_reject_other_characters(string $value): void {
        $this->expectException(InvalidArgumentException::class);
        CheckDigitHelper::mod97CheckDigits($value);
    }
}
