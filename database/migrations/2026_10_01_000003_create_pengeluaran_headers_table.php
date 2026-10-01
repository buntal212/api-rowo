<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluaran_headers', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_pengeluaran');
            $table->decimal('total_nominal', 15, 2)->default(0);
            $table->timestamps();
            $table->index('tanggal_pengeluaran');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_headers');
    }
};
