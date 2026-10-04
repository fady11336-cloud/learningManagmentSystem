<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Answer;
use App\Models\User;
use Illuminate\Support\Facades\DB;


class AttemptService
{
    public function attempt(User $user,Quiz $quiz, array $data)
    {
        return DB::transaction(function() use($user,$quiz,$data)
        {
            $score = 0;

            $attempt = $quiz->attempts()->create([
                'user_id'=>$user->id,
                'score'=>$score,
                'status'=>'failed',
                'started_at'=>now(),
                'completed_at'=>now(),
            ]);    

            foreach($data as $questionId => $answer)
                {
                    $question = $quiz->questions()->findOrFail($questionId);

                    $attempt->answers()->create([
                        'question_id'=>$questionId,
                        'answer'=>$answer,
                    ]);

                    if($answer === $question->correct_answer)
                        {
                            $score += $question->mark;
                        }
                }

            $score = ($score/$quiz->total_marks) *100;

            $status = $score>=50 ? 'successed' :'failed';

            $attempt->update([
                'score'=>$score,
                'status'=>$status,
            ]);

            return $attempt->refresh();
        });
        
    }
}
