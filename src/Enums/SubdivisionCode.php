<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CountryCode.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace APIToolkit\Enums;

enum SubdivisionCode: string {
    // Spanien (ES) - Autonome Gemeinschaften und besondere Verwaltungsgebiete
    case CanaryIslands = 'ES_CN';           // Kanarische Inseln
    case Ceuta = 'ES_CE';                   // Ceuta
    case Melilla = 'ES_ML';                 // Melilla

        // Griechenland (GR) - Besonderes Verwaltungsgebiet
    case MountAthos = 'GR_69';              // Berg Athos

        // Frankreich (FR) - Überseegebiete
    case Guadeloupe = 'FR_GP';              // Guadeloupe
    case Martinique = 'FR_MQ';              // Martinique
    case FrenchGuiana = 'FR_GF';            // Französisch-Guayana
    case Réunion = 'FR_RE';                 // Réunion
    case Mayotte = 'FR_YT';                 // Mayotte

        // Norwegen (NO) - Svalbard und Jan Mayen
    case SvalbardAndJanMayen = 'NO_SJ';     // Svalbard und Jan Mayen

        // USA (US) - Außengebiete
    case PuertoRico = 'US_PR';              // Puerto Rico
    case VirginIslandsUS = 'US_VI';         // Amerikanische Jungferninseln
    case Guam = 'US_GU';                    // Guam
    case AmericanSamoa = 'US_AS';           // Amerikanisch-Samoa
    case NorthernMarianaIslands = 'US_MP';  // Nördliche Marianen

        // Australien (AU) - Externe Territorien
    case NorfolkIsland = 'AU_NF';           // Norfolkinsel
    case ChristmasIsland = 'AU_CX';         // Weihnachtsinsel
    case CocosKeelingIslands = 'AU_CC';     // Kokosinseln (Keelinginseln)

        // China (CN) - Spezielle Verwaltungsregionen
    case HongKong = 'CN_HK';                // Hongkong
    case Macau = 'CN_MO';                   // Macau

        // Finnland (FI) - Åland-Inseln
    case AlandIslands = 'FI_AX';            // Åland-Inseln
}