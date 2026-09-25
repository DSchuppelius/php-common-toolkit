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
}
