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
 * Ein Parser hat eine konfigurierte Grenze (entpackte Bytes, Zeilen) überschritten.
 * Bleibt eine RuntimeException, damit bestehende Aufrufer nichts ändern müssen;
 * `kind`/`limit`/`actual` erlauben eine übersetzte Meldung ohne Text-Vergleich.
 */
class DocumentLimitExceededException extends RuntimeException {
    public const KIND_BYTES = 'bytes';

    public const KIND_ROWS = 'rows';

    public function __construct(
        string $message,
        private readonly string $kind,
        private readonly int $limit,
        private readonly ?int $actual = null,
        private readonly ?string $document = null,
    ) {
        parent::__construct($message);
    }

    /** `bytes` oder `rows`. */
    public function getKind(): string {
        return $this->kind;
    }

    public function getLimit(): int {
        return $this->limit;
    }

    /** Tatsächlicher Wert, sofern bekannt (Bytes); bei Zeilen null (Abbruch beim Erreichen). */
    public function getActual(): ?int {
        return $this->actual;
    }

    /** Basename der Datei, nie ein Serverpfad. */
    public function getDocument(): ?string {
        return $this->document;
    }
}
