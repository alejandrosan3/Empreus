<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the two levels above what the app calls a "company" today:
 *
 *   Organization (the paying account, e.g. Empreus)
 *     └─ Business (a line of work, e.g. Cleaning, Painting)
 *          └─ Division (a branch, e.g. Jacksonville) = the existing `companies` table
 *
 * Existing companies are moved under one organization and one business so nothing
 * changes for current users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'name']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->softDeletes();
        });

        // Division names only need to be unique inside their business.
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['business_id', 'name']);
        });

        if (DB::table('companies')->exists()) {
            $now = now();
            $organizationId = DB::table('organizations')->insertGetId([
                'name' => config('app.name', 'My organization'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $businessId = DB::table('businesses')->insertGetId([
                'organization_id' => $organizationId,
                'name' => 'Cleaning',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('companies')->whereNull('business_id')->update(['business_id' => $businessId]);
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropUnique(['business_id', 'name']);
            $table->dropColumn('business_id');
            $table->dropSoftDeletes();
            $table->unique(['name']);
        });
        Schema::dropIfExists('businesses');
        Schema::dropIfExists('organizations');
    }
};
