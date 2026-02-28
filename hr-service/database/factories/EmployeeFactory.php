<?php

namespace Database\Factories;

use App\Enums\CountryEnum;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $country = fake()->randomElement(CountryEnum::supportedCountries());

        $base = [
            'name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'salary' => fake()->numberBetween(30000, 120000),
            'country' => $country,
        ];

        if ($country === CountryEnum::COUNTRY_USA->value) {
            $base['ssn'] = fake()->numerify('###-##-####');
            $base['address'] = fake()->address();
        } else {
            $base['goal'] = fake()->sentence();
            $base['tax_id'] = 'DE' . fake()->numerify('#########');
        }

        return $base;
    }

    public function usa(): static
    {
        return $this->state(fn () => [
            'country' => CountryEnum::COUNTRY_USA->value,
            'ssn' => fake()->numerify('###-##-####'),
            'address' => fake()->address(),
            'goal' => null,
            'tax_id' => null,
        ]);
    }

    public function germany(): static
    {
        return $this->state(fn () => [
            'country' => CountryEnum::COUNTRY_GERMANY->value,
            'goal' => fake()->sentence(),
            'tax_id' => 'DE' . fake()->numerify('#########'),
            'ssn' => null,
            'address' => null,
        ]);
    }
}
