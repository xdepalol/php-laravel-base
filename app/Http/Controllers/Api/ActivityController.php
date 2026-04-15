<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\IndexActivityRequest;
use App\Http\Requests\Activity\StoreActivityRequest;
use App\Http\Requests\Activity\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;

class ActivityController extends Controller
{
    /**
     * Display a listing of the activity.
     *
     * Optional query: subject_group_ids[], academic_year_id, status.
     * Supports multiple dashboards (teacher groups, student calendar, etc.).
     * Sprint/backlog scoping can extend this pattern later (e.g. phase_id, is_sprint).
     */
    public function index(IndexActivityRequest $request)
    {
        $this->authorize('activity-list');

        $query = Activity::query()->with(['subjectGroups', 'activityRoleType']);

        $filters = $request->validated();

        $groupIds = $filters['subject_group_ids'] ?? null;
        if (is_array($groupIds) && count($groupIds) > 0) {
            $query->whereHas('subjectGroups', function ($q) use ($groupIds) {
                $q->whereIn('subject_groups.id', $groupIds);
            });
        }

        if (array_key_exists('academic_year_id', $filters) && $filters['academic_year_id'] !== null) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        $activitys = $query->orderBy('id')->get();

        return ActivityResource::collection($activitys);
    }

    /**
     * Store a newly created activity in storage.
     */
    public function store(StoreActivityRequest $request)
    {
        $this->authorize('activity-create');

        $activity = new Activity;
        $activity->academic_year_id = $request->academic_year_id;
        $activity->title = $request->title;
        $activity->description = $request->description;
        $activity->activity_role_type_id = $request->activity_role_type_id;
        $activity->type = $request->type;
        $activity->has_sprints = $request->has_sprints;
        $activity->has_backlog = $request->has_backlog;
        $activity->students_may_assign_own_team_role = $request->boolean('students_may_assign_own_team_role');
        $activity->is_intermodular = $request->is_intermodular;
        $activity->status = $request->status;
        $activity->start_date = $request->start_date;
        $activity->end_date = $request->end_date;
        if ($activity->save()) {
            $activity->subjectGroups()->sync($request->subject_groups);
            $activity->load(['subjectGroups', 'activityRoleType']);

            return new ActivityResource($activity);
        }
    }

    /**
     * Display the specified activity.
     */
    public function show(Activity $activity)
    {
        $this->authorize('activity-view');

        $activity->load(['subjectGroups', 'activityRoleType']);

        return new ActivityResource($activity);
    }

    /**
     * Update the specified activity in storage.
     */
    public function update(UpdateActivityRequest $request, Activity $activity)
    {
        $this->authorize('activity-edit');

        $activity->academic_year_id = $request->academic_year_id;
        $activity->title = $request->title;
        $activity->description = $request->description;
        $activity->activity_role_type_id = $request->activity_role_type_id;
        $activity->type = $request->type;
        $activity->has_sprints = $request->has_sprints;
        $activity->has_backlog = $request->has_backlog;
        $activity->students_may_assign_own_team_role = $request->boolean('students_may_assign_own_team_role');
        $activity->is_intermodular = $request->is_intermodular;
        $activity->status = $request->status;
        $activity->start_date = $request->start_date;
        $activity->end_date = $request->end_date;
        if ($activity->save()) {
            $activity->subjectGroups()->sync($request->subject_groups);
            $activity->load(['subjectGroups', 'activityRoleType']);

            return new ActivityResource($activity);
        }

        return null;
    }

    /**
     * Remove the specified activity from storage.
     */
    public function destroy(Activity $activity)
    {
        $this->authorize('activity-delete');

        $activity->delete();

        return $activity;
    }
}
