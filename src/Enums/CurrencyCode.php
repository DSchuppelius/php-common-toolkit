<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CurrencyCode.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

use InvalidArgumentException;

enum CurrencyCode: string {
    case ArabEmirateDirham = 'AED';         // AED - Arab. Emirate Dirham
    case AfghaniOld = 'AFA';                // AFA - Afghani - alt
    case Afghani = 'AFN';                   // AFN - Afghani
    case AlbanianLek = 'ALL';               // ALL - Albanische Lek
    case ArmenianDram = 'AMD';              // AMD - Armenien Dram
    case AntillenGulden = 'ANG';            // ANG - Antillen Gulden
    case AngolaKwanza = 'AOA';              // AOA - Angola Kwanza
    case AngolaKwanzaOld = 'AON';           // AON - Angola Kwanza - alt
    case AngolaKwanzaROld = 'AOR';          // AOR - Angola Kwanza R - alt
    case ArgentinianPeso = 'ARS';           // ARS - Argentinische Peso
    case AustrianSchilling = 'ATS';         // ATS - Österreichische Schilling
    case AustralianDollar = 'AUD';          // AUD - Australische Dollar
    case ArubaFlorin = 'AWG';               // AWG - Aruba Gulden
    case AzerbaijaniManatOld = 'AZM';       // AZM - Aserbaidschan Manat - alt
    case AzerbaijaniManat = 'AZN';          // AZN - Aserbaidschan Manat
    case BosnianMark = 'BAM';               // BAM - Bosnien Mark
    case BarbadosDollar = 'BBD';            // BBD - Barbados Dollar
    case BangladeshTaka = 'BDT';            // BDT - Bangladesch Taka
    case BelgianFranc = 'BEF';              // BEF - Belgische Francs
    case BulgarianLevOld = 'BGL';           // BGL - Bulgarische Lew - alt
    case BulgarianLev = 'BGN';              // BGN - Bulgarische Lew
    case BahrainDinar = 'BHD';              // BHD - Bahrain Dinar
    case BurundiFranc = 'BIF';              // BIF - Burundi Franc
    case BermudaDollar = 'BMD';             // BMD - Bermuda Dollar
    case BruneiDollar = 'BND';              // BND - Brunei Dollar
    case Boliviano = 'BOB';                 // BOB - Boliviano
    case BrazilianReal = 'BRL';             // BRL - Brasilianische Real
    case BahamianDollar = 'BSD';            // BSD - Bahama Dollar
    case BhutanNgultrum = 'BTN';            // BTN - Bhutan Ngultrum
    case BotswanaPula = 'BWP';              // BWP - Botswana Pula
    case BelarusRubleOld = 'BYB';           // BYB - Belarus Rubel - alt
    case BelarusRuble = 'BYN';              // BYN - Belarus Rubel
    case BelarusRubleSecondOld = 'BYR';     // BYR - Belarus Rubel - alt
    case BelizeDollar = 'BZD';              // BZD - Belize Dollar
    case CanadianDollar = 'CAD';            // CAD - Kanadische Dollar
    case CongoFranc = 'CDF';                // CDF - Kongo Franc
    case SwissFranc = 'CHF';                // CHF - Schweizer Franken
    case ChileanPeso = 'CLP';               // CLP - Chilenische Peso
    case ChineseYuanRenminbi = 'CNY';       // CNY - Chinesische Renminbi
    case ColombianPeso = 'COP';             // COP - Kolumbianischer Peso
    case CostaRicaColon = 'CRC';            // CRC - Costa Rica Colon
    case SerbianDinarOld = 'CSD';           // CSD - Serbische Dinar - alt
    case CubanPeso = 'CUP';                 // CUP - Cuba Peso
    case CapeVerdeEscudo = 'CVE';           // CVE - Kap Verde Escudo
    case CyprusPound = 'CYP';               // CYP - Zypriotisches Pfund
    case CzechKoruna = 'CZK';               // CZK - Tschechische Kronen
    case GermanMark = 'DEM';                // DEM - Deutsche Mark
    case DjiboutiFrancOld = 'DJF';          // DJF - Dschibuti Franc - alt
    case DjiboutiFranc = 'DJV';             // DJV - Dschibuti Franc
    case DanishKrone = 'DKK';               // DKK - Dänische Krone
    case DominicanPeso = 'DOP';             // DOP - Dominikanische Peso
    case AlgerianDinar = 'DZD';             // DZD - Algerischer Dinar
    case EcuadorSucre = 'ECS';              // ECS - Ecuador Sucre
    case EstonianKroon = 'EEK';             // EEK - Estnische Krone
    case EgyptianPound = 'EGP';             // EGP - Ägyptisches Pfund
    case EritreanNakfa = 'ERN';             // ERN - Eritreischer Nakfa
    case SpanishPeseta = 'ESP';             // ESP - Spanische Peseta
    case EthiopianBirr = 'ETB';             // ETB - Äthiopischer Birr
    case Euro = 'EUR';                      // EUR - Euro
    case FinnishMarkka = 'FIM';             // FIM - Finnmark
    case FijianDollar = 'FJD';              // FJD - Fidschi-Dollar
    case FalklandIslandsPound = 'FKP';      // FKP - Falkland-Pfund
    case FrenchFranc = 'FRF';               // FRF - Französische Francs
    case BritishPound = 'GBP';              // GBP - Britisches Pfund
    case GeorgianLari = 'GEL';              // GEL - Georgischer Lari
    case GhanaCediOld = 'GHC';              // GHC - Ghanaischer Cedi - alt
    case GhanaCedi = 'GHS';                 // GHS - Ghanaischer Cedi
    case GibraltarPound = 'GIP';            // GIP - Gibraltar-Pfund
    case GambianDalasi = 'GMD';             // GMD - Gambischer Dalasi
    case GuineanFranc = 'GNF';              // GNF - Guinea-Franc
    case GreekDrachma = 'GRD';              // GRD - Griechische Drachme
    case GuatemalanQuetzal = 'GTQ';         // GTQ - Guatemala-Quetzal
    case GuyaneseDollar = 'GYD';            // GYD - Guyana-Dollar
    case HongKongDollar = 'HKD';            // HKD - Hongkong-Dollar
    case HonduranLempira = 'HNL';           // HNL - Honduras-Lempira
    case CroatianKuna = 'HRK';              // HRK - Kroatische Kuna
    case HaitianGourde = 'HTG';             // HTG - Haitianische Gourde
    case HungarianForint = 'HUF';           // HUF - Ungarischer Forint
    case IndonesianRupiah = 'IDR';          // IDR - Indonesische Rupiah
    case IrishPound = 'IEP';                // IEP - Irisches Pfund
    case IsraeliShekel = 'ILS';             // ILS - Israelischer Schekel
    case IndianRupee = 'INR';               // INR - Indische Rupie
    case IraqiDinar = 'IQD';                // IQD - Irakischer Dinar
    case IranianRial = 'IRR';               // IRR - Iranischer Rial
    case IcelandicKrona = 'ISK';            // ISK - Isländische Krone
    case ItalianLira = 'ITL';               // ITL - Italienische Lira
    case JamaicanDollar = 'JMD';            // JMD - Jamaika-Dollar
    case JordanianDinar = 'JOD';            // JOD - Jordanischer Dinar
    case JapaneseYen = 'JPY';               // JPY - Japanischer Yen
    case KenyanShilling = 'KES';            // KES - Kenia-Schilling
    case KyrgyzstaniSom = 'KGS';            // KGS - Kirgisischer Som
    case CambodianRiel = 'KHR';             // KHR - Kambodschanischer Riel
    case ComorianFranc = 'KMF';             // KMF - Komoren-Franc
    case SouthKoreanWon = 'KRW';            // KRW - Südkoreanischer Won
    case NorthKoreanWon = 'KPW';            // KPW - Nordkoreanischer Won
    case KuwaitiDinar = 'KWD';              // KWD - Kuwait-Dinar
    case CaymanIslandsDollar = 'KYD';       // KYD - Kaimaninseln-Dollar
    case KazakhstaniTenge = 'KZT';          // KZT - Kasachische Tenge
    case LaoKip = 'LAK';                    // LAK - Laotischer Kip
    case LebanesePound = 'LBP';             // LBP - Libanesisches Pfund
    case SriLankanRupee = 'LKR';            // LKR - Sri-Lanka-Rupie
    case LiberianDollar = 'LRD';            // LRD - Liberianischer Dollar
    case LesothoLoti = 'LSL';               // LSL - Lesotho Loti
    case LithuanianLitas = 'LTL';           // LTL - Litauischer Litas
    case LuxembourgFranc = 'LUF';           // LUF - Luxemburgischer Franc
    case LatvianLats = 'LVL';               // LVL - Lettischer Lats
    case LibyanDinar = 'LYD';               // LYD - Libyscher Dinar
    case MoroccanDirham = 'MAD';            // MAD - Marokkanischer Dirham
    case MalagasyAriary = 'MGA';            // MGA - Madagaskar Ariary
    case MoldovanLeu = 'MDL';               // MDL - Moldauischer Leu
    case MalagasyFrancOld = 'MGF';          // MGF - Madagaskar-Franc - alt
    case MacedonianDenar = 'MKD';           // MKD - Mazedonischer Denar
    case MyanmarKyat = 'MMK';               // MMK - Myanmar-Kyat
    case MongolianTugrik = 'MNT';           // MNT - Mongolischer Tugrik
    case MacanesePataca = 'MOP';            // MOP - Macau-Pataca
    case MauritanianOuguiyaOld = 'MRO';     // MRO - Mauretanische Ouguiya - alt
    case MauritanianOuguiya = 'MRU';        // MRU - Mauretanische Ouguiya
    case MalteseLira = 'MTL';               // MTL - Maltesische Lira
    case MauritianRupee = 'MUR';            // MUR - Mauritius-Rupie
    case MaldivianRufiyaa = 'MVR';          // MVR - Malediven-Rufiyaa
    case MalawianKwacha = 'MWK';            // MWK - Malawi-Kwacha
    case MexicanPeso = 'MXN';               // MXN - Mexikanischer Peso
    case MalaysianRinggit = 'MYR';          // MYR - Malaysischer Ringgit
    case MozambicanMeticalOld = 'MZM';      // MZM - Mosambik-Metical - alt
    case MozambicanMetical = 'MZN';         // MZN - Mosambik-Metical
    case NamibianDollar = 'NAD';            // NAD - Namibia-Dollar
    case NigerianNaira = 'NGN';             // NGN - Nigerianischer Naira
    case NicaraguanCordoba = 'NIO';         // NIO - Nicaragua-Córdoba
    case DutchGuilder = 'NLG';              // NLG - Niederländischer Gulden
    case NorwegianKrone = 'NOK';            // NOK - Norwegische Krone
    case NepaleseRupee = 'NPR';             // NPR - Nepalesische Rupie
    case NewZealandDollar = 'NZD';          // NZD - Neuseeland-Dollar
    case OmaniRial = 'OMR';                 // OMR - Omanischer Rial
    case PanamanianBalboa = 'PAB';          // PAB - Panama-Balboa
    case PeruvianSol = 'PEN';               // PEN - Peruanischer Sol
    case PapuaNewGuineaKina = 'PGK';        // PGK - Papua-Neuguinea-Kina
    case PhilippinePeso = 'PHP';            // PHP - Philippinischer Peso
    case PakistaniRupee = 'PKR';            // PKR - Pakistanische Rupie
    case PolishZloty = 'PLN';               // PLN - Polnischer Zloty
    case PolishZlotyOld = 'PLZ';            // PLZ - Polnischer Zloty - alt
    case PortugueseEscudo = 'PTE';          // PTE - Portugiesischer Escudo
    case ParaguayanGuarani = 'PYG';         // PYG - Paraguay-Guarani
    case QatariRial = 'QAR';                // QAR - Katar-Rial
    case RomanianLeuOld = 'ROL';            // ROL - Rumänischer Leu - alt
    case RomanianLeu = 'RON';               // RON - Rumänischer Leu
    case SerbianDinar = 'RSD';              // RSD - Serbischer Dinar
    case RussianRuble = 'RUB';              // RUB - Russischer Rubel
    case RussianRubleOld = 'RUR';           // RUR - Russischer Rubel - alt
    case RwandanFranc = 'RWF';              // RWF - Ruanda-Franc
    case SaudiRiyal = 'SAR';                // SAR - Saudi-Rial
    case SolomonIslandsDollar = 'SBD';      // SBD - Salomonen-Dollar
    case SeychellesRupee = 'SCR';           // SCR - Seychellen-Rupie
    case SudaneseDinarOld = 'SDD';          // SDD - Sudanesischer Dinar - alt
    case SudanesePound = 'SDG';             // SDG - Sudanesisches Pfund
    case SwedishKrona = 'SEK';              // SEK - Schwedische Krone
    case SingaporeDollar = 'SGD';           // SGD - Singapur-Dollar
    case SaintHelenaPound = 'SHP';          // SHP - St. Helena-Pfund
    case SlovenianTolar = 'SIT';            // SIT - Slowenischer Tolar
    case SlovakKoruna = 'SKK';              // SKK - Slowakische Krone
    case SierraLeoneanLeone = 'SLL';        // SLL - Sierra-Leone-Leone
    case SomaliShilling = 'SOS';            // SOS - Somali-Schilling
    case SurinameseDollar = 'SRD';          // SRD - Surinamischer Dollar
    case SurinameseGuilder = 'SRG';         // SRG - Surinamischer Gulden
    case SouthSudanesePound = 'SSP';        // SSP - Südsudanesisches Pfund
    case SaoTomeDobraOld = 'STD';           // STD - São-Tomé-Dobra - alt
    case ElSalvadorColon = 'SVC';           // SVC - El-Salvador-Colón
    case SyrianPound = 'SYP';               // SYP - Syrisches Pfund
    case SwaziLilangeni = 'SZL';            // SZL - Swasiland-Lilangeni
    case ThaiBaht = 'THB';                  // THB - Thailändischer Baht
    case TajikistaniRuble = 'TJR';          // TJR - Tadschikischer Rubel
    case TajikistaniSomoni = 'TJS';         // TJS - Tadschikischer Somoni
    case TurkmenistaniManatOld = 'TMM';     // TMM - Turkmenischer Manat - alt
    case TurkmenistaniManat = 'TMT';        // TMT - Turkmenischer Manat
    case TunisianDinar = 'TND';             // TND - Tunesischer Dinar
    case TonganPaanga = 'TOP';              // TOP - Tonga-Paʻanga
    case TurkishLiraOld = 'TRL';            // TRL - Türkische Lira - alt
    case TurkishLira = 'TRY';               // TRY - Türkische Lira
    case TrinidadAndTobagoDollar = 'TTD';   // TTD - Trinidad-und-Tobago-Dollar
    case NewTaiwanDollar = 'TWD';           // TWD - Neuer Taiwan-Dollar
    case TanzanianShilling = 'TZS';         // TZS - Tansanischer Schilling
    case UkrainianHryvnia = 'UAH';          // UAH - Ukrainische Hrywnja
    case UgandanShilling = 'UGX';           // UGX - Ugandischer Schilling
    case USDollar = 'USD';                  // USD - US-Dollar
    case UruguayanPeso = 'UYU';             // UYU - Uruguayischer Peso
    case UzbekistaniSom = 'UZS';            // UZS - Usbekischer Soʻm
    case VenezuelanBolivarOld = 'VEB';      // VEB - Venezuelanischer Bolivar - alt
    case VenezuelanBolivar = 'VED';         // VED - Venezuelanischer Bolivar
    case VietnameseDong = 'VND';            // VND - Vietnamesischer Dong
    case VanuatuVatu = 'VUV';               // VUV - Vanuatu-Vatu
    case SamoanTala = 'WST';                // WST - Samoanischer Tala
    case CFAFrancBEAC = 'XAF';              // XAF - CFA-Franc BEAC
    case EastCaribbeanDollar = 'XCD';       // XCD - Ostkaribischer Dollar
    case CFAFrancBCEAO = 'XOF';             // XOF - CFA-Franc BCEAO
    case CFPFranc = 'XPF';                  // XPF - CFP-Franc
    case YemeniRial = 'YER';                // YER - Jemenitischer Rial
    case SouthAfricanRand = 'ZAR';          // ZAR - Südafrikanischer Rand
    case ZambianKwachaOld = 'ZMK';          // ZMK - Sambischer Kwacha - alt
    case ZambianKwacha = 'ZMW';             // ZMW - Sambischer Kwacha
    case ZaireOld = 'ZRN';                  // ZRN - Zaire - alt
    case ZimbabweDollarOld = 'ZWD';         // ZWD - Simbabwe-Dollar - alt
    case ZimbabweDollarSecondOld = 'ZWR';   // ZWR - Simbabwe-Dollar - alt

