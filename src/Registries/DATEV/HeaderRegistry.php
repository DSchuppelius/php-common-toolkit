<?php
/*
 * Created on   : Mon Dec 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HeaderRegistry.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Registries\DATEV;

use CommonToolkit\Contracts\Interfaces\DATEV\{HeaderDefinitionInterface, MetaHeaderDefinitionInterface};
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\DATEV\Header\V700\{
    MetaHeaderDefinition as MetaHeaderDefinition700,
    BookingBatchHeaderDefinition,
    DebitorsCreditorsHeaderDefinition,
    VariousAddressesHeaderDefinition,
    GLAccountDescriptionHeaderDefinition,
    RecurringBookingsHeaderDefinition,
    PaymentTermsHeaderDefinition,
    NaturalStackHeaderDefinition
};
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;
use RuntimeException;

final class HeaderRegistry {
    /** @var array<int, class-string<MetaHeaderDefinitionInterface>> */
    private static array $definitions = [
        700 => MetaHeaderDefinition700::class,
        // später weitere Versionen hinzufügen
    ];

    /** @var array<int, array<int, class-string<HeaderDefinitionInterface>>> */
    private static array $formatDefinitions = [];

    private static function initializeFormatDefinitions(): void {
        if (empty(self::$formatDefinitions)) {
            self::$formatDefinitions = [
                700 => [
                    Category::Buchungsstapel->value => BookingBatchHeaderDefinition::class,
                    Category::DebitorenKreditoren->value => DebitorsCreditorsHeaderDefinition::class,
                    Category::DiverseAdressen->value => VariousAddressesHeaderDefinition::class,
                    Category::Sachkontenbeschriftungen->value => GLAccountDescriptionHeaderDefinition::class,
                    Category::WiederkehrendeBuchungen->value => RecurringBookingsHeaderDefinition::class,
                    Category::Zahlungsbedingungen->value => PaymentTermsHeaderDefinition::class,
                    Category::NaturalStapel->value => NaturalStackHeaderDefinition::class,
                ]
            ];
        }
    }

    public static function get(int $version): MetaHeaderDefinitionInterface {
        $class = self::$definitions[$version] ?? null;
        if (!$class) {
            throw new RuntimeException("Keine DATEV-Headerdefinition für Version {$version} registriert.");
        }
        return new $class;
    }

    /**
     * Liefert die Format-spezifische Header-Definition für eine Kategorie und Version.
     */
    public static function getFormatDefinition(Category $category, int $version): HeaderDefinitionInterface {
        self::initializeFormatDefinitions();
        $class = self::$formatDefinitions[$version][$category->value] ?? null;
        if (!$class) {
            throw new RuntimeException(
                "Keine Header-Definition für Format '{$category->nameValue()}' Version {$version} registriert."
            );
        }
        return new $class;
    }

    /**
     * Prüft ob eine Format/Version-Kombination unterstützt wird.
     */
    public static function isFormatSupported(Category $category, int $version): bool {
        self::initializeFormatDefinitions();
        return isset(self::$formatDefinitions[$version][$category->value]);
    }

    /**
     * Automatische Erkennung aus dem rohen Werte-Array.
     * Prüft die Versionsnummer an Position 1 (feste DATEV-Struktur).
     */
    public static function detectFromValues(array $values): ?MetaHeaderDefinitionInterface {
        // Versionsnummer muss an Position 1 stehen (DATEV-Standard)
        if (isset($values[1]) && preg_match('/^\d+$/', (string)$values[1])) {
            $version = (int)$values[1];
            if (isset(self::$definitions[$version])) {
                return self::get($version);
            }
        }

        return null;
    }

    /**
     * Automatische Erkennung direkt aus einer geparsten DataLine.
     * Prüft die Versionsnummer an Position 1 (feste DATEV-Struktur).
     */
    public static function detectFromDataLine(DataLine $dataLine): ?MetaHeaderDefinitionInterface {
        $fields = $dataLine->getFields();

        // Versionsnummer an Position 1 prüfen (DATEV-Standard)
        if (isset($fields[1])) {
            $versionValue = $fields[1]->getValue();
            if (preg_match('/^\d+$/', $versionValue)) {
                $version = (int)$versionValue;
                if (isset(self::$definitions[$version])) {
                    return self::get($version);
                }
            }
        }

        return null;
    }

    /**
     * Registriert eine neue Header-Definition für eine Version.
     */
    public static function register(int $version, string $definitionClass): void {
        if (!is_subclass_of($definitionClass, MetaHeaderDefinitionInterface::class)) {

            throw new RuntimeException("Definition class must implement MetaHeaderDefinitionInterface");
        }
        self::$definitions[$version] = $definitionClass;
    }

    /**
     * Gibt alle registrierten Versionen zurück.
     */
    public static function getSupportedVersions(): array {
        return array_keys(self::$definitions);
    }
}