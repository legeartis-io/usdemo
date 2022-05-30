<?php

namespace UnifiedSearch\modules;

use UnifiedSearch\language\LanguageTemplateEn;
use UnifiedSearch\language\LanguageTemplateRu;

class Language
{
    /**
     * @var string
     */
    protected $language;

    public function __construct($defaultLanguage = 'ru_RU')
    {
        $this->language = $defaultLanguage;
        if (!isset($_COOKIE['interface_language'])) {
            $this->language = $defaultLanguage;
        }
    }

    /**
     * @return array
     */
    public function getLocalizationsList()
    {
        return [
            'Русский' => 'ru_RU',
            'English (USA)' => 'en_US',
            'Chinese' => 'zh_CN',
            'Turkish' => 'tr_TR',
            'French' => 'fr_FR',
            'German' => 'de_DE',
            'Hindi' => 'hi_IN',
            'Spanish' => 'es_ES',
            'Japanese' => 'ja_JP',
            'Dutch' => 'nl_NL',
            'English (UK)' => 'en_GB',
            'Greek' => 'el_GR',
            'Italian' => 'it_IT',
            'Korean' => 'ko_KR',
            'Polish' => 'pl_PL',
            'Português' => 'pt_PT',
            'Svenska' => 'sv_SE',
            'Thai' => 'th_TH',
            'Traditional Chinese' => 'zh_TW',
            'Czech' => 'cs_CZ',
            'Danish' => 'da_DK',
            'Finnish' => 'fi_FI',
            'Hungarian' => 'hu_HU',
            'Romanian' => 'ro_RO',
            'Croatian' => 'hr_HR',
            'Estonian' => 'et_EE',
            'Latvian' => 'lv_LV',
            'Lithuanian' => 'lt_LT',
            'Български' => 'bg_BG',
            'Slovak' => 'sk_SK',
        ];
    }


    public function getLocalization(): string
    {
        return $this->language;
    }

    public function setLocalization(string $code)
    {
        $this->language = $code;
        setcookie('interface_language', $code);
    }

    public function t($name)
    {
        $name = (string)$name;

        $localization = $this->getLocalization();

        $langArr = $this->getLanguageData($localization);

        if (array_key_exists($name, $langArr) && $langArr[$name]) {
            return (string)$langArr[$name];
        } else {
            return (string)$name;
        }
    }

    /**
     * @param string $lang
     * @return string[]
     */
    public function getLanguageData(string $lang): array
    {
        static $data = [];

        if (!array_key_exists($lang, $data)) {
            $currentTemplateClass = 'UnifiedSearch\language\LanguageTemplateEn';

            if ($lang) {
                $currentTemplateClass = 'UnifiedSearch\language\LanguageTemplate' . ucfirst($lang);
            }

            switch ($lang) {
                case 'ru':
                case 'ru_RU':
                    $data[$lang] = LanguageTemplateRu::$language_data;
                    break;
                case 'en':
                case 'en_GB':
                    $data[$lang] = LanguageTemplateEn::$language_data;
                    break;
                default:
                    /** @var string $language_data */
                    $data[$lang] = class_exists($currentTemplateClass) ? $currentTemplateClass::$language_data : LanguageTemplateEn::$language_data;
            }
        }

        return $data[$lang];
    }
}