<?php
/*
 * Created on   : Thu Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : LanguageCode.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

use InvalidArgumentException;

/**
 * BCP-47 Sprachcodes (Language-Region Format).
 * Vollständige Implementierung aller ISO 639-1/ISO 3166-1 Kombinationen mit Bindestrich.
 *
 * @see http://www.lingoes.net/en/translator/langcode.htm
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/gl-account-description
 */
enum LanguageCode: string {
    // Afrikaans
    case AF_ZA = 'af-ZA'; // Afrikaans (South Africa)

        // Arabic
    case AR_AE = 'ar-AE'; // Arabic (U.A.E.)
    case AR_BH = 'ar-BH'; // Arabic (Bahrain)
    case AR_DZ = 'ar-DZ'; // Arabic (Algeria)
    case AR_EG = 'ar-EG'; // Arabic (Egypt)
    case AR_IQ = 'ar-IQ'; // Arabic (Iraq)
    case AR_JO = 'ar-JO'; // Arabic (Jordan)
    case AR_KW = 'ar-KW'; // Arabic (Kuwait)
    case AR_LB = 'ar-LB'; // Arabic (Lebanon)
    case AR_LY = 'ar-LY'; // Arabic (Libya)
    case AR_MA = 'ar-MA'; // Arabic (Morocco)
    case AR_OM = 'ar-OM'; // Arabic (Oman)
    case AR_QA = 'ar-QA'; // Arabic (Qatar)
    case AR_SA = 'ar-SA'; // Arabic (Saudi Arabia)
    case AR_SY = 'ar-SY'; // Arabic (Syria)
    case AR_TN = 'ar-TN'; // Arabic (Tunisia)
    case AR_YE = 'ar-YE'; // Arabic (Yemen)

        // Azeri
    case AZ_AZ = 'az-AZ'; // Azeri (Azerbaijan)

        // Belarusian
    case BE_BY = 'be-BY'; // Belarusian (Belarus)

        // Bulgarian
    case BG_BG = 'bg-BG'; // Bulgarian (Bulgaria)

        // Bosnian
    case BS_BA = 'bs-BA'; // Bosnian (Bosnia and Herzegovina)

        // Catalan
    case CA_ES = 'ca-ES'; // Catalan (Spain)

        // Czech
    case CS_CZ = 'cs-CZ'; // Czech (Czech Republic)

        // Welsh
    case CY_GB = 'cy-GB'; // Welsh (United Kingdom)

        // Danish
    case DA_DK = 'da-DK'; // Danish (Denmark)

        // German
    case DE_AT = 'de-AT'; // German (Austria)
    case DE_CH = 'de-CH'; // German (Switzerland)
    case DE_DE = 'de-DE'; // German (Germany)
    case DE_LI = 'de-LI'; // German (Liechtenstein)
    case DE_LU = 'de-LU'; // German (Luxembourg)

        // Divehi
    case DV_MV = 'dv-MV'; // Divehi (Maldives)

        // Greek
    case EL_GR = 'el-GR'; // Greek (Greece)

        // English
    case EN_AU = 'en-AU'; // English (Australia)
    case EN_BZ = 'en-BZ'; // English (Belize)
    case EN_CA = 'en-CA'; // English (Canada)
    case EN_CB = 'en-CB'; // English (Caribbean)
    case EN_GB = 'en-GB'; // English (United Kingdom)
    case EN_IE = 'en-IE'; // English (Ireland)
    case EN_JM = 'en-JM'; // English (Jamaica)
    case EN_NZ = 'en-NZ'; // English (New Zealand)
    case EN_PH = 'en-PH'; // English (Republic of the Philippines)
    case EN_TT = 'en-TT'; // English (Trinidad and Tobago)
    case EN_US = 'en-US'; // English (United States)
    case EN_ZA = 'en-ZA'; // English (South Africa)
    case EN_ZW = 'en-ZW'; // English (Zimbabwe)

