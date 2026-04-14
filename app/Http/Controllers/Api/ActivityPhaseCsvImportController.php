<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Phase\ImportActivityPhaseCsvRequest;
use App\Models\Activity;
use App\Services\PhaseCsvImportService;
use Illuminate\Http\JsonResponse;

class ActivityPhaseCsvImportController extends Controller
{
    public function store(ImportActivityPhaseCsvRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('phase-create');

        $result = app(PhaseCsvImportService::class)->import(
            $activity,
            $request->validated('csv')
        );

        return response()->json($result);
    }
}
