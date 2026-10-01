<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengeluaranHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengeluaranController extends Controller
{
    public function getList(Request $request)
    {
        $data = $request->validate([
            'tanggal_dari' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tanggal_dari'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $sekarang = now();
        $tanggalDari = $data['tanggal_dari'] ?? $sekarang->copy()->startOfMonth()->toDateString();
        $tanggalSampai = $data['tanggal_sampai'] ?? $sekarang->toDateString();

        $pengeluaran = PengeluaranHeader::query()
            ->whereBetween('tanggal_pengeluaran', [$tanggalDari, $tanggalSampai])
            ->with('rincis:id,pengeluaran_header_id,harga_satuan,jumlah,nominal,keterangan')
            ->orderByDesc('tanggal_pengeluaran')
            ->orderByDesc('id')
            ->simplePaginate($data['per_page'] ?? 20);

        return response()->json([
            'status' => true,
            'message' => 'Data pengeluaran berhasil diambil.',
            'data' => $pengeluaran,
        ]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'kegiatan' => ['required', 'string', 'max:255'],
            'rincian' => ['required', 'array', 'min:1'],
            'rincian.*.harga_satuan' => ['required', 'numeric', 'gt:0'],
            'rincian.*.jumlah' => ['required', 'integer', 'min:1'],
            'rincian.*.keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $pengeluaran = DB::transaction(function () use ($data) {
            $rincian = collect($data['rincian'])->map(function ($item) {
                $hargaSatuan = (float) $item['harga_satuan'];
                $jumlah = (int) $item['jumlah'];

                return [
                    ...$item,
                    'nominal' => $hargaSatuan * $jumlah,
                ];
            });
            $totalNominal = $rincian->sum('nominal');

            $header = PengeluaranHeader::create([
                'tanggal_pengeluaran' => date('Y-m-d'),
                'kegiatan' => $data['kegiatan'],
                'total_nominal' => $totalNominal,
            ]);

            $header->rincis()->createMany($rincian);

            return $header->load('rincis');
        });

        return response()->json([
            'status' => true,
            'message' => 'Pengeluaran berhasil disimpan.',
            'data' => $pengeluaran,
        ]);
    }
}
