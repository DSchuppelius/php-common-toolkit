<?php
/*
 * Created on   : Mon Oct 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PersonNameHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\PersonNameHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

class PersonNameHelperTest extends BaseTestCase {
    /** @return array<string, array{0: ?string, 1: array{salutation: ?string, title: ?string, first_name: ?string, middle_names: ?string, last_name: ?string}}> */
    public static function names(): array {
        $p = static fn (?string $salutation, ?string $title, ?string $first, ?string $middle, ?string $last): array => [
            'salutation' => $salutation, 'title' => $title, 'first_name' => $first, 'middle_names' => $middle, 'last_name' => $last,
        ];

        return [
            'leer' => [null, $p(null, null, null, null, null)],
            'nur Leerraum' => ['   ', $p(null, null, null, null, null)],
            'Vor- und Nachname' => ['Max Mustermann', $p(null, null, 'Max', null, 'Mustermann')],
            'weitere Vornamen' => ['Anna Maria Luise Müller', $p(null, null, 'Anna', 'Maria Luise', 'Müller')],
            'nur Nachname' => ['Müller', $p(null, null, null, null, 'Müller')],
            'Anrede Herr' => ['Herr Max Müller', $p('mr', null, 'Max', null, 'Müller')],
            'Anrede Frau ohne Vorname' => ['Frau Schmidt', $p('ms', null, null, null, 'Schmidt')],
            'Anrede abgekürzt' => ['Hr. Jan Meier', $p('mr', null, 'Jan', null, 'Meier')],
            'Anrede und Titel' => ['Frau Prof. Dr. Eva Weiß', $p('ms', 'Prof. Dr.', 'Eva', null, 'Weiß')],
            'Titel Dipl.-Ing.' => ['Dipl.-Ing. Karl Berg', $p(null, 'Dipl.-Ing.', 'Karl', null, 'Berg')],
            'Titel ohne Punkt' => ['Dr Tom Bauer', $p(null, 'Dr', 'Tom', null, 'Bauer')],
            'Vorname wie Titelwort' => ['Ingrid Hahn', $p(null, null, 'Ingrid', null, 'Hahn')],
            'englisch' => ['Mrs Jane Doe', $p('ms', null, 'Jane', null, 'Doe')],
            'französisch' => ['M. Jean Dupont', $p('mr', null, 'Jean', null, 'Dupont')],
            'französisch Madame' => ['Mme Claire Martin', $p('ms', null, 'Claire', null, 'Martin')],
            'Initiale ohne Punkt' => ['M Dupont', $p(null, null, 'M', null, 'Dupont')],
            'spanisch' => ['Sra. Lucía García', $p('ms', null, 'Lucía', null, 'García')],
            'italienisch' => ['Sig.ra Giulia Rossi', $p('ms', null, 'Giulia', null, 'Rossi')],
            'Namenszusatz von der' => ['Anna von der Leyen', $p(null, null, 'Anna', null, 'von der Leyen')],
            'Namenszusatz van' => ['Ludwig van Beethoven', $p(null, null, 'Ludwig', null, 'van Beethoven')],
            'Zusatz ohne Vorname' => ['Herr von Müller', $p('mr', null, null, null, 'von Müller')],
            'Doppelname' => ['Jean-Luc Müller-Lüdenscheidt', $p(null, null, 'Jean-Luc', null, 'Müller-Lüdenscheidt')],
            'Komma-Form' => ['Müller, Anna Maria', $p(null, null, 'Anna', 'Maria', 'Müller')],
            'Komma-Form mit Anrede' => ['Schulz, Herr Dr. Peter', $p('mr', 'Dr.', 'Peter', null, 'Schulz')],
            'Ehepaar' => ['Herr und Frau Müller', $p(null, null, null, null, 'Müller')],
            'Leerraum' => ["  Herr   Max \t Müller ", $p('mr', null, 'Max', null, 'Müller')],
            'Anredewort allein' => ['Herr', $p(null, null, null, null, 'Herr')],
        ];
    }

    /** @param array{salutation: ?string, title: ?string, first_name: ?string, middle_names: ?string, last_name: ?string} $expected */
    #[DataProvider('names')]
    public function test_split(?string $name, array $expected): void {
        $this->assertSame($expected, PersonNameHelper::split($name));
    }

    public function test_split_salutation(): void {
        $this->assertSame(['salutation' => 'mr', 'title' => 'Dr.'], PersonNameHelper::splitSalutation('Herr Dr.'));
        $this->assertSame(['salutation' => 'ms', 'title' => null], PersonNameHelper::splitSalutation(' frau '));
        $this->assertSame(['salutation' => null, 'title' => 'Prof. Dr.'], PersonNameHelper::splitSalutation('Prof. Dr.'));
        $this->assertSame(['salutation' => null, 'title' => null], PersonNameHelper::splitSalutation('Firma'));
        $this->assertSame(['salutation' => null, 'title' => null], PersonNameHelper::splitSalutation(null));
    }

    public function test_join_leaves_out_salutation_and_empty_parts(): void {
        $this->assertSame('Dr. Anna Maria Müller', PersonNameHelper::join([
            'salutation' => 'ms', 'title' => 'Dr.', 'first_name' => 'Anna', 'middle_names' => ' Maria ', 'last_name' => 'Müller',
        ]));
        $this->assertSame('Müller', PersonNameHelper::join(['first_name' => '', 'last_name' => 'Müller']));
        $this->assertSame('', PersonNameHelper::join([]));
    }

    public function test_join_restores_split_names(): void {
        foreach (['Max Mustermann', 'Prof. Dr. Eva Weiß', 'Anna Maria von der Leyen'] as $name) {
            $this->assertSame($name, PersonNameHelper::join(PersonNameHelper::split($name)));
        }
    }
}
