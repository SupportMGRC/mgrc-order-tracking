<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn('criticality');
            $table->dropIndex('equipment_criticality_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->enum('criticality', ['Critical', 'Non-critical'])->default('Non-critical')->after('pic_user_id');
            $table->index('criticality');
        });
    }
}; 