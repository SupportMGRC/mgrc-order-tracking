<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records which COA template a product uses.
     *
     *   a template key ('msc_p2_name', 'nk', ...) -> open that template directly
     *   'none'                                    -> product has no COA; hide the button
     *   NULL                                      -> not configured yet; the editor
     *                                                falls back to asking the user
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('coa_template', 32)->nullable()->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('coa_template');
        });
    }
};
