<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::create([
            'name' => 'Programming',

        ]);

        Category::create([
            'name' => 'Database',
        ]);

        Category::create([
            'name' => 'Web Development',
        ]);

        Category::create([
            'name' => 'Software Engineering',
        ]);
    }
}