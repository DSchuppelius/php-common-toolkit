<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : IbanTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\ValueObjects\Iban;
use InvalidArgumentException;
use JsonSerializable;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Stringable;
use Tests\Contracts\BaseTestCase;

class IbanTest extends BaseTestCase {
    // ==================== Konstruktion / Normalisierung ====================

    public function test_of_normalizes_case_and_whitespace(): void {
        $iban = Iban::of('de89 3704 0044 0532 0130 00');
        $this->assertSame('DE89370400440532013000', $iban->getValue());
    }

    #[DataProvider('validIbanProvider')]
    public function test_of_accepts_valid_ibans_of_multiple_countries(string $input, string $expectedCountry): void {
        $iban = Iban::of($input);
        $this->assertSame($expectedCountry, $iban->getCountry()->name);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function validIbanProvider(): array {
        return [
            'Deutschland' => ['DE89370400440532013000', 'Germany'],
            'Österreich' => ['AT611904300234573201', 'Austria'],
            'Frankreich' => ['FR1420041010050500013M02606', 'France'],
            'Großbritannien' => ['GB29NWBK60161331926819', 'UnitedKingdomOfGreatBritainAndNorthernIreland'],
            'Schweiz' => ['CH9300762011623852957', 'Switzerland'],
        ];
    }

    public function test_of_rejects_invalid_checksum(): void {
        $this->expectException(InvalidArgumentException::class);
        Iban::of('DE89370400440532013001');
    }

    public function test_of_rejects_wrong_length_for_country(): void {
        $this->expectException(InvalidArgumentException::class);
        Iban::of('DE8937040044053201300');
    }

    public function test_of_rejects_masked_iban(): void {
        $this->expectException(InvalidArgumentException::class);
        Iban::of('DE89 3704 XXXX XXXX XXXX 00');
    }

    public function test_of_rejects_garbage(): void {
        $this->expectException(InvalidArgumentException::class);
        Iban::of('keine IBAN');
    }

    public function test_try_from(): void {
        $this->assertNull(Iban::tryFrom(null));
        $this->assertNull(Iban::tryFrom(''));
        $this->assertNull(Iban::tryFrom('DE89370400440532013001'));

        $iban = Iban::tryFrom('DE89 3704 0044 0532 0130 00');
        $this->assertNotNull($iban);
        $this->assertSame('DE89370400440532013000', $iban->getValue());
    }

    // ==================== Formatierung / Maskierung ====================

    public function test_formatted_groups_of_four_from_left(): void {
        $iban = Iban::of('DE89370400440532013000');
        $this->assertSame('DE89 3704 0044 0532 0130 00', $iban->formatted());
        $this->assertSame('DE89-3704-0044-0532-0130-00', $iban->formatted('-'));
    }

    public function test_masked_defaults(): void {
        $masked = Iban::of('DE89370400440532013000')->masked();

        $this->assertSame('DE89XXXXXXXXXXXXXX3000', $masked);
        $this->assertSame(22, strlen($masked), 'Länge bleibt erhalten.');
        $this->assertSame(14, substr_count($masked, 'X'), 'Nur 8 Zeichen bleiben sichtbar.');
    }

    public function test_masked_with_custom_visibility(): void {
        $this->assertSame('DEXXXXXXXXXXXXXXXXXX00', Iban::of('DE89370400440532013000')->masked(2, 2));
    }

    public function test_masked_rejects_revealing_everything(): void {
        $this->expectException(InvalidArgumentException::class);
        Iban::of('DE89370400440532013000')->masked(11, 11);
    }

    // ==================== Fachlicher Zugriff ====================

    public function test_get_country(): void {
        $this->assertSame(CountryCode::Germany, Iban::of('DE89370400440532013000')->getCountry());
    }

    public function test_is_sepa(): void {
        $this->assertTrue(Iban::of('DE89370400440532013000')->isSepa());
        $this->assertTrue(Iban::of('CH9300762011623852957')->isSepa(), 'Schweiz ist SEPA, aber nicht EU.');
    }

    public function test_bank_code_and_account_number(): void {
        $iban = Iban::of('DE89370400440532013000');
        $this->assertSame('37040044', $iban->getBankCode());
        $this->assertSame('0532013000', $iban->getAccountNumber());
    }

    // ==================== Gleichheit / Sensibilität ====================

    public function test_equals_ignores_input_formatting(): void {
        $a = Iban::of('DE89 3704 0044 0532 0130 00');
        $b = Iban::of('de89370400440532013000');
        $c = Iban::of('AT611904300234573201');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_no_string_or_json_interface(): void {
        $reflection = new ReflectionClass(Iban::class);
        $this->assertFalse($reflection->implementsInterface(Stringable::class), 'Sensibler Wert darf nicht implizit zum String werden.');
        $this->assertFalse($reflection->implementsInterface(JsonSerializable::class), 'Sensibler Wert darf nicht implizit ins JSON gelangen.');
        $this->assertFalse($reflection->hasMethod('__toString'));
    }
}
