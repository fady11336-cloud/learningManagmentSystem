<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'title',
        'description',
        'content',
        'duration',
        'order',
        'course_id',
    ];

    public function course(): BelongsTo
    {
        return $this -> belongsTo(Course::class);
    }

    public function progresses(): HasMany
    {
        return $this -> hasMany(Progress::class);
    }
}
