<?php

namespace App\Http\Requests;

use App\Enums\CountryEnum;
use Illuminate\Foundation\Http\FormRequest;
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
}
