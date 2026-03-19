<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Http\Resources\CourseResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;

class CourseController extends Controller
{
    /**
     * Display a listing of the course.
     */
    public function index()
    {
        $this->authorize('course-list');

        $courses = Course::all();
        return CourseResource::collection($courses);    }

    /**
     * Store a newly created course in storage.
     */
    public function store(StoreCourseRequest $request)
    {
        $this->authorize('course-create');
        $course = new Course();
        $course->name = $request->name;
        $course->acronym = $request->acronym;
        if ($course->save()) {
            return new CourseResource($course);
        }
    }

    /**
     * Display the specified course.
     */
    public function show(Course $course)
    {
        $this->authorize('course-view');
        return new CourseResource($course);
    }

    /**
     * Update the specified course in storage.
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        $this->authorize('course-edit');

        $course->name = $request->name;
        $course->acronym = $request->acronym;
        if ($course->save()) {
            return new CourseResource($course);
        }
        return null;
    }

    /**
     * Remove the specified course from storage.
     */
    public function destroy(Course $course)
    {
        $this->authorize('course-delete');

        $course->delete();
        return new CourseResource($course);
    }
}
