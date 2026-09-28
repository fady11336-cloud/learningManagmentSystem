<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function me()
    {
        $user = Auth::user();

        Gate::authorize('me',$user);

        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved successfully.',
            'user' => new UserResource($user),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();

        Gate::authorize('updateProfile',$user);

        $data = $request->validated();

        $user->update($data);

        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => new UserResource($user),
        ]);
    }

    public function index()
    {
        $users = User::all();

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'users' => UserResource::collection($users),
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully.',
            'user' => new UserResource($user),
        ]);
    }


    public function changeRole(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'role' => 'required|in:admin,instructor,student',
        ]);

        Gate::authorize('changeRole',$user);

        $user -> update($data);
        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'User role updated successfully.',
            'user' => new UserResource($user),
        ]);
    }

    public function changeUserActivity(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'is_blocked'=>'required|boolean',
        ]);

        Gate::authorize('block',$user);

        $user->update($data);
        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'User Activaty Updated successfully.',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        Gate::authorize('delete',$user);

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }
}
