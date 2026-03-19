<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Http\Resources\SubjectResource;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * Display a listing of the subject.
     */
    public function index()
    {
        $this->authorize('subject-list');

        $subjects = Subject::all();
        return SubjectResource::collection($subjects);    }

    /**
     * Store a newly created subject in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('subject-create');
        $subject = new Subject();
        $subject->name = $request->name;
        $subject->acronym = $request->acronym;
        $subject->year_hours = $request->year_hours;
        if ($subject->save()) {
            return new SubjectResource($subject);
        }
    }

    /**
     * Display the specified subject.
     */
    public function show(Subject $subject)
    {
        $this->authorize('subject-view');
        return new SubjectResource($subject);
    }

    /**
     * Update the specified subject in storage.
     */
    public function update(Request $request, Subject $subject)
    {
        $this->authorize('subject-edit');

        $subject->name = $request->name;
        $subject->acronym = $request->acronym;
        $subject->year_hours = $request->year_hours;
        if ($subject->save()) {
            return new SubjectResource($subject);
        }
        return null;
    }

    /**
     * Remove the specified subject from storage.
     */
    public function destroy(Subject $subject)
    {
        $this->authorize('subject-delete');

        $subject->delete();
        return new SubjectResource($subject);
    }
}
