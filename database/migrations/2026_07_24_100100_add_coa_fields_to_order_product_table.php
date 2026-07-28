<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-order-line COA data.
     *
     * batch_number, patient_name, qc_document_number and prepared_by already
     * exist on this table, so only the fields the new templates introduce are
     * added here.
     *
     * Dates are stored as plain strings because the certificates print them in
     * human form ("10th April 2026", "June 2026") and some templates show only
     * month and year. Formatting is a presentation decision made by QC, not
     * something to normalise into a date column.
     */
    public function up(): void
    {
        Schema::table('order_product', function (Blueprint $table) {
            // Which template this line's COA was generated from. Copied from
            // products.coa_template when the COA is first opened, then frozen,
            // so re-pointing a product later cannot silently change a COA that
            // has already been issued to a client.
            $table->string('coa_template', 32)->nullable()->after('coa_required');

            $table->string('coa_number')->nullable()->after('coa_template');
            $table->string('coa_product_date')->nullable()->after('coa_number');
            $table->string('coa_mfg_date')->nullable()->after('coa_product_date');
            $table->string('coa_expiry_date')->nullable()->after('coa_mfg_date');
            $table->string('coa_viable_cell_count')->nullable()->after('coa_expiry_date');
            $table->string('coa_signature_date')->nullable()->after('coa_viable_cell_count');

            // Uploaded morphology-of-cells micrograph (page 2, cell templates).
            // Stores a path relative to the public disk.
            $table->string('coa_morphology_image')->nullable()->after('coa_signature_date');

            // Audit: who last saved this COA and when.
            $table->unsignedBigInteger('coa_updated_by')->nullable()->after('coa_morphology_image');
            $table->timestamp('coa_updated_at')->nullable()->after('coa_updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('order_product', function (Blueprint $table) {
            $table->dropColumn([
                'coa_template',
                'coa_number',
                'coa_product_date',
                'coa_mfg_date',
                'coa_expiry_date',
                'coa_viable_cell_count',
                'coa_signature_date',
                'coa_morphology_image',
                'coa_updated_by',
                'coa_updated_at',
            ]);
        });
    }
};
