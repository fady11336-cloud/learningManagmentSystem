<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;

class SubmissionService
{
    /**
     * Create a new class instance.
     */

    public function submit(User $user, Assignment $assignment, array $data)
    {
        $alreadySubmited = $assignment->submissions()->where('user_id',$user->id)
        ->exists();

        if($alreadySubmited)
            {
                return null;
            }

        return $assignment->submissions()->create([
            'user_id'=>$user->id,
            'submission_file'=>$data['submission_file'],
            'submission_at'=>now(),
        ]);
    }

    public function grade(Submission $submission, array $data)
    {

        $submission->update([
            'grade'=>$data['grade'],
            'grade_status'=>'graded'
        ]);

        return $submission->fresh();
    }
    
}
