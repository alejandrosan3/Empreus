<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Organization;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Services\PeopleService;
use Illuminate\Database\Seeder;

/**
 * Starter roles for every organization, and access for the demo users:
 * admin@demo.com is platform owner and Owner of the organization,
 * worker@demo.com is a Worker in every division of the first business.
 */
class AccessSeeder extends Seeder
{
    public function run(): void
    {
        $people = app(PeopleService::class);

        foreach (Organization::all() as $organization) {
            Role::createStarterRoles($organization);
        }

        $organization = Organization::orderBy('id')->first();
        $business = Business::orderBy('id')->first();
        if (! $organization || ! $business) {
            return;
        }

        if ($admin = User::where('email', 'admin@demo.com')->first()) {
            $admin->forceFill(['is_platform_admin' => true])->save();
            $people->grant($admin, $organization->roles()->where('key', 'owner')->first(), RoleAssignment::ORGANIZATION, $organization->id);
        }

        if ($worker = User::where('email', 'worker@demo.com')->first()) {
            $people->grant($worker, $organization->roles()->where('key', 'worker')->first(), RoleAssignment::BUSINESS, $business->id);
        }
    }
}
