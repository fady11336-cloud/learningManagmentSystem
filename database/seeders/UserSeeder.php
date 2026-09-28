<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\Concerns\Has;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $student = Role::where('name', 'student')->firstOrFail();
        $instructor = Role::where('name','instructor')->firstOrFail();
        $admin = Role::where('name', 'admin')->firstOrFail();

        User::factory()->create([
            'name' => 'Test Student',
            'email' => 'test@example.com',
            'password' => Hash::make('student123'),
            'role_id' => $student->id,
        ]);

        User::factory()->create([
            'name' => 'Instructor User',
            'email' => 'instructor@example.com',
            'password' => Hash::make('instructor123'),
            'role_id' => $instructor->id,
        ]);

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role_id' => $admin->id,
        ]);
    }
}