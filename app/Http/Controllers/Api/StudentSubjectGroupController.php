<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\IndexMyStudentSubjectGroupsRequest;
use App\Http\Resources\SubjectGroupResource;
use App\Models\Student;
use App\Models\SubjectGroup;

class StudentSubjectGroupController extends Controller
{
    /**
     * Subject groups the authenticated student is enrolled in for the given academic year.
     */
    public function index(IndexMyStudentSubjectGroupsRequest $request)
    {
        $this->authorize('own-enrollments');

        $userId = $request->user()->id;

        $student = Student::query()->where('user_id', $userId)->first();
        if ($student === null) {
            return SubjectGroupResource::collection(collect());
        }

        $subjectGroups = SubjectGroup::query()
            ->where('academic_year_id', $request->validated('academic_year_id'))
            ->whereHas('enrollments', function ($q) use ($student) {
                $q->where('student_id', $student->user_id)
                    ->where('status', EnrollmentStatus::ENROLLED);
            })
            ->with(['group', 'subject', 'teachers'])
            ->orderBy('id')
            ->get();

        return SubjectGroupResource::collection($subjectGroups);
    }
}
