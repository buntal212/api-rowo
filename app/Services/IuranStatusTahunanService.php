<?php

namespace App\Services;

use App\Models\Iuran;
use App\Models\IuranStatusTahunan;
use App\Models\Miuran;
use App\Models\Penduduk;

class IuranStatusTahunanService
{
    public function pastikanUntukWarga(int $pendudukId, int $tahun, ?float $targetIuran = null): IuranStatusTahunan
    {
        $targetIuran ??= (float) (Miuran::query()->value('nominaliuran') ?? 0);

        return IuranStatusTahunan::query()->firstOrCreate(
            [
                'penduduk_id' => $pendudukId,
                'tahun' => $tahun,
            ],
            [
                'target_iuran' => $targetIuran,
                'status' => 'belum_bayar',
            ]
        );
    }

    public function perbaruiStatus(int $pendudukId, int $tahun, ?float $targetIuran = null): IuranStatusTahunan
    {
        $rekap = $this->pastikanUntukWarga($pendudukId, $tahun, $targetIuran);

        $totalIuran = (float) Iuran::query()
            ->where('penduduk_id', $pendudukId)
            ->where('tahun', $tahun)
            ->sum('nominal');

        $status = $this->tentukanStatus($totalIuran, (float) $rekap->target_iuran);

        if ($rekap->status !== $status) {
            $rekap->update(['status' => $status]);
        }

        return $rekap;
    }

    public function pastikanUntukTahun(int $tahun, ?float $targetIuran = null): void
    {
        $targetIuran ??= (float) (Miuran::query()->value('nominaliuran') ?? 0);

        Penduduk::query()
            ->where('flaging', true)
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($warga) use ($tahun, $targetIuran) {
                foreach ($warga as $penduduk) {
                    $this->perbaruiStatus($penduduk->id, $tahun, $targetIuran);
                }
            });
    }

    private function tentukanStatus(float $totalIuran, float $targetIuran): string
    {
        if ($totalIuran <= 0) {
            return 'belum_bayar';
        }

        return $targetIuran > 0 && $totalIuran >= $targetIuran
            ? 'lunas'
            : 'belum_lunas';
    }
}
