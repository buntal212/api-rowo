<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemasukan_kotak_masjid', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_masuk');
            $table->decimal('nominal', 15, 2);
            $table->string('keterangan', 500)->nullable();
            $table->timestamps();

            $table->index('tanggal_masuk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemasukan_kotak_masjid');
    }
};
