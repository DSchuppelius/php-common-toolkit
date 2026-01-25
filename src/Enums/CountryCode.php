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

namespace CommonToolkit\Enums;

use InvalidArgumentException;

enum CountryCode: string {
    case Afghanistan = 'AF';
    case ÅlandIslands = 'AX';
    case Albania = 'AL';
    case Algeria = 'DZ';
    case AmericanSamoa = 'AS';
    case Andorra = 'AD';
    case Angola = 'AO';
    case Anguilla = 'AI';
    case Antarctica = 'AQ';
    case AntiguaAndBarbuda = 'AG';
    case Argentina = 'AR';
    case Armenia = 'AM';
    case Aruba = 'AW';
    case AscensionIsland = 'AC';
    case Australia = 'AU';
    case Austria = 'AT';
    case Azerbaijan = 'AZ';
    case Bahamas = 'BS';
    case Bahrain = 'BH';
    case Bangladesh = 'BD';
    case Barbados = 'BB';
    case Belarus = 'BY';
    case Belgium = 'BE';
    case Belize = 'BZ';
    case Benin = 'BJ';
    case Bermuda = 'BM';
    case Bhutan = 'BT';
    case Bolivia = 'BO';
    case BonaireSaintEustatiusAndSaba = 'BQ';
    case BosniaAndHerzegovina = 'BA';
    case Botswana = 'BW';
    case BouvetIsland = 'BV';
    case Brazil = 'BR';
    case BritishIndianOceanTerritory = 'IO';
    case BruneiDarussalam = 'BN';
    case Bulgaria = 'BG';
    case BurkinaFaso = 'BF';
    case Burundi = 'BI';
    case CaboVerde = 'CV';
    case Cambodia = 'KH';
    case Cameroon = 'CM';
    case Canada = 'CA';
    case CaymanIslands = 'KY';
    case CentralAfricanRepublic = 'CF';
    case Chad = 'TD';
    case Chile = 'CL';
    case China = 'CN';
    case ChristmasIsland = 'CX';
    case CocosKeelingIslands = 'CC';
    case Colombia = 'CO';
    case Comoros = 'KM';
    case Congo = 'CG';
    case CongoDemocraticRepublicOfThe = 'CD';
    case CookIslands = 'CK';
    case CostaRica = 'CR';
    case CôteDIvoire = 'CI';
    case Croatia = 'HR';
    case Cuba = 'CU';
    case Curaçao = 'CW';
    case Cyprus = 'CY';
    case Czechia = 'CZ';
    case Denmark = 'DK';
    case DiegoGarcia = 'DG';
    case Djibouti = 'DJ';
    case Dominica = 'DM';
    case DominicanRepublic = 'DO';
    case Ecuador = 'EC';
    case Egypt = 'EG';
    case ElSalvador = 'SV';
    case EquatorialGuinea = 'GQ';
    case Eritrea = 'ER';
    case Estonia = 'EE';
    case Eswatini = 'SZ';
    case Ethiopia = 'ET';
    case FalklandIslandsMalvinas = 'FK';
    case FaroeIslands = 'FO';
    case Fiji = 'FJ';
    case Finland = 'FI';
    case France = 'FR';
    case FrenchGuiana = 'GF';
    case FrenchPolynesia = 'PF';
    case FrenchSouthernTerritories = 'TF';
    case Gabon = 'GA';
    case Gambia = 'GM';
    case Georgia = 'GE';
    case Germany = 'DE';
    case Ghana = 'GH';
    case Gibraltar = 'GI';
    case Greece = 'GR';
    case Greenland = 'GL';
    case Grenada = 'GD';
    case Guadeloupe = 'GP';
    case Guam = 'GU';
    case Guatemala = 'GT';
    case Guernsey = 'GG';
    case Guinea = 'GN';
    case GuineaBissau = 'GW';
    case Guyana = 'GY';
    case Haiti = 'HT';
    case HeardIslandAndMcDonaldIslands = 'HM';
    case HolySee = 'VA';
    case Honduras = 'HN';
    case HongKong = 'HK';
    case Hungary = 'HU';
    case Iceland = 'IS';
    case India = 'IN';
    case Indonesia = 'ID';
    case IranIslamicRepublicOf = 'IR';
    case Iraq = 'IQ';
    case Ireland = 'IE';
    case IsleOfMan = 'IM';
    case Israel = 'IL';
    case Italy = 'IT';
    case Jamaica = 'JM';
    case Japan = 'JP';
    case Jersey = 'JE';
    case Jordan = 'JO';
    case Kazakhstan = 'KZ';
    case Kenya = 'KE';
    case Kiribati = 'KI';
    case KoreaDemocraticPeoplesRepublicOf = 'KP';
    case KoreaRepublicOf = 'KR';
    case Kosovo = 'XK';
    case Kuwait = 'KW';
    case Kyrgyzstan = 'KG';
    case LaoPeoplesDemocraticRepublic = 'LA';
    case Latvia = 'LV';
    case Lebanon = 'LB';
    case Lesotho = 'LS';
    case Liberia = 'LR';
    case Libya = 'LY';
    case Liechtenstein = 'LI';
    case Lithuania = 'LT';
    case Luxembourg = 'LU';
    case Macao = 'MO';
    case Madagascar = 'MG';
    case Malawi = 'MW';
    case Malaysia = 'MY';
    case Maldives = 'MV';
    case Mali = 'ML';
    case Malta = 'MT';
    case MarshallIslands = 'MH';
    case Martinique = 'MQ';
    case Mauritania = 'MR';
    case Mauritius = 'MU';
    case Mayotte = 'YT';
    case Mexico = 'MX';
    case MicronesiaFederatedStatesOf = 'FM';
    case MoldovaRepublicOf = 'MD';
    case Monaco = 'MC';
    case Mongolia = 'MN';
    case Montenegro = 'ME';
    case Montserrat = 'MS';
    case Morocco = 'MA';
    case Mozambique = 'MZ';
    case Myanmar = 'MM';
    case Namibia = 'NA';
    case Nauru = 'NR';
    case Nepal = 'NP';
    case Netherlands = 'NL';
    case NetherlandsAntilles = 'AN';
    case NewCaledonia = 'NC';
    case NewZealand = 'NZ';
    case Nicaragua = 'NI';
    case Niger = 'NE';
    case Nigeria = 'NG';
    case Niue = 'NU';
    case NorfolkIsland = 'NF';
    case NorthMacedonia = 'MK';
    case NorthernIreland = "XI";
    case NorthernMarianaIslands = 'MP';
    case Norway = 'NO';
    case Oman = 'OM';
    case Pakistan = 'PK';
    case Palau = 'PW';
    case PalestineStateOf = 'PS';
    case Panama = 'PA';
    case PapuaNewGuinea = 'PG';
    case Paraguay = 'PY';
    case Peru = 'PE';
    case Philippines = 'PH';
    case Pitcairn = 'PN';
    case Poland = 'PL';
    case Portugal = 'PT';
    case PuertoRico = 'PR';
    case Qatar = 'QA';
    case Réunion = 'RE';
    case Romania = 'RO';
    case RussianFederation = 'RU';
    case Rwanda = 'RW';
    case SaintBarthélemy = 'BL';
    case SaintHelenaAscensionAndTristandaCunha = 'SH';
    case SaintKittsAndNevis = 'KN';
    case SaintLucia = 'LC';
    case SaintMartinFrenchPart = 'MF';
    case SaintPierreAndMiquelon = 'PM';
    case SaintVincentAndTheGrenadines = 'VC';
    case Samoa = 'WS';
    case SanMarino = 'SM';
    case SaoTomeAndPrincipe = 'ST';
    case SaudiArabia = 'SA';
    case Senegal = 'SN';
    case Serbia = 'RS';
    case Seychelles = 'SC';
    case SierraLeone = 'SL';
    case Singapore = 'SG';
    case SintMaartenDutchPart = 'SX';
    case Slovakia = 'SK';
    case Slovenia = 'SI';
    case SolomonIslands = 'SB';
    case Somalia = 'SO';
    case SouthAfrica = 'ZA';
    case SouthGeorgiaAndTheSouthSandwichIslands = 'GS';
    case SouthSudan = 'SS';
    case Spain = 'ES';
    case SriLanka = 'LK';
    case Sudan = 'SD';
    case Suriname = 'SR';
    case SvalbardAndJanMayen = 'SJ';
    case Sweden = 'SE';
    case Switzerland = 'CH';
    case SyrianArabRepublic = 'SY';
    case TaiwanProvinceOfChina = 'TW';
    case Tajikistan = 'TJ';
    case TanzaniaUnitedRepublicOf = 'TZ';
    case Thailand = 'TH';
    case TimorLeste = 'TL';
    case Togo = 'TG';
    case Tokelau = 'TK';
    case Tonga = 'TO';
    case TrinidadAndTobago = 'TT';
    case TristanDaCunha = 'TA';
    case Tunisia = 'TN';
    case Turkey = 'TR';
    case Turkmenistan = 'TM';
    case TurksAndCaicosIslands = 'TC';
    case Tuvalu = 'TV';
    case Uganda = 'UG';
    case Ukraine = 'UA';
    case UnitedArabEmirates = 'AE';
    case UnitedKingdomOfGreatBritainAndNorthernIreland = 'GB';
    case UnitedStatesOfAmerica = 'US';
    case UnitedStatesMinorOutlyingIslands = 'UM';
    case Uruguay = 'UY';
    case Uzbekistan = 'UZ';
    case Vanuatu = 'VU';
    case VenezuelaBolivarianRepublicOf = 'VE';
    case VietNam = 'VN';
    case VirginIslandsBritish = 'VG';
    case VirginIslandsUS = 'VI';
    case WallisAndFutuna = 'WF';
    case WesternSahara = 'EH';
    case Yemen = 'YE';
    case Zambia = 'ZM';
    case Zimbabwe = 'ZW';

