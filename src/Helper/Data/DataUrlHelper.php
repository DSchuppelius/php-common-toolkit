<?php
/*
 * Created on   : Thu Sep 17 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DataUrlHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Helper\FileSystem\File;
use ERRORToolkit\Traits\ErrorLog;

/**
 * Helper für Data-URLs nach RFC 2397 (`data:[<mediatype>][;base64],<daten>`).
 *
 * Typischer Einsatz: Bilder, die ein Browser als Data-URL schickt
 * (Unterschrift aus einer Zeichenfläche, eingefügter Screenshot). Solche
 * Eingaben kommen vom Client — deshalb dekodiert {@see decode()} streng,
 * begrenzt die Größe und prüft den Typ am **Inhalt** (Magic Bytes/finfo),
 * nicht an der Angabe in der URL.
 */
class DataUrlHelper extends HelperAbstract {
    use ErrorLog;

    /** Medientyp, wenn die Data-URL keinen nennt (RFC 2397, Abschnitt 2). */
    public const DEFAULT_MIME_TYPE = 'text/plain';

    /**
     * Zerlegt eine Data-URL in ihre Bestandteile, ohne die Nutzdaten zu dekodieren.
     *
     * @param string $dataUrl Die Data-URL.
     * @return array{mime_type: string, base64: bool, data: string}|false Medientyp
     *         (kleingeschrieben, ohne Parameter), Base64-Kennzeichen und die noch
     *         kodierten Nutzdaten — oder false, wenn keine gültige Data-URL vorliegt.
     */
    public static function parse(string $dataUrl): array|false {
        $dataUrl = trim($dataUrl);
        if (strncasecmp($dataUrl, 'data:', 5) !== 0) {
            return self::logWarningAndReturn(false, 'Keine Data-URL: Präfix "data:" fehlt.');
        }

        $comma = strpos($dataUrl, ',');
        if ($comma === false) {
            return self::logWarningAndReturn(false, 'Ungültige Data-URL: Trennzeichen "," fehlt.');
        }

        $parameters = explode(';', substr($dataUrl, 5, $comma - 5));
        $base64 = count($parameters) > 1 && strcasecmp(trim((string) end($parameters)), 'base64') === 0;
        if ($base64) {
            array_pop($parameters);
        }

        $mimeType = strtolower(trim($parameters[0]));
        if ($mimeType === '') {
            $mimeType = self::DEFAULT_MIME_TYPE;
        } elseif (preg_match('#^[a-z0-9!\#$&^_.+-]+/[a-z0-9!\#$&^_.+-]+$#', $mimeType) !== 1) {
            return self::logWarningAndReturn(false, "Ungültige Data-URL: Medientyp \"$mimeType\" ist nicht wohlgeformt.");
        }

        return [
            'mime_type' => $mimeType,
            'base64' => $base64,
            'data' => substr($dataUrl, $comma + 1),
        ];
    }

    /**
     * Dekodiert eine Data-URL oder reines Base64 zu Rohbytes.
     *
     * Base64 wird strikt dekodiert (Zeichen außerhalb des Alphabets -> false).
     * Sind erlaubte Typen angegeben, muss der am Inhalt erkannte MIME-Typ
     * darunter sein; nennt die Data-URL selbst einen Typ, muss er außerdem mit
     * dem erkannten übereinstimmen.
     *
     * @param string $payload Data-URL oder reines Base64.
     * @param list<string> $allowedMimeTypes Erlaubte MIME-Typen des Inhalts (leer = keine Typprüfung).
     * @param int|null $maxBytes Höchstgröße der dekodierten Daten in Bytes (null = unbegrenzt).
     * @return string|false Die Rohbytes oder false bei ungültiger, zu großer oder unzulässiger Eingabe.
     */
    public static function decode(string $payload, array $allowedMimeTypes = [], ?int $maxBytes = null): string|false {
        $payload = trim($payload);
        if ($payload === '') {
            return self::logWarningAndReturn(false, 'Data-URL-Dekodierung: leere Eingabe.');
        }

        $declaredMimeType = null;
        $base64 = true;
        $data = $payload;
        if (strncasecmp($payload, 'data:', 5) === 0) {
            $parts = self::parse($payload);
            if ($parts === false) {
                return false;
            }
            $declaredMimeType = $parts['mime_type'];
            $base64 = $parts['base64'];
            $data = $parts['data'];
        }

        // Frühe Grenze, bevor ein übergroßer Block dekodiert wird: Base64 braucht
        // 4 Zeichen je 3 Bytes; der Faktor 2 lässt Platz für Zeilenumbrüche.
        if ($maxBytes !== null && strlen($data) > 2 * ($maxBytes + 3)) {
            return self::logWarningAndReturn(false, "Data-URL-Dekodierung: Eingabe überschreitet die Höchstgröße von $maxBytes Bytes.");
        }

        $bytes = $base64 ? base64_decode($data, true) : rawurldecode($data);
        if ($bytes === false || $bytes === '') {
            return self::logWarningAndReturn(false, 'Data-URL-Dekodierung: ungültige oder leere Nutzdaten.');
        }

        if ($maxBytes !== null && strlen($bytes) > $maxBytes) {
            return self::logWarningAndReturn(false, 'Data-URL-Dekodierung: ' . strlen($bytes) . " Bytes überschreiten die Höchstgröße von $maxBytes Bytes.");
        }

        if ($allowedMimeTypes === []) {
            return $bytes;
        }

        $detectedMimeType = File::mimeTypeFromContent($bytes);
        if ($detectedMimeType === false || !in_array(strtolower($detectedMimeType), array_map('strtolower', $allowedMimeTypes), true)) {
            return self::logWarningAndReturn(false, 'Data-URL-Dekodierung: Inhaltstyp "' . ($detectedMimeType ?: 'unbekannt') . '" ist nicht erlaubt.');
        }

        if ($declaredMimeType !== null && $declaredMimeType !== strtolower($detectedMimeType)) {
            return self::logWarningAndReturn(false, "Data-URL-Dekodierung: angegebener Typ \"$declaredMimeType\" passt nicht zum Inhalt \"$detectedMimeType\".");
        }

        return $bytes;
    }

    /**
     * Baut eine Base64-Data-URL.
     *
     * @param string $bytes Die Rohbytes.
     * @param string|null $mimeType Medientyp; null = am Inhalt erkennen.
     * @return string|false Die Data-URL oder false bei leerem Inhalt.
     */
    public static function encode(string $bytes, ?string $mimeType = null): string|false {
        if ($bytes === '') {
            return self::logWarningAndReturn(false, 'Data-URL-Erzeugung: leerer Inhalt.');
        }

        $mimeType ??= File::mimeTypeFromContent($bytes);
        if ($mimeType === false || $mimeType === '') {
            $mimeType = 'application/octet-stream';
        }

        return 'data:' . strtolower($mimeType) . ';base64,' . base64_encode($bytes);
    }
}
