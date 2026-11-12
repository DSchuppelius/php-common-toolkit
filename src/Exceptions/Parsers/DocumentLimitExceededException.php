<?php
/*
 * Created on   : Thu Sep 11 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DocumentLimitExceededException.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Exceptions\Parsers;

use RuntimeException;

/**
 * Ein Parser oder Archiv-Leser hat eine konfigurierte Grenze überschritten:
 * entpackte Bytes, Zeilen, Datei-Einträge, das Kompressionsverhältnis oder
 * die Verschachtelungstiefe.
 * Bleibt eine RuntimeException, damit bestehende Aufrufer nichts ändern müssen;
 * `kind`/`limit`/`actual` erlauben eine übersetzte Meldung ohne Text-Vergleich.
 */
class DocumentLimitExceededException extends RuntimeException {
    public const KIND_BYTES = 'bytes';

    public const KIND_ROWS = 'rows';

    /** Anzahl Datei-Einträge eines Archivs (ZipFile) bzw. Anhänge einer Nachricht (OutlookMessageParser). */
    public const KIND_ENTRIES = 'entries';

    /** Kompressionsverhältnis eines einzelnen Archiv-Eintrags (ZipFile::extract, Zip-Bombe). */
    public const KIND_RATIO = 'ratio';

    /** Verschachtelungstiefe, z. B. eingebettete Nachrichten in Nachrichten (OutlookMessageParser). */
    public const KIND_DEPTH = 'depth';

    public function __construct(
        string $message,
        private readonly string $kind,
        private readonly int|float $limit,
        private readonly ?int $actual = null,
        private readonly ?string $document = null,
    ) {
        parent::__construct($message);
    }

    /** `bytes`, `rows`, `entries`, `ratio` oder `depth`. */
    public function getKind(): string {
        return $this->kind;
    }

    /** Ganzzahl bei Bytes/Zeilen/Einträgen, Gleitkommazahl beim Kompressionsverhältnis. */
    public function getLimit(): int|float {
        return $this->limit;
    }

    /** Tatsächlicher Wert, sofern bekannt (Bytes, Einträge); sonst null (Abbruch beim Erreichen). */
    public function getActual(): ?int {
        return $this->actual;
    }

    /** Basename der Datei/des Archivs, nie ein Serverpfad; bei In-Memory-Archiven null. */
    public function getDocument(): ?string {
        return $this->document;
    }
}
