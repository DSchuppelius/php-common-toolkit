<?php
/*
 * Created on   : Fri Sep 18 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CountryCodeLabelTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Enums;

use CommonToolkit\Enums\CountryCode;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

/**
 * CountryCode::getLabel() mit optionaler Locale (ICU via ext-intl) und
 * Fallback auf das gepflegte deutsche Label.
 */
class CountryCodeLabelTest extends BaseTestCase {
    public function test_without_locale_returns_german_label(): void {
        $this->assertSame('Deutschland', CountryCode::Germany->getLabel());
        $this->assertSame('Vereinigte Staaten von Amerika', CountryCode::UnitedStatesOfAmerica->getLabel());
        $this->assertSame('Nordirland (XI)', CountryCode::NorthernIreland->getLabel());
    }

    /**
     * @return array<string, array{CountryCode, string, string}>
     */
    public static function localizedProvider(): array {
        return [
            'de en' => [CountryCode::Germany, 'en', 'Germany'],
            'de fr' => [CountryCode::Germany, 'fr', 'Allemagne'],
            'de it' => [CountryCode::Germany, 'it', 'Germania'],
            'de es' => [CountryCode::Germany, 'es', 'Alemania'],
            'at en' => [CountryCode::Austria, 'en', 'Austria'],
            'at fr' => [CountryCode::Austria, 'fr', 'Autriche'],
            'ch it' => [CountryCode::Switzerland, 'it', 'Svizzera'],
            'ch es' => [CountryCode::Switzerland, 'es', 'Suiza'],
            'nl fr' => [CountryCode::Netherlands, 'fr', 'Pays-Bas'],
            'nl it' => [CountryCode::Netherlands, 'it', 'Paesi Bassi'],
            'es en' => [CountryCode::Spain, 'en', 'Spain'],
            'es es' => [CountryCode::Spain, 'es', 'España'],
            'fr en region' => [CountryCode::France, 'en_GB', 'France'],
            'xk en' => [CountryCode::Kosovo, 'en', 'Kosovo'],
            'de-CH bindestrich' => [CountryCode::Germany, 'de-CH', 'Deutschland'],
        ];
    }

    #[DataProvider('localizedProvider')]
    public function test_localized_label(CountryCode $country, string $locale, string $expected): void {
        $this->assertSame($expected, $country->getLabel($locale));
    }

    public function test_german_locale_uses_maintained_label(): void {
        // ICU: "Botsuana"/"Sonderverwaltungsregion Hongkong" – gepflegt: "Botswana"/"Hongkong".
        foreach (['de', 'DE', 'de_DE', 'de-DE', ' de '] as $locale) {
            $this->assertSame('Botswana', CountryCode::Botswana->getLabel($locale));
            $this->assertSame('Hongkong', CountryCode::HongKong->getLabel($locale));
        }
    }

    public function test_falls_back_to_german_when_intl_unknown(): void {
        // XI (Nordirland, USt-Präfix) kennt ICU nicht → Code zurück → deutsches Label.
        $this->assertSame('Nordirland (XI)', CountryCode::NorthernIreland->getLabel('en'));
        // Leere Locale liefert bei ICU den Code → Fallback.
        $this->assertSame('Deutschland', CountryCode::Germany->getLabel(''));
    }

    public function test_aliased_code_is_not_resolved_to_successor(): void {
        // ICU löst AN als Alias auf "Curaçao" auf – das wäre falsch.
        $this->assertSame('Niederländische Antillen', CountryCode::NetherlandsAntilles->getLabel('en'));
        $this->assertSame('Curaçao', CountryCode::Curaçao->getLabel('en'));
    }

    public function test_all_cases_are_alpha2_and_labeled_in_every_locale(): void {
        foreach (CountryCode::cases() as $case) {
            $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $case->value);
            foreach (['en', 'fr', 'it', 'es', 'de_CH'] as $locale) {
                $label = $case->getLabel($locale);
                $this->assertNotSame('', $label);
                $this->assertNotSame($case->value, $label, "{$case->value}/$locale");
            }
        }
    }
}
