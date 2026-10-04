<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
        'user_id',
        'category_id',
        'visibility',
    ];

    public function user(): BelongsTo
    {
        return $this -> belongTo(User::class);
    }
    
    public function category(): BelongsTo
    {
        return $this -> belongsTo(Category::class);
    }

    public function lessons(): HasMany
    {
        return $this -> hasMany(Lesson::class);
    }

    public function certificates(): HasMany
    {
        return $this -> hasMany(Certificate::class);
    }

    public function enrollments(): HasMany
    {
        return $this -> hasMany(Enrollment::class);
    }

    public function assignments(): HasMany
    {
        return $this -> hasMany(Assignment::class);
    }

    public function quizzes(): HasMany
    {
        return $this -> hasMany(Quiz::class);
    }
}
