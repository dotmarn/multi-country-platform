<?php

namespace App\Enums;

enum CountryEnum: string
{
    case COUNTRY_USA = 'USA';
    case COUNTRY_GERMANY = 'GERMANY';

    public static function supportedCountries(): array
    {
        return [
            self::COUNTRY_USA->value,
            self::COUNTRY_GERMANY->value,
        ];
    }
}
