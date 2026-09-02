<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kandang;
use Illuminate\Database\QueryException;

class KandangController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Kandang::orderBy('id', 'desc')->get();
            return response()->json(['data' => $data]);
        }
        $data = Kandang::orderBy('id', 'desc')->get();
        return view('kandang.index', compact('data'));
    }

    public function store(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'nama' => 'required|string|max:50',
                'jenis_ayam' => 'required|string|max:50',
                'jantan' => 'required|integer|min:0',
                'betina' => 'required|integer|min:0',
            ],
            [
                'nama.required' => 'Nama kandang wajib diisi.',
                'jenis_ayam.required' => 'Jenis ayam wajib diisi.',
                'jantan.required' => 'Jumlah jantan wajib diisi.',
                'betina.required' => 'Jumlah betina wajib diisi.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $kandang = Kandang::create($request->only(['nama', 'jenis_ayam', 'jantan', 'betina']));
            return response()->json([
                'success' => true,
                'message' => 'Kandang berhasil ditambahkan.',
                'data' => $kandang
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan kandang: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        $kandang = Kandang::find($id);
        if (!$kandang) {
            return response()->json([
                'success' => false,
                'message' => 'Kandang tidak ditemukan.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $kandang,
        ]);
    }

    public function update(Request $request, $id)
    {
        $kandang = Kandang::find($id);
        if (!$kandang) {
            return response()->json([
                'success' => false,
                'message' => 'Kandang tidak ditemukan.',
            ], 404);
        }

        $validator = \Validator::make(
            $request->all(),
            [
                'nama' => 'required|string|max:50',
                'jenis_ayam' => 'required|string|max:50',
                'jantan' => 'required|integer|min:0',
                'betina' => 'required|integer|min:0',
            ],
            [
                'nama.required' => 'Nama kandang wajib diisi.',
                'jenis_ayam.required' => 'Jenis ayam wajib diisi.',
                'jantan.required' => 'Jumlah jantan wajib diisi.',
                'betina.required' => 'Jumlah betina wajib diisi.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $kandang->update($request->only(['nama', 'jenis_ayam', 'jantan', 'betina']));
            return response()->json([
                'success' => true,
                'message' => 'Kandang berhasil diperbarui.',
                'data' => $kandang
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui kandang: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $kandang = Kandang::find($id);
        if (!$kandang) {
            return response()->json([
                'success' => false,
                'message' => 'Kandang tidak ditemukan.',
            ], 404);
        }

        if ($kandang->telurs()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kandang ini tidak dapat dihapus karena masih memiliki data telur.',
            ], 409);
        }

        try {
            $kandang->delete();
            return response()->json([
                'success' => true,
                'message' => 'Kandang berhasil dihapus.',
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus kandang: ' . $e->getMessage(),
            ], 500);
        }
    }
}