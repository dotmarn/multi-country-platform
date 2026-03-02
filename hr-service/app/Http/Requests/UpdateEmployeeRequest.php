<?php

namespace App\Http\Requests;

use App\Enums\CountryEnum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $country = $employee?->country ?? $this->input('country');

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'salary' => ['sometimes', 'numeric', 'gt:0'],
        ];

        if ($country === CountryEnum::COUNTRY_USA->value) {
            $rules['ssn'] = ['sometimes', 'string', 'regex:/^\d{3}-\d{2}-\d{4}$/'];
            $rules['address'] = ['sometimes', 'string', 'min:1'];
        }

        if ($country === CountryEnum::COUNTRY_GERMANY->value) {
            $rules['goal'] = ['sometimes', 'string', 'min:1'];
            $rules['tax_id'] = ['sometimes', 'string', 'regex:/^DE\d{9}$/'];
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

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->error(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'There is one or more validation errors',
            $validator->errors()
        ));
    }
}
