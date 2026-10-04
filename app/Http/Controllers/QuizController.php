<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\Course;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\QuizRequest;
class QuizController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Course $course)
    {
        Gate::authorize('viewAny', [Quiz::class, $course]);

        $quizzes = $course->quizzes()->get();

        return response()->json([
            'success' => true,
            'message' => 'Quizzes retrieved successfully',
            'data' => $quizzes
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(QuizRequest $request, Course $course)
    {
        Gate::authorize('create',[Quiz::class,$course]);

        $data = $request->validated();

        $quiz = $course->quizzes()->create($data);

        return response()->json([
            'success'=>true,
            'message'=>'Quiz created successfully',
            'data'=>$quiz,
        ],201);

    }

    /**
     * Display the specified resource.
     */
    public function show(Quiz $quiz)
    {
        Gate::authorize('view', $quiz);

        return response()->json([
            'success' => true,
            'message' => 'Quiz retrieved successfully',
            'data' => $quiz
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(QuizRequest $request, Quiz $quiz)
    {
        Gate::authorize('update', $quiz);

        $data = $request->validated();

        $quiz->update($data);

        $quiz->refresh(); // Refresh the model instance to get the latest data

        return response()->json([
            'success' => true,
            'message' => 'Quiz updated successfully',
            'data' => $quiz
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Quiz $quiz)
    {
        Gate::authorize('delete', $quiz);

        $quiz->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quiz deleted successfully'
        ]);
    }
}
