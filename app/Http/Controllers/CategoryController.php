<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'success'=>true,
            'message'=>'categories retrieved successfully',
            'data'=>CategoryResource::collection($categories),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request)
    {
        $category = Category::create($request->validated());

        return response()->json([
            'success'=>true,
            'message'=>'category created successfully',
            'data'=>new CategoryResource($category),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::findOrFail($id);


        return response()->json([
            'success'=>true,
            'message'=>'category retrieved successfully',
            'data'=>new CategoryResource($category),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryRequest $request, string $id)
    {
        $category = Category::findOrFail($id);

        $data = $request->validated();

        $category -> update($data);

        $category -> refresh();
        
        return response()->json([
            'success'=>true,
            'message'=>'category updated successfully',
            'data'=>new CategoryResource($category),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        $category -> delete();

        return response()->json([
            'success'=>true,
            'message'=>'category deleted successfully',
        ]);
    }
}
