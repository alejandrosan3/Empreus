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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->index()->constrained('lots')->cascadeOnDelete();
            $table->foreignId('task_type_id')->index()->constrained('task_types')->cascadeOnDelete();
            $table->foreignId('status_type_id')->index()->constrained('status_types');
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->boolean('is_extra')->default(false);
            $table->decimal('price', 10, 2)->unsigned();
            $table->dateTime('scheduled_start_date')->nullable()->comment('Scheduled start date');
            $table->dateTime('scheduled_end_date')->nullable()->comment('Scheduled end date');
            $table->dateTime('started_at')->nullable()->comment('Actual start date');
            $table->dateTime('ended_at')->nullable()->comment('Actual end date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
