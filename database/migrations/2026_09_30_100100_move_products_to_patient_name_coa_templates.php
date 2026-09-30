<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Secretome, the three Exosomes and MSC P3 now come with and without the
 * patient's name, like MSC P2. Secretome moves to REV3 and NK to REV4.
 *
 * Products move to the default member of their group, which is what QC sees
 * first on a new COA: "with patient name" (and 100B for the Exosomes). QC
 * can switch to the other versions from the COA editor.
 *
 * MSC P3 needs no move: its existing template is the with-name default.
 *
 * Order lines are left alone. A COA already submitted keeps its template, so
 * the signed certificate never changes; CoaTemplateService moves any
 * unsubmitted line on a retired template (Secretome REV2, NK REV3) by itself.
 */
return new class extends Migration
{
    private const MOVES = [
        'secretome'         => 'secretome_name',
        'nk'                => 'nk_r4',
        'exo_general_100b'  => 'exo_general_100b_name',
        'exo_general_50b'   => 'exo_general_100b_name',
        'exo_wellness_100b' => 'exo_wellness_100b_name',
        'exo_wellness_50b'  => 'exo_wellness_100b_name',
        'exo_cardio_100b'   => 'exo_cardio_100b_name',
        'exo_cardio_50b'    => 'exo_cardio_100b_name',
    ];

    public function up(): void
    {
        foreach (self::MOVES as $old => $new) {
            DB::table('products')->where('coa_template', $old)->update(['coa_template' => $new]);
        }
    }

    public function down(): void
    {
        DB::table('products')->where('coa_template', 'secretome_name')->update(['coa_template' => 'secretome']);
        DB::table('products')->where('coa_template', 'nk_r4')->update(['coa_template' => 'nk']);
        DB::table('products')->where('coa_template', 'exo_general_100b_name')->update(['coa_template' => 'exo_general_100b']);
        DB::table('products')->where('coa_template', 'exo_wellness_100b_name')->update(['coa_template' => 'exo_wellness_100b']);
        DB::table('products')->where('coa_template', 'exo_cardio_100b_name')->update(['coa_template' => 'exo_cardio_100b']);
    }
};
