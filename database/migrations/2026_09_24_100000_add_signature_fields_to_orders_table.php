<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catch-up migration: self-collect signature fields.
     *
     * These columns were added straight to the live database and never had a
     * migration, so a database rebuilt from this repo came out missing them.
     * Guarded with hasColumn so running it on live changes nothing.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'collected_by')) {
                $table->string('collected_by')->nullable()->after('delivered_by');
            }
            if (!Schema::hasColumn('orders', 'signature_data')) {
                $table->text('signature_data')->nullable()->after('collected_by');
            }
            if (!Schema::hasColumn('orders', 'signature_date')) {
                $table->timestamp('signature_date')->nullable()->after('signature_data');
            }
            if (!Schema::hasColumn('orders', 'signature_ip')) {
                $table->string('signature_ip')->nullable()->after('signature_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['collected_by', 'signature_data', 'signature_date', 'signature_ip'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
