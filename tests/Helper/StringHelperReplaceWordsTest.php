<?php
/*
 * Created on   : Mon Aug 03 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StringHelperReplaceWordsTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Helper\Data\StringHelper;
use Tests\Contracts\BaseTestCase;

class StringHelperReplaceWordsTest extends BaseTestCase {
    public function test_simple_replacement(): void {
        $this->assertEquals(
            'Serverwartung durchgeführt',
            StringHelper::replaceWords('Serverwartunng durchgeführt', ['serverwartunng' => 'Serverwartung'])
        );
    }

    public function test_no_substring_match(): void {
        $map = ['kauf' => 'Kauf'];
        $this->assertEquals('Verkauf abgeschlossen', StringHelper::replaceWords('Verkauf abgeschlossen', $map));
        $this->assertEquals('Einkaufsliste', StringHelper::replaceWords('Einkaufsliste', $map));
    }

    public function test_umlaut_word_boundaries(): void {
        // PCRE-\b würde an Umlauten falsche Grenzen setzen — Lookarounds nicht.
        $this->assertEquals(
            'Bürostuhl für Büro',
            StringHelper::replaceWords('Bürosttuhl für Büro', ['bürosttuhl' => 'Bürostuhl'])
        );
        // "tag" darf in "Beiträge" (via ASCII-\b: Grenze vor "träge") nicht matchen
        $this->assertEquals('Beiträge geprüft', StringHelper::replaceWords('Beiträge geprüft', ['träge' => 'X']));
    }

    public function test_punctuation_and_hyphen_adjacency(): void {
        $map = ['emial' => 'E-Mail'];
        $this->assertEquals('E-Mail-Adresse geändert.', StringHelper::replaceWords('Emial-Adresse geändert.', $map));
        $this->assertEquals('Neue E-Mail!', StringHelper::replaceWords('Neue Emial!', $map));
        $this->assertEquals('(E-Mail)', StringHelper::replaceWords('(Emial)', $map));
    }

    public function test_case_preservation(): void {
        $map = ['serverwartunng' => 'serverwartung'];
        $this->assertEquals('SERVERWARTUNG', StringHelper::replaceWords('SERVERWARTUNNG', $map));
        $this->assertEquals('Serverwartung', StringHelper::replaceWords('Serverwartunng', $map));
        $this->assertEquals('serverwartung', StringHelper::replaceWords('serverwartunng', $map));
    }

    public function test_mixed_case_source_keeps_replacement(): void {
        $this->assertEquals('GitHub', StringHelper::replaceWords('GiTHub', ['github' => 'GitHub']));
    }

    public function test_lowercase_source_keeps_curated_replacement(): void {
        // Kuratierte Schreibweisen werden nie abgesenkt: lower-Quelle behält Ersetzungs-Case
        $this->assertEquals('GitHub', StringHelper::replaceWords('githup', ['githup' => 'GitHub']));
        $this->assertEquals('E-Mail senden', StringHelper::replaceWords('emial senden', ['emial' => 'E-Mail']));
    }

    public function test_multi_word_phrase_with_flexible_whitespace(): void {
        $map = ['vor ort termin' => 'Vor-Ort-Termin'];
        $this->assertEquals('Vor-Ort-Termin vereinbart', StringHelper::replaceWords('Vor Ort Termin vereinbart', $map));
        $this->assertEquals('Vor-Ort-Termin vereinbart', StringHelper::replaceWords("vor  ort\tTermin vereinbart", $map));
    }

    public function test_longest_key_wins(): void {
        $map = ['server wartung' => 'Serverwartung', 'server' => 'Server'];
        $this->assertEquals('Serverwartung erledigt', StringHelper::replaceWords('server wartung erledigt', $map));
    }

    public function test_case_sensitive_mode(): void {
        $map = ['IT' => 'Informationstechnik'];
        $this->assertEquals('Informationstechnik-Abteilung', StringHelper::replaceWords('IT-Abteilung', $map, true));
        $this->assertEquals('Mit it arbeiten', StringHelper::replaceWords('Mit it arbeiten', $map, true));
    }

    public function test_preserve_case_disabled(): void {
        $this->assertEquals(
            'E-Mail geschrieben',
            StringHelper::replaceWords('EMIAL geschrieben', ['emial' => 'E-Mail'], false, false)
        );
    }

    public function test_empty_map_and_empty_text(): void {
        $this->assertEquals('Text bleibt', StringHelper::replaceWords('Text bleibt', []));
        $this->assertEquals('', StringHelper::replaceWords('', ['a' => 'b']));
        $this->assertEquals('Text bleibt', StringHelper::replaceWords('Text bleibt', ['' => 'x', '   ' => 'y']));
    }

    public function test_multiple_occurrences_and_umlaut_case_folding(): void {
        $this->assertEquals(
            'Übergabe und Übergabe',
            StringHelper::replaceWords('übergabe und ÜBERGABE', ['übergabe' => 'Übergabe'], false, false)
        );
    }

    public function test_large_map_chunking_smoke(): void {
        $map = [];
        for ($i = 0; $i < 1203; $i++) {
            $map["fehlerwort{$i}x"] = "korrektur{$i}";
        }
        $map['serverwartunng'] = 'Serverwartung';

        $result = StringHelper::replaceWords('fehlerwort42x und Serverwartunng und fehlerwort1200x', $map);
        $this->assertEquals('korrektur42 und Serverwartung und korrektur1200', $result);
    }

    public function test_match_case_directly(): void {
        $this->assertEquals('WARTUNG', StringHelper::matchCase('FEHLER', 'Wartung'));
        $this->assertEquals('Wartung', StringHelper::matchCase('fehler', 'Wartung'));
        $this->assertEquals('Wartung', StringHelper::matchCase('Fehler', 'wartung'));
        $this->assertEquals('E-Mail', StringHelper::matchCase('Emial', 'E-Mail'));
        $this->assertEquals('Wartung', StringHelper::matchCase('FeHlEr', 'Wartung'));
        $this->assertEquals('Wartung', StringHelper::matchCase('', 'Wartung'));
        $this->assertEquals('', StringHelper::matchCase('Fehler', ''));
        // Einzelner Großbuchstabe zählt als Title, nicht als UPPER
        $this->assertEquals('Wartung', StringHelper::matchCase('F', 'wartung'));
    }
}
