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
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('builder_id')->constrained('builders')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('supervisors')->cascadeOnDelete();
            $table->foreignId('status_type_id')->index()->constrained('status_types');
            $table->unsignedSmallInteger('lot_number')->index();
            $table->string('phase')->nullable();
            $table->string('address')->nullable();
            $table->string('subdivision')->nullable();
            $table->decimal('floor_area', 10, 2)->comment('Total floor area in square foot')->unsigned();
            $table->unsignedTinyInteger('num_of_floors')->comment('Number of floors');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
