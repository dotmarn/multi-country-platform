<?php

namespace App\Http\Requests;

use App\Enums\CountryEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'salary' => ['required', 'numeric', 'gt:0'],
            'country' => ['required', 'string', Rule::in(CountryEnum::supportedCountries())],
        ];

        $country = $this->input('country');

        if ($country === CountryEnum::COUNTRY_USA->value) {
            $rules['ssn'] = ['required', 'string', 'regex:/^\d{3}-\d{2}-\d{4}$/'];
            $rules['address'] = ['required', 'string', 'min:1'];
        }

        if ($country === CountryEnum::COUNTRY_GERMANY->value) {
            $rules['goal'] = ['required', 'string', 'min:1'];
            $rules['tax_id'] = ['required', 'string', 'regex:/^DE\d{9}$/'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'ssn.regex' => 'SSN must be in format XXX-XX-XXXX.',
            'tax_id.regex' => 'Tax ID must be in format DE followed by 9 digits (e.g., DE123456789).',
        ];
    }
}
