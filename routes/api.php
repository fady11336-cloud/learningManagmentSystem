<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonController;
use App\Models\Assignment;
use GuzzleHttp\Middleware;
use Illuminate\Support\Facades\Route;

Route::post('/register',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verification.verify');

Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail'])
    ->middleware(['auth:api', 'throttle:6,1'])
    ->name('verification.send');

Route::post('/forgot-password',[AuthController::class,'forgotPassword']);
Route::post('/reset-password',[AuthController::class,'resetPassword'])->middleware('throttle:6,60')->name('password.reset');

// Courses
Route::get('/courses',[CourseController::class,'index']);
Route::get('/courses/{id}',[CourseController::class,'show']);

//Categories
Route::get('/categories',[CategoryController::class,'index']);
Route::get('/categories/{id}',[CategoryController::class,'show']);

Route::middleware('auth:api')->group(function(){

    Route::post('/logout',[AuthController::class,'logout']);
    Route::post('/refresh',[AuthController::class,'refresh']);
    Route::get('/me',[AuthController::class,'me']);

    Route::get('/users/me',[UserController::class,'me']);
    Route::put('/users/me',[UserController::class,'updateProfile']);

    //courses
    Route::post('/courses',[CourseController::class,'store']);
    Route::put('/courses/{course}',[CourseController::class,'update']);
    Route::delete('/courses/{course}',[CourseController::class,'destroy']);
    Route::patch('/courses/{course}/publish',[CourseController::class,'publishCourse']);
    Route::patch('/courses/{course}/archive',[CourseController::class,'archiveCourse']);

    //lessons
    Route::get('/courses/{course}/lessons',[LessonController::class,'index']);
    Route::get('/lessons/{lesson}',[LessonController::class,'show']);
    Route::post('/course/{course}/lessons',[LessonController::class,'store']);
    Route::put('/lessons/{lesson}',[LessonController::class,'update']);
    Route::delete('/lessons/{lesson}',[LessonController::class,'destroy']);
    Route::patch('/lesson/{lesson}/reorder',[LessonController::class,'reorder']);

    //Enrollments
    Route::get('/users/me/enrollments',[EnrollmentController::class,'me']);
    Route::get('/courses/{course}/enrollments',[EnrollmentController::class,'index']);
    Route::post('/course/{course}/enroll',[EnrollmentController::class,'store']);
    Route::get('/enrollment/{enrollment}',[EnrollmentController::class,'show']);

    //Assignments
    Route::get('/courses/{course}/assignments',[Assignment::class,'index']);
    Route::get('/assignment/{assignment}',[Assignment::class,'show']);
    Route::post('/courses/{course}/assignments',[Assignment::class,'store']);
    Route::put('/assignment/{assignment}',[Assignment::class,'update']);
    Route::delete('/assignment/{assignment}',[Assignment::class,'destroy']);
    
});

Route::middleware(['auth:api','isAdmin'])->group(function()
{
    Route::get('/users',[UserController::class,'index']);
    Route::get('/users/{id}',[UserController::class,'show']);
    Route::put('/users/{id}',[UserController::class,'update']);
    Route::patch('/users/{id}/role',[UserController::class,'changeRole']);
    Route::patch('users/{id}/block',[UserController::class,'blockUser']);
    Route::delete('/users/{id}',[UserController::class,'destroy']);

    //Categories

    Route::post('/categories',[CategoryController::class,'store']);
    Route::put('/categories/{id}',[CategoryController::class,'update']);
    Route::delete('/categories/{id}',[CategoryController::class,'destroy']);
});