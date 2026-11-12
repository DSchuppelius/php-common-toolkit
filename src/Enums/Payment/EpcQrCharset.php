<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EpcQrCharset.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Payment;

/**
 * Zeichensatz-Kennung (Zeile 3) eines EPC-QR-Codes (Girocode, EPC069-12).
 * Der Wert ist die Ziffer in der Nutzlast, {@see encoding()} der Name für
 * mbstring/iconv.
 */
enum EpcQrCharset: int {
    case Utf8 = 1;
    case Latin1 = 2;
    case Latin2 = 3;
    case Latin4 = 4;
    case Cyrillic = 5;
    case Greek = 6;
    case Latin6 = 7;
    case Latin9 = 8;

    /** Name der Kodierung, wie ihn mbstring und iconv verstehen. */
    public function encoding(): string {
        return match ($this) {
            self::Utf8 => 'UTF-8',
            self::Latin1 => 'ISO-8859-1',
            self::Latin2 => 'ISO-8859-2',
            self::Latin4 => 'ISO-8859-4',
            self::Cyrillic => 'ISO-8859-5',
            self::Greek => 'ISO-8859-7',
            self::Latin6 => 'ISO-8859-10',
            self::Latin9 => 'ISO-8859-15',
        };
    }
}
