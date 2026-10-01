<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'assignment_id'=>$this->assignment->id,
            'user_id'=>$this->user->id,
            'submission_file'=>$this->submission_file,
            'submission_at'=>$this->submission_at,
            'grade'=>$this->grade,
            'grade_status'=>$this->grade_status
        ];
    }
}