    public function getLabel(): string {
        return match ($this) {
            self::ArabEmirateDirham => 'Arab. Emirate Dirham',
            self::AfghaniOld => 'Afghani - alt',
            self::Afghani => 'Afghani',
            self::AlbanianLek => 'Albanische Lek',
            self::ArmenianDram => 'Armenien Dram',
            self::AntillenGulden => 'Antillen Gulden',
            self::AngolaKwanza => 'Angola Kwanza',
            self::AngolaKwanzaOld => 'Angola Kwanza - alt',
            self::AngolaKwanzaROld => 'Angola Kwanza R - alt',
            self::ArgentinianPeso => 'Argentinische Peso',
            self::AustrianSchilling => 'Österreichische Schilling',
            self::AustralianDollar => 'Australische Dollar',
            self::ArubaFlorin => 'Aruba Gulden',
            self::AzerbaijaniManatOld => 'Aserbaidschan Manat - alt',
            self::AzerbaijaniManat => 'Aserbaidschan Manat',
            self::BosnianMark => 'Bosnien Mark',
            self::BarbadosDollar => 'Barbados Dollar',
            self::BangladeshTaka => 'Bangladesch Taka',
            self::BelgianFranc => 'Belgische Francs',
            self::BulgarianLevOld => 'Bulgarische Lew - alt',
            self::BulgarianLev => 'Bulgarische Lew',
            self::BahrainDinar => 'Bahrain Dinar',
            self::BurundiFranc => 'Burundi Franc',
            self::BermudaDollar => 'Bermuda Dollar',
            self::BruneiDollar => 'Brunei Dollar',
            self::Boliviano => 'Boliviano',
            self::BrazilianReal => 'Brasilianische Real',
            self::BahamianDollar => 'Bahama Dollar',
            self::BhutanNgultrum => 'Bhutan Ngultrum',
            self::BotswanaPula => 'Botswana Pula',
            self::BelarusRubleOld => 'Belarus Rubel - alt',
            self::BelarusRuble => 'Belarus Rubel',
            self::BelarusRubleSecondOld => 'Belarus Rubel - alt',
            self::BelizeDollar => 'Belize Dollar',
            self::CanadianDollar => 'Kanadische Dollar',
            self::CongoFranc => 'Kongo Franc',
            self::SwissFranc => 'Schweizer Franken',
            self::ChileanPeso => 'Chilenische Peso',
            self::ChineseYuanRenminbi => 'Chinesische Renminbi',
            self::ColombianPeso => 'Kolumbianischer Peso',
            self::CostaRicaColon => 'Costa Rica Colon',
            self::SerbianDinarOld => 'Serbische Dinar - alt',
            self::CubanPeso => 'Cuba Peso',
            self::CapeVerdeEscudo => 'Kap Verde Escudo',
            self::CyprusPound => 'Zypriotisches Pfund',
            self::CzechKoruna => 'Tschechische Kronen',
            self::GermanMark => 'Deutsche Mark',
            self::DjiboutiFrancOld => 'Dschibuti Franc - alt',
            self::DjiboutiFranc => 'Dschibuti Franc',
            self::DanishKrone => 'Dänische Krone',
            self::DominicanPeso => 'Dominikanische Peso',
            self::AlgerianDinar => 'Algerischer Dinar',
            self::EcuadorSucre => 'Ecuador Sucre',
            self::EstonianKroon => 'Estnische Krone',
            self::EgyptianPound => 'Ägyptisches Pfund',
            self::EritreanNakfa => 'Eritreischer Nakfa',
            self::SpanishPeseta => 'Spanische Peseta',
            self::EthiopianBirr => 'Äthiopischer Birr',
            self::Euro => 'Euro',
            self::FinnishMarkka => 'Finnmark',
            self::FijianDollar => 'Fidschi-Dollar',
            self::FalklandIslandsPound => 'Falkland-Pfund',
            self::FrenchFranc => 'Französische Francs',
            self::BritishPound => 'Britisches Pfund',
            self::GeorgianLari => 'Georgischer Lari',
            self::GhanaCediOld => 'Ghanaischer Cedi - alt',
            self::GhanaCedi => 'Ghanaischer Cedi',
            self::GibraltarPound => 'Gibraltar-Pfund',
            self::GambianDalasi => 'Gambischer Dalasi',
            self::GuineanFranc => 'Guinea-Franc',
            self::GreekDrachma => 'Griechische Drachme',
            self::GuatemalanQuetzal => 'Guatemala-Quetzal',
            self::GuyaneseDollar => 'Guyana-Dollar',
            self::HongKongDollar => 'Hongkong-Dollar',
            self::HonduranLempira => 'Honduras-Lempira',
            self::CroatianKuna => 'Kroatische Kuna',
            self::HaitianGourde => 'Haitianische Gourde',
            self::HungarianForint => 'Ungarischer Forint',
            self::IndonesianRupiah => 'Indonesische Rupiah',
            self::IrishPound => 'Irisches Pfund',
            self::IsraeliShekel => 'Israelischer Schekel',
            self::IndianRupee => 'Indische Rupie',
            self::IraqiDinar => 'Irakischer Dinar',
            self::IranianRial => 'Iranischer Rial',
            self::IcelandicKrona => 'Isländische Krone',
            self::ItalianLira => 'Italienische Lira',
            self::JamaicanDollar => 'Jamaika-Dollar',
            self::JordanianDinar => 'Jordanischer Dinar',
            self::JapaneseYen => 'Japanischer Yen',
            self::KenyanShilling => 'Kenia-Schilling',
            self::KyrgyzstaniSom => 'Kirgisischer Som',
            self::CambodianRiel => 'Kambodschanischer Riel',
            self::ComorianFranc => 'Komoren-Franc',
            self::NorthKoreanWon => 'Nordkoreanischer Won',
            self::SouthKoreanWon => 'Südkoreanischer Won',
            self::KuwaitiDinar => 'Kuwait-Dinar',
            self::CaymanIslandsDollar => 'Kaimaninseln-Dollar',
            self::KazakhstaniTenge => 'Kasachische Tenge',
            self::LaoKip => 'Laotischer Kip',
            self::LebanesePound => 'Libanesisches Pfund',
            self::SriLankanRupee => 'Sri-Lanka-Rupie',
            self::LiberianDollar => 'Liberianischer Dollar',
            self::LesothoLoti => 'Lesotho Loti',
            self::LithuanianLitas => 'Litauischer Litas',
            self::LuxembourgFranc => 'Luxemburgischer Franc',
            self::LatvianLats => 'Lettischer Lats',
            self::LibyanDinar => 'Libyscher Dinar',
            self::MoroccanDirham => 'Marokkanischer Dirham',
            self::MalagasyAriary => 'Madagaskar Ariary',
            self::MoldovanLeu => 'Moldauischer Leu',
            self::MalagasyFrancOld => 'Madagaskar-Franc - alt',
            self::MacedonianDenar => 'Mazedonischer Denar',
            self::MyanmarKyat => 'Myanmar-Kyat',
            self::MongolianTugrik => 'Mongolischer Tugrik',
            self::MacanesePataca => 'Macau-Pataca',
            self::MauritanianOuguiyaOld => 'Mauretanische Ouguiya - alt',
            self::MauritanianOuguiya => 'Mauretanische Ouguiya',
            self::MalteseLira => 'Maltesische Lira',
            self::MauritianRupee => 'Mauritius-Rupie',
            self::MaldivianRufiyaa => 'Malediven-Rufiyaa',
            self::MalawianKwacha => 'Malawi-Kwacha',
            self::MexicanPeso => 'Mexikanischer Peso',
            self::MalaysianRinggit => 'Malaysischer Ringgit',
            self::MozambicanMeticalOld => 'Mosambik-Metical - alt',
            self::MozambicanMetical => 'Mosambik-Metical',
            self::NamibianDollar => 'Namibia-Dollar',
            self::NigerianNaira => 'Nigerianischer Naira',
            self::NicaraguanCordoba => 'Nicaragua-Córdoba',
            self::DutchGuilder => 'Niederländischer Gulden',
            self::NorwegianKrone => 'Norwegische Krone',
            self::NepaleseRupee => 'Nepalesische Rupie',
            self::NewZealandDollar => 'Neuseeland-Dollar',
            self::OmaniRial => 'Omanischer Rial',
            self::PanamanianBalboa => 'Panama-Balboa',
            self::PeruvianSol => 'Peruanischer Sol',
            self::PapuaNewGuineaKina => 'Papua-Neuguinea-Kina',
            self::PhilippinePeso => 'Philippinischer Peso',
            self::PakistaniRupee => 'Pakistanische Rupie',
            self::PolishZloty => 'Polnischer Zloty',
            self::PolishZlotyOld => 'Polnischer Zloty - alt',
            self::PortugueseEscudo => 'Portugiesischer Escudo',
            self::ParaguayanGuarani => 'Paraguay-Guarani',
            self::QatariRial => 'Katar-Rial',
            self::RomanianLeuOld => 'Rumänischer Leu - alt',
            self::RomanianLeu => 'Rumänischer Leu',
            self::SerbianDinar => 'Serbischer Dinar',
            self::RussianRuble => 'Russischer Rubel',
            self::RussianRubleOld => 'Russischer Rubel - alt',
            self::RwandanFranc => 'Ruanda-Franc',
            self::SaudiRiyal => 'Saudi-Rial',
            self::SolomonIslandsDollar => 'Salomonen-Dollar',
            self::SeychellesRupee => 'Seychellen-Rupie',
            self::SudaneseDinarOld => 'Sudanesischer Dinar - alt',
            self::SudanesePound => 'Sudanesisches Pfund',
            self::SwedishKrona => 'Schwedische Krone',
            self::SingaporeDollar => 'Singapur-Dollar',
            self::SaintHelenaPound => 'St. Helena-Pfund',
            self::SlovenianTolar => 'Slowenischer Tolar',
            self::SlovakKoruna => 'Slowakische Krone',
            self::SierraLeoneanLeone => 'Sierra-Leone-Leone',
            self::SomaliShilling => 'Somali-Schilling',
            self::SurinameseDollar => 'Surinamischer Dollar',
            self::SurinameseGuilder => 'Surinamischer Gulden',
            self::SouthSudanesePound => 'Südsudanesisches Pfund',
            self::SaoTomeDobraOld => 'São-Tomé-Dobra - alt',
            self::ElSalvadorColon => 'El-Salvador-Colón',
            self::SyrianPound => 'Syrisches Pfund',
            self::SwaziLilangeni => 'Swasiland-Lilangeni',
            self::ThaiBaht => 'Thailändischer Baht',
            self::TajikistaniRuble => 'Tadschikischer Rubel',
            self::TajikistaniSomoni => 'Tadschikischer Somoni',
            self::TurkmenistaniManatOld => 'Turkmenischer Manat - alt',
            self::TurkmenistaniManat => 'Turkmenischer Manat',
            self::TunisianDinar => 'Tunesischer Dinar',
            self::TonganPaanga => 'Tonga-Paʻanga',
            self::TurkishLiraOld => 'Türkische Lira - alt',
            self::TurkishLira => 'Türkische Lira',
            self::TrinidadAndTobagoDollar => 'Trinidad-und-Tobago-Dollar',
            self::NewTaiwanDollar => 'Neuer Taiwan-Dollar',
            self::TanzanianShilling => 'Tansanischer Schilling',
            self::UkrainianHryvnia => 'Ukrainische Hrywnja',
            self::UgandanShilling => 'Ugandischer Schilling',
            self::USDollar => 'US-Dollar',
            self::UruguayanPeso => 'Uruguayischer Peso',
            self::UzbekistaniSom => 'Usbekischer Soʻm',
            self::VenezuelanBolivarOld => 'Venezuelanischer Bolivar - alt',
            self::VenezuelanBolivar => 'Venezuelanischer Bolivar',
            self::VietnameseDong => 'Vietnamesischer Dong',
            self::VanuatuVatu => 'Vanuatu-Vatu',
            self::SamoanTala => 'Samoanischer Tala',
            self::CFAFrancBEAC => 'CFA-Franc BEAC',
            self::EastCaribbeanDollar => 'Ostkaribischer Dollar',
            self::CFAFrancBCEAO => 'CFA-Franc BCEAO',
            self::CFPFranc => 'CFP-Franc',
            self::YemeniRial => 'Jemenitischer Rial',
            self::SouthAfricanRand => 'Südafrikanischer Rand',
            self::ZambianKwachaOld => 'Sambischer Kwacha - alt',
            self::ZambianKwacha => 'Sambischer Kwacha',
            self::ZaireOld => 'Zaire - alt',
            self::ZimbabweDollarOld => 'Simbabwe-Dollar - alt',
            self::ZimbabweDollarSecondOld => 'Simbabwe-Dollar - alt',
            default => $this->value, // Fallback
        };
    }

