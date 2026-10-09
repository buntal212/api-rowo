<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PemasukanKotakMasjid;
use Illuminate\Http\Request;

class PemasukanKotakMasjidController extends Controller
{
    private const JENIS_PEMASUKAN = ['KOTAK_AMAL', 'PEMBANGUNAN_MASJID'];

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

        $pemasukan = PemasukanKotakMasjid::query()
            ->whereBetween('tanggal_masuk', [$tanggalDari, $tanggalSampai])
            ->orderByDesc('tanggal_masuk')
            ->orderByDesc('id')
            ->simplePaginate($data['per_page'] ?? 20);

        return response()->json([
            'status' => true,
            'message' => 'Data uang masuk kotak masjid berhasil diambil.',
            'data' => $pemasukan,
        ]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'jenis_pemasukan' => ['required', 'string', 'in:' . implode(',', self::JENIS_PEMASUKAN)],
            'nominal' => ['required', 'numeric', 'gt:0'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ], [
            'jenis_pemasukan.required' => 'Jenis pemasukkan wajib dipilih.',
            'jenis_pemasukan.in' => 'Jenis pemasukkan tidak valid.',
        ]);

        $pemasukan = PemasukanKotakMasjid::create([
            ...$data,
            'tanggal_masuk' => date('Y-m-d'),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Uang masuk kotak masjid berhasil disimpan.',
            'data' => $pemasukan,
        ], 201);
    }

    public function hapus(PemasukanKotakMasjid $pemasukanKotakMasjid)
    {
        $sekarang = now();
        $tanggalMasuk = $pemasukanKotakMasjid->tanggal_masuk;

        if ($tanggalMasuk->year !== $sekarang->year || $tanggalMasuk->month !== $sekarang->month) {
            return response()->json([
                'status' => false,
                'message' => 'Uang masuk hanya dapat dihapus pada bulan berjalan.',
            ], 422);
        }

        $pemasukanKotakMasjid->delete();

        return response()->json([
            'status' => true,
            'message' => 'Uang masuk kotak masjid berhasil dihapus.',
        ]);
    }
}
