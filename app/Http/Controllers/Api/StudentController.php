<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Support\PrimeFilter;
use Illuminate\Database\UniqueConstraintViolationException;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('student-list');

        $students = Student::with(['user'])->get();
        return StudentResource::collection($students);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request)
    {
        $this->authorize('student-create');

        // $user = isset($request->user_id) ? User::find($request->user_id) : auth();
        $student = new Student();
        $student->user_id = $request->user_id;
        $student->student_number = $request->student_number;

        try
        {
            if ($student->save())
            {
                return new StudentResource($student);
            }
        } catch (UniqueConstraintViolationException $ex) {
            return response()->json([
                "message" => "Duplicated student entry"
            ], 409);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        $this->authorize('student-view');

        return new StudentResource($student);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student)
    {
        $this->authorize('student-edit');

        $student->student_number = $request->student_number;
        if ($student->save()) {
            return new StudentResource($student);
        }
        return null;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        $this->authorize('student-delete');

        $student->delete();
        return $student;
    }
}
