<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->nullable(); // optional: hex or tailwind token
            $table->timestamps();
        });

        Schema::create('tag_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            // time of day for the tag on this task (no date component, only time)
            $table->time('time_of_day')->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_task');
        Schema::dropIfExists('tags');
    }
};
