<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CountryRequest;
use App\Services\ChecklistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ChecklistController extends Controller
{
    public function __construct(
        private readonly ChecklistService $checklistService,
    ) {}

    public function index(CountryRequest $request): JsonResponse
    {
        $country = $request->validated('country');

        $checklist = $this->checklistService->getChecklist($country);

        return response()->success(Response::HTTP_OK, "Checklists fetched successfully...", $checklist);
    }
}
