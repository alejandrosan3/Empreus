<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Records that used to be shared by every company now belong to one place:
 *
 *  - builders, supervisors  → organization (the same builder hires several businesses)
 *  - task types, tags       → business (painting tasks differ from cleaning tasks)
 *  - teams                  → division (crews work in one branch)
 *
 * Existing rows are assigned to the organization/business/division they are used in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builders', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
        Schema::table('supervisors', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
        Schema::table('task_types', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
        Schema::table('tags', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Emails and team names only need to be unique inside their own organization / division.
        Schema::table('builders', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['organization_id', 'email']);
        });
        Schema::table('supervisors', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['organization_id', 'email']);
        });
        Schema::table('teams', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['company_id', 'name']);
        });

        $this->backfill();
    }

    private function backfill(): void
    {
        $organizationId = DB::table('organizations')->orderBy('id')->value('id');
        $businessId = DB::table('businesses')->orderBy('id')->value('id');
        $firstDivisionId = DB::table('companies')->orderBy('id')->value('id');

        if ($organizationId) {
            DB::table('builders')->whereNull('organization_id')->update(['organization_id' => $organizationId]);
            DB::table('supervisors')->whereNull('organization_id')->update(['organization_id' => $organizationId]);
        }
        if ($businessId) {
            DB::table('task_types')->whereNull('business_id')->update(['business_id' => $businessId]);
            DB::table('tags')->whereNull('business_id')->update(['business_id' => $businessId]);
        }

        // A team belongs to the division whose tasks it works on most.
        foreach (DB::table('teams')->whereNull('company_id')->pluck('id') as $teamId) {
            $divisionId = DB::table('tasks')
                ->where('team_id', $teamId)
                ->select('company_id', DB::raw('count(*) as total'))
                ->groupBy('company_id')
                ->orderByDesc('total')
                ->value('company_id');

            if (! $divisionId) {
                $divisionId = DB::table('team_worker')
                    ->join('company_users', 'company_users.user_id', '=', 'team_worker.worker_id')
                    ->where('team_worker.team_id', $teamId)
                    ->value('company_users.company_id');
            }

            DB::table('teams')->where('id', $teamId)->update(['company_id' => $divisionId ?: $firstDivisionId]);
        }
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropUnique(['company_id', 'name']);
            $table->dropColumn('company_id');
            $table->unique(['name']);
        });
        Schema::table('supervisors', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropUnique(['organization_id', 'email']);
            $table->dropColumn('organization_id');
            $table->unique(['email']);
        });
        Schema::table('builders', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropUnique(['organization_id', 'email']);
            $table->dropColumn('organization_id');
            $table->unique(['email']);
        });
        Schema::table('tags', fn (Blueprint $table) => $table->dropConstrainedForeignId('business_id'));
        Schema::table('task_types', fn (Blueprint $table) => $table->dropConstrainedForeignId('business_id'));
    }
};
