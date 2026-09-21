<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pickup module, phase 2: requests and their items.
     * Status columns for On the Way / Picked Up / Received details are added
     * in the phase 3 migration.
     */
    public function up(): void
    {
        Schema::create('pickups', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 20)->nullable()->unique();
            // Cascade matches orders.customer_id. Customer delete is admin-only.
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('requested_by')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone', 20);
            $table->text('pickup_address');
            $table->date('pickup_date');
            $table->time('pickup_time');
            $table->boolean('time_sensitive')->default(false);
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->timestamps();

            $table->index('pickup_date');
        });

        Schema::create('pickup_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_id')->constrained()->cascadeOnDelete();
            // Restrict: a product used on a pickup cannot be deleted from under it.
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_items');
        Schema::dropIfExists('pickups');
    }
};
