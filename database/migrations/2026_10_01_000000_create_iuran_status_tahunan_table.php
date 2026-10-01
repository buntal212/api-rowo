<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iuran_status_tahunan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penduduk_id')->constrained('penduduks')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->decimal('target_iuran', 15, 2)->default(0);
            $table->string('status', 20)->default('belum_bayar');
            $table->timestamps();

            $table->unique(['penduduk_id', 'tahun']);
            $table->index(['tahun', 'status']);
        });

        $targetIuran = (float) (DB::table('miuran')->value('nominaliuran') ?? 0);
        $tahunLaporan = DB::table('iurans')->distinct()->pluck('tahun')->map(fn ($tahun) => (int) $tahun)->all();
        $tahunLaporan[] = (int) now()->year;
        $tahunLaporan = array_values(array_unique($tahunLaporan));
        $pendudukIds = DB::table('penduduks')->where('flaging', true)->pluck('id');
        $waktuSekarang = now();

        foreach ($tahunLaporan as $tahun) {
            $totalPerWarga = DB::table('iurans')
                ->where('tahun', $tahun)
                ->select('penduduk_id', DB::raw('COALESCE(SUM(nominal), 0) as total_iuran'))
                ->groupBy('penduduk_id')
                ->pluck('total_iuran', 'penduduk_id');

            $baris = $pendudukIds->map(function ($pendudukId) use ($tahun, $targetIuran, $totalPerWarga, $waktuSekarang) {
                $totalIuran = (float) ($totalPerWarga[$pendudukId] ?? 0);
                $status = $totalIuran <= 0
                    ? 'belum_bayar'
                    : ($targetIuran > 0 && $totalIuran >= $targetIuran ? 'lunas' : 'belum_lunas');

                return [
                    'penduduk_id' => $pendudukId,
                    'tahun' => $tahun,
                    'target_iuran' => $targetIuran,
                    'status' => $status,
                    'created_at' => $waktuSekarang,
                    'updated_at' => $waktuSekarang,
                ];
            });

            foreach ($baris->chunk(500) as $chunk) {
                DB::table('iuran_status_tahunan')->insert($chunk->all());
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('iuran_status_tahunan');
    }
};
