<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add 'request_payment' to the status_types.identifier ENUM
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE status_types
            MODIFY COLUMN identifier ENUM(
                'pending',
                'confirmed',
                'in_progress',
                'completed',
                'blocked',
                'payment_pending',
                'cancelled',
                'request_payment'
            ) NOT NULL
        ");
    }

    /**
     * Revert: remove 'request_payment' from the ENUM
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE status_types
            MODIFY COLUMN identifier ENUM(
                'pending',
                'confirmed',
                'in_progress',
                'completed',
                'blocked',
                'payment_pending',
                'cancelled'
            ) NOT NULL
        ");
    }

};
