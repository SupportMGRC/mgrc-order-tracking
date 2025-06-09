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
            $table->string('collected_by')->nullable()->after('delivered_by');
            $table->text('signature_data')->nullable()->after('collected_by'); // Store signature as base64 image
            $table->timestamp('signature_date')->nullable()->after('signature_data');
            $table->string('signature_ip')->nullable()->after('signature_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['collected_by', 'signature_data', 'signature_date', 'signature_ip']);
        });
    }
}; 