    public function isHistorical(): bool {
        return match ($this) {
            // Euro-Vorgänger und andere eingestellte Währungen
            self::GermanMark,          // DEM
            self::FrenchFranc,         // FRF
            self::ItalianLira,         // ITL
            self::SpanishPeseta,       // ESP
            self::DutchGuilder,        // NLG
            self::BelgianFranc,        // BEF
            self::AustrianSchilling,   // ATS
            self::FinnishMarkka,       // FIM
            self::SlovenianTolar,      // SIT
            self::SlovakKoruna,        // SKK
            self::EstonianKroon,       // EEK
            self::LatvianLats,         // LVL
            self::LithuanianLitas,     // LTL
            self::CyprusPound,         // CYP
            self::MalteseLira,         // MTL
            self::GreekDrachma,        // GRD
            self::IrishPound,          // IEP
            self::PortugueseEscudo,    // PTE

            // Diverse alte Währungen / Umstellungen
            self::MalagasyFrancOld,        // MGF
            self::ZaireOld,                // ZRN
            self::ZimbabweDollarOld,       // ZWD
            self::ZimbabweDollarSecondOld, // ZWR
            self::VenezuelanBolivarOld,    // VEB
            self::TurkishLiraOld,          // TRL
            self::PolishZlotyOld,          // PLZ
            self::RomanianLeuOld,          // ROL
            self::RussianRubleOld,         // RUR
            self::AfghaniOld,              // AFA
            self::AngolaKwanzaOld,         // AON
            self::AngolaKwanzaROld,        // AOR
            self::AzerbaijaniManatOld,     // AZM
            self::SerbianDinarOld,         // CSD
            self::DjiboutiFrancOld,        // DJF
            self::SudaneseDinarOld,        // SDD
            self::TajikistaniRuble,        // TJR
            self::TurkmenistaniManatOld,   // TMM
            self::SaoTomeDobraOld,         // STD
            self::MauritanianOuguiyaOld,   // MRO

            self::CroatianKuna,            // HRK - seit 2023 durch EUR ersetzt

            => true,

            default => false,
        };
    }

