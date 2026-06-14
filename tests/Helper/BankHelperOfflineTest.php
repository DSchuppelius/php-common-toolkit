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
    private function callLoadDataFile(string $path, ?string $url, int $expiry, bool $networkEnabled): array {
        $method = new ReflectionMethod(BankHelper::class, 'loadDataFile');
        $method->setAccessible(true);
        return $method->invoke(null, $path, $url, $expiry, $networkEnabled);
    }

    public function testMissingFileReturnsEmptyListWithoutThrowing(): void {
        $absentPath = sys_get_temp_dir() . '/does-not-exist-' . bin2hex(random_bytes(4)) . '.csv';

        // Netzabruf deaktiviert -> kein Download, Datei fehlt -> leere Liste, kein Throw.
        $result = $this->callLoadDataFile($absentPath, null, 365, false);

        $this->assertSame([], $result);
        $this->assertFileDoesNotExist($absentPath);
    }

    public function testEmptyFileReturnsEmptyListWithoutThrowing(): void {
        $emptyPath = sys_get_temp_dir() . '/empty-bank-data-' . bin2hex(random_bytes(4)) . '.csv';
        file_put_contents($emptyPath, '');

        try {
            $result = $this->callLoadDataFile($emptyPath, null, 365, false);
            $this->assertSame([], $result);
        } finally {
            @unlink($emptyPath);
        }
    }

    public function testFailedDownloadFallsBackToEmptyWhenNoLocalFile(): void {
        // Ungültige URL-Schema -> File::download() liefert false; Datei fehlt -> leere Liste.
        $absentPath = sys_get_temp_dir() . '/no-data-' . bin2hex(random_bytes(4)) . '.csv';

        $result = @$this->callLoadDataFile($absentPath, 'file:///definitely/not/http', 365, true);

        $this->assertSame([], $result);
    }

    public function testBicFromIbanDoesNotThrow(): void {
        // Mit mitgelieferter Datendatei ist das Ergebnis ein String; ohne wäre es null.
        // Wichtig ist: es fliegt keine Exception (auch bei leerem Index -> null).
        $bic = BankHelper::bicFromIBAN('DE44500105175407324931');
        $this->assertTrue($bic === null || is_string($bic));
    }
}
