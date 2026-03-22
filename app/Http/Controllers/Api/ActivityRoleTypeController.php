<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityRoleType\StoreActivityRoleTypeRequest;
use App\Http\Requests\ActivityRoleType\UpdateActivityRoleTypeRequest;
use App\Http\Resources\ActivityRoleTypeResource;
use App\Models\ActivityRoleType;

class ActivityRoleTypeController extends Controller
{
    /**
     * Display a listing of the activity role type.
     */
    public function index()
    {
        $this->authorize('activityroletype-list');

        $activityRoleTypes = ActivityRoleType::with('activityRoles')->get();

        return ActivityRoleTypeResource::collection($activityRoleTypes);
    }

    /**
     * Store a newly created activity role type in storage.
     */
    public function store(StoreActivityRoleTypeRequest $request)
    {
        $this->authorize('activityroletype-create');

        $activityRoleType = new ActivityRoleType();
        $activityRoleType->name = $request->name;
        $activityRoleType->description = $request->description;
        if ($activityRoleType->save()) {
            return new ActivityRoleTypeResource($activityRoleType);
        }
    }

    /**
     * Display the specified activity role type.
     */
    public function show(ActivityRoleType $activityRoleType)
    {
        $this->authorize('activityroletype-view');

        $activityRoleType->load('activityRoles');

        return new ActivityRoleTypeResource($activityRoleType);
    }

    /**
     * Update the specified activity role type in storage.
     */
    public function update(UpdateActivityRoleTypeRequest $request, ActivityRoleType $activityRoleType)
    {
        $this->authorize('activityroletype-edit');

        $activityRoleType->name = $request->name;
        $activityRoleType->description = $request->description;
        if ($activityRoleType->save()) {
            return new ActivityRoleTypeResource($activityRoleType);
        }

        return null;
    }

    /**
     * Remove the specified activity role type from storage.
     */
    public function destroy(ActivityRoleType $activityRoleType)
    {
        $this->authorize('activityroletype-delete');

        $activityRoleType->delete();

        return new ActivityRoleTypeResource($activityRoleType);
    }
}
