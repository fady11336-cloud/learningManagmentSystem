<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Http\Requests\CourseRequest;
use App\Http\Resources\CourseResource;
use App\Http\Requests\UpdateCourseRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $courses = Course::all();

        return response()->json([
            'success'=>true,
            'message'=>'courses retrieved successfully',
            'data'=> CourseResource::collection($courses),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CourseRequest $request)
    {

        $request->validated();

        Gate::authorize('create', Course::class);
        
        $course = Course::create([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status,
            'user_id' => Auth::id(),
            'category_id' => $request->category_id,
        ]);

        $course->refresh();

        // dd($course->toArray());

        return response()->json([
            'success' => true,
            'message' => 'course created successfully',
            'data' =>new CourseResource($course),
        ],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $course = Course::findOrFail($id);

        return response()->json([
            'success'=>true,
            'message'=>'course retrieved successfully',
            'data'=>new CourseResource($course),
        ]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCourseRequest $request, string $id)
    {
        $course = Course::findOrFail($id);

        $data = $request->validated();

        Gate::authorize('update',$course);

        $course -> update($data); 
        
        $course->refresh();

        return response()->json([
            'success'=>true,
            'message'=>'course updated successfully',
            'data'=>new CourseResource($course),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $course = Course::findOrFail($id);

        Gate::authorize('delete',$course);

        $course->delete();

        return response()->json([
            'success'=>true,
            'message'=>'course deleted successfully',
        ]);
    }

    public function publishCourse(string $id)
    {
        $course = Course::findOrFail($id);

        Gate::authorize('publish',$course);

        $course->update(['visibility' => 'published']);
        $course->refresh();

        return response()->json([
            'success'=>true,
            'message'=>'course published successfully',
            'data'=>new CourseResource($course)
        ]);

    }

    public function archiveCourse(string $id)
    {
        $course = Course::findOrFail($id);

        Gate::authorize('archieve',$course);

        $course->update(['visibility' => 'archived']);

        $course->refresh();

        return response()->json([
            'success'=>true,
            'message'=>'course archieved successfully',
            'data'=>new CourseResource($course),
        ]);    
    }
}
