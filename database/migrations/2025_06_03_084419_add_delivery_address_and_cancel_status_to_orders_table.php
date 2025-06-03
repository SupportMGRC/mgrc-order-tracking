<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add delivery_address column
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_address')->nullable()->after('delivered_by');
            $table->string('order_photo')->nullable()->after('delivery_address');
        });

        // Add 'cancel' to status enum (raw SQL for MySQL compatibility)
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('new', 'preparing', 'ready', 'delivered', 'cancel') DEFAULT 'new'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove delivery_address and order_photo columns only if they exist
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_address')) {
                $table->dropColumn('delivery_address');
            }
            if (Schema::hasColumn('orders', 'order_photo')) {
                $table->dropColumn('order_photo');
            }
        });

        // Remove 'cancel' from status enum (raw SQL for MySQL compatibility)
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('new', 'preparing', 'ready', 'delivered') DEFAULT 'new'");
    }
};
