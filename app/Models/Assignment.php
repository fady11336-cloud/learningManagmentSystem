<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    protected $fillable = [
        'title',
        'description',
        'due_date',
        'total_marks',
        'course_id'
    ];

    public function course(): BelongsTo
    {
        return $this -> belongsTo(Course::class);
    }

    public function submissions(): HasMany
    {
        return $this -> hasMany(Submission::class);
    }
}