        // Spanish
    case ES_AR = 'es-AR'; // Spanish (Argentina)
    case ES_BO = 'es-BO'; // Spanish (Bolivia)
    case ES_CL = 'es-CL'; // Spanish (Chile)
    case ES_CO = 'es-CO'; // Spanish (Colombia)
    case ES_CR = 'es-CR'; // Spanish (Costa Rica)
    case ES_DO = 'es-DO'; // Spanish (Dominican Republic)
    case ES_EC = 'es-EC'; // Spanish (Ecuador)
    case ES_ES = 'es-ES'; // Spanish (Spain)
    case ES_GT = 'es-GT'; // Spanish (Guatemala)
    case ES_HN = 'es-HN'; // Spanish (Honduras)
    case ES_MX = 'es-MX'; // Spanish (Mexico)
    case ES_NI = 'es-NI'; // Spanish (Nicaragua)
    case ES_PA = 'es-PA'; // Spanish (Panama)
    case ES_PE = 'es-PE'; // Spanish (Peru)
    case ES_PR = 'es-PR'; // Spanish (Puerto Rico)
    case ES_PY = 'es-PY'; // Spanish (Paraguay)
    case ES_SV = 'es-SV'; // Spanish (El Salvador)
    case ES_UY = 'es-UY'; // Spanish (Uruguay)
    case ES_VE = 'es-VE'; // Spanish (Venezuela)

        // Estonian
    case ET_EE = 'et-EE'; // Estonian (Estonia)

        // Basque
    case EU_ES = 'eu-ES'; // Basque (Spain)

        // Farsi
    case FA_IR = 'fa-IR'; // Farsi (Iran)

        // Finnish
    case FI_FI = 'fi-FI'; // Finnish (Finland)

        // Faroese
    case FO_FO = 'fo-FO'; // Faroese (Faroe Islands)

        // French
    case FR_BE = 'fr-BE'; // French (Belgium)
    case FR_CA = 'fr-CA'; // French (Canada)
    case FR_CH = 'fr-CH'; // French (Switzerland)
    case FR_FR = 'fr-FR'; // French (France)
    case FR_LU = 'fr-LU'; // French (Luxembourg)
    case FR_MC = 'fr-MC'; // French (Principality of Monaco)

        // Galician
    case GL_ES = 'gl-ES'; // Galician (Spain)

        // Gujarati
    case GU_IN = 'gu-IN'; // Gujarati (India)

        // Hebrew
    case HE_IL = 'he-IL'; // Hebrew (Israel)

        // Hindi
    case HI_IN = 'hi-IN'; // Hindi (India)

        // Croatian
    case HR_BA = 'hr-BA'; // Croatian (Bosnia and Herzegovina)
    case HR_HR = 'hr-HR'; // Croatian (Croatia)

        // Hungarian
    case HU_HU = 'hu-HU'; // Hungarian (Hungary)

        // Armenian
    case HY_AM = 'hy-AM'; // Armenian (Armenia)

        // Indonesian
    case ID_ID = 'id-ID'; // Indonesian (Indonesia)

        // Icelandic
    case IS_IS = 'is-IS'; // Icelandic (Iceland)

        // Italian
    case IT_CH = 'it-CH'; // Italian (Switzerland)
    case IT_IT = 'it-IT'; // Italian (Italy)

        // Japanese
    case JA_JP = 'ja-JP'; // Japanese (Japan)

        // Georgian
    case KA_GE = 'ka-GE'; // Georgian (Georgia)

        // Kazakh
    case KK_KZ = 'kk-KZ'; // Kazakh (Kazakhstan)

        // Kannada
    case KN_IN = 'kn-IN'; // Kannada (India)

        // Korean
    case KO_KR = 'ko-KR'; // Korean (Korea)

        // Konkani
    case KOK_IN = 'kok-IN'; // Konkani (India)

        // Kyrgyz
    case KY_KG = 'ky-KG'; // Kyrgyz (Kyrgyzstan)

        // Lithuanian
    case LT_LT = 'lt-LT'; // Lithuanian (Lithuania)

        // Latvian
    case LV_LV = 'lv-LV'; // Latvian (Latvia)

        // Maori
    case MI_NZ = 'mi-NZ'; // Maori (New Zealand)

        // Macedonian
    case MK_MK = 'mk-MK'; // FYRO Macedonian

