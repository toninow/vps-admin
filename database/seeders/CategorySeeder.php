<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::firstOrCreate(
            ['slug' => 'inicio'],
            [
                'parent_id' => null,
                'name' => 'INICIO',
                'position' => 0,
                'is_active' => true,
            ]
        );
    }
}
