<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Roles belong to an organization (or to the platform library when
 * organization_id is empty). A person's access is a list of role assignments:
 * "this role, in this organization / business / division".
 *
 * Existing people keep what they had: admins become Owners of the
 * organization, workers become Workers in the divisions they were in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key')->nullable()->comment('Starter role this was copied from, if any');
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('levels');
            $table->json('details');
            $table->string('reach')->default('all');
            $table->boolean('assignable')->default(false)->comment('People with this role can be given tasks');
            $table->boolean('is_owner')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'name']);
        });

        Schema::create('role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type'); // organization | business | division
            $table->unsignedBigInteger('scope_id');
            $table->timestamps();

            $table->unique(['user_id', 'role_id', 'scope_type', 'scope_id'], 'role_assignments_unique');
            $table->index(['scope_type', 'scope_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_platform_admin')->default(false)->after('role');
            $table->timestamp('deactivated_at')->nullable()->after('is_platform_admin');
        });

        Schema::table('supervisors', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('organization_id')
                ->comment('Optional login linked to this supervisor')
                ->constrained()->nullOnDelete();
        });

        $this->moveExistingPeople();
    }

    private function moveExistingPeople(): void
    {
        $now = now();

        foreach (DB::table('organizations')->pluck('id') as $organizationId) {
            $roleIds = [];
            foreach (config('permissions.starter_roles') as $key => $role) {
                $roleIds[$key] = DB::table('roles')->insertGetId([
                    'organization_id' => $organizationId,
                    'key' => $key,
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'levels' => json_encode(\App\Models\Role::expandLevels($role['levels'])),
                    'details' => json_encode($role['details']),
                    'reach' => $role['reach'],
                    'assignable' => $role['assignable'],
                    'is_owner' => $role['is_owner'] ?? false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $divisionIds = DB::table('companies')
                ->join('businesses', 'businesses.id', '=', 'companies.business_id')
                ->where('businesses.organization_id', $organizationId)
                ->pluck('companies.id');

            $memberIds = DB::table('company_users')->whereIn('company_id', $divisionIds)->pluck('user_id')->unique();

            // Admins: Owner of the whole organization.
            foreach (DB::table('users')->where('role', 'admin')->whereIn('id', $memberIds)->pluck('id') as $userId) {
                DB::table('role_assignments')->insert([
                    'user_id' => $userId, 'role_id' => $roleIds['owner'],
                    'scope_type' => 'organization', 'scope_id' => $organizationId,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            // Everyone else: Worker in each division they were part of.
            $rows = DB::table('company_users')
                ->join('users', 'users.id', '=', 'company_users.user_id')
                ->where('users.role', '!=', 'admin')
                ->whereIn('company_users.company_id', $divisionIds)
                ->select('company_users.user_id', 'company_users.company_id')
                ->distinct()
                ->get();

            // Workers created by the lot importer were never added to a division:
            // give them access where their tasks are.
            $importedRows = DB::table('task_worker')
                ->join('tasks', 'tasks.id', '=', 'task_worker.task_id')
                ->join('users', 'users.id', '=', 'task_worker.worker_id')
                ->where('users.role', 'worker')
                ->whereIn('tasks.company_id', $divisionIds)
                ->select('task_worker.worker_id as user_id', 'tasks.company_id')
                ->distinct()
                ->get();

            foreach ($rows->concat($importedRows) as $row) {
                DB::table('role_assignments')->insertOrIgnore([
                    'user_id' => $row->user_id, 'role_id' => $roleIds['worker'],
                    'scope_type' => 'division', 'scope_id' => $row->company_id,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('supervisors', fn (Blueprint $table) => $table->dropConstrainedForeignId('user_id'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['is_platform_admin', 'deactivated_at']));
        Schema::dropIfExists('role_assignments');
        Schema::dropIfExists('roles');
    }
};
