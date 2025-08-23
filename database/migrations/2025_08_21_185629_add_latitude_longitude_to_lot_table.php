<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            // DECIMAL(10,7) is fine; ~1cm precision. Keep them signed (negatives exist).
            $table->decimal('latitude', 10, 7)->nullable()->after('subdivision');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
