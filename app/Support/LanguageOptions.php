<?php

namespace App\Support;

class LanguageOptions
{
    /**
     * Languages commonly used across Erasmus+ application and reporting work.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'en' => 'English',
            'ro' => 'Romanian',
            'fr' => 'French',
            'de' => 'German',
            'es' => 'Spanish',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'nl' => 'Dutch',
            'pl' => 'Polish',
            'cs' => 'Czech',
            'sk' => 'Slovak',
            'hu' => 'Hungarian',
            'bg' => 'Bulgarian',
            'el' => 'Greek',
            'hr' => 'Croatian',
            'sl' => 'Slovenian',
            'lt' => 'Lithuanian',
            'lv' => 'Latvian',
            'et' => 'Estonian',
            'fi' => 'Finnish',
            'sv' => 'Swedish',
            'da' => 'Danish',
            'ga' => 'Irish',
            'mt' => 'Maltese',
            'no' => 'Norwegian',
            'tr' => 'Turkish',
            'uk' => 'Ukrainian',
            'sr' => 'Serbian',
            'mk' => 'Macedonian',
            'sq' => 'Albanian',
            'bs' => 'Bosnian',
        ];
    }

    public static function label(?string $code): string
    {
        if (! $code) {
            return 'English';
        }

        return self::all()[$code] ?? strtoupper($code);
    }

    public static function wordLocale(?string $code): string
    {
        return match ($code) {
            'en' => 'en-US',
            'ro' => 'ro-RO',
            'fr' => 'fr-FR',
            'de' => 'de-DE',
            'es' => 'es-ES',
            'it' => 'it-IT',
            'pt' => 'pt-PT',
            'nl' => 'nl-NL',
            'pl' => 'pl-PL',
            'cs' => 'cs-CZ',
            'sk' => 'sk-SK',
            'hu' => 'hu-HU',
            'bg' => 'bg-BG',
            'el' => 'el-GR',
            'hr' => 'hr-HR',
            'sl' => 'sl-SI',
            'lt' => 'lt-LT',
            'lv' => 'lv-LV',
            'et' => 'et-EE',
            'fi' => 'fi-FI',
            'sv' => 'sv-SE',
            'da' => 'da-DK',
            'ga' => 'ga-IE',
            'mt' => 'mt-MT',
            'tr' => 'tr-TR',
            'uk' => 'uk-UA',
            default => $code ?: 'en-US',
        };
    }
}
