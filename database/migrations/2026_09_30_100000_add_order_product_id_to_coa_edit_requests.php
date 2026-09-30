<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * COA edit requests belong to an order line, not a product.
 *
 * The same product can be on one order more than once (two patients, or a
 * 100B and a 50B General Exosome), and each line has its own COA. Requests
 * were stored by order_id + product_id, which cannot tell those lines apart.
 *
 * Existing rows are pointed at the first line of that product on the order,
 * which is the line the old code always acted on.
 *
 * Guarded with hasColumn, so running it where the SQL was already applied by
 * hand in phpMyAdmin changes nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('coa_edit_requests', 'order_product_id')) {
            Schema::table('coa_edit_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('order_product_id')->nullable()->after('product_id');
                $table->index(['order_product_id', 'status']);
            });
        }

        DB::statement('
            UPDATE coa_edit_requests r
            SET r.order_product_id = (
                SELECT MIN(op.id)
                FROM order_product op
                WHERE op.order_id = r.order_id
                  AND op.product_id = r.product_id
            )
            WHERE r.order_product_id IS NULL
        ');
    }

    public function down(): void
    {
        if (Schema::hasColumn('coa_edit_requests', 'order_product_id')) {
            Schema::table('coa_edit_requests', function (Blueprint $table) {
                $table->dropIndex(['order_product_id', 'status']);
                $table->dropColumn('order_product_id');
            });
        }
    }
};
