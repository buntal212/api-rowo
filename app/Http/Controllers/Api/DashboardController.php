<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Iuran;
use App\Models\Saldo;

class DashboardController extends Controller
{
    public function saldoRukem()
    {
        $sekarang = now();

        $saldoBulanBerjalan = (float) Saldo::query()
            ->where('pemilik', 'RUKEM')
            ->where('bulan', $sekarang->month)
            ->where('tahun', $sekarang->year)
            ->sum('nominal');

        $iuranBulanBerjalan = (float) Iuran::query()
            ->whereMonth('tanggal_bayar', $sekarang->month)
            ->whereYear('tanggal_bayar', $sekarang->year)
            ->sum('nominal');

        return response()->json([
            'status' => true,
            'message' => 'Saldo RUKEM berhasil diambil.',
            'data' => [
                'bulan' => $sekarang->month,
                'tahun' => $sekarang->year,
                'saldo_bulan_berjalan' => $saldoBulanBerjalan,
                'iuran_bulan_berjalan' => $iuranBulanBerjalan,
                'saldo_rukem' => $saldoBulanBerjalan + $iuranBulanBerjalan,
            ],
        ]);
    }
}
