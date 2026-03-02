<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchemaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'country' => $this['country'],
            'step_id' => $this['step_id'],
            'schema' => $this['schema'],
        ];
    }
}
