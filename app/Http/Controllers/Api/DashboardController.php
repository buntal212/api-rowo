<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Iuran;
use App\Models\PengeluaranHeader;
use App\Models\Saldo;

class DashboardController extends Controller
{
    public function saldoRukem()
    {
        $sekarang = now();

        // Ambil bulan sebelumnya
        $bulanLalu = $sekarang->copy()->subMonth();

        // Saldo akhir bulan sebelumnya
        $saldoAkhirBulanLalu = (float) Saldo::query()
            ->where('pemilik', 'RUKEM')
            ->where('bulan', $bulanLalu->month)
            ->where('tahun', $bulanLalu->year)
            ->sum('nominal');

        // Iuran bulan berjalan
        $iuranBulanBerjalan = (float) Iuran::query()
            ->whereMonth('tanggal_bayar', $sekarang->month)
            ->whereYear('tanggal_bayar', $sekarang->year)
            ->sum('nominal');

        // Pengeluaran bulan berjalan
        $pengeluaranBulanBerjalan = (float) PengeluaranHeader::query()
            ->where('jenis_transaksi', 'RUKEM')
            ->whereMonth('tanggal_pengeluaran', $sekarang->month)
            ->whereYear('tanggal_pengeluaran', $sekarang->year)
            ->sum('total_nominal');

        // Saldo RUKEM saat ini
        $saldoRukem = $saldoAkhirBulanLalu
            + $iuranBulanBerjalan
            - $pengeluaranBulanBerjalan;

        return response()->json([
            'status' => true,
            'message' => 'Saldo RUKEM berhasil diambil.',
            'data' => [
                'bulan' => $sekarang->month,
                'tahun' => $sekarang->year,

                'saldo_akhir_bulan_lalu' => $saldoAkhirBulanLalu,
                'iuran_bulan_berjalan' => $iuranBulanBerjalan,
                'pengeluaran_bulan_berjalan' => $pengeluaranBulanBerjalan,

                'saldo_rukem' => $saldoRukem,
            ],
        ]);
    }
}
