<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityRole\StoreActivityRoleRequest;
use App\Http\Requests\ActivityRole\UpdateActivityRoleRequest;
use App\Http\Resources\ActivityRoleResource;
use App\Models\ActivityRole;

class ActivityRoleController extends Controller
{
    /**
     * Display a listing of the activity role.
     */
    public function index()
    {
        $this->authorize('activityrole-list');

        $activityRoles = ActivityRole::with('activityRoleType')->get();

        return ActivityRoleResource::collection($activityRoles);
    }

    /**
     * Store a newly created activity role in storage.
     */
    public function store(StoreActivityRoleRequest $request)
    {
        $this->authorize('activityrole-create');

        $activityRole = new ActivityRole();
        $activityRole->activity_role_type_id = $request->activity_role_type_id;
        $activityRole->name = $request->name;
        $activityRole->description = $request->description;
        $activityRole->is_mandatory = $request->boolean('is_mandatory');
        $activityRole->max_per_team = $request->max_per_team;
        $activityRole->position = $request->position;
        if ($activityRole->save()) {
            $activityRole->load('activityRoleType');

            return new ActivityRoleResource($activityRole);
        }
    }

    /**
     * Display the specified activity role.
     */
    public function show(ActivityRole $activityRole)
    {
        $this->authorize('activityrole-view');

        $activityRole->load('activityRoleType');

        return new ActivityRoleResource($activityRole);
    }

    /**
     * Update the specified activity role in storage.
     */
    public function update(UpdateActivityRoleRequest $request, ActivityRole $activityRole)
    {
        $this->authorize('activityrole-edit');

        $activityRole->activity_role_type_id = $request->activity_role_type_id;
        $activityRole->name = $request->name;
        $activityRole->description = $request->description;
        $activityRole->is_mandatory = $request->boolean('is_mandatory');
        $activityRole->max_per_team = $request->max_per_team;
        $activityRole->position = $request->position;
        if ($activityRole->save()) {
            $activityRole->load('activityRoleType');

            return new ActivityRoleResource($activityRole);
        }

        return null;
    }

    /**
     * Remove the specified activity role from storage.
     */
    public function destroy(ActivityRole $activityRole)
    {
        $this->authorize('activityrole-delete');

        $activityRole->delete();

        return new ActivityRoleResource($activityRole);
    }
}
