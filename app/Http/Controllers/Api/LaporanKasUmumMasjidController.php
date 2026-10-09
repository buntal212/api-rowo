<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PemasukanKotakMasjid;
use App\Models\PengeluaranMasjidHeader;
use App\Models\Saldo;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanKasUmumMasjidController extends Controller
{
    private const JENIS_SUMBER_DANA = [
        'KOTAK_AMAL' => 'Kotak Amal',
        'PEMBANGUNAN_MASJID' => 'Pembangunan Masjid',
    ];

    public function index(Request $request)
    {
        $data = $request->validate([
            'tanggal_dari' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tanggal_dari'],
        ]);

        $sekarang = now();
        $tanggalDari = $data['tanggal_dari'] ?? $sekarang->copy()->startOfMonth()->toDateString();
        $tanggalSampai = $data['tanggal_sampai'] ?? $sekarang->toDateString();
        $periodeAwal = Carbon::createFromFormat('Y-m-d', $tanggalDari);

        $laporanPerJenis = collect(self::JENIS_SUMBER_DANA)->mapWithKeys(function (string $label, string $jenis) use ($tanggalDari, $tanggalSampai, $periodeAwal) {
            $saldoAwal = (float) Saldo::query()
                ->where('pemilik', $jenis)
                ->where('bulan', $periodeAwal->month)
                ->where('tahun', $periodeAwal->year)
                ->sum('nominal');

            $pemasukan = PemasukanKotakMasjid::query()
                ->where('jenis_pemasukan', $jenis)
                ->whereBetween('tanggal_masuk', [$tanggalDari, $tanggalSampai])
                ->orderBy('tanggal_masuk')
                ->orderBy('id')
                ->get();

            $pengeluaran = PengeluaranMasjidHeader::query()
                ->where('jenis_sumber_dana', $jenis)
                ->whereBetween('tanggal_pengeluaran', [$tanggalDari, $tanggalSampai])
                ->with('rincis:id,pengeluaran_masjid_header_id,harga_satuan,jumlah,satuan,nominal,keterangan')
                ->orderBy('tanggal_pengeluaran')
                ->orderBy('id')
                ->get();

            $totalPemasukan = (float) $pemasukan->sum('nominal');
            $totalPengeluaran = (float) $pengeluaran->sum('total_nominal');
            $saldoBerjalan = $saldoAwal;

            $mutasi = $pemasukan
                ->map(fn ($item) => [
                    'id' => 'masuk-' . $item->id,
                    'tanggal' => $item->tanggal_masuk->toDateString(),
                    'urutan' => 1,
                    'keterangan' => 'Uang masuk ' . $label,
                    'detail' => $item->keterangan,
                    'debet' => (float) $item->nominal,
                    'kredit' => 0,
                ])
                ->concat($pengeluaran->map(fn ($item) => [
                    'id' => 'keluar-' . $item->id,
                    'tanggal' => $item->tanggal_pengeluaran->toDateString(),
                    'urutan' => 2,
                    'keterangan' => 'Pengeluaran - ' . $item->kegiatan,
                    'detail' => $item->rincis->pluck('keterangan')->filter()->implode(', '),
                    'debet' => 0,
                    'kredit' => (float) $item->total_nominal,
                ]))
                ->sortBy(fn ($item) => sprintf('%s-%02d-%s', $item['tanggal'], $item['urutan'], $item['id']))
                ->values()
                ->map(function ($item) use (&$saldoBerjalan) {
                    $saldoBerjalan += $item['debet'] - $item['kredit'];

                    return [...$item, 'saldo' => $saldoBerjalan];
                })
                ->values();

            $bukuKas = collect([[
                'id' => 'saldo-awal-' . $jenis,
                'tanggal' => $tanggalDari,
                'urutan' => 0,
                'keterangan' => 'Saldo awal ' . $label,
                'detail' => null,
                'debet' => 0,
                'kredit' => 0,
                'saldo' => $saldoAwal,
            ]])->concat($mutasi)->values();

            return [
                $jenis => [
                    'label' => $label,
                    'saldo_awal' => $saldoAwal,
                    'total_pemasukan' => $totalPemasukan,
                    'total_pengeluaran' => $totalPengeluaran,
                    'saldo_akhir' => $saldoAwal + $totalPemasukan - $totalPengeluaran,
                    'buku_kas' => $bukuKas,
                ],
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Laporan kas umum masjid berhasil diambil.',
            'ringkasan' => [
                'tanggal_dari' => $tanggalDari,
                'tanggal_sampai' => $tanggalSampai,
                'saldo_awal' => $laporanPerJenis->sum('saldo_awal'),
                'total_pemasukan' => $laporanPerJenis->sum('total_pemasukan'),
                'total_pengeluaran' => $laporanPerJenis->sum('total_pengeluaran'),
                'saldo_akhir' => $laporanPerJenis->sum('saldo_akhir'),
            ],
            'data' => $laporanPerJenis,
        ]);
    }
}
