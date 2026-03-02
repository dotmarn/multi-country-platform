<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistEmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'employee_id' => $this['employee_id'],
            'name' => $this['name'],
            'is_complete' => $this['is_complete'],
            'completion_percentage' => $this['completion_percentage'],
            'fields' => $this['fields'],
        ];
    }
}
