<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Immunophenotyping results for Table 1 on page 2 of the MSC certificates.
     *
     * Four cells: the three positive markers and the pooled negative marker
     * figure. Stored as strings, not decimals, because QC types what the flow
     * cytometry report shows — "98.88", "> 95", "N/A" — and the certificate
     * prints that text verbatim. Rounding it into a numeric column would
     * change what a signed certificate says.
     *
     * Only the MSC P2 and MSC P3 templates expose these; NK, NKT, the exosome
     * range and Secretome have no such table.
     */
    public function up(): void
    {
        Schema::table('order_product', function (Blueprint $table) {
            $table->string('coa_immuno_cd73', 32)->nullable()->after('coa_signature_date');
            $table->string('coa_immuno_cd90', 32)->nullable()->after('coa_immuno_cd73');
            $table->string('coa_immuno_cd105', 32)->nullable()->after('coa_immuno_cd90');
            $table->string('coa_immuno_negative', 32)->nullable()->after('coa_immuno_cd105');
        });
    }

    public function down(): void
    {
        Schema::table('order_product', function (Blueprint $table) {
            $table->dropColumn([
                'coa_immuno_cd73',
                'coa_immuno_cd90',
                'coa_immuno_cd105',
                'coa_immuno_negative',
            ]);
        });
    }
};
