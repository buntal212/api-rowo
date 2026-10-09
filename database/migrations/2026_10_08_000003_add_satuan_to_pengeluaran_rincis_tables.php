<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengeluaran_rincis', function (Blueprint $table) {
            $table->string('satuan', 50)->default('Unit')->after('jumlah');
        });

        Schema::table('pengeluaran_masjid_rincis', function (Blueprint $table) {
            $table->string('satuan', 50)->default('Unit')->after('jumlah');
        });
    }

    public function down(): void
    {
        Schema::table('pengeluaran_masjid_rincis', function (Blueprint $table) {
            $table->dropColumn('satuan');
        });

        Schema::table('pengeluaran_rincis', function (Blueprint $table) {
            $table->dropColumn('satuan');
        });
    }
};
