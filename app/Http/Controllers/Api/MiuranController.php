<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Miuran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MiuranController extends Controller
{
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
     * Simpan / update nominal iuran.
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
            'nominaliuran.required' => 'Nominal iuran wajib diisi.',
            'nominaliuran.numeric' => 'Nominal iuran harus berupa angka.',
            'nominaliuran.min' => 'Nominal iuran tidak boleh kurang dari 0.',
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

                // UPDATE
                $miuran->update([
                    'nominaliuran' => $request->nominaliuran,
                ]);

                $message = 'Nominal iuran berhasil diperbarui';

            } else {

                // INSERT
                $miuran = Miuran::create([
                    'nominaliuran' => $request->nominaliuran,
                ]);

                $message = 'Nominal iuran berhasil disimpan';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $miuran->fresh(),
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan nominal iuran',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
