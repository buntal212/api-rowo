<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Iuran;
use App\Models\Miuran;
use App\Models\Penduduk;
use Illuminate\Http\Request;

class LaporanIuranWargaController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'search' => ['nullable', 'string', 'max:150'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $perPage = $data['per_page'] ?? 20;

        $targetIuran = (float) (Miuran::query()->value('nominaliuran') ?? 0);

        $query = Penduduk::query()
            ->leftJoin('iurans', function ($join) use ($data) {
                $join->on('iurans.penduduk_id', '=', 'penduduks.id')
                    ->where('iurans.tahun', $data['tahun']);
            })
            ->where('penduduks.flaging', true)
            ->when($data['search'] ?? null, function ($builder, $search) {
                $builder->where('penduduks.nama', 'like', "%{$search}%");
            })
            ->groupBy('penduduks.id', 'penduduks.nama');

        $ringkasan = (clone $query)
            ->selectRaw('COUNT(penduduks.id) as total_warga')
            ->selectRaw('COALESCE(SUM(iurans.nominal), 0) as total_iuran')
            ->get()
            ->reduce(function (array $total, $warga) {
                $total['total_warga'] += 1;
                $total['total_iuran'] += (float) $warga->total_iuran;

                return $total;
            }, [
                'total_warga' => 0,
                'total_iuran' => 0,
            ]);

        $warga = $query
            ->select([
                'penduduks.id',
                'penduduks.nama',
            ])
            ->selectRaw('COUNT(iurans.id) as total_transaksi')
            ->selectRaw('COALESCE(SUM(iurans.nominal), 0) as total_iuran')
            ->orderBy('penduduks.nama')
            ->simplePaginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Laporan iuran warga berhasil diambil.',
            'ringkasan' => [
                'total_warga' => $ringkasan['total_warga'],
                'total_iuran' => $ringkasan['total_iuran'],
                'target_iuran' => $targetIuran,
            ],
            'data' => $warga,
        ]);
    }

    public function detail(Request $request)
    {
        $data = $request->validate([
            'penduduk_id' => ['required', 'integer', 'exists:penduduks,id'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ], [
            'penduduk_id.required' => 'Warga wajib dipilih.',
            'penduduk_id.exists' => 'Data warga tidak ditemukan.',
        ]);

        $warga = Penduduk::query()
            ->whereKey($data['penduduk_id'])
            ->where('flaging', true)
            ->first();

        if (!$warga) {
            return response()->json([
                'status' => false,
                'message' => 'Data warga tidak ditemukan atau sudah tidak aktif.',
            ], 422);
        }

        $targetIuran = (float) (Miuran::query()->value('nominaliuran') ?? 0);
        $perPage = $data['per_page'] ?? 20;

        $query = Iuran::query()
            ->where('penduduk_id', $warga->id)
            ->where('tahun', $data['tahun']);

        $totalIuran = (float) (clone $query)->sum('nominal');

        $transaksi = $query
            ->select(['id', 'nominal', 'tanggal_bayar', 'keterangan'])
            ->orderByDesc('tanggal_bayar')
            ->orderByDesc('id')
            ->simplePaginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Detail iuran warga berhasil diambil.',
            'warga' => [
                'id' => $warga->id,
                'nama' => $warga->nama,
                'total_iuran' => $totalIuran,
                'target_iuran' => $targetIuran,
            ],
            'data' => $transaksi,
        ]);
    }
}
