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
        Schema::table('iurans', function (Blueprint $table) {
            $table->dropUnique(
                'iurans_penduduk_id_bulan_minggu_tahun_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('iurans', function (Blueprint $table) {
            $table->unique(
                ['penduduk_id', 'bulan', 'minggu', 'tahun'],
                'iurans_penduduk_id_bulan_minggu_tahun_unique'
            );
        });
    }
};
