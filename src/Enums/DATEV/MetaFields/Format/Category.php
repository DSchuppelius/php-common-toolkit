<?php
/*
 * Created on   : Mon Dec 01 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Category.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV\MetaFields\Format;

enum Category: int {
    case DebitorenKreditoren      = 16;
    case Sachkontenbeschriftungen = 20;
    case Buchungsstapel           = 21;
    case Zahlungsbedingungen      = 46;
    case DiverseAdressen          = 48;
    case WiederkehrendeBuchungen  = 65;
    case NaturalStapel            = 66;

    public function nameValue(): string {
        return match ($this) {
            self::DebitorenKreditoren      => 'Debitoren/Kreditoren',
            self::Sachkontenbeschriftungen => 'Kontenbeschriftungen',
            self::Buchungsstapel           => 'Buchungsstapel',
            self::Zahlungsbedingungen      => 'Zahlungsbedingungen',
            self::DiverseAdressen          => 'Diverse Adressen',
            self::WiederkehrendeBuchungen  => 'Wiederkehrende Buchungen',
            self::NaturalStapel            => 'Naturalstapel',
        };
    }

    /**
     * Liefert alle Kategorien als Liste (z. B. für Pattern-Erzeugung).
     */
    public static function values(): array {
        return array_map(static fn(self $c) => $c->value, self::cases());
    }

    public static function names(): array {
        return array_map(static fn(self $c) => $c->nameValue(), self::cases());
    }
}