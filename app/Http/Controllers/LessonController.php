<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lesson;
use App\Models\Course;
use App\Http\Resources\LessonResource;
use App\Http\Requests\LessonRequest;
use Illuminate\Support\Facades\Gate;
use App\Services\LessonService;
class LessonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Course $course)
    {
        Gate::authorize('viewAny',[Lesson::class,$course]);

        $lessons = $course->lessons()->orderBy('order')->get();

        return response()->json([
            'success'=>true,
            'message'=>'lessons retrieved successfully',
            'data'=>LessonResource::collection($lessons),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LessonRequest $request,Course $course, LessonService $lessonService)
    {
        Gate::authorize('create',[Lesson::class,$course]);

        $data = $request->validated();

        $lesson = $lessonService->createLesson($course,$data);

        $lesson->refresh();

        return response()->json([
            'success' => true,
            'message' => 'lesson created successfully',
            'data' => new LessonResource($lesson),
        ],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Lesson $lesson)
    {
        $course = $lesson->course;
        
        Gate::authorize('view',[Lesson::class,$course]);

        return response() -> json([
            'success'=>true,
            'message'=>'lesson retrieved successfully',
            'data'=>new LessonResource($lesson),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LessonRequest $request, Lesson $lesson, LessonService $lessonService)
    {
        $course = $lesson->course;

        Gate::authorize('update',[Lesson::class,$course]);

        $data = $request->validated();

        $lesson = $lessonService->updateLesson($lesson,$data);
        
        return response()->json([
            'success'=>true,
            'message'=>'lesson updated successfully',
            'data'=>new LessonResource($lesson),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lesson $lesson, LessonService $lessonService)
    {
        $course = $lesson->course;

        Gate::authorize('delete',[Lesson::class,$course]);

        $lessonService->deleteLesson($lesson);

        return response()->json([
        'success'=>true,
        'message'=>'lesson deleted successfully',
        ]);
            
    }

    public function reorder(Request $request,Lesson $lesson, LessonService $lessonService)
    {
        $course = $lesson->course;

        Gate::authorize('reorder',[Lesson::class,$course]);
        
        $data = $request->validate([
            'order'=>'required|integer',
        ]);

        $reorderLesson = $lessonService->updateLesson($lesson,$data);

        return response()->json([
            'success'=>true,
            'message'=>'reordered successfully',
            'data'=>new LessonResource($reorderLesson),
        ]);

    }
}
