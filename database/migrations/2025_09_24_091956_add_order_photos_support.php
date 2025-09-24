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
        Schema::table('orders', function (Blueprint $table) {
            // Add JSON column for multiple photos
            $table->json('order_photos')->nullable()->after('order_photo');
        });
        
        // Migrate existing single photos to the new format
        $this->migrateSinglePhotosToMultiple();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('order_photos');
        });
    }
    
    /**
     * Migrate existing single photos to multiple photos format
     */
    private function migrateSinglePhotosToMultiple()
    {
        $orders = \App\Models\Order::whereNotNull('order_photo')->get();
        
        foreach ($orders as $order) {
            $order->order_photos = [$order->order_photo];
            $order->save();
        }
    }
};
