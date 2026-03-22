<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\SyncActivitySubjectGroupsRequest;
use App\Http\Resources\SubjectGroupResource;
use App\Models\Activity;

class ActivitySubjectGroupController extends Controller
{
    /**
     * List subject groups linked to the activity (activity_subject_group).
     */
    public function index(Activity $activity)
    {
        $this->authorize('activity-view');

        $activity->load('subjectGroups');

        return SubjectGroupResource::collection($activity->subjectGroups);
    }

    /**
     * Replace all subject group links for the activity (full sync of the pivot).
     */
    public function sync(SyncActivitySubjectGroupsRequest $request, Activity $activity)
    {
        $this->authorize('activity-edit');

        $activity->subjectGroups()->sync($request->subject_groups);
        $activity->load('subjectGroups');

        return SubjectGroupResource::collection($activity->subjectGroups);
    }
}
