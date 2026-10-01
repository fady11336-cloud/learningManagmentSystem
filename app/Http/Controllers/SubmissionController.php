<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Support\Facades\Gate;
use App\Services\SubmissionService;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\SubmissionResource;
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
            'data'=>SubmissionResource::collection($submissions),
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

        if($submission === null)
            {
                return response()->json([
                    'success'=>false,
                    'message'=>'already submited',
                ],409);
            }

        return response()->json([
            'success'=>true,
            'message'=>'submission created successfully',
            'data'=> new SubmissionResource($submission)
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
            'data'=>new SubmissionResource($submission),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Submission $submission, SubmissionService $submissionService)
    {
        Gate::authorize('update',$submission);

        $data = $request->validate([
            'grade'=>'required|numeric|max:100|min:0',
        ]);

        $grade = $submissionService->grade($submission,$data);

        return response()->json([
            'success'=>true,
            'message'=>'grade added successfully',
            'data'=>[
                'id'=>$grade['id'],
                'grade'=>$grade['grade'],
                'grade_status'=>$grade['grade_status'],
                'assignment_id'=>$grade['assignment_id'],
                'user_id'=>$grade['user_id'],
            ]
        ]);
    }

}
