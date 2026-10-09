<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Iuran;
use App\Models\PengeluaranHeader;
use App\Models\Saldo;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanKasUmumController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'tanggal_dari' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tanggal_dari'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $sekarang = now();
        $tanggalDari = $data['tanggal_dari'] ?? $sekarang->copy()->startOfMonth()->toDateString();
        $tanggalSampai = $data['tanggal_sampai'] ?? $sekarang->toDateString();
        $periodeAwal = Carbon::createFromFormat('Y-m-d', $tanggalDari);

        $saldoAwal = (float) Saldo::query()
            ->where('pemilik', 'RUKEM')
            ->where('bulan', $periodeAwal->month)
            ->where('tahun', $periodeAwal->year)
            ->sum('nominal');

        $queryPemasukan = Iuran::query()
            ->leftJoin('penduduks', 'penduduks.id', '=', 'iurans.penduduk_id')
            ->whereBetween('tanggal_bayar', [$tanggalDari, $tanggalSampai])
            ->select('iurans.id', 'iurans.penduduk_id', 'iurans.nominal', 'iurans.tanggal_bayar', 'iurans.keterangan', 'penduduks.nama');

        $pemasukanIuran = (float) (clone $queryPemasukan)->sum('iurans.nominal');

        $pemasukan = (clone $queryPemasukan)
            ->orderByDesc('iurans.tanggal_bayar')
            ->orderByDesc('iurans.id')
            ->get();

        $queryPengeluaran = PengeluaranHeader::query()
            ->whereBetween('tanggal_pengeluaran', [$tanggalDari, $tanggalSampai]);

        $pengeluaranRukem = (float) (clone $queryPengeluaran)
            ->where('jenis_transaksi', 'RUKEM')
            ->sum('total_nominal');

        $pengeluaranPerJenis = collect([
            'RUKEM' => 'RUKEM',
            'KOTAK_MASJID' => 'KOTAK_MASJID',
            'SUMBANGAN_WARGA' => 'SUMBANGAN_WARGA',
        ])->mapWithKeys(fn ($jenis) => [
            $jenis => (float) (clone $queryPengeluaran)
                ->where('jenis_transaksi', $jenis)
                ->sum('total_nominal'),
        ]);

        $pengeluaranRukemRows = (clone $queryPengeluaran)
            ->where('jenis_transaksi', 'RUKEM')
            ->with('rincis:id,pengeluaran_header_id,harga_satuan,jumlah,satuan,nominal,keterangan')
            ->orderBy('tanggal_pengeluaran')
            ->orderBy('id')
            ->get();

        $mutasi = $pemasukan
            ->map(fn ($iuran) => [
                'id' => $iuran->id,
                'tanggal' => $iuran->tanggal_bayar->toDateString(),
                'urutan' => 1,
                'keterangan' => 'Iuran warga - ' . ($iuran->nama ?: 'Warga'),
                'detail' => $iuran->keterangan,
                'debet' => (float) $iuran->nominal,
                'kredit' => 0,
            ])
            ->concat($pengeluaranRukemRows->map(fn ($pengeluaran) => [
                'id' => $pengeluaran->id,
                'tanggal' => $pengeluaran->tanggal_pengeluaran->toDateString(),
                'urutan' => 2,
                'keterangan' => 'Pengeluaran RUKEM - ' . $pengeluaran->kegiatan,
                'detail' => $pengeluaran->rincis->pluck('keterangan')->filter()->implode(', '),
                'debet' => 0,
                'kredit' => (float) $pengeluaran->total_nominal,
            ]))
            ->sortBy(fn ($item) => sprintf('%s-%02d-%010d', $item['tanggal'], $item['urutan'], $item['id']))
            ->values();

        $saldoBerjalan = $saldoAwal;
        $bukuKas = collect([[
            'id' => 'saldo-awal',
            'tanggal' => $tanggalDari,
            'keterangan' => 'Saldo awal RUKEM',
            'detail' => null,
            'debet' => 0,
            'kredit' => 0,
            'saldo' => $saldoBerjalan,
        ]])->concat($mutasi->map(function ($item) use (&$saldoBerjalan) {
            $saldoBerjalan += $item['debet'] - $item['kredit'];

            return [
                ...$item,
                'saldo' => $saldoBerjalan,
            ];
        }))->values();

        return response()->json([
            'status' => true,
            'message' => 'Laporan kas umum berhasil diambil.',
            'ringkasan' => [
                'tanggal_dari' => $tanggalDari,
                'tanggal_sampai' => $tanggalSampai,
                'saldo_awal_rukem' => $saldoAwal,
                'pemasukan_iuran' => $pemasukanIuran,
                'pengeluaran_rukem' => $pengeluaranRukem,
                'saldo_akhir_rukem' => $saldoAwal + $pemasukanIuran - $pengeluaranRukem,
                'pengeluaran_per_jenis' => $pengeluaranPerJenis,
            ],
            'buku_kas' => $bukuKas,
        ]);
    }
}
