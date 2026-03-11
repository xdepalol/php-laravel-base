<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\TeacherResource;
use App\Http\Requests\Teacher\StoreTeacherRequest;
use App\Http\Requests\Teacher\UpdateTeacherRequest;
use Illuminate\Database\UniqueConstraintViolationException;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('teacher-list');

        $teachers = Teacher::with(['user'])->get();
        return TeacherResource::collection($teachers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeacherRequest $request)
    {
        $this->authorize('teacher-create');

        // $user = isset($request->user_id) ? User::find($request->user_id) : auth();
        $teacher = new Teacher();
        $teacher->user_id = $request->user_id;
        $teacher->ss_number = $request->ss_number;
        $teacher->teacher_number = $request->teacher_number;

        try
        {
            if ($teacher->save())
            {
                return new TeacherResource($teacher);
            }
        } catch (UniqueConstraintViolationException $ex)         {
            return response()->json([
                "message" => "Duplicated teacher entry"
            ], 409);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Teacher $teacher)
    {
        $this->authorize('teacher-view');

        $teacher->load('user');
        return new TeacherResource($teacher);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeacherRequest $request, Teacher $teacher)
    {
        $this->authorize('teacher-edit');

        $teacher->ss_number = $request->ss_number;
        $teacher->teacher_number = $request->teacher_number;
        if ($teacher->save()) {
            return new TeacherResource($teacher);
        }
        return null;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Teacher $teacher)
    {
        $this->authorize('teacher-delete');

        $teacher->delete();
        return $teacher;
    }
}
