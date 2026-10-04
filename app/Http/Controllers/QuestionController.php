<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\QuestionRequest;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Quiz $quiz)
    {
        Gate::authorize('viewAny', [Question::class, $quiz]);

        $questions = $quiz->questions()->get();

        return response()->json([
            'success' => true,
            'message' => 'Questions retrieved successfully',
            'data' => $questions
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(QuestionRequest $request, Quiz $quiz)
    {
        Gate::authorize('create',[Question::class,$quiz]);

        $data = $request->validated();

        $question = $quiz->questions()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Question created successfully',
            'data' => $question
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Question $question)
    {
        Gate::authorize('view', $question);

        return response()->json([
            'success' => true,
            'message' => 'Question retrieved successfully',
            'data' => $question
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(QuestionRequest $request, Question $question)
    {
        Gate::authorize('update', $question);

        $data = $request->validated();

        $question->update($data);

        $question->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully',
            'data' => $question
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question)
    {
        Gate::authorize('delete', $question);

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully'
        ]);
    }
}