    public static function fromStringValue(string $value): self {
        $value = strtoupper(trim($value));

        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        throw new InvalidArgumentException("Ungültiger ISO-Ländercode: $value");
    }

    public static function fromGermanName(string $name): self {
        $name = trim($name);
        $nameLower = mb_strtolower($name);

        foreach (self::cases() as $case) {
            if (mb_strtolower($case->getLabel()) === $nameLower) {
                return $case;
            }
        }

        throw new InvalidArgumentException("Ungültiger deutscher Ländername: $name");
    }

    public function isEU(): bool {
        return match ($this) {
            self::Austria,
            self::Belgium,
            self::Bulgaria,
            self::Croatia,
            self::Cyprus,
            self::Czechia,
            self::Denmark,
            self::Estonia,
            self::Finland,
            self::France,
            self::Germany,
            self::Greece,
            self::Hungary,
            self::Ireland,
            self::Italy,
            self::Latvia,
            self::Lithuania,
            self::Luxembourg,
            self::Malta,
            self::Netherlands,
            self::Poland,
            self::Portugal,
            self::Romania,
            self::Slovakia,
            self::Slovenia,
            self::Spain,
            self::Sweden,
            => true,

            default => false,
        };
    }

    public function hasEuroCurrency(): bool {
        return match ($this) {
            self::Austria,
            self::Belgium,
            self::Cyprus,
            self::Estonia,
            self::Finland,
            self::France,
            self::Germany,
            self::Greece,
            self::Ireland,
            self::Italy,
            self::Latvia,
            self::Lithuania,
            self::Luxembourg,
            self::Malta,
            self::Netherlands,
            self::Portugal,
            self::Slovakia,
            self::Slovenia,
            self::Spain,
            self::Croatia,
            => true,

            default => false,
        };
    }


