<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanyUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all user and company IDs
        $userIds = User::all()->pluck('id');
        $companyIds = Company::all()->pluck('id');

        // Attach random users to random companies
        foreach ($companyIds as $companyId) {
            $numUsersToAttach = rand(1, $userIds->count()); // Attach between 1 and all users
            $usersToAttach = $userIds->random($numUsersToAttach); // Get random user IDs

            // Add users to this company
            Company::find($companyId)->users()->attach($usersToAttach);
        }
    }
}
