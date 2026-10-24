<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What we pay the crew for a task. Credited to the task's team, or split
     * evenly between the assigned workers when the task has no team.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('crew_pay', 10, 2)->unsigned()->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('crew_pay');
        });
    }
};
