<?php

namespace App\Http\Resources;

use App\Enums\CountryEnum;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'last_name' => $this->last_name,
            'salary' => $this->salary,
            'country' => $this->country,
        ];

        if ($this->country === CountryEnum::COUNTRY_USA->value) {
            $data['ssn'] = $this->ssn;
            $data['address'] = $this->address;
        }

        if ($this->country === CountryEnum::COUNTRY_GERMANY->value) {
            $data['goal'] = $this->goal;
            $data['tax_id'] = $this->tax_id;
        }

        $data['created_at'] = $this->created_at->toDateTimeString();
        $data['updated_at'] = $this->updated_at->toDateTimeString();

        return $data;
    }
}
