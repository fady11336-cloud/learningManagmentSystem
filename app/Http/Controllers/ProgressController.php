<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Progress;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProgressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function me()
    {
        /**
         *  @var User $user
         */
        $user = Auth::user();

        $progress = $user->enrollments->progresses;

        return response()->json([
            'success'=>true,
            'message'=>'progress retrieved successfully',
            'data'=>$progress
        ]);
    }
    public function index(Enrollment $enrollment)
    {
        Gate::authorize('viewAny',[Progress::class,$enrollment]);

        $progresses = $enrollment->progresses;

        return response()->json([
            'success'=>true,
            'message'=>'progress retrieved successfully',
            'data'=>$progresses
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course)
    {
        Gate::authorize('view',[Progress::class,$course]);

        $progress = $course->enrollments()->where('user_id',Auth::id())->first()->progresses;

        return response()->json([
            'success'=>true,
            'message'=>'progress retrieved successfully',
            'data'=>$progress
        ]);
    }

}
