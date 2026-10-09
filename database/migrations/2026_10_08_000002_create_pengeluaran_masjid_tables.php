<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluaran_masjid_headers', function (Blueprint $table) {
            $table->id();
            $table->string('kegiatan', 255);
            $table->string('jenis_sumber_dana', 30);
            $table->date('tanggal_pengeluaran');
            $table->decimal('total_nominal', 15, 2)->default(0);
            $table->timestamps();

            $table->index('tanggal_pengeluaran');
            $table->index('jenis_sumber_dana');
        });

        Schema::create('pengeluaran_masjid_rincis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengeluaran_masjid_header_id')
                ->constrained('pengeluaran_masjid_headers')
                ->cascadeOnDelete();
            $table->decimal('harga_satuan', 15, 2);
            $table->unsignedInteger('jumlah');
            $table->decimal('nominal', 15, 2);
            $table->string('keterangan', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_masjid_rincis');
        Schema::dropIfExists('pengeluaran_masjid_headers');
    }
};
