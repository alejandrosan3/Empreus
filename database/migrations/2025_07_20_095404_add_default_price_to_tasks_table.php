<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // requires doctrine/dbal package for ->change()
        Schema::table('tasks', function (Blueprint $table) {
            $table
                ->decimal('price', 10, 2)
                ->unsigned()
                ->default(0)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table
                ->decimal('price', 10, 2)
                ->unsigned()
                ->default(null)
                ->change();
        });
    }
};
