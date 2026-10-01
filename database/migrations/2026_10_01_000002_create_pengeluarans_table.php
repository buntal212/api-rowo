<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluarans', function (Blueprint $table) {
            $table->id();
            $table->string('pemilik', 100);
            $table->decimal('nominal', 15, 2);
            $table->date('tanggal_pengeluaran');
            $table->string('keterangan', 500)->nullable();
            $table->timestamps();

            $table->index(['tanggal_pengeluaran', 'pemilik']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluarans');
    }
};
