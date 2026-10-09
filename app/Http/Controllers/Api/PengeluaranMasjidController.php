<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengeluaranMasjidHeader;
use App\Models\PengeluaranMasjidRinci;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengeluaranMasjidController extends Controller
{
    private const JENIS_SUMBER_DANA = ['KOTAK_AMAL', 'PEMBANGUNAN_MASJID'];

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

        $pengeluaran = PengeluaranMasjidHeader::query()
            ->whereBetween('tanggal_pengeluaran', [$tanggalDari, $tanggalSampai])
            ->with('rincis:id,pengeluaran_masjid_header_id,harga_satuan,jumlah,satuan,nominal,keterangan')
            ->orderByDesc('tanggal_pengeluaran')
            ->orderByDesc('id')
            ->simplePaginate($data['per_page'] ?? 20);

        return response()->json([
            'status' => true,
            'message' => 'Data pengeluaran masjid berhasil diambil.',
            'data' => $pengeluaran,
        ]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'kegiatan' => ['required', 'string', 'max:255'],
            'jenis_sumber_dana' => ['required', 'string', 'in:' . implode(',', self::JENIS_SUMBER_DANA)],
            'rincian' => ['required', 'array', 'min:1'],
            'rincian.*.harga_satuan' => ['required', 'numeric', 'gt:0'],
            'rincian.*.jumlah' => ['required', 'integer', 'min:1'],
            'rincian.*.satuan' => ['required', 'string', 'max:50'],
            'rincian.*.keterangan' => ['nullable', 'string', 'max:500'],
        ], [
            'kegiatan.required' => 'Kegiatan wajib diisi.',
            'jenis_sumber_dana.required' => 'Jenis sumber dana wajib dipilih.',
            'jenis_sumber_dana.in' => 'Jenis sumber dana tidak valid.',
            'rincian.required' => 'Rincian pengeluaran wajib diisi.',
            'rincian.min' => 'Minimal harus ada satu rincian pengeluaran.',
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

            $header = PengeluaranMasjidHeader::create([
                'kegiatan' => $data['kegiatan'],
                'jenis_sumber_dana' => $data['jenis_sumber_dana'],
                'tanggal_pengeluaran' => date('Y-m-d'),
                'total_nominal' => $rincian->sum('nominal'),
            ]);

            $header->rincis()->createMany($rincian);

            return $header->load('rincis');
        });

        return response()->json([
            'status' => true,
            'message' => 'Pengeluaran masjid berhasil disimpan.',
            'data' => $pengeluaran,
        ], 201);
    }

    public function ubahHeader(Request $request, PengeluaranMasjidHeader $pengeluaranMasjid)
    {
        if (!$this->adalahBulanBerjalan($pengeluaranMasjid->tanggal_pengeluaran)) {
            return $this->responBulanTidakSesuai('Pengeluaran masjid hanya dapat diubah pada bulan berjalan.');
        }

        $data = $request->validate([
            'kegiatan' => ['required', 'string', 'max:255'],
        ], ['kegiatan.required' => 'Kegiatan wajib diisi.']);

        $pengeluaranMasjid->update(['kegiatan' => $data['kegiatan']]);

        return response()->json([
            'status' => true,
            'message' => 'Kegiatan pengeluaran masjid berhasil diperbarui.',
            'data' => $pengeluaranMasjid->fresh(),
        ]);
    }

    public function hapusHeader(PengeluaranMasjidHeader $pengeluaranMasjid)
    {
        if (!$this->adalahBulanBerjalan($pengeluaranMasjid->tanggal_pengeluaran)) {
            return $this->responBulanTidakSesuai('Pengeluaran masjid hanya dapat dihapus pada bulan berjalan.');
        }

        $pengeluaranMasjid->delete();

        return response()->json([
            'status' => true,
            'message' => 'Pengeluaran masjid beserta rinciannya berhasil dihapus.',
        ]);
    }

    public function hapusRincian(PengeluaranMasjidRinci $rincian)
    {
        $header = $rincian->header;

        if (!$header || !$this->adalahBulanBerjalan($header->tanggal_pengeluaran)) {
            return $this->responBulanTidakSesuai('Rincian pengeluaran masjid hanya dapat dihapus pada bulan berjalan.');
        }

        $transaksiDihapus = DB::transaction(function () use ($rincian) {
            $header = PengeluaranMasjidHeader::query()->lockForUpdate()->findOrFail($rincian->pengeluaran_masjid_header_id);
            $rincianTersimpan = $header->rincis()->lockForUpdate()->findOrFail($rincian->id);
            $rincianTersimpan->delete();

            if ($header->rincis()->count() === 0) {
                $header->delete();

                return true;
            }

            $header->update(['total_nominal' => $header->rincis()->sum('nominal')]);

            return false;
        });

        return response()->json([
            'status' => true,
            'message' => $transaksiDihapus
                ? 'Rincian terakhir dihapus dan transaksi pengeluaran masjid dihapus.'
                : 'Rincian pengeluaran masjid berhasil dihapus.',
        ]);
    }

    private function adalahBulanBerjalan($tanggal): bool
    {
        $sekarang = now();

        return $tanggal->year === $sekarang->year && $tanggal->month === $sekarang->month;
    }

    private function responBulanTidakSesuai(string $pesan)
    {
        return response()->json(['status' => false, 'message' => $pesan], 422);
    }
}
