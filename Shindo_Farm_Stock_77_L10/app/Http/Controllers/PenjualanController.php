<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Penjualan;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class PenjualanController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $awal = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $akhir = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();

        $query = Penjualan::whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc');

        if ($request->ajax()) {
            return response()->json(['data' => $query->get()]);
        }

        $data = $query->get();
        return view('penjualan.index', compact('data', 'bulan', 'tahun'));
    }

    public function store(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'tanggal' => 'required|date',
                'nama_pembeli' => 'required|string|max:100',
                'jumlah_telur' => 'required|integer|min:1',
                'total_harga' => 'required|numeric|min:0',
            ],
            [
                'tanggal.required' => 'Tanggal wajib diisi.',
                'nama_pembeli.required' => 'Nama pembeli wajib diisi.',
                'jumlah_telur.required' => 'Jumlah telur wajib diisi.',
                'jumlah_telur.min' => 'Jumlah telur minimal 1.',
                'total_harga.required' => 'Total harga wajib diisi.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $penjualan = Penjualan::create($request->only(['tanggal', 'nama_pembeli', 'jumlah_telur', 'total_harga']));
            return response()->json([
                'success' => true,
                'message' => 'Penjualan berhasil dicatat.',
                'data' => $penjualan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencatat penjualan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        $penjualan = Penjualan::find($id);
        if (!$penjualan) {
            return response()->json([
                'success' => false,
                'message' => 'Data penjualan tidak ditemukan.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $penjualan,
        ]);
    }

    public function update(Request $request, $id)
    {
        $penjualan = Penjualan::find($id);
        if (!$penjualan) {
            return response()->json([
                'success' => false,
                'message' => 'Data penjualan tidak ditemukan.',
            ], 404);
        }

        $validator = \Validator::make(
            $request->all(),
            [
                'tanggal' => 'required|date',
                'nama_pembeli' => 'required|string|max:100',
                'jumlah_telur' => 'required|integer|min:1',
                'total_harga' => 'required|numeric|min:0',
            ],
            [
                'tanggal.required' => 'Tanggal wajib diisi.',
                'nama_pembeli.required' => 'Nama pembeli wajib diisi.',
                'jumlah_telur.required' => 'Jumlah telur wajib diisi.',
                'jumlah_telur.min' => 'Jumlah telur minimal 1.',
                'total_harga.required' => 'Total harga wajib diisi.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $penjualan->update($request->only(['tanggal', 'nama_pembeli', 'jumlah_telur', 'total_harga']));
            return response()->json([
                'success' => true,
                'message' => 'Penjualan berhasil diperbarui.',
                'data' => $penjualan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui penjualan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $penjualan = Penjualan::find($id);
        if (!$penjualan) {
            return response()->json([
                'success' => false,
                'message' => 'Data penjualan tidak ditemukan.',
            ], 404);
        }

        try {
            $penjualan->delete();
            return response()->json([
                'success' => true,
                'message' => 'Data penjualan berhasil dihapus.',
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus penjualan: ' . $e->getMessage(),
            ], 500);
        }
    }
}