        // Mongolian
    case MN_MN = 'mn-MN'; // Mongolian (Mongolia)

        // Marathi
    case MR_IN = 'mr-IN'; // Marathi (India)

        // Malay
    case MS_BN = 'ms-BN'; // Malay (Brunei Darussalam)
    case MS_MY = 'ms-MY'; // Malay (Malaysia)

        // Maltese
    case MT_MT = 'mt-MT'; // Maltese (Malta)

        // Norwegian
    case NB_NO = 'nb-NO'; // Norwegian Bokmål (Norway)
    case NN_NO = 'nn-NO'; // Norwegian Nynorsk (Norway)

        // Dutch
    case NL_BE = 'nl-BE'; // Dutch (Belgium)
    case NL_NL = 'nl-NL'; // Dutch (Netherlands)

        // Northern Sotho
    case NS_ZA = 'ns-ZA'; // Northern Sotho (South Africa)

        // Punjabi
    case PA_IN = 'pa-IN'; // Punjabi (India)

        // Polish
    case PL_PL = 'pl-PL'; // Polish (Poland)

        // Pashto
    case PS_AR = 'ps-AR'; // Pashto (Afghanistan)

        // Portuguese
    case PT_BR = 'pt-BR'; // Portuguese (Brazil)
    case PT_PT = 'pt-PT'; // Portuguese (Portugal)

        // Quechua
    case QU_BO = 'qu-BO'; // Quechua (Bolivia)
    case QU_EC = 'qu-EC'; // Quechua (Ecuador)
    case QU_PE = 'qu-PE'; // Quechua (Peru)

        // Romanian
    case RO_RO = 'ro-RO'; // Romanian (Romania)

        // Russian
    case RU_RU = 'ru-RU'; // Russian (Russia)

        // Sanskrit
    case SA_IN = 'sa-IN'; // Sanskrit (India)

        // Sami
    case SE_FI = 'se-FI'; // Sami (Finland)
    case SE_NO = 'se-NO'; // Sami (Norway)
    case SE_SE = 'se-SE'; // Sami (Sweden)

        // Slovak
    case SK_SK = 'sk-SK'; // Slovak (Slovakia)

        // Slovenian
    case SL_SI = 'sl-SI'; // Slovenian (Slovenia)

        // Albanian
    case SQ_AL = 'sq-AL'; // Albanian (Albania)

        // Serbian
    case SR_BA = 'sr-BA'; // Serbian (Bosnia and Herzegovina)
    case SR_SP = 'sr-SP'; // Serbian (Serbia and Montenegro)

        // Swedish
    case SV_FI = 'sv-FI'; // Swedish (Finland)
    case SV_SE = 'sv-SE'; // Swedish (Sweden)

        // Swahili
    case SW_KE = 'sw-KE'; // Swahili (Kenya)

        // Syriac
    case SYR_SY = 'syr-SY'; // Syriac (Syria)

        // Tamil
    case TA_IN = 'ta-IN'; // Tamil (India)

        // Telugu
    case TE_IN = 'te-IN'; // Telugu (India)

        // Thai
    case TH_TH = 'th-TH'; // Thai (Thailand)

        // Tagalog
    case TL_PH = 'tl-PH'; // Tagalog (Philippines)

        // Tswana
    case TN_ZA = 'tn-ZA'; // Tswana (South Africa)

        // Turkish
    case TR_TR = 'tr-TR'; // Turkish (Turkey)

        // Tatar
    case TT_RU = 'tt-RU'; // Tatar (Russia)

        // Ukrainian
    case UK_UA = 'uk-UA'; // Ukrainian (Ukraine)

        // Urdu
    case UR_PK = 'ur-PK'; // Urdu (Islamic Republic of Pakistan)

        // Uzbek
    case UZ_UZ = 'uz-UZ'; // Uzbek (Uzbekistan)

        // Vietnamese
    case VI_VN = 'vi-VN'; // Vietnamese (Viet Nam)

        // Xhosa
    case XH_ZA = 'xh-ZA'; // Xhosa (South Africa)

