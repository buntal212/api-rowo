<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluaran_rincis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengeluaran_header_id')->constrained('pengeluaran_headers')->cascadeOnDelete();
            $table->decimal('nominal', 15, 2);
            $table->string('keterangan', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_rincis');
    }
};
