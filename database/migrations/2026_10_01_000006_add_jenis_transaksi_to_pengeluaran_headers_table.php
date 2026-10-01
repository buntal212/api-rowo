<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengeluaran_headers', function (Blueprint $table) {
            $table->string('jenis_transaksi', 30)->default('RUKEM')->after('kegiatan');
            $table->index('jenis_transaksi');
        });
    }

    public function down(): void
    {
        Schema::table('pengeluaran_headers', function (Blueprint $table) {
            $table->dropIndex(['jenis_transaksi']);
            $table->dropColumn('jenis_transaksi');
        });
    }
};
