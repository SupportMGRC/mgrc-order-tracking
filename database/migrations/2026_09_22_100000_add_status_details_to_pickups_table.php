<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pickup module, phase 3: who did each step and when.
     * The *_name columns keep the name as it was at the time, so the record
     * still reads correctly if the user is later renamed or deleted.
     */
    public function up(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            // On the Way
            $table->dateTime('on_the_way_at')->nullable()->after('status');
            $table->foreignId('despatcher_id')->nullable()->after('on_the_way_at')->constrained('users')->nullOnDelete();
            $table->string('despatcher_name')->nullable()->after('despatcher_id');

            // Picked Up
            $table->dateTime('picked_up_at')->nullable()->after('despatcher_name');
            $table->foreignId('picked_up_by_id')->nullable()->after('picked_up_at')->constrained('users')->nullOnDelete();
            $table->string('picked_up_by_name')->nullable()->after('picked_up_by_id');
            $table->string('handed_over_by')->nullable()->after('picked_up_by_name');
            $table->string('pickup_temperature', 20)->nullable()->after('handed_over_by');
            $table->text('pickup_photos')->nullable()->after('pickup_temperature');
            $table->text('pickup_remarks')->nullable()->after('pickup_photos');

            // Received
            $table->dateTime('received_at')->nullable()->after('pickup_remarks');
            $table->foreignId('received_by_id')->nullable()->after('received_at')->constrained('users')->nullOnDelete();
            $table->string('received_by_name')->nullable()->after('received_by_id');
            $table->string('received_temperature', 20)->nullable()->after('received_by_name');
            $table->text('received_remarks')->nullable()->after('received_temperature');

            // Cancelled
            $table->dateTime('cancelled_at')->nullable()->after('received_remarks');
            $table->foreignId('cancelled_by_id')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->string('cancelled_by_name')->nullable()->after('cancelled_by_id');
            $table->text('cancel_reason')->nullable()->after('cancelled_by_name');
        });
    }

    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->dropForeign(['despatcher_id']);
            $table->dropForeign(['picked_up_by_id']);
            $table->dropForeign(['received_by_id']);
            $table->dropForeign(['cancelled_by_id']);
            $table->dropColumn([
                'on_the_way_at', 'despatcher_id', 'despatcher_name',
                'picked_up_at', 'picked_up_by_id', 'picked_up_by_name', 'handed_over_by',
                'pickup_temperature', 'pickup_photos', 'pickup_remarks',
                'received_at', 'received_by_id', 'received_by_name', 'received_temperature', 'received_remarks',
                'cancelled_at', 'cancelled_by_id', 'cancelled_by_name', 'cancel_reason',
            ]);
        });
    }
};
