<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COA submit-and-lock workflow.
 *
 *   order_product   who submitted the COA, when, and the name printed as the
 *                   signature. Once submitted the certificate is locked.
 *   users           coa_approver: may approve or reject a request to edit a
 *                   submitted COA (QC HOD). Set by superadmin only.
 *   coa_edit_requests  one row per request, kept after it is decided so the
 *                   history of every unlock survives.
 *
 * Guarded with hasColumn / hasTable, so running it where the SQL was already
 * applied by hand in phpMyAdmin changes nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_product', function (Blueprint $table) {
            if (!Schema::hasColumn('order_product', 'coa_submitted_by')) {
                $table->unsignedBigInteger('coa_submitted_by')->nullable()->after('coa_updated_at');
            }
            if (!Schema::hasColumn('order_product', 'coa_submitted_at')) {
                $table->timestamp('coa_submitted_at')->nullable()->after('coa_submitted_by');
            }
            if (!Schema::hasColumn('order_product', 'coa_signatory_name')) {
                $table->string('coa_signatory_name', 150)->nullable()->after('coa_submitted_at');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'coa_approver')) {
                $table->boolean('coa_approver')->default(false)->after('department');
            }
        });

        if (!Schema::hasTable('coa_edit_requests')) {
            Schema::create('coa_edit_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('requested_by');
                $table->text('reason');
                $table->string('status', 20)->default('pending');   // pending | approved | rejected
                $table->unsignedBigInteger('decided_by')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'product_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coa_edit_requests');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'coa_approver')) {
                $table->dropColumn('coa_approver');
            }
        });

        Schema::table('order_product', function (Blueprint $table) {
            foreach (['coa_submitted_by', 'coa_submitted_at', 'coa_signatory_name'] as $column) {
                if (Schema::hasColumn('order_product', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
