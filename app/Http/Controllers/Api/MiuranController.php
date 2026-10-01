<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Miuran;
use App\Services\IuranStatusTahunanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MiuranController extends Controller
{
    public function __construct(private IuranStatusTahunanService $iuranStatusTahunanService)
    {
    }

    /**
     * Mengambil pengaturan iuran.
     */
    public function index()
    {
        try {
            $data = Miuran::first();

            return response()->json([
                'success' => true,
                'message' => 'Data iuran berhasil diambil',
                'data' => $data,
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data iuran',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Simpan / update total iuran tahunan.
     *
     * Karena pengaturan iuran hanya menggunakan satu record,
     * jika data belum ada -> insert.
     * jika data sudah ada -> update.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nominaliuran' => [
                'required',
                'numeric',
                'min:0',
            ],
        ], [
            'nominaliuran.required' => 'Total iuran tahunan wajib diisi.',
            'nominaliuran.numeric' => 'Total iuran tahunan harus berupa angka.',
            'nominaliuran.min' => 'Total iuran tahunan tidak boleh kurang dari 0.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {

            $miuran = Miuran::first();

            if ($miuran) {
                $targetSebelumnya = (float) $miuran->nominaliuran;

                if ($targetSebelumnya !== (float) $request->nominaliuran) {
                    $this->iuranStatusTahunanService->pastikanUntukTahun(
                        (int) now()->year,
                        $targetSebelumnya
                    );
                }

                // UPDATE
                $miuran->update([
                    'nominaliuran' => $request->nominaliuran,
                ]);

                $message = 'Total iuran tahunan berhasil diperbarui';

            } else {

                // INSERT
                $miuran = Miuran::create([
                    'nominaliuran' => $request->nominaliuran,
                ]);

                $this->iuranStatusTahunanService->pastikanUntukTahun(
                    (int) now()->year,
                    (float) $miuran->nominaliuran
                );

                $message = 'Total iuran tahunan berhasil disimpan';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $miuran->fresh(),
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan total iuran tahunan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
