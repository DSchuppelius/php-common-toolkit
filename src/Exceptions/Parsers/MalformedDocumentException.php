<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MalformedDocumentException.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Exceptions\Parsers;

use RuntimeException;
use Throwable;

/**
 * Eine Eingabe ist strukturell ungültig oder beschädigt: falsche Signatur,
 * abgeschnittene Datei, Zyklus in einer Sektorkette, Verweis außerhalb der
 * Datei, Pflichtfeld fehlt. Gegenstück zu {@see DocumentLimitExceededException}
 * (Eingabe ist gültig, aber zu groß).
 *
 * Bleibt eine RuntimeException, damit Aufrufer, die Parser-Fehler bisher
 * allgemein fangen, nichts ändern müssen. Die Meldung nennt nie Inhalte der
 * Eingabe (keine Adressen, IBANs, Dateiinhalte), nur Struktur und Position.
 */
class MalformedDocumentException extends RuntimeException {
    public function __construct(
        string $message,
        private readonly ?string $document = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /** Basename der Datei, nie ein Serverpfad; bei Eingaben aus dem Speicher ohne Namen null. */
    public function getDocument(): ?string {
        return $this->document;
    }
}
