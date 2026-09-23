<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iurans', function (Blueprint $table) {
            // MySQL memakai indeks unik lama untuk foreign key penduduk_id.
            // Sediakan indeks pengganti sebelum indeks unik tersebut dilepas.
            $table->index('penduduk_id');
            $table->dropUnique(['penduduk_id', 'bulan', 'tahun']);
            $table->unsignedTinyInteger('minggu')->default(1)->after('bulan');
            $table->unique(['penduduk_id', 'bulan', 'minggu', 'tahun']);
            $table->index(['tahun', 'bulan', 'minggu']);
        });
    }

    public function down(): void
    {
        Schema::table('iurans', function (Blueprint $table) {
            $table->dropUnique(['penduduk_id', 'bulan', 'minggu', 'tahun']);
            $table->dropIndex(['tahun', 'bulan', 'minggu']);
            $table->dropColumn('minggu');
            $table->unique(['penduduk_id', 'bulan', 'tahun']);
            $table->dropIndex(['penduduk_id']);
        });
    }
};
