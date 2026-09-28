<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\AssignmentRequest;

class AssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Course $course)
    {
        Gate::authorize('viewAny',[Assignment::class,$course]);

        $assignments = $course->assignments()->paginate(6);

        return response()->json([
            'success'=>true,
            'message'=>'assignments retrieved successfully',
            'data'=>$assignments,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AssignmentRequest $request, Course $course)
    {
        Gate::authorize('create',[Assignment::class,$course]);

        $data = $request->validated();

        $assignment = $course->assignments()->create($data);

        return response()->json([
            'success'=>true,
            'message'=>'assignment created successfully',
            'data'=>$assignment,
        ],201);    
    }

    /**
     * Display the specified resource.
     */
    public function show(Assignment $assignment)
    {
        Gate::authorize('view',[Assignment::class,$assignment->course]);

        return response()->json([
            'success'=>true,
            'message'=>'assignment retrieved successfully',
            'data'=>$assignment,
        ]);  
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssignmentRequest $request, Assignment $assignment)
    {
        Gate::authorize('update',[Assignment::class,$assignment->course]);

        $data = $request->validated();

        $assignment->update($data);

        $assignment->refresh();

        return response()->json([
            'success'=>true,
            'message'=>'assignment updated successfully',
            'data'=>$assignment,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Assignment $assignment)
    {
        Gate::authorize('delete',[Assignment::class,$assignment->course]);

        $assignment->delete();

        return response()->json([
            'success'=>true,
            'message'=>'assignment deleted successfully',
        ]);
    }
}
