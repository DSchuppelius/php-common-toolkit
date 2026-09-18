<?php
/*
 * Created on   : Fri Sep 18 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StringHelperParseBoolTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\StringHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Stringable;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für StringHelper::parseBool() – toleranter Boolean-Import (DE/EN/FR/IT/ES).
 */
class StringHelperParseBoolTest extends BaseTestCase {
    /**
     * @return array<string, array{mixed}>
     */
    public static function truthyProvider(): array {
        $cases = [];
        foreach (['1', 'true', 'yes', 'y', 'on', 'ja', 'j', 'wahr', 'x', 'oui', 'si', 'sì', 'sí', 'vero', 'verdadero', 'active', 'aktiv'] as $value) {
            $cases["'$value'"] = [$value];
        }
        $cases['gross'] = ['TRUE'];
        $cases['gemischt'] = ['Ja'];
        $cases['unicode gross'] = ['SÌ'];
        $cases['getrimmt'] = ["  yes \t"];
        $cases['bool'] = [true];
        $cases['int'] = [1];
        $cases['float'] = [1.0];

        return $cases;
    }

    #[DataProvider('truthyProvider')]
    public function test_truthy_values(mixed $value): void {
        $this->assertTrue(StringHelper::parseBool($value));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function falsyProvider(): array {
        $cases = [];
        foreach (['0', 'false', 'no', 'n', 'off', 'nein', 'falsch', 'non', 'falso', 'inactive', 'inaktiv'] as $value) {
            $cases["'$value'"] = [$value];
        }
        $cases['gross'] = ['NEIN'];
        $cases['getrimmt'] = [' Off '];
        $cases['bool'] = [false];
        $cases['int'] = [0];
        $cases['float'] = [0.0];
        $cases['negative null'] = [-0.0];

        return $cases;
    }

    #[DataProvider('falsyProvider')]
    public function test_falsy_values(mixed $value): void {
        $this->assertFalse(StringHelper::parseBool($value));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function unknownProvider(): array {
        return [
            'null' => [null],
            'leer' => [''],
            'nur leerraum' => ["  \t\n"],
            'unbekannt' => ['vielleicht'],
            'andere zahl' => [2],
            'negativ' => [-1],
            'bruch' => [0.5],
            'nan' => [NAN],
            'zahlstring' => ['2'],
            'array' => [['1']],
            'objekt' => [new \stdClass],
        ];
    }

    #[DataProvider('unknownProvider')]
    public function test_unknown_values_return_default(mixed $value): void {
        $this->assertNull(StringHelper::parseBool($value));
        $this->assertTrue(StringHelper::parseBool($value, true));
        $this->assertFalse(StringHelper::parseBool($value, false));
    }

    public function test_bool_ignores_default(): void {
        $this->assertTrue(StringHelper::parseBool(true, false));
        $this->assertFalse(StringHelper::parseBool('nein', true));
    }

    public function test_stringable_is_parsed(): void {
        $value = new class implements Stringable {
            public function __toString(): string {
                return ' Ja ';
            }
        };

        $this->assertTrue(StringHelper::parseBool($value));
    }

    public function test_custom_lists_replace_defaults(): void {
        // Eigene Wahr-Liste: "yes" gilt dann nicht mehr als wahr.
        $this->assertNull(StringHelper::parseBool('yes', null, ['available', 'free']));
        $this->assertTrue(StringHelper::parseBool(' AVAILABLE ', null, ['available', 'free']));
        // Falsch-Liste bleibt Standard, solange sie nicht übergeben wird.
        $this->assertFalse(StringHelper::parseBool('no', null, ['available']));
        // Eigene Falsch-Liste; Einträge werden ebenfalls normalisiert.
        $this->assertFalse(StringHelper::parseBool('taken', null, ['available'], [' TAKEN ']));
        $this->assertNull(StringHelper::parseBool('no', null, ['available'], ['taken']));
        // Leere Listen: nur noch bool/int/float und $default.
        $this->assertNull(StringHelper::parseBool('true', null, [], []));
        $this->assertTrue(StringHelper::parseBool(1, null, [], []));
    }

    public function test_empty_string_is_default_even_if_listed(): void {
        $this->assertNull(StringHelper::parseBool('', null, [''], ['']));
    }

    public function test_covers_all_app_variants(): void {
        // Werte aus den abgelösten App-Varianten (AbstractEntitySpec, ChartOfAccountsService,
        // DomainAvailability/DomainSync, Toggl/Kimai/Clockify-CSV-Parser).
        foreach (['1', 'ja', 'yes', 'true', 'wahr', 'y', 'j', 'x', 'active', 'oui', 'sì', 'si'] as $value) {
            $this->assertTrue(StringHelper::parseBool($value), $value);
        }
    }
}
