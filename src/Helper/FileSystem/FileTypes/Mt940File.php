<?php
/*
 * Created on   : Wed May 07 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt940File.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\FileSystem\FileTypes;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Entities\Banking\Mt940Transaction;
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Parsers\Mt940TransactionParser;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use Throwable;

class Mt940File extends HelperAbstract {
    /**
     * Gibt den Inhalt als einzelne MT940-Blöcke zurück.
     *
     * @param string $file Pfad zur MT940-Datei.
     * @return array Array mit MT940-Blöcken (beginnt typischerweise mit `:20:` und endet mit `-`).
     */
    public static function getBlocks(string $file): array {
        $file = File::getRealPath($file);
        if (!File::isReadable($file)) {
            self::logError("MT940-Datei $file nicht gefunden.");
            throw new FileNotFoundException("MT940-Datei nicht lesbar: $file");
        }

        $content = File::read($file);
        $blocks = preg_split('/(?=:20:)/', $content, -1, PREG_SPLIT_NO_EMPTY);

        self::logInfo("MT940-Datei $file enthält " . count($blocks) . " Block(e).");
        return $blocks;
    }

    /**
     * Gibt alle Buchungen (`:61:`) aus der MT940-Datei zurück.
     *
     * @param string $file Pfad zur MT940-Datei.
     * @return Mt940Transaction[] Array mit MT940-Transaktionen.
     */
    public static function getTransactions(string $file): array {
        $file = File::getRealPath($file);
        if (!File::isReadable($file)) {
            throw new FileNotFoundException("MT940-Datei nicht lesbar: $file");
        }

        $lines = preg_split('/\r\n|\n|\r/', File::read($file));
        $transactions = [];
        $lineCount = count($lines);

        $i = 0;
        while ($i < $lineCount) {
            $line = $lines[$i];

            if (str_starts_with($line, ':61:')) {
                $bookingLine = $line;
                $i++;

                // Suche zugehörige :86:-Zeile + mögliche ?xx-Zeilen
                $purposeLines = [];
                if (isset($lines[$i]) && str_starts_with($lines[$i], ':86:')) {
                    $purposeLines[] = trim(substr($lines[$i], 4));
                    $i++;

                    while ($i < $lineCount && str_starts_with($lines[$i], '?')) {
                        $purposeLines[] = trim(substr($lines[$i], 3)); // ?00, ?20 etc. entfernen
                        $i++;
                    }

                    $purpose = implode(' ', $purposeLines);

                    try {
                        $transaction = \CommonToolkit\Parsers\Mt940TransactionParser::parse($bookingLine, $purpose);
                        $transactions[] = $transaction;
                    } catch (Throwable $e) {
                        self::logError("Fehler beim Parsen einer Transaktion: {$e->getMessage()}");
                    }
                } else {
                    self::logWarning("Keine :86:-Zeile nach :61: gefunden bei Zeile: $bookingLine");
                }

                continue; // vermeide zusätzliches $i++
            }

            $i++;
        }

        return $transactions;
    }


    /**
     * Prüft, ob es sich um eine gültige MT940-Datei handelt.
     */
    public static function isValid(string $file): bool {
        try {
            $blocks = self::getBlocks($file);
            foreach ($blocks as $block) {
                if (!str_contains($block, ':20:') || !str_contains($block, ':62F:')) {
                    self::logDebug("Ungültiger MT940-Block erkannt.");
                    return false;
                }
            }
            return count($blocks) > 0;
        } catch (Throwable $e) {
            self::logError("Fehler beim Validieren der MT940-Datei: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gibt die Anzahl der Buchungen (`:61:`) über alle Blöcke zurück.
     */
    public static function countTransactions(string $file): int {
        $blocks = self::getBlocks($file);
        $count = 0;
        foreach ($blocks as $block) {
            $count += substr_count($block, ':61:');
        }
        self::logInfo("MT940-Datei enthält $count Buchung(en).");
        return $count;
    }
}
