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
        // Get admin user
        $adminUser = User::where('email', 'admin@demo.com')->first();

        // Get all companies
        $companies = Company::all();

        // Attach admin to all companies
        foreach ($companies as $company) {
            $company->users()->syncWithoutDetaching([$adminUser->id]);
        }

        // Get remaining users for random assignment
        $otherUsers = User::where('email', '!=', 'admin@demo.com')->pluck('id');

        // Attach random users to random companies
        foreach ($companies as $company) {
            $numUsersToAttach = rand(1, $otherUsers->count()); // Attach between 1 and all other users
            $usersToAttach = $otherUsers->random($numUsersToAttach); // Get random user IDs

            // Add other users to this company
            $company->users()->attach($usersToAttach);
        }
    }
}
