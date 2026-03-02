<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CountryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class StepsController extends Controller
{
    public function index(CountryRequest $request): JsonResponse
    {
        $country = $request->validated('country');

        $steps = config("countries.{$country}.steps", []);

        return response()->success(
            Response::HTTP_OK,
            "Steps fetched successfully for country '{$country}'.",
            [
                'country' => $country,
                'steps' => $steps,
            ]
        );
    }
}
