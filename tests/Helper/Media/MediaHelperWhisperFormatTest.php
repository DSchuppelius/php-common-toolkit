<?php
/*
 * Created on   : Sun Aug 30 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MediaHelperWhisperFormatTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper\Media;

use CommonToolkit\Helper\Media\MediaHelper;
use Tests\Contracts\BaseTestCase;

/**
 * Das Whisper-Ausgabeformat geht in eine Kommandozeile. Es wird deshalb
 * gegen eine feste Liste geprüft, **bevor** irgendetwas ausgeführt wird –
 * diese Reihenfolge ist der eigentliche Prüfgegenstand.
 */
class MediaHelperWhisperFormatTest extends BaseTestCase {
    public function test_untertitelformate_sind_vorgesehen(): void {
        $this->assertContains('vtt', MediaHelper::WHISPER_FORMATS);
        $this->assertContains('srt', MediaHelper::WHISPER_FORMATS);
        $this->assertContains('txt', MediaHelper::WHISPER_FORMATS);
    }

    public function test_all_ist_nicht_zugelassen(): void {
        // 'all' kennt Whisper, es schreibt dann aber mehrere Dateien –
        // für die es keinen einzelnen Rückgabewert gibt.
        $this->assertNotContains('all', MediaHelper::WHISPER_FORMATS);
    }

    public function test_unbekanntes_format_bricht_vor_der_ausfuehrung_ab(): void {
        $dir = sys_get_temp_dir() . '/whisper-format-' . bin2hex(random_bytes(4));

        $result = MediaHelper::transcribeWhisper(
            __FILE__,
            $dir,
            'base',
            '',
            'auto',
            'transcribe',
            'cpu',
            'txt; touch /tmp/pwned'
        );

        $this->assertNull($result);
        // Kein Ausgabeverzeichnis: der Abbruch kam vor jedem Seiteneffekt.
        $this->assertDirectoryDoesNotExist($dir);
    }
}
