<?php
/*
 * Created on   : Sat Jun 14 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankHelperOfflineTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Helper\Data\BankHelper;
use ReflectionMethod;
use Tests\Contracts\BaseTestCase;

/**
 * Stellt sicher, dass das Laden der Bundesbank-Datendatei ausfallsicher ist:
 * Fehlt die Datei oder ist sie leer (z.B. offline und ohne mitgelieferte Datei),
 * darf KEINE Exception fliegen und es muss eine leere Liste geliefert werden,
 * sodass bicFromIBAN()/bicFromBLZ() sauber null statt eines Fehlers liefern.
 */
class BankHelperOfflineTest extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();
        // Sauberer Ausgangszustand: Cache + Netzschalter-Override zurücksetzen.
        BankHelper::clearCache();
    }

    protected function tearDown(): void {
        BankHelper::clearCache();
        parent::tearDown();
    }

    private function callLoadDataFile(string $path, ?string $url, int $expiry, bool $networkEnabled): array {
        $method = new ReflectionMethod(BankHelper::class, 'loadDataFile');
        $method->setAccessible(true);
        return $method->invoke(null, $path, $url, $expiry, $networkEnabled);
    }

    public function test_missing_file_returns_empty_list_without_throwing(): void {
        $absentPath = sys_get_temp_dir() . '/does-not-exist-' . bin2hex(random_bytes(4)) . '.csv';

        // Netzabruf deaktiviert -> kein Download, Datei fehlt -> leere Liste, kein Throw.
        $result = $this->callLoadDataFile($absentPath, null, 365, false);

        $this->assertSame([], $result);
        $this->assertFileDoesNotExist($absentPath);
    }

    public function test_empty_file_returns_empty_list_without_throwing(): void {
        $emptyPath = sys_get_temp_dir() . '/empty-bank-data-' . bin2hex(random_bytes(4)) . '.csv';
        file_put_contents($emptyPath, '');

        try {
            $result = $this->callLoadDataFile($emptyPath, null, 365, false);
            $this->assertSame([], $result);
        } finally {
            @unlink($emptyPath);
        }
    }

    public function test_failed_download_falls_back_to_empty_when_no_local_file(): void {
        // Ungültige URL-Schema -> File::download() liefert false; Datei fehlt -> leere Liste.
        $absentPath = sys_get_temp_dir() . '/no-data-' . bin2hex(random_bytes(4)) . '.csv';

        $result = @$this->callLoadDataFile($absentPath, 'file:///definitely/not/http', 365, true);

        $this->assertSame([], $result);
    }

    public function test_bic_from_iban_does_not_throw(): void {
        // Mit mitgelieferter Datendatei ist das Ergebnis ein String; ohne wäre es null.
        // Wichtig ist: es fliegt keine Exception (auch bei leerem Index -> null).
        $bic = BankHelper::bicFromIBAN('DE44500105175407324931');
        $this->assertTrue($bic === null || is_string($bic));
    }

    /**
     * Kern-Regression: Mit den ausgelieferten Datendateien muss bicFromIBAN()/bicFromBLZ()
     * OHNE Online-Lauf einen echten BIC liefern (genau dieser Fall scheiterte downstream).
     */
    public function test_shipped_data_yields_real_bic_offline(): void {
        BankHelper::setNetworkEnabled(false);

        // Commerzbank Berlin (BLZ 10040000) -> COBADEBBXXX laut ausgelieferter Bundesbank-Datei.
        $bic = BankHelper::bicFromBLZ('10040000');
        $this->assertSame('COBADEBBXXX', $bic);

        // Gleiche BLZ via IBAN (ING DiBa als zweiter Beleg über den IBAN-Pfad).
        $bicIban = BankHelper::bicFromIBAN('DE44500105175407324931');
        $this->assertSame('INGDDEFFXXX', $bicIban);
    }

    /**
     * Bankname aus dem Bezeichnungsfeld (Offset 9, Länge 58) derselben BLZ-Datei.
     */
    public function test_shipped_data_yields_bank_name_offline(): void {
        BankHelper::setNetworkEnabled(false);

        $this->assertStringContainsString('Commerzbank', (string) BankHelper::bankNameFromBLZ('10040000'));
        $this->assertStringContainsString('Commerzbank', (string) BankHelper::bankNameFromIBAN('DE89370400440532013000'));
        // Unbekannte BLZ -> null.
        $this->assertNull(BankHelper::bankNameFromBLZ('00000000'));
    }

    public function test_is_network_enabled_reflects_override(): void {
        // Default (kein Override): effektiver Wert ist true (Config-Default).
        $this->assertTrue(BankHelper::isNetworkEnabled());

        BankHelper::setNetworkEnabled(false);
        $this->assertFalse(BankHelper::isNetworkEnabled());

        BankHelper::setNetworkEnabled(true);
        $this->assertTrue(BankHelper::isNetworkEnabled());

        // null -> zurück auf Config-Default.
        BankHelper::setNetworkEnabled(null);
        $this->assertTrue(BankHelper::isNetworkEnabled());
    }

    public function test_clear_cache_resets_network_override(): void {
        BankHelper::setNetworkEnabled(false);
        $this->assertFalse(BankHelper::isNetworkEnabled());

        BankHelper::clearCache();
        $this->assertTrue(BankHelper::isNetworkEnabled());
    }

    /**
     * Bei deaktiviertem Netz darf KEIN Download-Versuch erfolgen; trotzdem muss
     * der BIC aus der lokalen Datei kommen. Wir geben absichtlich eine URL mit,
     * deren Abruf einen Fehler verursachen WÜRDE, falls er ausgelöst würde.
     */
    public function test_network_disabled_skips_download_but_reads_local_file(): void {
        $tmp = sys_get_temp_dir() . '/local-bank-data-' . bin2hex(random_bytes(4)) . '.txt';
        file_put_contents($tmp, "ABCDEFGH\nIJKLMNOP\n");

        try {
            // expiry 0 => Datei gilt als abgelaufen -> Download-Pfad würde betreten,
            // ABER networkEnabled=false verhindert jeden Netzabruf. URL ist ungültig:
            // würde sie abgerufen, käme false; da sie NICHT abgerufen wird, bleibt die
            // lokale Datei unverändert und wird gelesen.
            $result = $this->callLoadDataFile($tmp, 'http://invalid.invalid/nope', 0, false);
            $this->assertSame(['ABCDEFGH', 'IJKLMNOP'], $result);
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Stale-Fallback: vorhandene Datei ist abgelaufen (expiry 0) UND der Download
     * schlägt fehl (ungültiges Schema -> File::download() liefert false) -> die
     * vorhandene (stale) Datei muss weiter geliefert werden, NICHT [].
     */
    public function test_stale_file_is_used_when_expired_and_download_fails(): void {
        $tmp = sys_get_temp_dir() . '/stale-bank-data-' . bin2hex(random_bytes(4)) . '.txt';
        file_put_contents($tmp, "STALELINE1\nSTALELINE2\n");
        // Datei künstlich altern lassen (über jedes vernünftige expiry hinaus).
        touch($tmp, strtotime('-1000 days'));

        try {
            // networkEnabled=true + ungültige URL -> Download wird versucht und scheitert,
            // lässt die vorhandene Datei aber unangetastet -> stale Daten werden geliefert.
            $result = @$this->callLoadDataFile($tmp, 'file:///definitely/not/http', 0, true);
            $this->assertSame(['STALELINE1', 'STALELINE2'], $result);
        } finally {
            @unlink($tmp);
        }
    }

    // =====================================================================
    // extractIBAN / extractIBANs
    // =====================================================================

    public function test_extract_iban_finds_first_valid_in_text(): void {
        $text = 'Buchung Konto DE89370400440532013000 Betrag 10,00';
        $this->assertSame('DE89370400440532013000', BankHelper::extractIBAN($text));
    }

    public function test_extract_iban_returns_null_when_none_present(): void {
        $this->assertNull(BankHelper::extractIBAN('keine iban, nur text 12345'));
        $this->assertNull(BankHelper::extractIBAN(''));
        $this->assertNull(BankHelper::extractIBAN(null));
    }

    public function test_extract_iba_ns_dedupes_and_keeps_order(): void {
        $text = 'A DE89370400440532013000 B DE89370400440532013000 C DE12500105170648489890';
        $this->assertSame(
            ['DE89370400440532013000', 'DE12500105170648489890'],
            BankHelper::extractIBANs($text)
        );
    }

    public function test_extract_iba_ns_ignores_anonymized(): void {
        $this->assertSame([], BankHelper::extractIBANs('Konto DE12XXXXXXXXXXXXXXXXXX Ende'));
    }

    public function test_extract_iban_strict_applies_checksum(): void {
        $badChecksum = 'DE00370400440532013000'; // Format gültig, Prüfsumme falsch
        $this->assertSame($badChecksum, BankHelper::extractIBAN($badChecksum));        // Format-Level
        $this->assertNull(BankHelper::extractIBAN($badChecksum, true));                // strikt
        $this->assertSame('DE89370400440532013000', BankHelper::extractIBAN('DE89370400440532013000', true));
    }

    public function test_extract_iban_space_tolerant_handles_grouped_iban(): void {
        // PDF-typische 4er-Gruppierung mit 2-stelligem Rest – darf NICHT abgeschnitten werden.
        $text = 'Konto: DE89 3704 0044 0532 0130 00 (IBAN)';
        $this->assertNull(BankHelper::extractIBAN($text));                          // zusammenhängend: nichts
        $this->assertSame('DE89370400440532013000', BankHelper::extractIBAN($text, false, true));
    }
}
