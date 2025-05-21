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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('user_id'); // The staff/admin/user who created the order
            $table->string('order_placed_by')->nullable(); // Person who placed the order
            $table->string('delivered_by')->nullable(); // Person who delivered the order
            $table->date('order_date');
            $table->time('order_time')->nullable();
            $table->enum('status', ['new', 'preparing', 'ready', 'delivered'])->default('new');
            $table->date('delivery_date')->nullable();
            $table->time('delivery_time')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        
            // Foreign keys
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
