<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollment\StoreStudentEnrollmentRequest;
use App\Http\Requests\Enrollment\UpdateStudentEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\UniqueConstraintViolationException;

class StudentEnrollmentController extends Controller
{
    /**
     * This student's enrollments (subject groups).
     */
    public function index(Student $student)
    {
        $this->authorize('enrollment-list');

        $enrollments = $student->enrollments()->with(['student.user', 'subjectGroup'])->get();
        $enrollments->each(fn (Enrollment $e) => $e->setRelation('student', $student));

        return EnrollmentResource::collection($enrollments);
    }

    /**
     * Enroll this student in a subject group.
     */
    public function store(StoreStudentEnrollmentRequest $request, Student $student)
    {
        $this->authorize('enrollment-create');

        $enrollment = new Enrollment();
        $enrollment->student_id = $student->user_id;
        $enrollment->subject_group_id = $request->subject_group_id;
        $enrollment->status = $request->input('status') ?? EnrollmentStatus::ENROLLED;

        try {
            if ($enrollment->save()) {
                $enrollment->load(['student.user', 'subjectGroup']);
                $enrollment->setRelation('student', $student);

                return new EnrollmentResource($enrollment);
            }
        } catch (UniqueConstraintViolationException $ex) {
            return response()->json([
                'message' => 'Duplicated enrollment entry',
            ], 409);
        }

        return null;
    }

    public function show(Student $student, Enrollment $enrollment)
    {
        $this->authorize('enrollment-view');

        $enrollment->load(['student.user', 'subjectGroup']);
        $enrollment->setRelation('student', $student);

        return new EnrollmentResource($enrollment);
    }

    public function update(UpdateStudentEnrollmentRequest $request, Student $student, Enrollment $enrollment)
    {
        $this->authorize('enrollment-edit');

        $enrollment->student_id = $student->user_id;
        $enrollment->subject_group_id = $request->subject_group_id;
        if ($request->exists('status')) {
            $enrollment->status = $request->input('status') ?? EnrollmentStatus::ENROLLED;
        }

        try {
            if ($enrollment->save()) {
                $enrollment->load(['student.user', 'subjectGroup']);
                $enrollment->setRelation('student', $student);

                return new EnrollmentResource($enrollment);
            }
        } catch (UniqueConstraintViolationException $ex) {
            return response()->json([
                'message' => 'Duplicated enrollment entry',
            ], 409);
        }

        return null;
    }

    public function destroy(Student $student, Enrollment $enrollment)
    {
        $this->authorize('enrollment-delete');

        $enrollment->load(['student.user', 'subjectGroup']);
        $enrollment->setRelation('student', $student);
        $enrollment->delete();

        return new EnrollmentResource($enrollment);
    }
}