        // Chinese
    case ZH_CN = 'zh-CN'; // Chinese (Simplified)
    case ZH_HK = 'zh-HK'; // Chinese (Hong Kong)
    case ZH_MO = 'zh-MO'; // Chinese (Macau)
    case ZH_SG = 'zh-SG'; // Chinese (Singapore)
    case ZH_TW = 'zh-TW'; // Chinese (Traditional)

        // Zulu
    case ZU_ZA = 'zu-ZA'; // Zulu (South Africa)

    /**
     * Gibt die englische Bezeichnung der Sprache zurück.
     */
    public function getLabel(): string {
        return match ($this) {
            self::AF_ZA => 'Afrikaans (South Africa)',
            self::AR_AE => 'Arabic (U.A.E.)',
            self::AR_BH => 'Arabic (Bahrain)',
            self::AR_DZ => 'Arabic (Algeria)',
            self::AR_EG => 'Arabic (Egypt)',
            self::AR_IQ => 'Arabic (Iraq)',
            self::AR_JO => 'Arabic (Jordan)',
            self::AR_KW => 'Arabic (Kuwait)',
            self::AR_LB => 'Arabic (Lebanon)',
            self::AR_LY => 'Arabic (Libya)',
            self::AR_MA => 'Arabic (Morocco)',
            self::AR_OM => 'Arabic (Oman)',
            self::AR_QA => 'Arabic (Qatar)',
            self::AR_SA => 'Arabic (Saudi Arabia)',
            self::AR_SY => 'Arabic (Syria)',
            self::AR_TN => 'Arabic (Tunisia)',
            self::AR_YE => 'Arabic (Yemen)',
            self::AZ_AZ => 'Azeri (Azerbaijan)',
            self::BE_BY => 'Belarusian (Belarus)',
            self::BG_BG => 'Bulgarian (Bulgaria)',
            self::BS_BA => 'Bosnian (Bosnia and Herzegovina)',
            self::CA_ES => 'Catalan (Spain)',
            self::CS_CZ => 'Czech (Czech Republic)',
            self::CY_GB => 'Welsh (United Kingdom)',
            self::DA_DK => 'Danish (Denmark)',
            self::DE_AT => 'German (Austria)',
            self::DE_CH => 'German (Switzerland)',
            self::DE_DE => 'German (Germany)',
            self::DE_LI => 'German (Liechtenstein)',
            self::DE_LU => 'German (Luxembourg)',
            self::DV_MV => 'Divehi (Maldives)',
            self::EL_GR => 'Greek (Greece)',
            self::EN_AU => 'English (Australia)',
            self::EN_BZ => 'English (Belize)',
            self::EN_CA => 'English (Canada)',
            self::EN_CB => 'English (Caribbean)',
            self::EN_GB => 'English (United Kingdom)',
            self::EN_IE => 'English (Ireland)',
            self::EN_JM => 'English (Jamaica)',
            self::EN_NZ => 'English (New Zealand)',
            self::EN_PH => 'English (Philippines)',
            self::EN_TT => 'English (Trinidad and Tobago)',
            self::EN_US => 'English (United States)',
            self::EN_ZA => 'English (South Africa)',
            self::EN_ZW => 'English (Zimbabwe)',
            self::ES_AR => 'Spanish (Argentina)',
            self::ES_BO => 'Spanish (Bolivia)',
            self::ES_CL => 'Spanish (Chile)',
            self::ES_CO => 'Spanish (Colombia)',
            self::ES_CR => 'Spanish (Costa Rica)',
            self::ES_DO => 'Spanish (Dominican Republic)',
            self::ES_EC => 'Spanish (Ecuador)',
            self::ES_ES => 'Spanish (Spain)',
            self::ES_GT => 'Spanish (Guatemala)',
            self::ES_HN => 'Spanish (Honduras)',
            self::ES_MX => 'Spanish (Mexico)',
            self::ES_NI => 'Spanish (Nicaragua)',
            self::ES_PA => 'Spanish (Panama)',
            self::ES_PE => 'Spanish (Peru)',
            self::ES_PR => 'Spanish (Puerto Rico)',
            self::ES_PY => 'Spanish (Paraguay)',
            self::ES_SV => 'Spanish (El Salvador)',
            self::ES_UY => 'Spanish (Uruguay)',
            self::ES_VE => 'Spanish (Venezuela)',
            self::ET_EE => 'Estonian (Estonia)',
            self::EU_ES => 'Basque (Spain)',
            self::FA_IR => 'Farsi (Iran)',
            self::FI_FI => 'Finnish (Finland)',
            self::FO_FO => 'Faroese (Faroe Islands)',
            self::FR_BE => 'French (Belgium)',
            self::FR_CA => 'French (Canada)',
            self::FR_CH => 'French (Switzerland)',
            self::FR_FR => 'French (France)',
            self::FR_LU => 'French (Luxembourg)',
            self::FR_MC => 'French (Monaco)',
            self::GL_ES => 'Galician (Spain)',
            self::GU_IN => 'Gujarati (India)',
            self::HE_IL => 'Hebrew (Israel)',
            self::HI_IN => 'Hindi (India)',
            self::HR_BA => 'Croatian (Bosnia and Herzegovina)',
            self::HR_HR => 'Croatian (Croatia)',
            self::HU_HU => 'Hungarian (Hungary)',
            self::HY_AM => 'Armenian (Armenia)',
            self::ID_ID => 'Indonesian (Indonesia)',
            self::IS_IS => 'Icelandic (Iceland)',
            self::IT_CH => 'Italian (Switzerland)',
            self::IT_IT => 'Italian (Italy)',
            self::JA_JP => 'Japanese (Japan)',
            self::KA_GE => 'Georgian (Georgia)',
            self::KK_KZ => 'Kazakh (Kazakhstan)',
            self::KN_IN => 'Kannada (India)',
            self::KO_KR => 'Korean (Korea)',
            self::KOK_IN => 'Konkani (India)',
            self::KY_KG => 'Kyrgyz (Kyrgyzstan)',
            self::LT_LT => 'Lithuanian (Lithuania)',
            self::LV_LV => 'Latvian (Latvia)',
            self::MI_NZ => 'Maori (New Zealand)',
            self::MK_MK => 'Macedonian (North Macedonia)',
            self::MN_MN => 'Mongolian (Mongolia)',
            self::MR_IN => 'Marathi (India)',
            self::MS_BN => 'Malay (Brunei)',
            self::MS_MY => 'Malay (Malaysia)',
            self::MT_MT => 'Maltese (Malta)',
            self::NB_NO => 'Norwegian Bokmål (Norway)',
            self::NN_NO => 'Norwegian Nynorsk (Norway)',
            self::NL_BE => 'Dutch (Belgium)',
            self::NL_NL => 'Dutch (Netherlands)',
            self::NS_ZA => 'Northern Sotho (South Africa)',
            self::PA_IN => 'Punjabi (India)',
            self::PL_PL => 'Polish (Poland)',
            self::PS_AR => 'Pashto (Afghanistan)',
            self::PT_BR => 'Portuguese (Brazil)',
            self::PT_PT => 'Portuguese (Portugal)',
            self::QU_BO => 'Quechua (Bolivia)',
            self::QU_EC => 'Quechua (Ecuador)',
            self::QU_PE => 'Quechua (Peru)',
            self::RO_RO => 'Romanian (Romania)',
            self::RU_RU => 'Russian (Russia)',
            self::SA_IN => 'Sanskrit (India)',
            self::SE_FI => 'Sami (Finland)',
            self::SE_NO => 'Sami (Norway)',
            self::SE_SE => 'Sami (Sweden)',
            self::SK_SK => 'Slovak (Slovakia)',
            self::SL_SI => 'Slovenian (Slovenia)',
            self::SQ_AL => 'Albanian (Albania)',
            self::SR_BA => 'Serbian (Bosnia and Herzegovina)',
            self::SR_SP => 'Serbian (Serbia)',
            self::SV_FI => 'Swedish (Finland)',
            self::SV_SE => 'Swedish (Sweden)',
            self::SW_KE => 'Swahili (Kenya)',
            self::SYR_SY => 'Syriac (Syria)',
            self::TA_IN => 'Tamil (India)',
            self::TE_IN => 'Telugu (India)',
            self::TH_TH => 'Thai (Thailand)',
            self::TL_PH => 'Tagalog (Philippines)',
            self::TN_ZA => 'Tswana (South Africa)',
            self::TR_TR => 'Turkish (Turkey)',
            self::TT_RU => 'Tatar (Russia)',
            self::UK_UA => 'Ukrainian (Ukraine)',
            self::UR_PK => 'Urdu (Pakistan)',
            self::UZ_UZ => 'Uzbek (Uzbekistan)',
            self::VI_VN => 'Vietnamese (Vietnam)',
            self::XH_ZA => 'Xhosa (South Africa)',
            self::ZH_CN => 'Chinese (Simplified)',
            self::ZH_HK => 'Chinese (Hong Kong)',
            self::ZH_MO => 'Chinese (Macau)',
            self::ZH_SG => 'Chinese (Singapore)',
            self::ZH_TW => 'Chinese (Traditional)',
            self::ZU_ZA => 'Zulu (South Africa)',
        };
    }

