<?php

namespace App\Services;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
class EnrollmentService
{
    /**
     * Create a new class instance.
     */
    public function enroll(User $user, Course $course)
    {
        $alreadyEnrolled = Enrollment::where('user_id',$user->id)
        ->where('course_id',$course->id)->exists();

        if($alreadyEnrolled)
            {
                return response()->json([
                    'success'=>false,
                    'message'=>'already enrolled',
                ]);
            }

        $enroll = Enrollment::create([
            'user_id'=>$user->id,
            'course_id'=>$course->id,
            'enrolled_at'=>now(),
        ]);

        return response()->json([
            'success'=>true,
            'message'=>'enrolled successfully',
            'data'=>$enroll,
        ]);
    }
}
