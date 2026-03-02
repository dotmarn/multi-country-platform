<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'country' => $this['country'],
            'overall_completion' => $this['overall_completion'],
            'total_employees' => $this['total_employees'],
            'complete_count' => $this['complete_count'],
            'incomplete_count' => $this['incomplete_count'],
            'employees' => ChecklistEmployeeResource::collection(collect($this['employees'])),
        ];
    }
}
