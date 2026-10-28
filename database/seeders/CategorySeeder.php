<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Technical Support', 'slug' => 'technical-support'],
            ['name' => 'Billing', 'slug' => 'billing'],
            ['name' => 'Account & Access', 'slug' => 'account-access'],
            ['name' => 'Feature Request', 'slug' => 'feature-request'],
            ['name' => 'General Inquiry', 'slug' => 'general-inquiry'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
