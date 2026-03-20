<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollment\StoreEnrollmentRequest;
use App\Http\Requests\Enrollment\UpdateEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    /**
     * Display a listing of the enrollment.
     */
    public function index()
    {
        $this->authorize('enrollment-list');

        $enrollments = Enrollment::all();
        return EnrollmentResource::collection($enrollments);
    }

    /**
     * Store a newly created enrollment in storage.
     */
    public function store(StoreEnrollmentRequest $request)
    {
        $this->authorize('enrollment-create');

        // $user = isset($request->user_id) ? User::find($request->user_id) : auth();
        $enrollment = new Enrollment();
        $enrollment->student_id = $request->student_id;
        $enrollment->subject_group_id = $request->subject_group_id;
        $enrollment->status = $request->status;

        try
        {
            if ($enrollment->save())
            {
                return new EnrollmentResource($enrollment);
            }
        } catch (UniqueConstraintViolationException $ex) {
            return response()->json([
                "message" => "Duplicated enrollment entry"
            ], 409);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified enrollment.
     */
    public function show(Enrollment $enrollment)
    {
        $this->authorize('enrollment-view');

        return new EnrollmentResource($enrollment);
    }

    /**
     * Update the specified enrollment in storage.
     */
    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment)
    {
        $this->authorize('enrollment-edit');

        $enrollment->student_id = $request->student_id;
        $enrollment->subject_group_id = $request->subject_group_id;
        $enrollment->status = $request->status;
        if ($enrollment->save()) {
            return new EnrollmentResource($enrollment);
        }
        return null;
    }

    /**
     * Remove the specified enrollment from storage.
     */
    public function destroy(Enrollment $enrollment)
    {
        $this->authorize('enrollment-delete');

        $enrollment->delete();
        return $enrollment;
    }
}
