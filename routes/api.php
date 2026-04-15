<?php

use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\ActivityAvailableTeamStudentsController;
use App\Http\Controllers\Api\ActivityBacklogController;
use App\Http\Controllers\Api\ActivityBacklogCsvImportController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ActivityPhaseCsvImportController;
use App\Http\Controllers\Api\ActivityRoleController;
use App\Http\Controllers\Api\ActivityRoleTypeController;
use App\Http\Controllers\Api\ActivitySubjectGroupController;
use App\Http\Controllers\Api\ActivityTaskController;
use App\Http\Controllers\Api\ActivityTeamMemberRolesController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DeliverableController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PhaseController;
use App\Http\Controllers\Api\PhaseStudentRoleController;
use App\Http\Controllers\Api\PhaseTaskController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentEnrollmentController;
use App\Http\Controllers\Api\StudentSubjectGroupController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\SubjectGroupActivityController;
use App\Http\Controllers\Api\SubjectGroupController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\TeacherSubjectGroupController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TeamStudentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('users', UserController::class);
    Route::post('users/updateimg', [UserController::class, 'updateimg']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('roles', RoleController::class);

    Route::get('role-list', [RoleController::class, 'getList']);
    Route::get('role-permissions/{id}', [PermissionController::class, 'getRolePermissions']);
    Route::put('/role-permissions', [PermissionController::class, 'updateRolePermissions']);
    Route::apiResource('permissions', PermissionController::class);

    Route::get('me/subject-groups', [TeacherSubjectGroupController::class, 'index']);
    Route::get('me/student/subject-groups', [StudentSubjectGroupController::class, 'index']);

    Route::get('/user', [ProfileController::class, 'user']);
    Route::get('/user/signin', [ProfileController::class, 'user']);
    Route::put('/user', [ProfileController::class, 'update']);

    Route::apiResource('posts', PostController::class)
        ->missing(function ($request) {
            $postId = $request->route('post');

            return response()->json(['message' => "Post not found (#{$postId})"], 404);
        });

    Route::apiResource('academic-years', AcademicYearController::class);
    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('courses', CourseController::class);
    Route::apiResource('teachers', TeacherController::class);
    Route::apiResource('groups', GroupController::class);
    Route::get('subject-groups/{subject_group}/activities', [SubjectGroupActivityController::class, 'index']);
    Route::apiResource('subject-groups', SubjectGroupController::class);
    Route::apiResource('subject-groups.enrollments', EnrollmentController::class)->scoped();
    Route::apiResource('activities', ActivityController::class);
    Route::get('activities/{activity}/team-member-roles', ActivityTeamMemberRolesController::class)->scopeBindings();
    Route::get('activities/{activity}/students-available-for-teams', ActivityAvailableTeamStudentsController::class)->scopeBindings();
    Route::post('activities/{activity}/backlog-items/csv-import', [ActivityBacklogCsvImportController::class, 'store']);
    Route::apiResource('activities.backlog-items', ActivityBacklogController::class)->scoped();
    Route::apiResource('activities.tasks', ActivityTaskController::class)->scoped();
    Route::get('activities/{activity}/subject-groups', [ActivitySubjectGroupController::class, 'index']);
    Route::put('activities/{activity}/subject-groups', [ActivitySubjectGroupController::class, 'sync']);

    Route::apiResource('activities.teams', TeamController::class)->scoped();
    Route::get('activities/{activity}/teams/{team}/students', [TeamStudentController::class, 'index'])->scopeBindings();
    Route::put('activities/{activity}/teams/{team}/students', [TeamStudentController::class, 'sync'])->scopeBindings();
    Route::apiResource('activities.deliverables', DeliverableController::class)->scoped();
    Route::patch('deliverables/{deliverable}/submissions/{submission}/grade', [SubmissionController::class, 'grade'])
        ->scopeBindings()
        ->name('deliverables.submissions.grade');
    Route::apiResource('deliverables.submissions', SubmissionController::class)->scoped();
    Route::post('activities/{activity}/phases/csv-import', [ActivityPhaseCsvImportController::class, 'store'])->scopeBindings();
    Route::apiResource('activities.phases', PhaseController::class)->scoped();
    Route::apiResource('phase-student-roles', PhaseStudentRoleController::class);
    Route::apiResource('phase-tasks', PhaseTaskController::class);
    Route::apiResource('activity-role-types', ActivityRoleTypeController::class);
    Route::apiResource('activity-roles', ActivityRoleController::class);

    Route::apiResource('students', StudentController::class)
        ->missing(function ($request) {
            $studentId = $request->route('student');

            return response()->json(['message' => "Student not found (#{$studentId})"], 404);
        });
    Route::apiResource('students.enrollments', StudentEnrollmentController::class)->scoped();

    Route::get('abilities', function (Request $request) {
        return $request->user()->roles()->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();
    });
});

Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('category-list', [CategoryController::class, 'getList']);
