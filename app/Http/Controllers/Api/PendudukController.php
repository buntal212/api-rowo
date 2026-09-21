<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penduduk;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PendudukController extends Controller
{
    public function getList(Request $request)
    {
        $search = $request->search;
        $perPage = $request->per_page ?? 20;

        $data = Penduduk::query()
            ->when($search, function ($query) use ($search) {
                $query->where(
                    'nama',
                    'like',
                    "%{$search}%"
                );
            })
            ->orderBy('nama', 'asc')
            ->where('flaging', true)
            ->simplePaginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Data penduduk berhasil diambil.',
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'per_page' => $data->perPage(),
            'has_more' => $data->hasMorePages(),
        ]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate(
            [
                'id' => [
                    'nullable',
                    'integer',
                ],
                'nama' => [
                    'required',
                    'string',
                    'max:150',
                ],
            ],
            [
                'id.integer' => 'ID penduduk tidak valid.',
                'nama.required' => 'Nama penduduk wajib diisi.',
                'nama.string' => 'Nama penduduk tidak valid.',
                'nama.max' => 'Nama penduduk maksimal 150 karakter.',
            ]
        );

        $id = $data['id'] ?? null;

        unset($data['id']);

        $penduduk = Penduduk::updateOrCreate(
            ['id' => $id],
            $data
        );

        return response()->json([
            'status' => true,
            'message' => $penduduk->wasRecentlyCreated
                ? 'Data penduduk berhasil ditambahkan.'
                : 'Data penduduk berhasil diperbarui.',
            'data' => $penduduk,
        ]);
    }

    public function getDetail(Request $request)
    {
        $request->validate(
            [
                'id' => [
                    'required',
                    'integer',
                    'exists:penduduks,id',
                ],
            ],
            [
                'id.required' => 'ID penduduk wajib diisi.',
                'id.integer' => 'ID penduduk tidak valid.',
                'id.exists' => 'Data penduduk tidak ditemukan.',
            ]
        );

        $penduduk = Penduduk::find($request->id);

        return response()->json([
            'status' => true,
            'message' => 'Detail penduduk berhasil diambil.',
            'data' => $penduduk,
        ]);
    }

    public function delete(Request $request)
    {
        $data = $request->validate(
            [
                'id' => [
                    'required',
                    'integer',
                    'exists:penduduks,id',
                ],
            ],
            [
                'id.required' => 'ID penduduk wajib diisi.',
                'id.integer' => 'ID penduduk tidak valid.',
                'id.exists' => 'Data penduduk tidak ditemukan.',
            ]
        );

        $penduduk = Penduduk::find($data['id']);

        if (!$penduduk->flaging) {
            return response()->json([
                'status' => false,
                'message' => 'Data penduduk sudah dihapus.',
            ], 422);
        }

        $penduduk->update([
            'flaging' => false,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Data penduduk berhasil dihapus.',
        ]);
    }
}
