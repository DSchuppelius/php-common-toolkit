<?php
/*
 * Created on   : Thu Apr 17 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TemperatureUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Enums;

enum TemperatureUnit: string {
    case CELSIUS = 'C';
    case FAHRENHEIT = 'F';
    case KELVIN = 'K';
}