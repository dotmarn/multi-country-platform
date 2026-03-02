<?php

namespace App\Http\Requests;

use App\Enums\CountryEnum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country' => ['required', 'string', Rule::enum(CountryEnum::class)],
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
