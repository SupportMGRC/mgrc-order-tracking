<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catch-up migration: patient IC and the COA document upload fields on
     * order items. Added directly on live, never migrated.
     * Guarded with hasColumn so running it on live changes nothing.
     */
    public function up(): void
    {
        Schema::table('order_product', function (Blueprint $table) {
            if (!Schema::hasColumn('order_product', 'patient_ic')) {
                $table->string('patient_ic', 30)->nullable()->after('patient_name');
            }
            if (!Schema::hasColumn('order_product', 'coa_document')) {
                $table->string('coa_document')->nullable()->after('coa_morphology_image');
            }
            if (!Schema::hasColumn('order_product', 'coa_document_uploaded_by')) {
                // No foreign key on live, so none is added here either.
                $table->unsignedBigInteger('coa_document_uploaded_by')->nullable()->after('coa_document');
            }
            if (!Schema::hasColumn('order_product', 'coa_document_uploaded_at')) {
                $table->timestamp('coa_document_uploaded_at')->nullable()->after('coa_document_uploaded_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_product', function (Blueprint $table) {
            foreach (['patient_ic', 'coa_document', 'coa_document_uploaded_by', 'coa_document_uploaded_at'] as $column) {
                if (Schema::hasColumn('order_product', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
