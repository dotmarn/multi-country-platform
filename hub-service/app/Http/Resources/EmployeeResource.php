<?php

namespace App\Http\Resources;

use App\Enums\CountryEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = collect($this->resource)->toArray();

        $country = $request->input('country');

        if ($country === CountryEnum::COUNTRY_USA->value && !empty($data['ssn'])) {
            $data['ssn'] = '***-**-' . substr($data['ssn'], -4);
        }

        return $data;
    }
}
