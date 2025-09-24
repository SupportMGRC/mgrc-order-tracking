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
        // Remove fields from orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_method');
            $table->dropColumn('total_amount');
            $table->dropColumn('batch_number');
            $table->dropColumn('batch_quantity');
        });

        // Add fields to order_product table
        Schema::table('order_product', function (Blueprint $table) {
            $table->string('batch_number')->nullable();
            $table->integer('batch_quantity')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back fields to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('batch_number')->nullable();
            $table->integer('batch_quantity')->nullable();
        });

        // Remove fields from order_product table
        Schema::table('order_product', function (Blueprint $table) {
            $table->dropColumn('batch_number');
            $table->dropColumn('batch_quantity');
        });
    }
}; 