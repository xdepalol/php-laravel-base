<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\IndexMySubjectGroupsRequest;
use App\Http\Resources\SubjectGroupResource;
use App\Models\SubjectGroup;

class TeacherSubjectGroupController extends Controller
{
    /**
     * Subject groups the authenticated user teaches in the given academic year.
     */
    public function index(IndexMySubjectGroupsRequest $request)
    {
        $this->authorize('subjectgroup-list');

        $userId = $request->user()->id;

        $subjectGroups = SubjectGroup::query()
            ->where('academic_year_id', $request->validated('academic_year_id'))
            ->whereHas('teachers', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with(['group', 'subject', 'teachers'])
            ->orderBy('id')
            ->get();

        return SubjectGroupResource::collection($subjectGroups);
    }
}
