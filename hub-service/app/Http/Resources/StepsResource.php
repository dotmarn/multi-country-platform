<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StepsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'country' => $this['country'],
            'steps' => StepResource::collection(collect($this['steps'])),
        ];
    }
}
