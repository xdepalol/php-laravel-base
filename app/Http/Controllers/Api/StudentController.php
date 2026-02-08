<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Support\PrimeFilter;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('student-list');

        $perPage = (int) $request->query('per_page', 10);

        // Camps ordenables
        $allowedSorts = ['id', 'name', 'surname1', 'surname2', 'email', 'birthday_date', 'created_at'];
        $sortField = $request->query('sort_field');
        $sortOrder = request('sort_order', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        
        $query = Student::query();

        // Filters
        $allowedFilters = ['name', 'surname1', 'surname2', 'email'];
        $filters = PrimeFilter::getFiltersFromRequest($request, $allowedFilters);

        // Cerca global
        if (isset($filters['global'])) {
            $global = $filters['global'];

            $query->where(function ($q) use ($global, $allowedFilters)
            {
                foreach ($allowedFilters as $i => $field)
                {
                    $f = new PrimeFilter($field, $global->value, $global->matchMode);
                    $f->apply($q, $i > 0); // OR a partir del segon
                }
            });
            unset($filters['global']); // per no reaplicar
        }

        // Filtres locals
        foreach ($filters as $f)
        {
            $f->apply($query);
        }

        // Ordenació
        if ($sortField && in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->latest(); // default
        }        

        $students = $query->paginate($perPage);
        return $students;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request)
    {
        $this->authorize('student-create');

        $data = $request->validated();
        $student = Student::create($data);
        return $student;
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        $this->authorize('student-list');

        return $student;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student)
    {
        $this->authorize('student-edit');

        $data = $request->validated();
        $student->update($data);
        return $student;
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