    public function getLabel(): string {
        return match ($this) {
            self::Afghanistan => 'Afghanistan',
            self::ÅlandIslands => 'Ålandinseln',
            self::Albania => 'Albanien',
            self::Algeria => 'Algerien',
            self::AmericanSamoa => 'Amerikanisch-Samoa',
            self::Andorra => 'Andorra',
            self::Angola => 'Angola',
            self::Anguilla => 'Anguilla',
            self::Antarctica => 'Antarktis',
            self::AntiguaAndBarbuda => 'Antigua und Barbuda',
            self::Argentina => 'Argentinien',
            self::Armenia => 'Armenien',
            self::Aruba => 'Aruba',
            self::AscensionIsland => 'Ascension',
            self::Australia => 'Australien',
            self::Austria => 'Österreich',
            self::Azerbaijan => 'Aserbaidschan',
            self::Bahamas => 'Bahamas',
            self::Bahrain => 'Bahrain',
            self::Bangladesh => 'Bangladesch',
            self::Barbados => 'Barbados',
            self::Belarus => 'Belarus',
            self::Belgium => 'Belgien',
            self::Belize => 'Belize',
            self::Benin => 'Benin',
            self::Bermuda => 'Bermuda',
            self::Bhutan => 'Bhutan',
            self::Bolivia => 'Bolivien',
            self::BonaireSaintEustatiusAndSaba => 'Bonaire, Sint Eustatius und Saba',
            self::BosniaAndHerzegovina => 'Bosnien und Herzegowina',
            self::Botswana => 'Botswana',
            self::BouvetIsland => 'Bouvetinsel',
            self::Brazil => 'Brasilien',
            self::BritishIndianOceanTerritory => 'Britisches Territorium im Indischen Ozean',
            self::BruneiDarussalam => 'Brunei Darussalam',
            self::Bulgaria => 'Bulgarien',
            self::BurkinaFaso => 'Burkina Faso',
            self::Burundi => 'Burundi',
            self::CaboVerde => 'Cabo Verde',
            self::Cambodia => 'Kambodscha',
            self::Cameroon => 'Kamerun',
            self::Canada => 'Kanada',
            self::CaymanIslands => 'Kaimaninseln',
            self::CentralAfricanRepublic => 'Zentralafrikanische Republik',
            self::Chad => 'Tschad',
            self::Chile => 'Chile',
            self::China => 'China',
            self::ChristmasIsland => 'Weihnachtsinsel',
            self::CocosKeelingIslands => 'Kokosinseln',
            self::Colombia => 'Kolumbien',
            self::Comoros => 'Komoren',
            self::Congo => 'Kongo',
            self::CongoDemocraticRepublicOfThe => 'Demokratische Republik Kongo',
            self::CookIslands => 'Cookinseln',
            self::CostaRica => 'Costa Rica',
            self::CôteDIvoire => 'Côte d’Ivoire',
            self::Croatia => 'Kroatien',
            self::Cuba => 'Kuba',
            self::Curaçao => 'Curaçao',
            self::Cyprus => 'Zypern',
            self::Czechia => 'Tschechien',
            self::Denmark => 'Dänemark',
            self::DiegoGarcia => 'Diego Garcia',
            self::Djibouti => 'Dschibuti',
            self::Dominica => 'Dominica',
            self::DominicanRepublic => 'Dominikanische Republik',
            self::Ecuador => 'Ecuador',
            self::Egypt => 'Ägypten',
            self::ElSalvador => 'El Salvador',
            self::EquatorialGuinea => 'Äquatorialguinea',
            self::Eritrea => 'Eritrea',
            self::Estonia => 'Estland',
            self::Eswatini => 'Eswatini',
            self::Ethiopia => 'Äthiopien',
            self::FalklandIslandsMalvinas => 'Falklandinseln',
            self::FaroeIslands => 'Färöer',
            self::Fiji => 'Fidschi',
            self::Finland => 'Finnland',
            self::France => 'Frankreich',
            self::FrenchGuiana => 'Französisch-Guayana',
            self::FrenchPolynesia => 'Französisch-Polynesien',
            self::FrenchSouthernTerritories => 'Französische Süd- und Antarktisgebiete',
            self::Gabon => 'Gabun',
            self::Gambia => 'Gambia',
            self::Georgia => 'Georgien',
            self::Germany => 'Deutschland',
            self::Ghana => 'Ghana',
            self::Gibraltar => 'Gibraltar',
            self::Greece => 'Griechenland',
            self::Greenland => 'Grönland',
            self::Grenada => 'Grenada',
            self::Guadeloupe => 'Guadeloupe',
            self::Guam => 'Guam',
            self::Guatemala => 'Guatemala',
            self::Guernsey => 'Guernsey',
            self::Guinea => 'Guinea',
            self::GuineaBissau => 'Guinea-Bissau',
            self::Guyana => 'Guyana',
            self::Haiti => 'Haiti',
            self::HeardIslandAndMcDonaldIslands => 'Heard und McDonaldinseln',
            self::HolySee => 'Vatikanstadt',
            self::Honduras => 'Honduras',
            self::HongKong => 'Hongkong',
            self::Hungary => 'Ungarn',
            self::Iceland => 'Island',
            self::India => 'Indien',
            self::Indonesia => 'Indonesien',
            self::IranIslamicRepublicOf => 'Iran',
            self::Iraq => 'Irak',
            self::Ireland => 'Irland',
            self::IsleOfMan => 'Isle of Man',
            self::Israel => 'Israel',
            self::Italy => 'Italien',
            self::Jamaica => 'Jamaika',
            self::Japan => 'Japan',
            self::Jersey => 'Jersey',
            self::Jordan => 'Jordanien',
            self::Kazakhstan => 'Kasachstan',
            self::Kenya => 'Kenia',
            self::Kiribati => 'Kiribati',
            self::KoreaDemocraticPeoplesRepublicOf => 'Nordkorea',
            self::KoreaRepublicOf => 'Südkorea',
            self::Kosovo => 'Kosovo',
            self::Kuwait => 'Kuwait',
            self::Kyrgyzstan => 'Kirgisistan',
            self::LaoPeoplesDemocraticRepublic => 'Laos',
            self::Latvia => 'Lettland',
            self::Lebanon => 'Libanon',
            self::Lesotho => 'Lesotho',
            self::Liberia => 'Liberia',
            self::Libya => 'Libyen',
            self::Liechtenstein => 'Liechtenstein',
            self::Lithuania => 'Litauen',
            self::Luxembourg => 'Luxemburg',
            self::Macao => 'Macao',
            self::Madagascar => 'Madagaskar',
            self::Malawi => 'Malawi',
            self::Malaysia => 'Malaysia',
            self::Maldives => 'Malediven',
            self::Mali => 'Mali',
            self::Malta => 'Malta',
            self::MarshallIslands => 'Marshallinseln',
            self::Martinique => 'Martinique',
            self::Mauritania => 'Mauretanien',
            self::Mauritius => 'Mauritius',
            self::Mayotte => 'Mayotte',
            self::Mexico => 'Mexiko',
            self::MicronesiaFederatedStatesOf => 'Mikronesien',
            self::MoldovaRepublicOf => 'Moldau',
            self::Monaco => 'Monaco',
            self::Mongolia => 'Mongolei',
            self::Montenegro => 'Montenegro',
            self::Montserrat => 'Montserrat',
            self::Morocco => 'Marokko',
            self::Mozambique => 'Mosambik',
            self::Myanmar => 'Myanmar',
            self::Namibia => 'Namibia',
            self::Nauru => 'Nauru',
            self::Nepal => 'Nepal',
            self::Netherlands => 'Niederlande',
            self::NetherlandsAntilles => 'Niederländische Antillen',
            self::NewCaledonia => 'Neukaledonien',
            self::NewZealand => 'Neuseeland',
            self::Nicaragua => 'Nicaragua',
            self::Niger => 'Niger',
            self::Nigeria => 'Nigeria',
            self::Niue => 'Niue',
            self::NorfolkIsland => 'Norfolkinsel',
            self::NorthMacedonia => 'Nordmazedonien',
            self::NorthernIreland => 'Nordirland (XI)',
            self::NorthernMarianaIslands => 'Nördliche Marianen',
            self::Norway => 'Norwegen',
            self::Oman => 'Oman',
            self::Pakistan => 'Pakistan',
            self::Palau => 'Palau',
            self::PalestineStateOf => 'Palästina',
            self::Panama => 'Panama',
            self::PapuaNewGuinea => 'Papua-Neuguinea',
            self::Paraguay => 'Paraguay',
            self::Peru => 'Peru',
            self::Philippines => 'Philippinen',
            self::Pitcairn => 'Pitcairninseln',
            self::Poland => 'Polen',
            self::Portugal => 'Portugal',
            self::PuertoRico => 'Puerto Rico',
            self::Qatar => 'Katar',
            self::Réunion => 'Réunion',
            self::Romania => 'Rumänien',
            self::RussianFederation => 'Russland',
            self::Rwanda => 'Ruanda',
            self::SaintBarthélemy => 'Saint-Barthélemy',
            self::SaintHelenaAscensionAndTristandaCunha => 'St. Helena, Ascension und Tristan da Cunha',
            self::SaintKittsAndNevis => 'St. Kitts und Nevis',
            self::SaintLucia => 'St. Lucia',
            self::SaintMartinFrenchPart => 'Saint-Martin',
            self::SaintPierreAndMiquelon => 'Saint-Pierre und Miquelon',
            self::SaintVincentAndTheGrenadines => 'St. Vincent und die Grenadinen',
            self::Samoa => 'Samoa',
            self::SanMarino => 'San Marino',
            self::SaoTomeAndPrincipe => 'São Tomé und Príncipe',
            self::SaudiArabia => 'Saudi-Arabien',
            self::Senegal => 'Senegal',
            self::Serbia => 'Serbien',
            self::Seychelles => 'Seychellen',
            self::SierraLeone => 'Sierra Leone',
            self::Singapore => 'Singapur',
            self::SintMaartenDutchPart => 'Sint Maarten',
            self::Slovakia => 'Slowakei',
            self::Slovenia => 'Slowenien',
            self::SolomonIslands => 'Salomonen',
            self::Somalia => 'Somalia',
            self::SouthAfrica => 'Südafrika',
            self::SouthGeorgiaAndTheSouthSandwichIslands => 'Südgeorgien und die Südlichen Sandwichinseln',
            self::SouthSudan => 'Südsudan',
            self::Spain => 'Spanien',
            self::SriLanka => 'Sri Lanka',
            self::Sudan => 'Sudan',
            self::Suriname => 'Suriname',
            self::SvalbardAndJanMayen => 'Svalbard und Jan Mayen',
            self::Sweden => 'Schweden',
            self::Switzerland => 'Schweiz',
            self::SyrianArabRepublic => 'Syrien',
            self::TaiwanProvinceOfChina => 'Taiwan',
            self::Tajikistan => 'Tadschikistan',
            self::TanzaniaUnitedRepublicOf => 'Tansania',
            self::Thailand => 'Thailand',
            self::TimorLeste => 'Timor-Leste',
            self::Togo => 'Togo',
            self::Tokelau => 'Tokelau',
            self::Tonga => 'Tonga',
            self::TrinidadAndTobago => 'Trinidad und Tobago',
            self::TristanDaCunha => 'Tristan da Cunha',
            self::Tunisia => 'Tunesien',
            self::Turkey => 'Türkei',
            self::Turkmenistan => 'Turkmenistan',
            self::TurksAndCaicosIslands => 'Turks- und Caicosinseln',
            self::Tuvalu => 'Tuvalu',
            self::Uganda => 'Uganda',
            self::Ukraine => 'Ukraine',
            self::UnitedArabEmirates => 'Vereinigte Arabische Emirate',
            self::UnitedKingdomOfGreatBritainAndNorthernIreland => 'Vereinigtes Königreich',
            self::UnitedStatesOfAmerica => 'Vereinigte Staaten von Amerika',
            self::UnitedStatesMinorOutlyingIslands => 'US-Außengebiete',
            self::Uruguay => 'Uruguay',
            self::Uzbekistan => 'Usbekistan',
            self::Vanuatu => 'Vanuatu',
            self::VenezuelaBolivarianRepublicOf => 'Venezuela',
            self::VietNam => 'Vietnam',
            self::VirginIslandsBritish => 'Britische Jungferninseln',
            self::VirginIslandsUS => 'Amerikanische Jungferninseln',
            self::WallisAndFutuna => 'Wallis und Futuna',
            self::WesternSahara => 'Westsahara',
            self::Yemen => 'Jemen',
            self::Zambia => 'Sambia',
            self::Zimbabwe => 'Simbabwe',

            default => $this->name,
        };
    }

