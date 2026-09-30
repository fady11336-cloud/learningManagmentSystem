<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\User;

class SubmissionService
{
    /**
     * Create a new class instance.
     */

    public function submit(User $user, Assignment $assignment, array $data)
    {
        return $assignment->submissions()->create([
            'assignment_id'=>$assignment->id,
            'user_id'=>$user->id,
            'submission_file'=>$data['submission_file'],
            'submission_at'=>now(),
        ]);
    }
    
}
