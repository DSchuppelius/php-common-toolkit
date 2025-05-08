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
use CommonToolkit\Entities\Banking\Mt940\Mt940Document;
use CommonToolkit\Entities\Banking\Mt940\Mt940Transaction;
use CommonToolkit\Helper\FileSystem\File;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use Throwable;

class Mt940File extends HelperAbstract {
    /**
     * Gibt den Inhalt als einzelne MT940-Blöcke zurück.
     *
     * @param string $file Pfad zur MT940-Datei.
     * @return string[] Array mit Roh-Textblöcken.
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
     * Gibt alle `Mt940Document`-Objekte zurück.
     *
     * @param string $file
     * @return Mt940Document[]
     */
    public static function getDocuments(string $file): array {
        $documents = [];
        foreach (self::getBlocks($file) as $block) {
            try {
                $documents[] = new Mt940Document($block);
            } catch (Throwable $e) {
                self::logError("Fehler beim Parsen eines MT940-Blocks: " . $e->getMessage());
            }
        }
        return $documents;
    }

    /**
     * Gibt alle Transaktionen aus allen Blöcken zurück.
     *
     * @param string $file Pfad zur MT940-Datei.
     * @return Mt940Transaction[]
     */
    public static function getTransactions(string $file): array {
        $transactions = [];
        foreach (self::getDocuments($file) as $doc) {
            $transactions = array_merge($transactions, $doc->getTransactions());
        }
        return $transactions;
    }

    /**
     * Gibt die Anzahl der Transaktionen zurück.
     *
     * @param string $file
     * @return int
     */
    public static function countTransactions(string $file): int {
        return count(self::getTransactions($file));
    }

    /**
     * Prüft, ob es sich um eine gültige MT940-Datei handelt.
     *
     * @param string $file
     * @return bool
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
}