    public function getContinent(): string {
        return match ($this) {
            self::Algeria,
            self::Angola,
            self::Benin,
            self::Botswana,
            self::BurkinaFaso,
            self::Burundi,
            self::CaboVerde,
            self::Cameroon,
            self::CentralAfricanRepublic,
            self::Chad,
            self::Comoros,
            self::Congo,
            self::CongoDemocraticRepublicOfThe,
            self::CôteDIvoire,
            self::Djibouti,
            self::Egypt,
            self::EquatorialGuinea,
            self::Eritrea,
            self::Eswatini,
            self::Ethiopia,
            self::Gabon,
            self::Gambia,
            self::Ghana,
            self::Guinea,
            self::GuineaBissau,
            self::Kenya,
            self::Lesotho,
            self::Liberia,
            self::Libya,
            self::Madagascar,
            self::Malawi,
            self::Mali,
            self::Mauritania,
            self::Mauritius,
            self::Mayotte,
            self::Morocco,
            self::Mozambique,
            self::Namibia,
            self::Niger,
            self::Nigeria,
            self::Réunion,
            self::Rwanda,
            self::SaintHelenaAscensionAndTristandaCunha,
            self::SaoTomeAndPrincipe,
            self::Senegal,
            self::Seychelles,
            self::SierraLeone,
            self::Somalia,
            self::SouthAfrica,
            self::SouthSudan,
            self::Sudan,
            self::TanzaniaUnitedRepublicOf,
            self::Togo,
            self::Tunisia,
            self::Uganda,
            self::Zambia,
            self::Zimbabwe
            => 'Afrika',

            self::Anguilla,
            self::AntiguaAndBarbuda,
            self::Argentina,
            self::Aruba,
            self::Bahamas,
            self::Barbados,
            self::Belize,
            self::Bermuda,
            self::Bolivia,
            self::BonaireSaintEustatiusAndSaba,
            self::Brazil,
            self::Canada,
            self::CaymanIslands,
            self::Chile,
            self::Colombia,
            self::CostaRica,
            self::Cuba,
            self::Curaçao,
            self::Dominica,
            self::DominicanRepublic,
            self::Ecuador,
            self::ElSalvador,
            self::FalklandIslandsMalvinas,
            self::FrenchGuiana,
            self::Greenland,
            self::Grenada,
            self::Guadeloupe,
            self::Guatemala,
            self::Guyana,
            self::Haiti,
            self::Honduras,
            self::Jamaica,
            self::Martinique,
            self::Mexico,
            self::Montserrat,
            self::Nicaragua,
            self::Panama,
            self::Paraguay,
            self::Peru,
            self::PuertoRico,
            self::SaintBarthélemy,
            self::SaintKittsAndNevis,
            self::SaintLucia,
            self::SaintMartinFrenchPart,
            self::SaintPierreAndMiquelon,
            self::SaintVincentAndTheGrenadines,
            self::SintMaartenDutchPart,
            self::Suriname,
            self::TrinidadAndTobago,
            self::TurksAndCaicosIslands,
            self::UnitedStatesOfAmerica,
            self::UnitedStatesMinorOutlyingIslands,
            self::Uruguay,
            self::VirginIslandsBritish,
            self::VirginIslandsUS,
            self::VenezuelaBolivarianRepublicOf
            => 'Amerika',

            self::Afghanistan,
            self::Armenia,
            self::Azerbaijan,
            self::Bahrain,
            self::Bangladesh,
            self::Bhutan,
            self::BruneiDarussalam,
            self::Cambodia,
            self::China,
            self::Cyprus,
            self::Georgia,
            self::HongKong,
            self::India,
            self::Indonesia,
            self::IranIslamicRepublicOf,
            self::Iraq,
            self::Israel,
            self::Japan,
            self::Jordan,
            self::Kazakhstan,
            self::KoreaDemocraticPeoplesRepublicOf,
            self::KoreaRepublicOf,
            self::Kuwait,
            self::Kyrgyzstan,
            self::LaoPeoplesDemocraticRepublic,
            self::Lebanon,
            self::Macao,
            self::Malaysia,
            self::Maldives,
            self::Mongolia,
            self::Myanmar,
            self::Nepal,
            self::Oman,
            self::Pakistan,
            self::PalestineStateOf,
            self::Philippines,
            self::Qatar,
            self::SaudiArabia,
            self::Singapore,
            self::SriLanka,
            self::SyrianArabRepublic,
            self::TaiwanProvinceOfChina,
            self::Tajikistan,
            self::Thailand,
            self::TimorLeste,
            self::Turkey,
            self::Turkmenistan,
            self::UnitedArabEmirates,
            self::Uzbekistan,
            self::VietNam,
            self::Yemen
            => 'Asien',

            self::Albania,
            self::Andorra,
            self::Austria,
            self::Belarus,
            self::Belgium,
            self::BosniaAndHerzegovina,
            self::Bulgaria,
            self::Croatia,
            self::Czechia,
            self::Denmark,
            self::Estonia,
            self::FaroeIslands,
            self::Finland,
            self::France,
            self::Germany,
            self::Gibraltar,
            self::Greece,
            self::Guernsey,
            self::HolySee,
            self::Hungary,
            self::Iceland,
            self::Ireland,
            self::IsleOfMan,
            self::Italy,
            self::Jersey,
            self::Kosovo,
            self::Latvia,
            self::Liechtenstein,
            self::Lithuania,
            self::Luxembourg,
            self::Malta,
            self::MoldovaRepublicOf,
            self::Monaco,
            self::Montenegro,
            self::Netherlands,
            self::NorthMacedonia,
            self::NorthernIreland,
            self::Norway,
            self::Poland,
            self::Portugal,
            self::Romania,
            self::RussianFederation,
            self::SanMarino,
            self::Serbia,
            self::Slovakia,
            self::Slovenia,
            self::Spain,
            self::SvalbardAndJanMayen,
            self::Sweden,
            self::Switzerland,
            self::Ukraine,
            self::UnitedKingdomOfGreatBritainAndNorthernIreland
            => 'Europa',

            self::AmericanSamoa,
            self::Australia,
            self::CookIslands,
            self::Fiji,
            self::Guam,
            self::HeardIslandAndMcDonaldIslands,
            self::Kiribati,
            self::MarshallIslands,
            self::MicronesiaFederatedStatesOf,
            self::Nauru,
            self::NewCaledonia,
            self::NewZealand,
            self::Niue,
            self::NorfolkIsland,
            self::NorthernMarianaIslands,
            self::Palau,
            self::PapuaNewGuinea,
            self::Pitcairn,
            self::Samoa,
            self::SolomonIslands,
            self::Tokelau,
            self::Tonga,
            self::Tuvalu,
            self::Vanuatu,
            self::WallisAndFutuna
            => 'Ozeanien',

            self::Antarctica,
            self::FrenchSouthernTerritories,
            self::BouvetIsland,
            self::SouthGeorgiaAndTheSouthSandwichIslands
            => 'Antarktis',

            default => 'Unbekannt',
        };
    }

