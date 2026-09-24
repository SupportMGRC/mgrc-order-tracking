<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Receiving department for pickups (Cell Lab or Genomics).
     *
     * products.receiving_department: set on pickup items in Product. NULL for
     * order products.
     * pickups.receiving_department: copied from the items when the pickup is
     * created, so a later change on the product does not move old pickups.
     *
     * Live was changed by hand in phpMyAdmin with the same statements; this
     * file keeps the repo in step with it.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('receiving_department', 100)->nullable()->after('is_active');
        });

        Schema::table('pickups', function (Blueprint $table) {
            $table->string('receiving_department', 100)->nullable()->after('status');
            $table->index(['status', 'receiving_department']);
        });

        // Existing pickups: take the department from their items (once the
        // pickup products have a department set), otherwise Cell Lab, which
        // received every pickup before this change.
        DB::statement("
            UPDATE pickups p
            SET p.receiving_department = (
                SELECT MIN(pr.receiving_department)
                FROM pickup_items pi
                JOIN products pr ON pr.id = pi.product_id
                WHERE pi.pickup_id = p.id
            )
        ");
        DB::table('pickups')->whereNull('receiving_department')->update(['receiving_department' => 'Cell Lab']);
    }

    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->dropIndex(['status', 'receiving_department']);
            $table->dropColumn('receiving_department');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('receiving_department');
        });
    }
};
