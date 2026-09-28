<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'enrolled_at',
    ];


    public function user(): BelongsTo
    {
        return $this -> belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this -> belongsTo(Course::class);
    }

    public function progresses(): HasMany
    {
        return $this -> hasMany(Progress::class);
    }
}
