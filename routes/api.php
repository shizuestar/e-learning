<?php

use App\Http\Controllers\AssignmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/checkToken', [AuthController::class, 'checkToken']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Admin routes
    Route::get('/users', [UserController::class, 'index']); 
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show']); 
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    Route::get('/teachers', [TeacherController::class, 'index']);
    Route::post('/teachers', [TeacherController::class, 'store']);
    Route::get('/teachers/{teacher}', [TeacherController::class, 'show']);
    Route::put('/teachers/{teacher}', [TeacherController::class, 'update']);
    Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy']);

    Route::get('/school-classes', [SchoolClassController::class, 'index']);
    Route::post('/school-classes', [SchoolClassController::class, 'store']);
    Route::get('/school-classes/{schoolClass}', [SchoolClassController::class, 'show']);
    Route::put('/school-classes/{schoolClass}', [SchoolClassController::class, 'update']);
    Route::delete('/school-classes/{schoolClass}', [SchoolClassController::class, 'destroy']);
    Route::get('/plot-school-class', [SchoolClassController::class, 'getDataPloting']);
    Route::delete('plot/student/class/{schoolClass}/{student}', [SchoolClassController::class, 'deleteStudentFromClass']);
    Route::post('plot/student/class/{schoolClass}', [SchoolClassController::class, 'addStudentClass']);
    Route::post('plot/course/class/{schoolClass}', [SchoolClassController::class, 'addCourseToClass']);
    Route::delete('plot/course/class/{schoolClass}/{course}', [SchoolClassController::class, 'deleteCourseFromClass']);


    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::get('/students/{student}', [StudentController::class, 'show']);
    Route::put('/students/{student}', [StudentController::class, 'update']);
    Route::delete('/students/{student}', [StudentController::class, 'destroy']);

    // Teacher routes
    Route::get('/courses', [CourseController::class, 'index']);
    Route::post('/courses', [CourseController::class, 'store']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::put('/courses/{course}', [CourseController::class, 'update']);
    Route::delete('/courses/{course}', [CourseController::class, 'destroy']);
    Route::get('/courses/teacher/{teacher}', [CourseController::class, 'getCoursesByTeacher']);

    Route::get('courses/getDataCreate/{course:slug}', [AssignmentController::class, 'getDataCreate']);
    Route::post('courses/{course:slug}/createAssignment', [AssignmentController::class, 'addQuestionsBatch']);

    Route::get('/assignment/{assignment:slug}/viewAnswers', [AssignmentController::class, 'viewAnswers']);
    Route::get('/assignment/{assignment:slug}/viewStudentAnswer/{nis}', [AssignmentController::class, 'viewStudentAnswers']);
    Route::put('/assignment/{assignment:slug}/response/{answer}', [AssignmentController::class, 'gradeAssignment']);

    // Student routes
    Route::get('/courses/assignment/{slug}', [AssignmentController::class, 'showQuestions']);
    Route::post('/assignment/{assignment:slug}/submitAssignment', [AssignmentController::class, 'submitAssignment']);
    Route::get('/assignment/{assignment:slug}/viewSubmited', [AssignmentController::class, 'viewSubmited']);

    // Assignment routes
    Route::get('/user/class', [SchoolClassController::class, 'getClasses']); // Get all classes for the user
    Route::get('/courses/{course:slug}/assignments', [AssignmentController::class, 'getAssignmentsByCourse']);

    Route::get('/student/courses', [CourseController::class, 'getAllCourseStudent']);
    Route::get('/assignment/{assignment:slug}/result', [AssignmentController::class, 'viewUserResult']);
    Route::get('/getAssignments', [AssignmentController::class, 'getUserAssignments']);
    // Route::get('/courses/{course:slug}/assignments/{student}', [AssignmentController::class, 'getStudentAssignments']);
});
