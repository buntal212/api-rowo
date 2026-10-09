<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengeluaranMasjidHeader;
use Illuminate\Http\Request;

class LaporanPengeluaranMasjidController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'tanggal_dari' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tanggal_dari'],
            'jenis_sumber_dana' => ['nullable', 'string', 'in:KOTAK_AMAL,PEMBANGUNAN_MASJID'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $sekarang = now();
        $tanggalDari = $data['tanggal_dari'] ?? $sekarang->copy()->startOfYear()->toDateString();
        $tanggalSampai = $data['tanggal_sampai'] ?? $sekarang->toDateString();

        $query = PengeluaranMasjidHeader::query()
            ->whereBetween('tanggal_pengeluaran', [$tanggalDari, $tanggalSampai]);

        if (!empty($data['jenis_sumber_dana'])) {
            $query->where('jenis_sumber_dana', $data['jenis_sumber_dana']);
        }

        $ringkasan = (clone $query)
            ->selectRaw('COUNT(*) as total_transaksi, COALESCE(SUM(total_nominal), 0) as total_pengeluaran')
            ->first();

        $pengeluaran = $query
            ->with('rincis:id,pengeluaran_masjid_header_id,harga_satuan,jumlah,satuan,nominal,keterangan')
            ->orderByDesc('tanggal_pengeluaran')
            ->orderByDesc('id')
            ->simplePaginate($data['per_page'] ?? 20);

        return response()->json([
            'status' => true,
            'message' => 'Laporan pengeluaran masjid berhasil diambil.',
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
