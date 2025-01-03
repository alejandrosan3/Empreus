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
        Schema::create('task_type_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('builder_id')->constrained('builders')->cascadeOnDelete();
            $table->foreignId('task_type_id')->constrained('task_types')->cascadeOnDelete();
            $table->decimal('price', 10, 2)->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_type_prices');
    }
};
