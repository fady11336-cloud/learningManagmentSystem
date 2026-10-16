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
use App\Models\Quiz;
use App\Models\Attempt;
use App\Models\Progress;
use App\Policies\AssignmentPolicy;
use App\Policies\CoursePolicy;
use App\Policies\LessonPolicy;
use Illuminate\Support\Facades\Gate;
use App\Policies\EnrollmentPolicy;
use App\Policies\QuizPolicy;
use App\Policies\AttemptPolicy;
use App\Policies\ProgressPolicy;
use Dom\Attr;

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
        Gate::policy(Quiz::class, QuizPolicy::class);
        Gate::policy(Attempt::class, AttemptPolicy::class); 
        Gate::policy(Progress::class, ProgressPolicy::class);

        ResetPassword::createUrlUsing(function (User $user, string $token)
        {
            return config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
        });
    }
}