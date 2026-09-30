<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COA Return to QC, HOD reopen, and upload as a backup.
 *
 *   order_product.coa_upload_mode   the COA approver (QC HOD) switched this
 *                  line from the COA template to an uploaded COA because the
 *                  COA can't be prepared in TRACOM.
 *   coa_edit_requests.type          request | return | reopen | return_upload.
 *                  Existing rows are requests.
 *   coa_edit_requests.previous_*    who had submitted (or uploaded) the COA
 *                  before it was unlocked, since the line's own columns are
 *                  cleared.
 *
 * Guarded with hasColumn, so running it where the SQL was already applied by
 * hand in phpMyAdmin changes nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_product', function (Blueprint $table) {
            if (!Schema::hasColumn('order_product', 'coa_upload_mode')) {
                $table->boolean('coa_upload_mode')->default(false)->after('coa_signatory_name');
            }
        });

        Schema::table('coa_edit_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('coa_edit_requests', 'type')) {
                $table->string('type', 20)->default('request')->after('order_product_id');
            }
            if (!Schema::hasColumn('coa_edit_requests', 'previous_signatory_name')) {
                $table->string('previous_signatory_name', 150)->nullable()->after('decided_at');
            }
            if (!Schema::hasColumn('coa_edit_requests', 'previous_submitted_at')) {
                $table->timestamp('previous_submitted_at')->nullable()->after('previous_signatory_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coa_edit_requests', function (Blueprint $table) {
            foreach (['previous_submitted_at', 'previous_signatory_name', 'type'] as $column) {
                if (Schema::hasColumn('coa_edit_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('order_product', function (Blueprint $table) {
            if (Schema::hasColumn('order_product', 'coa_upload_mode')) {
                $table->dropColumn('coa_upload_mode');
            }
        });
    }
};
