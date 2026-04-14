<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class ActivityAvailableTeamStudentsController extends Controller
{
    /**
     * Estudiantes matriculados en los módulos de la actividad que aún no pertenecen a ningún equipo de esta actividad.
     */
    public function __invoke(Activity $activity)
    {
        $this->authorize('team-edit');

        $subjectGroupIds = DB::table('activity_subject_group')
            ->where('activity_id', $activity->id)
            ->pluck('subject_group_id');

        if ($subjectGroupIds->isEmpty()) {
            return StudentResource::collection(collect());
        }

        $enrolledIds = Enrollment::query()
            ->whereIn('subject_group_id', $subjectGroupIds)
            ->where('status', EnrollmentStatus::ENROLLED)
            ->pluck('student_id')
            ->unique();

        $assignedIds = DB::table('team_student')
            ->join('teams', 'teams.id', '=', 'team_student.team_id')
            ->where('teams.activity_id', $activity->id)
            ->pluck('team_student.student_id')
            ->unique();

        $freeIds = $enrolledIds->diff($assignedIds);

        if ($freeIds->isEmpty()) {
            return StudentResource::collection(collect());
        }

        $students = Student::query()
            ->with('user')
            ->whereIn('user_id', $freeIds)
            ->join('users', 'users.id', '=', 'students.user_id')
            ->orderBy('users.surname1')
            ->orderBy('users.surname2')
            ->orderBy('users.name')
            ->select('students.*')
            ->get();

        return StudentResource::collection($students);
    }
}
