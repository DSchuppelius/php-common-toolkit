<?php
/*
 * Created on   : Wed Dec 24 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TruncationStrategy.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\CSV;

/**
 * Enum für CSV-Truncation-Strategien.
 * 
 * Definiert wie Felder gekürzt werden, wenn sie die maximale Länge überschreiten.
 * 
 * @package CommonToolkit\Enums\Common\CSV
 */
enum TruncationStrategy: string {
    /**
     * Deaktiviert das Kürzen von Feldern.
     */
    case NONE = 'none';

    /**
     * Schneidet Felder bei der maximalen Länge ab.
     */
    case TRUNCATE = 'truncate';

    /**
     * Kürzt Felder und fügt '...' am Ende hinzu.
     */
    case ELLIPSIS = 'ellipsis';
}