    /**
     * Gibt die DateTime-Formatgruppe für das Land zurück.
     *
     * @return DateTimeFormatGroup Die Format-Gruppe
     */
    public function getDateTimeFormatGroup(): DateTimeFormatGroup {
        return match ($this) {
            // Europäische Länder - DD/MM/YYYY Format
            self::Germany, self::Austria, self::Switzerland,
            self::France, self::Italy, self::Spain, self::Portugal,
            self::Netherlands, self::Belgium, self::Luxembourg,
            self::Poland, self::Czechia, self::Slovakia,
            self::Hungary, self::Slovenia, self::Croatia,
            self::Denmark, self::Sweden, self::Norway, self::Finland => DateTimeFormatGroup::European,

            // Amerikanisches Format - MM/DD/YYYY
            self::UnitedStatesOfAmerica => DateTimeFormatGroup::American,

            // Commonwealth Länder - DD/MM/YYYY (wie europäisch)
            self::UnitedKingdomOfGreatBritainAndNorthernIreland,
            self::Canada, self::Australia, self::NewZealand,
            self::Ireland, self::SouthAfrica => DateTimeFormatGroup::European,

            // Asiatische Länder - YYYY/MM/DD bevorzugt
            self::Japan, self::KoreaRepublicOf, self::China,
            self::Singapore, self::HongKong => DateTimeFormatGroup::Asian,

            // Lateinamerikanische Länder - DD/MM/YYYY
            self::Brazil, self::Argentina, self::Chile,
            self::Colombia, self::Peru, self::Mexico => DateTimeFormatGroup::European,

            // Osteuropäische Länder - DD.MM.YYYY bevorzugt
            self::RussianFederation, self::Ukraine, self::Belarus => DateTimeFormatGroup::Russian,

            // Südasiatische Länder - DD/MM/YYYY (britischer Einfluss)
            self::India, self::Pakistan, self::Bangladesh => DateTimeFormatGroup::European,

            // Südeuropäische/Nahosteuropäische Länder - gemischt
            self::Turkey, self::Greece, self::Cyprus => DateTimeFormatGroup::Mixed,

            // Fallback für alle anderen Länder
            default => DateTimeFormatGroup::European,
        };
    }

