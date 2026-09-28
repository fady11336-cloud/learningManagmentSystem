<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Progress extends Model
{
    protected $fillable = [
        'lesson_id',
        'enrollment_id',
    ];

    public function lesson(): BelongsTo
    {
        return $this -> belongsTo(Lesson::class);   
    }

    public function enrollment(): BelongsTo
    {
        return $this -> belongsTo(Enrollment::class);
    }
}
