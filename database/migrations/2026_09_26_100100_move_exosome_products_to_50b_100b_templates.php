<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * General Exosome, Exosome Wellness and Exosome Cardio now have a 50B and a
 * 100B certificate (QC templates REV7 / REV1, 10 Aug 2026). Products pointing
 * at the retired REV6 templates move to the 100B member, which is the default
 * QC sees first and can switch to 50B.
 *
 * Order lines are left alone: a COA already submitted on REV6 keeps it, and
 * CoaTemplateService moves any unsubmitted line on by itself.
 */
return new class extends Migration
{
    private const MOVES = [
        'exo_general'  => 'exo_general_100b',
        'exo_wellness' => 'exo_wellness_100b',
        'exo_cardio'   => 'exo_cardio_100b',
    ];

    public function up(): void
    {
        foreach (self::MOVES as $old => $new) {
            DB::table('products')->where('coa_template', $old)->update(['coa_template' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::MOVES as $old => $new) {
            DB::table('products')
                ->whereIn('coa_template', [$new, str_replace('_100b', '_50b', $new)])
                ->update(['coa_template' => $old]);
        }
    }
};
