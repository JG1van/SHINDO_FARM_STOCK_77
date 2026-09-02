<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Telur;
use App\Models\Kandang;
use Illuminate\Database\QueryException;

class TelurController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $awal = \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $akhir = \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();

        $query = Telur::with('kandang')
            ->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc');

        if ($request->ajax()) {
            return response()->json(['data' => $query->get()]);
        }

        $data = $query->get();
        $kandangs = Kandang::orderBy('nama')->get();

        return view('telur.index', compact('data', 'kandangs', 'bulan', 'tahun'));
    }

    public function store(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'kandang_id' => 'required|exists:kandang,id',
                'tanggal' => 'required|date',
                'jumlah_butir' => 'required|integer|min:0',
            ],
            [
                'kandang_id.required' => 'Kandang wajib dipilih.',
                'kandang_id.exists' => 'Kandang tidak ditemukan.',
                'tanggal.required' => 'Tanggal wajib diisi.',
                'jumlah_butir.required' => 'Jumlah butir wajib diisi.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $telur = Telur::create($request->only(['kandang_id', 'tanggal', 'jumlah_butir']));
            return response()->json([
                'success' => true,
                'message' => 'Data telur berhasil ditambahkan.',
                'data' => $telur->load('kandang')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data telur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        $telur = Telur::find($id);
        if (!$telur) {
            return response()->json([
                'success' => false,
                'message' => 'Data telur tidak ditemukan.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $telur,
        ]);
    }

    public function update(Request $request, $id)
    {
        $telur = Telur::find($id);
        if (!$telur) {
            return response()->json([
                'success' => false,
                'message' => 'Data telur tidak ditemukan.',
            ], 404);
        }

        $validator = \Validator::make(
            $request->all(),
            [
                'kandang_id' => 'required|exists:kandang,id',
                'tanggal' => 'required|date',
                'jumlah_butir' => 'required|integer|min:0',
            ],
            [
                'kandang_id.required' => 'Kandang wajib dipilih.',
                'kandang_id.exists' => 'Kandang tidak ditemukan.',
                'tanggal.required' => 'Tanggal wajib diisi.',
                'jumlah_butir.required' => 'Jumlah butir wajib diisi.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $telur->update($request->only(['kandang_id', 'tanggal', 'jumlah_butir']));
            return response()->json([
                'success' => true,
                'message' => 'Data telur berhasil diperbarui.',
                'data' => $telur->load('kandang')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data telur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $telur = Telur::find($id);
        if (!$telur) {
            return response()->json([
                'success' => false,
                'message' => 'Data telur tidak ditemukan.',
            ], 404);
        }

        try {
            $telur->delete();
            return response()->json([
                'success' => true,
                'message' => 'Data telur berhasil dihapus.',
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data telur: ' . $e->getMessage(),
            ], 500);
        }
    }
}