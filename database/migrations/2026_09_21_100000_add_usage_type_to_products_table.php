<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records what a product is used for.
     *
     *   'order'  -> appears on New Order (default; every existing product)
     *   'pickup' -> appears on New Pickup only (e.g. Blood Tube)
     *
     * Named usage_type because USAGE is a reserved word in MySQL.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('usage_type', 16)->default('order')->after('coa_template');
            $table->index('usage_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['usage_type']);
            $table->dropColumn('usage_type');
        });
    }
};