    /**
     * Gibt die IBAN-Länge für das Land zurück.
     *
     * @return int|null Die Länge der IBAN oder null, wenn das Land kein IBAN-System hat.
     */
    public function getIBANLength(): ?int {
        return match ($this) {
            self::Albania => 28,
            self::Andorra => 24,
            self::Austria => 20,
            self::Azerbaijan => 28,
            self::Bahrain => 22,
            self::Belgium => 16,
            self::BosniaAndHerzegovina => 20,
            self::Brazil => 29,
            self::Bulgaria => 22,
            self::CostaRica => 22,
            self::Croatia => 21,
            self::Cyprus => 28,
            self::Czechia => 24,
            self::Denmark => 18,
            self::DominicanRepublic => 28,
            self::Estonia => 20,
            self::FaroeIslands => 18,
            self::Finland => 18,
            self::France => 27,
            self::Georgia => 22,
            self::Germany => 22,
            self::Gibraltar => 23,
            self::Greece => 27,
            self::Greenland => 18,
            self::Guatemala => 28,
            self::Hungary => 28,
            self::Iceland => 26,
            self::Ireland => 22,
            self::Israel => 23,
            self::Italy => 27,
            self::Jordan => 30,
            self::Kazakhstan => 20,
            self::Kosovo => 20,
            self::Kuwait => 30,
            self::Latvia => 21,
            self::Lebanon => 28,
            self::Liechtenstein => 21,
            self::Lithuania => 20,
            self::Luxembourg => 20,
            self::Malta => 31,
            self::Mauritania => 27,
            self::Mauritius => 30,
            self::MoldovaRepublicOf => 24,
            self::Monaco => 27,
            self::Montenegro => 22,
            self::Netherlands => 18,
            self::Norway => 15,
            self::Pakistan => 24,
            self::PalestineStateOf => 29,
            self::Poland => 28,
            self::Portugal => 25,
            self::Qatar => 29,
            self::Romania => 24,
            self::SanMarino => 27,
            self::SaudiArabia => 24,
            self::Serbia => 22,
            self::Slovakia => 24,
            self::Slovenia => 19,
            self::Spain => 24,
            self::Sweden => 24,
            self::Switzerland => 21,
            self::Tunisia => 24,
            self::Turkey => 26,
            self::UnitedArabEmirates => 23,
            self::UnitedKingdomOfGreatBritainAndNorthernIreland => 22,
            self::VirginIslandsBritish => 24,
            default => null,
        };
    }

