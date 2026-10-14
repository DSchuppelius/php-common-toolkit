<?php
/*
 * Created on   : Fri Sep 25 2026
 * Author       : Daniel Joerg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MediaHelperTimeoutTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper\Media;

use CommonToolkit\Helper\Media\MediaHelper;
use CommonToolkit\Helper\Shell;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\{BaseTestCase, TimeoutProbeTrait};

/**
 * Jede MediaHelper-Methode, die ein externes Programm startet, bricht nach
 * der uebergebenen Zeitgrenze ab - ohne echte Medien, mit einer schlafenden
 * Attrappe des jeweiligen Executables (siehe TimeoutProbeTrait).
 */
class MediaHelperTimeoutTest extends BaseTestCase {
    use TimeoutProbeTrait;

    /** @return array<string, array{0: string, 1: bool}> */
    public static function methods(): array {
        // Fall => liefert die Methode $output/$returnCode per Referenz (bool-Pfad) oder null (Wert-Pfad)?
        return [
            'convert (ffmpeg)' => ['convert', true],
            'getAudioInfo (ffmpeg-info)' => ['getAudioInfo', false],
            'getVideoInfo (ffmpeg-info)' => ['getVideoInfo', false],
            'transcribeWhisper' => ['transcribeWhisper', false],
            'synthesizePiper' => ['synthesizePiper', true],
            'synthesizeEspeak' => ['synthesizeEspeak', true],
        ];
    }

    #[DataProvider('methods')]
    public function test_zeitgrenze_bricht_langsames_programm_ab(string $case, bool $reportsViaReference): void {
        $probe = $this->runTimeoutProbe($case, 0.5);

        $this->assertLessThan(4.0, $probe['duration'], 'Die Attrappe schlaeft 5 s; die Methode muss frueher zurueckkommen.');

        if ($reportsViaReference) {
            $this->assertFalse($probe['result']);
            $this->assertSame(Shell::EXIT_TIMEOUT, $probe['returnCode']);
            $this->assertContains('Zeitgrenze 0.5 s ueberschritten', $probe['output']);
        } else {
            $this->assertNull($probe['result']);
        }
    }

    protected function tearDown(): void {
        Shell::setDefaultTimeout(null);
        parent::tearDown();
    }

    /** @return array<string, array{0: string}> */
    public static function speechMethods(): array {
        return ['synthesizePiper' => ['synthesizePiper'], 'synthesizeEspeak' => ['synthesizeEspeak']];
    }

    /**
     * Ohne Angabe laeuft nichts unbegrenzt: die Sprachsynthese bekommt die
     * Standard-Zeitgrenze plus 0.1 s je Zeichen - fuer "Hallo" 0.5 + 0.5 s.
     */
    #[DataProvider('speechMethods')]
    public function test_ohne_angabe_waechst_die_zeitgrenze_mit_dem_text(string $case): void {
        $probe = $this->runTimeoutProbe($case, 0.5, true);

        $this->assertFalse($probe['result']);
        $this->assertSame(Shell::EXIT_TIMEOUT, $probe['returnCode']);
        $this->assertContains('Zeitgrenze 1 s ueberschritten', $probe['output']);
        $this->assertLessThan(4.0, $probe['duration']);
    }

    public function test_medien_zeitgrenze_waechst_mit_der_laenge(): void {
        // Wandlung: Standard 180 s + 4 s je Sekunde Medienlaenge
        $this->assertSame(180.0 + 4.0 * 60.0, MediaHelper::mediaTimeout(60.0));
        // Whisper: 600 s Anlauf + 10 s je Sekunde
        $this->assertSame(600.0 + 10.0 * 60.0, MediaHelper::mediaTimeout(60.0, MediaHelper::TRANSCRIBE_SECONDS_PER_MEDIA_SECOND, MediaHelper::TRANSCRIBE_TIMEOUT_BASE));
        // Deckel bei langen Medien und ohne bekannte Laenge
        $this->assertSame(Shell::MAX_SCALED_TIMEOUT, MediaHelper::mediaTimeout(36000.0));
        $this->assertSame(Shell::MAX_SCALED_TIMEOUT, MediaHelper::mediaTimeout(null));
        $this->assertSame(Shell::MAX_SCALED_TIMEOUT, MediaHelper::mediaTimeout(0.0));
        // Eine hoeher gesetzte Standard-Zeitgrenze hebt die Frist mit an
        Shell::setDefaultTimeout(20000.0);
        $this->assertSame(20000.0, MediaHelper::mediaTimeout(null));
        $this->assertSame(20000.0, MediaHelper::mediaTimeout(10.0));
    }
}
