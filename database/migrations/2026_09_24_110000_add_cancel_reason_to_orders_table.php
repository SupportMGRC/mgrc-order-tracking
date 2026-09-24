<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cancelling an order now needs a reason, the same as a pickup.
     * Orders cancelled before this stay blank.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('cancel_reason')->nullable()->after('remarks');
            $table->string('cancelled_by')->nullable()->after('cancel_reason');
            $table->dateTime('cancelled_at')->nullable()->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cancel_reason', 'cancelled_by', 'cancelled_at']);
        });
    }
};