    public function isPrimaryCurrency(): bool {
        return match ($this) {
            self::Euro,
            self::USDollar,
            self::BritishPound,
            self::JapaneseYen,
            self::IsraeliShekel,
            self::IndianRupee,
            self::SouthKoreanWon,
            self::RussianRuble,
            self::VietnameseDong,
            self::NigerianNaira,
            self::TurkishLira,
            self::IranianRial,
            self::ArabEmirateDirham,
            => true,

            default => false,
        };
    }

    public function getSymbol(): string {
        return match ($this) {

            // Eurozone & direkte Ableitungen
            self::Euro => '€',

            // Dollar-Varianten (immer $ – Unterscheidung per getLabel())
            self::USDollar,
            self::AustralianDollar,
            self::CanadianDollar,
            self::NewZealandDollar,
            self::HongKongDollar,
            self::SingaporeDollar,
            self::FijianDollar,
            self::EastCaribbeanDollar,
            self::BelizeDollar,
            self::BahamianDollar,
            self::BermudaDollar,
            self::BarbadosDollar,
            self::JamaicanDollar,
            self::TrinidadAndTobagoDollar,
            self::NamibianDollar,
            self::ZimbabweDollarOld,
            self::ZimbabweDollarSecondOld,
            self::SolomonIslandsDollar,
            self::LiberianDollar,
            self::GuyaneseDollar,
            self::CaymanIslandsDollar,
            => '$',

            // Britische Pfund-Varianten
            self::BritishPound,
            self::GibraltarPound,
            self::FalklandIslandsPound,
            self::SaintHelenaPound,
            => '£',

            // Japanischer Yen und Yuan
            self::JapaneseYen,
            self::ChineseYuanRenminbi,
            => '¥',

            // Spezifische Symbole
            self::IsraeliShekel => '₪',

            self::IndianRupee => '₹',

            self::SouthKoreanWon,
            self::NorthKoreanWon => '₩',

            self::RussianRuble => '₽',

            self::VietnameseDong => '₫',

            self::NigerianNaira => '₦',

            self::TurkishLira,
            self::TurkishLiraOld,
            => '₺',

            self::IranianRial,
            self::QatariRial,
            self::SaudiRiyal,
            self::OmaniRial,
            self::YemeniRial,
            => '﷼',

            self::ArabEmirateDirham => 'د.إ',

            // Default
            default => '',
        };
    }

    public static function fromSymbol(string $symbol): self {
        $symbol = trim($symbol);

        $modern  = null;
        $historical = null;

        foreach (self::cases() as $case) {
            if ($case->getSymbol() !== $symbol) {
                continue;
            }

            if ($case->isPrimaryCurrency()) {
                return $case;
            } elseif (!$case->isHistorical() && $modern === null) {
                $modern = $case;
            } elseif ($case->isHistorical() && $historical === null) {
                $historical = $case;
            }
        }

        if ($modern !== null) {
            return $modern;
        } elseif ($historical !== null) {
            return $historical;
        }

        throw new InvalidArgumentException("Unbekanntes Währungssymbol: $symbol");
    }

    public static function fromCode(string $code): self {
        $code = strtoupper(trim($code));

        return match (true) {
            self::tryFrom($code) !== null => self::from($code),
            default => throw new InvalidArgumentException("Unbekannter ISO-4217-Code: $code")
        };
    }
}