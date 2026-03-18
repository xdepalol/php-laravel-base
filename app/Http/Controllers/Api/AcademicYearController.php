<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicYear\StoreAcademicYearRequest;
use App\Models\AcademicYear;
use App\Http\Requests\AcademicYear\UpdateAcademicYearRequest;
use App\Http\Resources\AcademicYearResource;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    /**
     * Display a listing of the academic year.
     */
    public function index()
    {
        $this->authorize('academicyear-list');

        $academicYears = AcademicYear::all();
        return AcademicYearResource::collection($academicYears);
    }

    /**
     * Store a newly created academic year in storage.
     */
    public function store(StoreAcademicYearRequest $request)
    {
        // $this->authorize('academicyear-create');
        $academicYear = new AcademicYear();
        $academicYear->year_code = $request->year_code;
        $academicYear->description = $request->description;
        $academicYear->is_active = $request->is_active;
        if ($academicYear->save()) {
            return new AcademicYearResource($academicYear);
        }
    }

    /**
     * Display the specified academic year.
     */
    public function show(AcademicYear $academicyear)
    {
        $this->authorize('academicyear-view');
        return $academicyear;

        // return new AcademicYearResource($academicYear);
    }

    /**
     * Update the specified academic year in storage.
     */
    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicyear)
    {
        $this->authorize('academicyear-edit');

        $academicyear->year_code = $request->year_code;
        $academicyear->description = $request->description;
        $academicyear->is_active = $request->is_active;
        if ($academicyear->save()) {
            return new AcademicYearResource($academicyear);
        }
        return null;
    }

    /**
     * Remove the specified academic year from storage.
     */
    public function destroy(AcademicYear $academicyear)
    {
        $this->authorize('academicyear-delete');

        $academicyear->delete();
        return new AcademicYearResource($academicyear);
    }
}