    /**
     * Gibt den ISO-639-1 Sprachcode zurück (erster Teil vor dem Bindestrich).
     */
    public function getLanguageCode(): string {
        return explode('-', $this->value)[0];
    }

    /**
     * Gibt den ISO-3166-1 Alpha-2 Ländercode zurück (zweiter Teil nach dem Bindestrich).
     */
    public function getCountryCode(): string {
        return explode('-', $this->value)[1];
    }

    /**
     * Factory für String-Werte.
     *
     * @throws InvalidArgumentException
     */
    public static function fromString(string $value): self {
        $trimmed = trim($value, '" ');
        $normalized = str_replace('_', '-', $trimmed);

        // Versuche exakte Übereinstimmung (case-insensitive)
        foreach (self::cases() as $case) {
            if (strcasecmp($case->value, $normalized) === 0) {
                return $case;
            }
        }

        throw new InvalidArgumentException("Ungültiger Sprachcode: $value");
    }

    /**
     * Factory für String-Werte mit null-Rückgabe bei ungültigen Werten.
     */
    public static function tryFromString(string $value): ?self {
        $trimmed = trim($value, '" ');
        if ($trimmed === '') {
            return null;
        }

        try {
            return self::fromString($trimmed);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Prüft, ob dies ein deutscher Sprachcode ist (de-*).
     */
    public function isGerman(): bool {
        return str_starts_with($this->value, 'de-');
    }

    /**
     * Prüft, ob dies ein englischer Sprachcode ist (en-*).
     */
    public function isEnglish(): bool {
        return str_starts_with($this->value, 'en-');
    }

    /**
     * Prüft, ob dies ein französischer Sprachcode ist (fr-*).
     */
    public function isFrench(): bool {
        return str_starts_with($this->value, 'fr-');
    }

    /**
     * Prüft, ob dies ein spanischer Sprachcode ist (es-*).
     */
    public function isSpanish(): bool {
        return str_starts_with($this->value, 'es-');
    }

    /**
     * Prüft, ob dies ein italienischer Sprachcode ist (it-*).
     */
    public function isItalian(): bool {
        return str_starts_with($this->value, 'it-');
    }

    /**
     * Gibt alle Sprachcodes für eine bestimmte Sprache zurück.
     *
     * @return self[]
     */
    public static function getByLanguage(string $languageCode): array {
        $prefix = strtolower($languageCode) . '-';
        $result = [];
        foreach (self::cases() as $case) {
            if (str_starts_with(strtolower($case->value), $prefix)) {
                $result[] = $case;
            }
        }
        return $result;
    }

    /**
     * Gibt alle Sprachcodes für ein bestimmtes Land zurück.
     *
     * @return self[]
     */
    public static function getByCountry(string $countryCode): array {
        $suffix = '-' . strtoupper($countryCode);
        $result = [];
        foreach (self::cases() as $case) {
            if (str_ends_with($case->value, $suffix)) {
                $result[] = $case;
            }
        }
        return $result;
    }

    /**
     * Gibt den Wert für DATEV-CSV-Export zurück (mit Anführungszeichen).
     */
    public function toCsvValue(): string {
        return '"' . $this->value . '"';
    }
}