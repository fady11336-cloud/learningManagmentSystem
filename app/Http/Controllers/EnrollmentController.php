<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Services\EnrollmentService;

class EnrollmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function me()
    {
       /**  @var User $user */

       $user = Auth::user();

       Gate::authorize('me',Enrollment::class);
       
       $enrollments = $user->enrollments()->paginate(10);

       return response()->json([
        'success'=>true,
        'message'=>'enrollments retrieved successfully',
        'data'=>$enrollments,
       ]);
    }

    public function index(Course $course)
    {
        
        Gate::authorize('viewAny',[Enrollment::class,$course]);

        $enrollments = $course->enrollments()->paginate(10);

        return response()->json([
        'success'=>true,
        'message'=>'enrollments retrieved successfully',
        'data'=>$enrollments,
        ]);
    }

    public function store(Course $course, EnrollmentService $enrollmentService)
    {
        $student = Auth::user();
        
        Gate::authorize('isStudent',$course);

        $enrollment = $enrollmentService->enroll($student,$course);

        return $enrollment;
    }


    /**
     * Display the specified resource.
     */
    public function show(Enrollment $enrollment)
    {

        Gate::authorize('view',$enrollment);

        return response()->json([
        'success'=>true,
        'message'=>'enrollment retrieved successfully',
        'data'=>$enrollment,
        ]);    
    }



}
