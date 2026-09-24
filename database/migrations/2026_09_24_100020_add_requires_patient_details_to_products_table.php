<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catch-up migration: the "Patient test product" flag on products.
     * Added directly on live, never migrated.
     * Guarded with hasColumn so running it on live changes nothing.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'requires_patient_details')) {
                $table->boolean('requires_patient_details')->default(false)->after('usage_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'requires_patient_details')) {
                $table->dropColumn('requires_patient_details');
            }
        });
    }
};
