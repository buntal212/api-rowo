<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemasukan_kotak_masjid', function (Blueprint $table) {
            $table->string('jenis_pemasukan', 30)
                ->default('KOTAK_AMAL')
                ->after('tanggal_masuk');
        });
    }

    public function down(): void
    {
        Schema::table('pemasukan_kotak_masjid', function (Blueprint $table) {
            $table->dropColumn('jenis_pemasukan');
        });
    }
};
