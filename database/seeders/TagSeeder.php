<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Walk', 'color' => '#0284c7'],
            ['name' => 'Closing', 'color' => '#16a34a'],
            ['name' => 'Spec Houses / Inventory', 'color' => '#a855f7'],
        ];

        foreach ($defaults as $d) {
            Tag::firstOrCreate(
                ['name' => $d['name']],
                ['color' => $d['color']]
            );
        }
    }
}
