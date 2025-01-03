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
        Schema::create('status_types', function (Blueprint $table) {
            $table->id();
            $table->enum('identifier', [
                'pending',
                'confirmed',
                'in_progress',
                'completed',
                'blocked',
                'payment_pending',
                'cancelled',
            ])->unique()->index();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('color')->comment('Light/Default color');
            $table->string('color_dark')->comment('Dark mode color.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_types');
    }
};
