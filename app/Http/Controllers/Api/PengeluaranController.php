<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengeluaranHeader;
use App\Models\PengeluaranRinci;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengeluaranController extends Controller
{
    private const JENIS_TRANSAKSI = ['RUKEM', 'KOTAK_MASJID', 'SUMBANGAN_WARGA'];

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
            'jenis_transaksi' => ['required', 'string', 'in:' . implode(',', self::JENIS_TRANSAKSI)],
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
                'jenis_transaksi' => $data['jenis_transaksi'],
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

    public function hapusRincian(PengeluaranRinci $rinci)
    {
        $sekarang = now();
        $header = $rinci->header;
        $tanggalPengeluaran = $header?->tanggal_pengeluaran;

        if (!$tanggalPengeluaran ||
            $tanggalPengeluaran->year !== $sekarang->year ||
            $tanggalPengeluaran->month !== $sekarang->month) {
            return response()->json([
                'status' => false,
                'message' => 'Rincian pengeluaran hanya dapat dihapus pada bulan berjalan.',
            ], 422);
        }

        $transaksiDihapus = DB::transaction(function () use ($rinci) {
            $header = PengeluaranHeader::query()
                ->lockForUpdate()
                ->findOrFail($rinci->pengeluaran_header_id);

            $rincian = $header->rincis()
                ->lockForUpdate()
                ->findOrFail($rinci->id);

            $rincian->delete();

            $sisaRincian = $header->rincis()->count();

            if ($sisaRincian === 0) {
                $header->delete();

                return true;
            }

            $header->update([
                'total_nominal' => $header->rincis()->sum('nominal'),
            ]);

            return false;
        });

        return response()->json([
            'status' => true,
            'message' => $transaksiDihapus
                ? 'Rincian terakhir dihapus dan transaksi pengeluaran dihapus.'
                : 'Rincian pengeluaran berhasil dihapus.',
        ]);
    }

    public function ubahHeader(Request $request, PengeluaranHeader $pengeluaran)
    {
        $data = $request->validate([
            'kegiatan' => ['required', 'string', 'max:255'],
            'jenis_transaksi' => ['prohibited'],
            'total_nominal' => ['prohibited'],
        ]);

        $pengeluaran->update([
            'kegiatan' => $data['kegiatan'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Header pengeluaran berhasil diperbarui.',
            'data' => $pengeluaran->fresh(),
        ]);
    }

    public function hapusHeader(PengeluaranHeader $pengeluaran)
    {
        $sekarang = now();
        $tanggalPengeluaran = $pengeluaran->tanggal_pengeluaran;

        if ($tanggalPengeluaran->year !== $sekarang->year ||
            $tanggalPengeluaran->month !== $sekarang->month) {
            return response()->json([
                'status' => false,
                'message' => 'Header pengeluaran hanya dapat dihapus pada bulan berjalan.',
            ], 422);
        }

        DB::transaction(function () use ($pengeluaran) {
            $header = PengeluaranHeader::query()
                ->lockForUpdate()
                ->findOrFail($pengeluaran->id);

            $header->delete();
        });

        return response()->json([
            'status' => true,
            'message' => 'Header pengeluaran beserta seluruh rinciannya berhasil dihapus.',
        ]);
    }
}
