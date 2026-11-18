<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Food & drink', 'applies_to' => 'both'],
            ['name' => 'Retail & shopping', 'applies_to' => 'both'],
            ['name' => 'Wholesale & suppliers', 'applies_to' => 'both'],
            ['name' => 'Home & services', 'applies_to' => 'both'],
            ['name' => 'Health & wellness', 'applies_to' => 'both'],
            ['name' => 'Outdoor & recreation', 'applies_to' => 'both'],
        ] as $category) {
            $slug = Str::slug($category['name']);
            Category::query()->updateOrCreate(
                ['slug' => $slug],
                [...$category, 'slug' => $slug, 'is_active' => true],
            );
        }
    }
}
