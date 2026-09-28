<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    protected $fillable = [
        'title',
        'description',
        'total_marks',
        'course_id',
    ];

    public function course(): BelongsTo
    {
        return $this -> belongsTo(Course::class);
    }

    public function questions(): HasMany
    {
        return $this -> hasMany(Question::class);
    }

    public function attempts(): HasMany
    {
        return $this -> hasMany(Attempt::class);
    }
}
