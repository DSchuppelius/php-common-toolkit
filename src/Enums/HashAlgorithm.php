<?php
/*
 * Created on   : Thu Jul 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HashAlgorithm.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

/**
 * Enum für Hash-Algorithmen (Backing-Werte = PHP-`hash()`-Bezeichner).
 *
 * Kryptographische Digests (SHA-2/SHA-3, RIPEMD) eignen sich für
 * Blind-Indizes/Integritätsprüfungen; MD5/SHA-1 nur noch für
 * Alt-Kompatibilität und CRC32B/XXH* ausschließlich als schnelle,
 * nicht-kryptographische Prüfsummen (vgl. {@see self::isCryptographic()}).
 */
enum HashAlgorithm: string {
    case MD5 = 'md5';
    case SHA1 = 'sha1';
    case SHA224 = 'sha224';
    case SHA256 = 'sha256';
    case SHA384 = 'sha384';
    case SHA512 = 'sha512';
    case SHA3_256 = 'sha3-256';
    case SHA3_384 = 'sha3-384';
    case SHA3_512 = 'sha3-512';
    case RIPEMD160 = 'ripemd160';
    case CRC32B = 'crc32b';
    case XXH64 = 'xxh64';
    case XXH128 = 'xxh128';
    case XXH3 = 'xxh3';

    /**
     * True für Algorithmen, die nach heutigem Stand kollisionsresistent sind.
     * MD5/SHA-1 gelten als gebrochen, CRC/XXH sind reine Prüfsummen.
     */
    public function isCryptographic(): bool {
        return match ($this) {
            self::MD5, self::SHA1, self::CRC32B, self::XXH64, self::XXH128, self::XXH3 => false,
            default => true,
        };
    }
}
