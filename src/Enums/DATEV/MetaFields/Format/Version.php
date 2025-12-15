<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Version.php
 * License      : MIT License
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV\MetaFields\Format;

enum Version: int {
    case V2  = 2;
    case V3  = 3;
    case V4  = 4;
    case V5  = 5;
    case V13 = 13;

    /**
     * Zuordnung Formatkategorie -> Header-Formatversion (V700).
     */
    public static function forCategory(Category $category): self {
        return match ($category) {
            Category::DebitorenKreditoren      => self::V5,   // 5 = Debitoren/Kreditoren
            Category::Sachkontenbeschriftungen => self::V3,   // 3 = Sachkontenbeschriftungen
            Category::Buchungsstapel           => self::V13,  // 13 = Buchungsstapel
            Category::Zahlungsbedingungen      => self::V2,   // 2 = Zahlungsbedingungen
            Category::DiverseAdressen          => self::V2,   // 2 = Diverse Adressen
            Category::WiederkehrendeBuchungen  => self::V4,   // 4 = Wiederkehrende Buchungen
            Category::NaturalStapel            => self::V2,   // 2 = Natural-Stapel
        };
    }

    /**
     * Regex für Feld 5 (Formatversion) im Metaheader.
     */
    public static function pattern(): string {
        return '^(2|3|4|5|13)$';
    }
}