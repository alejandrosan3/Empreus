<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->index('subdivision');
            $table->index('phase');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('scheduled_start_date');
            $table->index('scheduled_end_date');
            $table->index('started_at');
            $table->index('ended_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropIndex(['subdivision']);
            $table->dropIndex(['phase']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['scheduled_start_date']);
            $table->dropIndex(['scheduled_end_date']);
            $table->dropIndex(['started_at']);
            $table->dropIndex(['ended_at']);
        });
    }
};
