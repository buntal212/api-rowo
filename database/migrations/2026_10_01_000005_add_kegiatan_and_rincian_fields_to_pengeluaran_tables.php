<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengeluaran_headers', function (Blueprint $table) {
            $table->string('kegiatan', 255)->nullable()->after('id');
        });

        Schema::table('pengeluaran_rincis', function (Blueprint $table) {
            $table->decimal('harga_satuan', 15, 2)->default(0)->after('pengeluaran_header_id');
            $table->unsignedInteger('jumlah')->default(1)->after('harga_satuan');
        });
    }

    public function down(): void
    {
        Schema::table('pengeluaran_rincis', function (Blueprint $table) {
            $table->dropColumn(['harga_satuan', 'jumlah']);
        });

        Schema::table('pengeluaran_headers', function (Blueprint $table) {
            $table->dropColumn('kegiatan');
        });
    }
};
