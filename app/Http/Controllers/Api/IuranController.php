<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Iuran;
use App\Models\Penduduk;
use App\Services\IuranStatusTahunanService;
use Illuminate\Http\Request;

class IuranController extends Controller
{
    public function __construct(private IuranStatusTahunanService $iuranStatusTahunanService)
    {
    }

    public function getList(Request $request)
    {
        $data = $request->validate([
            'tanggal_dari' => ['required', 'date_format:Y-m-d'],
            'tanggal_sampai' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_dari'],
            'search' => ['nullable', 'string', 'max:150'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $perPage = $data['per_page'] ?? 20;

        $iuran = Iuran::query()
            ->join('penduduks', 'penduduks.id', '=', 'iurans.penduduk_id')
            ->where('penduduks.flaging', true)
            ->whereBetween('iurans.tanggal_bayar', [$data['tanggal_dari'], $data['tanggal_sampai']])
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where('penduduks.nama', 'like', "%{$search}%");
            })
            ->select([
                'iurans.id as iuran_id',
                'penduduks.id',
                'penduduks.nama',
                'iurans.nominal',
                'iurans.tanggal_bayar',
                'iurans.keterangan',
            ])
            ->orderByDesc('iurans.tanggal_bayar')
            ->orderByDesc('iurans.id')
            ->simplePaginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Data iuran berhasil diambil.',
            'data' => $iuran,
        ]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:iurans,id'],
            'penduduk_id' => ['required', 'integer', 'exists:penduduks,id'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'nominal' => ['required', 'numeric', 'gt:0'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ], [
            'id.integer' => 'ID iuran tidak valid.',
            'id.exists' => 'Data iuran tidak ditemukan.',
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

        $payload = [
            'penduduk_id' => $data['penduduk_id'],
            'bulan' => $data['bulan'],
            'tahun' => $data['tahun'],
            'nominal' => $data['nominal'],
            'keterangan' => $data['keterangan'] ?? null,
        ];

        if (!empty($data['id'])) {
            $iuran = Iuran::query()
                ->whereKey($data['id'])
                ->where('penduduk_id', $data['penduduk_id'])
                ->first();

            if (!$iuran) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data iuran tidak sesuai dengan warga yang dipilih.',
                ], 422);
            }

            $tahunSebelumnya = $iuran->tahun;
            $iuran->update($payload);

            $this->iuranStatusTahunanService->perbaruiStatus(
                $data['penduduk_id'],
                $data['tahun']
            );

            if ($tahunSebelumnya !== $data['tahun']) {
                $this->iuranStatusTahunanService->perbaruiStatus(
                    $data['penduduk_id'],
                    $tahunSebelumnya
                );
            }

            $message = 'Pembayaran iuran berhasil diperbarui.';
        } else {

            $rekap = $this->iuranStatusTahunanService->pastikanUntukWarga(
                $data['penduduk_id'],
                $data['tahun']
            );
            $batasIuran = (float) $rekap->target_iuran;

            if ($batasIuran <= 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Nominal iuran belum diatur.',
                ], 422);
            }

            // Total pembayaran warga yang sudah masuk
            $totIuran = Iuran::query()
                ->where('penduduk_id', $data['penduduk_id'])
                ->where('tahun', $data['tahun'])
                ->sum('nominal');

            // Total setelah ditambah pembayaran baru
            $totalSetelahBayar = $totIuran + $payload['nominal'];

            if ($totalSetelahBayar > $batasIuran) {
                return response()->json([
                    'status' => false,
                    'message' => 'Pembayaran melampaui batas pembayaran.',
                ], 422);
            }

            $iuran = Iuran::create([
                ...$payload,
                'tanggal_bayar' => date('Y-m-d'),
                'minggu' => 1,
            ]);

            $this->iuranStatusTahunanService->perbaruiStatus(
                $data['penduduk_id'],
                $data['tahun']
            );

            $message = 'Pembayaran iuran berhasil ditambahkan.';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $iuran,
        ]);
    }
}
