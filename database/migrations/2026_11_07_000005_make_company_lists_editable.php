<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task types and tags become lists each business manages on Settings:
 * task types get a default price, and both can be archived (never deleted).
 * A builder can have its own price for a task type (task_type_prices).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_types', function (Blueprint $table) {
            $table->decimal('default_price', 10, 2)->unsigned()->nullable()->after('description');
            $table->softDeletes();
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('task_type_prices', function (Blueprint $table) {
            $table->unique(['task_type_id', 'builder_id']);
        });
    }

    public function down(): void
    {
        Schema::table('task_type_prices', function (Blueprint $table) {
            $table->dropUnique(['task_type_id', 'builder_id']);
        });
        Schema::table('tags', fn (Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('task_types', function (Blueprint $table) {
            $table->dropColumn('default_price');
            $table->dropSoftDeletes();
        });
    }
};
