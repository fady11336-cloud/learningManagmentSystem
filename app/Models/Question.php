<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $fillable = [
        'mark',
        'question_type',
        'question_text',
        'quiz_id',
    ];

    public function quiz(): BelongsTo
    {
        return $this -> belongsTo(Quiz::class);
    }
}
