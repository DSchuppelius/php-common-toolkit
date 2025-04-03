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
    case NorthKoreanWon = 'KPW';            // KPW - Nordkoreanischer Won
    case SouthKoreanWon = 'KRW';            // KRW - Südkoreanischer Won
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
}