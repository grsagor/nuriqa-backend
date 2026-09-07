<?php

namespace Database\Seeders;

use App\Models\Cause;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CauseSeeder extends Seeder
{
    public function run(): void
    {
        $causes = [
            ['name' => 'General Charity Fund', 'description' => 'Flexible support for Nuriqa-partnered causes.', 'sort_order' => 1],
            ['name' => 'Clothing Relief', 'description' => 'Help provide clothing with dignity.', 'sort_order' => 2],
            ['name' => 'Community Support', 'description' => 'Local community programmes.', 'sort_order' => 3],
            ['name' => 'Education Access', 'description' => 'Support education and skills access.', 'sort_order' => 4],
        ];

        foreach ($causes as $cause) {
            Cause::query()->updateOrCreate(
                ['slug' => Str::slug($cause['name'])],
                [
                    'name' => $cause['name'],
                    'description' => $cause['description'],
                    'is_active' => true,
                    'sort_order' => $cause['sort_order'],
                ]
            );
        }
    }
}
