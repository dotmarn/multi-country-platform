<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'country' => $this['country'],
            'columns' => $this['columns'],
            'data' => EmployeeResource::collection(collect($this['data'])),
            'meta' => $this['meta'],
        ];
    }
}
