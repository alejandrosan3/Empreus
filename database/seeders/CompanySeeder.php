<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Business;
use App\Models\Company;
use App\Models\Organization;

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

        $organization = Organization::firstOrCreate(['name' => config('app.name', 'Demo organization')]);
        $business = Business::firstOrCreate(['organization_id' => $organization->id, 'name' => 'Cleaning']);

        foreach ($accounts as $account) {
            Company::updateOrCreate(
                ['business_id' => $business->id, 'name' => $account['name']],
                $account
            );
        }
    }
}
