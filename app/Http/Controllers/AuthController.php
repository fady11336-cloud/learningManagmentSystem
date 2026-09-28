<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Password;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {

      $request->validated();
        
      
      $role = Role::where('name', $request->role)->firstOrFail();
        

      $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => $request->password,
        'role_id' => $role->id,
      ]);

      $user->sendEmailVerificationNotification();

      return response()->json([
          'success' => true,
          'message' => 'User registered successfully. Please verify your email.',
          'user' => new UserResource($user),
       ], 201);
    
    }

    public function login(LoginRequest $request)
    {
        $request->validated();

        $credentials = $request->only('email', 'password');

        if(!$token = Auth::attempt($credentials))
            {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Invalid email or password',
                    ]
                );
            }

        $user = Auth::user();

        Auth::login($user);

        return response()->json([
            'success' => true,
            'message' => 'User logged in successfully',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
        
    }

    public function me()
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'user' => new UserResource($user),
        ]);
    }

    public function logout()
    {
        Auth::logout();

        return response()->json([
            'success' => true,
            'message' => 'User logged out successfully',
        ]);
    }

    public function refresh()
    {
        $token = Auth::refresh();

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'token' => $token,
        ]);
    }

    public function verifyEmail(Request $request, $id ,$hash)
    {
        if(!URL::hasValidSignature($request))
            {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired verification link',
                ], 403);
            }

        $user = User::findOrFail($id);

        if(!hash_equals(sha1($user->getEmailForVerification()), $hash))
            {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification link',
                ], 403);
            }

        if($user->hasVerifiedEmail())
            {
                return response()->json([
                    'success' => true,
                    'message' => 'Email already verified',
                ]);
            }

        $user->markEmailAsVerified();

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully',
        ]);
    }

    public function sendVerificationEmail()
    {
        $user = Auth::user();

        if (!$user instanceof User) {

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if($user->hasVerifiedEmail())
            {
                return response()->json([
                    'success' => true,
                    'message' => 'Email already verified',
                ]);
            }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent successfully',
        ]);
    }


    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if(!$status === Password::RESET_LINK_SENT)
            {
                return response()->json([
                    'message' => $status,
                ],422);
            }

        return response()->json([
            'message' => $status,
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $request->validated();

        $status = Password::reset($request->only('email','password','password_confirmation','token')
        ,function($user,$password)
        {
            $user->update([
                'password' => Hash::make($password),
            ]);
        });

        return response()->json([
            'message' => $status
        ],$status === Password::PASSWORD_RESET ? 200 : 400);
    }


}
