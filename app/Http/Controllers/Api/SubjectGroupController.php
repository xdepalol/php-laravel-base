<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubjectGroup\StoreSubjectGroupRequest;
use App\Http\Requests\SubjectGroup\UpdateSubjectGroupRequest;
use App\Http\Resources\SubjectGroupResource;
use App\Models\SubjectGroup;
use Illuminate\Http\Request;

class SubjectGroupController extends Controller
{
    /**
     * Display a listing of the group.
     */
    public function index()
    {
        $this->authorize('subjectgroup-list');

        $subjectGroups = SubjectGroup::with(['teachers'])->get();
        return SubjectGroupResource::collection($subjectGroups);
    }

    /**
     * Store a newly created group in storage.
     */
    public function store(StoreSubjectGroupRequest $request)
    {
        $this->authorize('subjectgroup-create');
        $subjectGroup = new SubjectGroup();
        $subjectGroup->academic_year_id = $request->academic_year_id;
        $subjectGroup->group_id = $request->group_id;
        $subjectGroup->subject_id = $request->subject_id;

        if ($subjectGroup->save()) {
            $subjectGroup->teachers()->sync($request->teachers);
            return new SubjectGroupResource($subjectGroup);
        }
    }

    /**
     * Display the specified group.
     */
    public function show(SubjectGroup $subjectGroup)
    {
        $this->authorize('subjectgroup-view');
        $subjectGroup->load('teachers');
        return new SubjectGroupResource($subjectGroup);
    }

    /**
     * Update the specified group in storage.
     */
    public function update(UpdateSubjectGroupRequest $request, SubjectGroup $subjectGroup)
    {
        $this->authorize('subjectgroup-edit');

        $subjectGroup->academic_year_id = $request->academic_year_id;
        $subjectGroup->group_id = $request->group_id;
        $subjectGroup->subject_id = $request->subject_id;
        if ($subjectGroup->save()) {
            $subjectGroup->teachers()->sync($request->teachers);
            return new SubjectGroupResource($subjectGroup);
        }
        return null;
    }

    /**
     * Remove the specified group from storage.
     */
    public function destroy(SubjectGroup $subjectGroup)
    {
        $this->authorize('subjectgroup-delete');

        $subjectGroup->delete();
        return new SubjectGroupResource($subjectGroup);
    }
}
