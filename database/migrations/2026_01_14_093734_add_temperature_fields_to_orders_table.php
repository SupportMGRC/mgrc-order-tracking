<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('temp_before_delivery')->nullable()->after('delivery_photos');
            $table->string('temp_after_delivery')->nullable()->after('temp_before_delivery');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('temp_before_delivery');
            $table->dropColumn('temp_after_delivery');
        });
    }
};
