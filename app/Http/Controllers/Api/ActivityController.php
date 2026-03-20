<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\StoreActivityRequest;
use App\Http\Requests\Activity\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Display a listing of the activity.
     */
    public function index()
    {
        $this->authorize('activity-list');

        $activitys = Activity::all();
        return ActivityResource::collection($activitys);
    }

    /**
     * Store a newly created activity in storage.
     */
    public function store(StoreActivityRequest $request)
    {
        $this->authorize('activity-create');

        $activity = new Activity();
        $activity->academic_year_id = $request->academic_year_id;
        $activity->title = $request->title;
        $activity->description = $request->description;
        $activity->type = $request->type;
        $activity->has_sprints = $request->has_sprints;
        $activity->has_backlog = $request->has_backlog;
        $activity->is_intermodular = $request->is_intermodular;
        $activity->status = $request->status;
        $activity->start_date = $request->start_date;
        $activity->end_date = $request->end_date;
        if ($activity->save()) {
            $activity->subjectGroups()->sync($request->subject_groups);
            return new ActivityResource($activity);
        }
    }

    /**
     * Display the specified activity.
     */
    public function show(Activity $activity)
    {
        $this->authorize('activity-view');

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
        $activity->type = $request->type;
        $activity->has_sprints = $request->has_sprints;
        $activity->has_backlog = $request->has_backlog;
        $activity->is_intermodular = $request->is_intermodular;
        $activity->status = $request->status;
        $activity->start_date = $request->start_date;
        $activity->end_date = $request->end_date;
        if ($activity->save()) {
            $activity->subjectGroups()->sync($request->subject_groups);
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
