<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\SubjectGroup;
use Illuminate\Http\Request;

class SubjectGroupActivityController extends Controller
{
    /**
     * Activities linked to this subject group (same academic year as the group).
     * Allowed: admin, teacher on the group, or student enrolled in the group.
     */
    public function index(Request $request, SubjectGroup $subjectGroup)
    {
        $this->authorize('activity-list');
        $this->authorize('subjectgroup-view');

        $user = $request->user();

        $allowed = $user->hasRole('admin')
            || $subjectGroup->teachers()->where('user_id', $user->id)->exists()
            || Enrollment::query()
                ->where('subject_group_id', $subjectGroup->id)
                ->where('student_id', $user->id)
                ->exists();

        if (! $allowed) {
            abort(403);
        }

        $activities = Activity::query()
            ->with(['subjectGroups', 'activityRoleType'])
            ->where('academic_year_id', $subjectGroup->academic_year_id)
            ->whereHas('subjectGroups', function ($q) use ($subjectGroup) {
                $q->where('subject_groups.id', $subjectGroup->id);
            })
            ->orderBy('id')
            ->get();

        return ActivityResource::collection($activities);
    }
}
