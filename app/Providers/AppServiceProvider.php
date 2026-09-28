<?php

namespace App\Providers;

use App\Models\Assignment;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Policies\AssignmentPolicy;
use App\Policies\CoursePolicy;
use App\Policies\LessonPolicy;
use Illuminate\Support\Facades\Gate;
use App\Policies\EnrollmentPolicy;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(Enrollment::class, EnrollmentPolicy::class);
        Gate::policy(Assignment::class, AssignmentPolicy::class);

        ResetPassword::createUrlUsing(function (User $user, string $token)
        {
            return config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
        });
    }
}