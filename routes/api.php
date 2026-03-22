<?php

use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ActivityRoleController;
use App\Http\Controllers\Api\ActivityRoleTypeController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DeliverableController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SubjectGroupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group(['middleware' => 'auth:sanctum'], function() {

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('users', UserController::class);
    Route::post('users/updateimg', [UserController::class,'updateimg']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('roles', RoleController::class);
   
    Route::get('role-list', [RoleController::class, 'getList']);
    Route::get('role-permissions/{id}', [PermissionController::class, 'getRolePermissions']);
    Route::put('/role-permissions', [PermissionController::class, 'updateRolePermissions']);
    Route::apiResource('permissions', PermissionController::class);
    
    Route::get('/user', [ProfileController::class, 'user']);
    Route::get('/user/signin', [ProfileController::class, 'user']);
    Route::put('/user', [ProfileController::class, 'update']);

    Route::apiResource('posts', PostController::class)
        ->missing(function($request) {
            $postId = $request->route('post');
            return response()->json(['message' => "Post not found (#{$postId})"], 404);
        });

    Route::apiResource('academic-years', AcademicYearController::class);
    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('courses', CourseController::class);
    Route::apiResource('teachers', TeacherController::class);
    Route::apiResource('groups', GroupController::class);
    Route::apiResource('subject-groups', SubjectGroupController::class);
    Route::apiResource('enrollments', EnrollmentController::class);
    Route::apiResource('activities', ActivityController::class);
    Route::apiResource('deliverables', DeliverableController::class);
    Route::apiResource('activity-role-types', ActivityRoleTypeController::class);
    Route::apiResource('activity-roles', ActivityRoleController::class);

    Route::apiResource('students', StudentController::class)
        ->missing(function($request) {
            $studentId = $request->route('student');
            return response()->json(['message' => "Student not found (#{$studentId})"], 404);
        });

    Route::get('abilities', function(Request $request) {
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