    /**
     * Prüft, ob das Land am IBAN-System teilnimmt.
     *
     * @return bool True, wenn das Land IBANs verwendet.
     */
    public function hasIBAN(): bool {
        return $this->getIBANLength() !== null;
    }

    /**
     * Prüft, ob das Land am SEPA-System teilnimmt.
     *
     * @return bool True, wenn das Land SEPA-Teilnehmer ist.
     */
    public function isSEPA(): bool {
        return match ($this) {
            // EU-Mitgliedstaaten
            self::Austria,
            self::Belgium,
            self::Bulgaria,
            self::Croatia,
            self::Cyprus,
            self::Czechia,
            self::Denmark,
            self::Estonia,
            self::Finland,
            self::France,
            self::Germany,
            self::Greece,
            self::Hungary,
            self::Ireland,
            self::Italy,
            self::Latvia,
            self::Lithuania,
            self::Luxembourg,
            self::Malta,
            self::Netherlands,
            self::Poland,
            self::Portugal,
            self::Romania,
            self::Slovakia,
            self::Slovenia,
            self::Spain,
            self::Sweden,
            // EWR-Länder
            self::Iceland,
            self::Liechtenstein,
            self::Norway,
            // Weitere SEPA-Teilnehmer
            self::Switzerland,
            self::Monaco,
            self::SanMarino,
            self::Andorra,
            self::HolySee,
            self::UnitedKingdomOfGreatBritainAndNorthernIreland,
            self::Gibraltar,
            // Territorien
            self::Guernsey,
            self::IsleOfMan,
            self::Jersey,
            self::SaintPierreAndMiquelon,
            self::SaintBarthélemy,
            self::SaintMartinFrenchPart,
            self::Guadeloupe,
            self::Martinique,
            self::FrenchGuiana,
            self::Réunion,
            self::Mayotte => true,
            default => false,
        };
    }

    /**
     * Gibt alle Länder zurück, die am IBAN-System teilnehmen.
     *
     * @return self[]
     */
    public static function getIBANCountries(): array {
        return array_filter(self::cases(), fn(self $country) => $country->hasIBAN());
    }

    /**
     * Gibt alle SEPA-Teilnehmerländer zurück.
     *
     * @return self[]
     */
    public static function getSEPACountries(): array {
        return array_filter(self::cases(), fn(self $country) => $country->isSEPA());
    }
}
