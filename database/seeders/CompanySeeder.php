<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            ['name' => 'Jacksonville', 'description' => 'North Florida region'],
            ['name' => 'Tampa', 'description' => 'West Central Florida region'],
            ['name' => 'Central Florida', 'description' => 'Orlando and surrounding areas'],
            ['name' => 'Volusia', 'description' => 'East Central Florida region'],
        ];

        foreach ($accounts as $account) {
            Company::updateOrCreate(
                ['name' => $account['name']],
                $account
            );
        }
    }
}
