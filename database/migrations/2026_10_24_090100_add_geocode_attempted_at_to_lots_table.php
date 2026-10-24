<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remember when we last tried to geocode a lot, so addresses that no free
     * geocoder can find are not retried on every run.
     */
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->timestamp('geocode_attempted_at')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn('geocode_attempted_at');
        });
    }
};
