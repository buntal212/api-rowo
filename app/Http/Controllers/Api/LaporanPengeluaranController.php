<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengeluaranHeader;
use Illuminate\Http\Request;

class LaporanPengeluaranController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'tanggal_dari' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tanggal_dari'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $sekarang = now();
        $tanggalDari = $data['tanggal_dari'] ?? $sekarang->copy()->startOfYear()->toDateString();
        $tanggalSampai = $data['tanggal_sampai'] ?? $sekarang->toDateString();

        $query = PengeluaranHeader::query()
            ->whereBetween('tanggal_pengeluaran', [$tanggalDari, $tanggalSampai]);

        $ringkasan = (clone $query)
            ->selectRaw('COUNT(*) as total_transaksi, COALESCE(SUM(total_nominal), 0) as total_pengeluaran')
            ->first();

        $pengeluaran = $query
            ->with('rincis:id,pengeluaran_header_id,harga_satuan,jumlah,nominal,keterangan')
            ->orderByDesc('tanggal_pengeluaran')
            ->orderByDesc('id')
            ->simplePaginate($data['per_page'] ?? 100);

        return response()->json([
            'status' => true,
            'message' => 'Laporan pengeluaran berhasil diambil.',
            'ringkasan' => [
                'tanggal_dari' => $tanggalDari,
                'tanggal_sampai' => $tanggalSampai,
                'total_transaksi' => (int) $ringkasan->total_transaksi,
                'total_pengeluaran' => (float) $ringkasan->total_pengeluaran,
            ],
            'data' => $pengeluaran,
        ]);
    }
}
