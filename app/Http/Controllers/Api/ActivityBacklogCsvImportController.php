<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BacklogItem\ImportActivityBacklogCsvRequest;
use App\Models\Activity;
use App\Models\Team;
use App\Services\BacklogCsvImportService;
use Illuminate\Http\JsonResponse;

class ActivityBacklogCsvImportController extends Controller
{
    public function store(ImportActivityBacklogCsvRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('backlogitem-create');
        $this->authorize('backlogitem-edit');

        $team = Team::query()
            ->where('id', $request->validated('team_id'))
            ->where('activity_id', $activity->id)
            ->firstOrFail();

        $result = app(BacklogCsvImportService::class)->import(
            $activity,
            $team,
            $request->validated('csv')
        );

        return response()->json($result);
    }
}
