<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    protected $fillable = [
        'grade',
        'assignment_id',
        'user_id',
        'submission_file',
        'submission_at'
    ];

    public function assignment(): BelongsTo
    {
        return $this -> belongsTo(Assignment::class);
    }

    public function user(): BelongsTo
    {
        return $this -> belongsTo(User::class);
    }
}
