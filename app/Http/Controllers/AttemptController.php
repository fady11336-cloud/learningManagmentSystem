<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Quiz;
use App\Models\User;
use App\Models\Attempt;
use App\Http\Requests\AttemptRequest;
use Illuminate\Support\Facades\Gate;
use App\Services\AttemptService;
class AttemptController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function me()
    {
        /**
         * @var User $user
         */
        $user = Auth::user();

        $attempts = $user->attempts;

        return response()->json([
            'success'=>true,
            'message'=>'attempts retrieved successfully',
            'data'=>$attempts
        ]);
    }

    public function index(Quiz $quiz)
    {
        Gate::authorize('viewAny',[Attempt::class,$quiz]);

        $attempts = $quiz->attempts;

        return response()->json([
            'success'=>true,
            'message'=>'attempts retrieved successfully',
            'data'=>$attempts
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AttemptRequest $request, Quiz $quiz, AttemptService $attemptService)
    {
        Gate::authorize('create',[Attempt::class,$quiz]);

        $data = $request->validated();

        $user = Auth::user();

        $newAttempt = $attemptService->attempt($user,$quiz,$data);

        return response()->json([
            'success'=>true,
            'message'=>'attempt created successfully',
            'data'=>$newAttempt
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Attempt $attempt)
    {
        Gate::authorize('view',$attempt);

        return response()->json([
            'success'=>true,
            'message'=>'attempt retrieved successfully',
            'data'=>$attempt
        ]);
    }

}
