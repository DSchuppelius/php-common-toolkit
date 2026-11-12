<?php
/*
 * Created on   : Mon Oct 19 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StringHelperHtmlToTextTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Helper\Data\StringHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

class StringHelperHtmlToTextTest extends BaseTestCase {
    public function test_empty_and_null_give_empty_string(): void {
        $this->assertSame('', StringHelper::htmlToText(null));
        $this->assertSame('', StringHelper::htmlToText(''));
        $this->assertSame('', StringHelper::htmlToText('<p>&nbsp;</p>'));
    }

    public function test_outlook_mail_keeps_lines_and_decodes_entities(): void {
        $html = "<html><head><style>p{margin:0}</style></head><body lang=DE>\r\n"
            . "<p class=MsoNormal>Hallo Simone,<o:p></o:p></p>\r\n"
            . "<p class=MsoNormal><o:p>&nbsp;</o:p></p>\r\n"
            . "<p class=MsoNormal>sorry,&nbsp;war durchgerutscht &amp; ist &uuml;berwiesen.</p>\r\n"
            . "<p class=MsoNormal>Gru&szlig; Ralph</p>\r\n</body></html>";

        $this->assertSame("Hallo Simone,\n\nsorry, war durchgerutscht & ist überwiesen.\nGruß Ralph", StringHelper::htmlToText($html));
    }

    public function test_gmail_divs_breaks_and_rules_become_lines(): void {
        $html = '<div dir="ltr">Hallo<div><br></div><div>Zeile 2<br>Zeile 3</div><hr>Fuß</div>';

        $this->assertSame("Hallo\n\nZeile 2\nZeile 3\n\nFuß", StringHelper::htmlToText($html));
    }

    public function test_lists_get_dashes_and_table_cells_a_space(): void {
        $html = '<p>Beton &amp; Stahl</p><ul><li>Decke</li><li>Wände</li></ul>'
            . '<table><tr><td>Tel.</td><td>+49 30 1</td></tr><tr><th>Fax</th><td>+49 30 2</td></tr></table>';

        $this->assertSame("Beton & Stahl\n\n- Decke\n- Wände\nTel. +49 30 1\nFax +49 30 2", StringHelper::htmlToText($html));
    }

    public function test_invisible_blocks_and_comments_disappear(): void {
        $html = '<head><title>x</title></head><!-- intern --><p>Sichtbar</p><script>alert(1)</script><style>.a{}</style><template><p>nie</p></template>';

        $this->assertSame('Sichtbar', StringHelper::htmlToText($html));
    }

    public function test_source_line_breaks_are_whitespace_like_in_a_browser(): void {
        $this->assertSame('Zeile eins zwei', StringHelper::htmlToText("<p>Zeile\n  eins\r\n\tzwei</p>"));
        $this->assertSame('ohne Tags', StringHelper::htmlToText("ohne\nTags"));
    }

    public function test_tags_are_stripped_before_entities_are_decoded(): void {
        $this->assertSame('Code: <b>fett</b> & "zitiert"', StringHelper::htmlToText('<p>Code: &lt;b&gt;fett&lt;/b&gt; &amp; &quot;zitiert&quot;</p>'));
    }

    public function test_more_than_one_blank_line_collapses(): void {
        $this->assertSame("a\n\nb", StringHelper::htmlToText('<p>a</p><p>&nbsp;</p><p>&nbsp;</p><br><br><p>b</p>'));
        $this->assertSame("a\nb", StringHelper::htmlToText("<div>a</div>\n<div>b</div>"));
    }

    public function test_unicode_spaces_collapse_within_a_line(): void {
        $this->assertSame('ein zwei drei', StringHelper::htmlToText("<p>ein\u{00A0}\u{00A0}zwei\u{202F} drei</p>"));
    }

    // ------------------------------------------------------------------
    // Nicht geschlossene Blöcke: Ergebnis wie bisher, Laufzeit linear (2.17.1)
    // ------------------------------------------------------------------

    public function test_unclosed_blocks_keep_their_previous_result(): void {
        // Ohne Endtag bleibt der Block stehen (wie der frühere reguläre Ausdruck); nur das Tag fällt weg.
        $this->assertSame("Hallo\nbody{}", StringHelper::htmlToText('<p>Hallo</p><style>body{}'));
        $this->assertSame('b', StringHelper::htmlToText('<style>a</style><style>b'));
        // Ein geschlossener Block hinter einem offenen wird trotzdem entfernt.
        $this->assertSame('x z', StringHelper::htmlToText('<style>x <script>y</script> z'));
        $this->assertSame('Text', StringHelper::htmlToText('<STYLE type="text/css">p{}</Style  >Text'));
        $this->assertSame("Kopf\nText", StringHelper::htmlToText('<header>Kopf</header> Text'), '<header> ist kein <head>, bleibt also stehen');
        $this->assertSame('a b', StringHelper::htmlToText('a <!-- weg --> b'));
        $this->assertSame("a\n- b", StringHelper::htmlToText('a<ul><li class="x">b</li></ul>'));
        $this->assertSame("a\nb", StringHelper::htmlToText('a<br class="x"/>b'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function adversarialProvider(): array {
        return [
            '<style> ohne Endtag, wiederholt' => ['<style>a'],
            '<script> ohne Endtag, wiederholt' => ['<script>'],
            '<style ohne >' => ['<style '],
            'Kommentar ohne Ende' => ['<!--'],
            '<li ohne >' => ['<li '],
        ];
    }

    /**
     * Bis 2.17.0 quadratisch: 125 KB wiederholter <style> ohne Endtag brauchten
     * 1,1 s, 1 MB in der App rund 80 s. Linear sind es hier Bruchteile davon;
     * die Grenzen lassen langsamen CI-Maschinen viel Luft.
     */
    #[DataProvider('adversarialProvider')]
    public function test_runtime_stays_linear_for_unclosed_constructs(string $unit): void {
        foreach ([1_000_000 => 3.0, 4_000_000 => 10.0] as $bytes => $limit) {
            $html = str_repeat($unit, intdiv($bytes, strlen($unit)));
            $start = microtime(true);
            StringHelper::htmlToText($html);
            $seconds = microtime(true) - $start;

            $this->assertLessThan($limit, $seconds, sprintf('%d Byte brauchten %.2f s', strlen($html), $seconds));
        }
    }
}
