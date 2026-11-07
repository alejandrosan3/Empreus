<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Organization;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\Supervisor;
use App\Models\User;
use App\Services\PeopleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Starter roles for every organization, and access for the demo users:
 * admin@demo.com is platform owner and Owner of the organization,
 * worker@demo.com is a Worker in every division of the first business.
 *
 * For manual testing it also adds one person per starter role (password "demo"):
 * owner@, officeadmin@ (Admin of one division only), scheduler@ (whole business),
 * supervisor@ (one division, linked to a supervisor record) and viewer@ (whole organization).
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

        if (! app()->environment('production')) {
            $this->seedTesters($organization, $business);
        }
    }

    private function seedTesters(Organization $organization, Business $business): void
    {
        $people = app(PeopleService::class);
        $division = $business->divisions()->orderBy('id')->first();
        $role = fn (string $key) => $organization->roles()->where('key', $key)->first();
        $person = fn (string $email, string $name) => User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'role' => 'admin', 'password' => Hash::make('demo'), 'email_verified_at' => now()],
        );

        $people->grant($person('owner@demo.com', 'Olivia Owner'), $role('owner'), RoleAssignment::ORGANIZATION, $organization->id);
        $people->grant($person('viewer@demo.com', 'Victor Viewer'), $role('viewer'), RoleAssignment::ORGANIZATION, $organization->id);
        $people->grant($person('scheduler@demo.com', 'Sam Scheduler'), $role('scheduler'), RoleAssignment::BUSINESS, $business->id);

        if ($division) {
            $people->grant($person('officeadmin@demo.com', 'Andy Admin'), $role('admin'), RoleAssignment::DIVISION, $division->id);

            $supervisorLogin = $person('supervisor@demo.com', 'Sue Supervisor');
            $people->grant($supervisorLogin, $role('supervisor'), RoleAssignment::DIVISION, $division->id);

            // Link the login to the supervisor with the most lots in that division.
            $supervisorId = DB::table('lots')->where('company_id', $division->id)->whereNotNull('supervisor_id')
                ->groupBy('supervisor_id')->orderByRaw('count(*) desc')->value('supervisor_id');
            if ($supervisorId) {
                Supervisor::everywhere()->whereKey($supervisorId)->update(['user_id' => $supervisorLogin->id]);
            }
        }
    }
}
