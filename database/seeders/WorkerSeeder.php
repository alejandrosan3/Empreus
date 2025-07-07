<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanyUser;

class WorkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 20 workers
        $workers = [
            ['name' => 'John Smith', 'email' => 'john.smith@demo.com'],
            ['name' => 'Mike Johnson', 'email' => 'mike.johnson@demo.com'],
            ['name' => 'David Brown', 'email' => 'david.brown@demo.com'],
            ['name' => 'Chris Wilson', 'email' => 'chris.wilson@demo.com'],
            ['name' => 'Robert Davis', 'email' => 'robert.davis@demo.com'],
            ['name' => 'James Miller', 'email' => 'james.miller@demo.com'],
            ['name' => 'William Garcia', 'email' => 'william.garcia@demo.com'],
            ['name' => 'Thomas Martinez', 'email' => 'thomas.martinez@demo.com'],
            ['name' => 'Richard Anderson', 'email' => 'richard.anderson@demo.com'],
            ['name' => 'Daniel Taylor', 'email' => 'daniel.taylor@demo.com'],
            ['name' => 'Matthew Thomas', 'email' => 'matthew.thomas@demo.com'],
            ['name' => 'Anthony Jackson', 'email' => 'anthony.jackson@demo.com'],
            ['name' => 'Mark White', 'email' => 'mark.white@demo.com'],
            ['name' => 'Donald Harris', 'email' => 'donald.harris@demo.com'],
            ['name' => 'Steven Martin', 'email' => 'steven.martin@demo.com'],
            ['name' => 'Paul Thompson', 'email' => 'paul.thompson@demo.com'],
            ['name' => 'Andrew Garcia', 'email' => 'andrew.garcia@demo.com'],
            ['name' => 'Joshua Martinez', 'email' => 'joshua.martinez@demo.com'],
            ['name' => 'Kenneth Robinson', 'email' => 'kenneth.robinson@demo.com'],
            ['name' => 'Kevin Clark', 'email' => 'kevin.clark@demo.com'],
        ];

        // Get all companies
        $companies = Company::all();

        foreach ($workers as $worker) {
            // Create the worker
            $user = User::create([
                'name' => $worker['name'],
                'email' => $worker['email'],
                'role' => 'worker',
                'password' => Hash::make('demo'),
                'email_verified_at' => now(),
            ]);

            // Assign the worker to all companies
            foreach ($companies as $company) {
                CompanyUser::create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                ]);
            }
        }

        $this->command->info('Created ' . count($workers) . ' workers and assigned them to ' . $companies->count() . ' companies.');
    }
}
