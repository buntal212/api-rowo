<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Iuran;
use App\Models\Miuran;
use App\Models\Penduduk;
use Illuminate\Http\Request;

class IuranController extends Controller
{
    public function getList(Request $request)
    {
        $data = $request->validate([
            'bulan' => ['required', 'integer', 'between:1,12'],
            'minggu' => ['required', 'integer', 'between:1,4'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'search' => ['nullable', 'string', 'max:150'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $nominalIuran = (float) (Miuran::first()?->nominaliuran ?? 0);
        $perPage = $data['per_page'] ?? 20;

        $iuran = Penduduk::query()
            ->leftJoin('iurans', function ($join) use ($data) {
                $join->on('iurans.penduduk_id', '=', 'penduduks.id')
                    ->where('iurans.bulan', $data['bulan'])
                    ->where('iurans.minggu', $data['minggu'])
                    ->where('iurans.tahun', $data['tahun']);
            })
            ->where('penduduks.flaging', true)
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where('penduduks.nama', 'like', "%{$search}%");
            })
            ->select([
                'penduduks.id',
                'penduduks.nama',
                'iurans.nominal',
                'iurans.tanggal_bayar',
                'iurans.keterangan',
            ])
            ->selectRaw("CASE WHEN iurans.id IS NULL THEN 'belum_bayar' ELSE 'lunas' END AS status")
            ->orderBy('penduduks.nama')
            ->paginate($perPage);

        $iuran->getCollection()->transform(function ($warga) use ($nominalIuran) {
            $warga->nominaliuran = (float) ($warga->nominal ?? $nominalIuran);

            return $warga;
        });

        return response()->json([
            'status' => true,
            'message' => 'Data iuran berhasil diambil.',
            'data' => $iuran,
        ]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'penduduk_id' => ['required', 'integer', 'exists:penduduks,id'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'minggu' => ['required', 'integer', 'between:1,4'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'nominal' => ['required', 'numeric', 'gt:0'],
            'tanggal_bayar' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $penduduk = Penduduk::query()
            ->whereKey($data['penduduk_id'])
            ->where('flaging', true)
            ->first();

        if (!$penduduk) {
            return response()->json([
                'status' => false,
                'message' => 'Data warga tidak ditemukan atau sudah tidak aktif.',
            ], 422);
        }

        $iuran = Iuran::updateOrCreate(
            [
                'penduduk_id' => $data['penduduk_id'],
                'bulan' => $data['bulan'],
                'minggu' => $data['minggu'],
                'tahun' => $data['tahun'],
            ],
            [
                'nominal' => $data['nominal'],
                'tanggal_bayar' => $data['tanggal_bayar'],
                'keterangan' => $data['keterangan'] ?? null,
            ],
        );

        return response()->json([
            'status' => true,
            'message' => 'Pembayaran iuran berhasil disimpan.',
            'data' => $iuran,
        ]);
    }
}
