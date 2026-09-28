<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Override;

#[Fillable(['name', 'email', 'password','role_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role->name,
        ];
    }

    public function role(): BelongsTo 
    {
        return $this -> belongsTo(Role::class);
    }

    public function courses(): HasMany
    {
        return $this -> hasMany(Course::class);
    }

    public function certificates(): HasMany
    {
        return $this -> hasMany(Certificate::class);
    }

    public function enrollments(): HasMany
    {
        return $this -> hasMany(Enrollment::class);
    }

    public function submissions(): HasMany
    {
        return $this -> hasMany(Submission::class);
    }

    public function attempts(): HasMany
    {
        return $this -> hasMany(Attempt::class);
    }

    public function notifications(): HasMany
    {
        return $this -> hasMany(Notification::class);
    }
}
