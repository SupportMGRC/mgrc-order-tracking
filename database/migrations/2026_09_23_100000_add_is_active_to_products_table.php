<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Active / Inactive switch on products.
     *
     * Inactive means the product is no longer in use: it disappears from the
     * New Order and New Pickup dropdowns but stays in Product Management, so
     * past orders and pickups keep their items, batch numbers and COA data.
     * Every existing product starts as active.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('usage_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
};
