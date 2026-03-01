<?php

namespace App\Validators;

use App\Contracts\CountryValidatorInterface;
use App\Enums\CountryEnum;
use InvalidArgumentException;

class CountryValidatorFactory
{
    /**
     * Map of country codes to validator classes.
     */
    private array $validators = [
        CountryEnum::COUNTRY_USA->value => USAValidator::class,
        CountryEnum::COUNTRY_GERMANY->value => GermanyValidator::class,
    ];

    /**
     * Create a validator for the given country.
     */
    public function make(string $country): CountryValidatorInterface
    {
        $validatorClass = $this->validators[$country] ?? null;

        if ($validatorClass === null) {
            throw new InvalidArgumentException("No validator registered for country: {$country}");
        }

        return new $validatorClass();
    }

    /**
     * Check if a validator exists for the given country.
     */
    public function supports(string $country): bool
    {
        return isset($this->validators[$country]);
    }

    /**
     * Get all supported country codes.
     *
     * @return string[]
     */
    public function supportedCountries(): array
    {
        return array_keys($this->validators);
    }
}
