<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Support\Facades\Gate;
use App\Services\SubmissionService;
use Illuminate\Support\Facades\Auth;
class SubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Assignment $assignment)
    {
        Gate::authorize('viewAny',[Submission::class,$assignment->course]);

        $submissions = $assignment->submissions()->paginate(5);

        return response()->json([
            'success'=>true,
            'message'=>'submissions retrieved successfully',
            'data'=>$submissions
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Assignment $assignment, SubmissionService $submissionService)
    {
        Gate::authorize('create',[Submission::class,$assignment->course]);

        $data = $request->validate([
            'submission_file'=>'required|string',
        ]);

        $submission = $submissionService->submit(Auth::user(),$assignment,$data);

        return response()->json([
            'success'=>true,
            'message'=>'submission created successfully',
            'data'=>$submission
        ],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Submission $submission)
    {
        $course = $submission->assignment->course;

        Gate::authorize('view',[Submission::class,$course]);

        return response()->json([
            'success'=>true,
            'message'=>'submission retrieved successfully',
            'data'=>$submission
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
