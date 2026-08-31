<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pengeluaran;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class PengeluaranController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $awal = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $akhir = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();

        $query = Pengeluaran::whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('tanggal', 'desc');

        if ($request->ajax()) {
            return response()->json(['data' => $query->get()]);
        }

        $data = $query->get();
        return view('pengeluaran.index', compact('data', 'bulan', 'tahun'));
    }

    public function store(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'tanggal' => 'required|date',
                'jumlah' => 'required|numeric|min:0',
                'keterangan' => 'required|string|max:255',
            ],
            [
                'tanggal.required' => 'Tanggal wajib diisi.',
                'jumlah.required' => 'Jumlah wajib diisi.',
                'keterangan.required' => 'Keterangan wajib diisi.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $pengeluaran = Pengeluaran::create($request->only(['tanggal', 'jumlah', 'keterangan']));
            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran berhasil dicatat.',
                'data' => $pengeluaran
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencatat pengeluaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        $pengeluaran = Pengeluaran::find($id);
        if (!$pengeluaran) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengeluaran tidak ditemukan.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $pengeluaran,
        ]);
    }

    public function update(Request $request, $id)
    {
        $pengeluaran = Pengeluaran::find($id);
        if (!$pengeluaran) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengeluaran tidak ditemukan.',
            ], 404);
        }

        $validator = \Validator::make(
            $request->all(),
            [
                'tanggal' => 'required|date',
                'jumlah' => 'required|numeric|min:0',
                'keterangan' => 'required|string|max:255',
            ],
            [
                'tanggal.required' => 'Tanggal wajib diisi.',
                'jumlah.required' => 'Jumlah wajib diisi.',
                'keterangan.required' => 'Keterangan wajib diisi.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $pengeluaran->update($request->only(['tanggal', 'jumlah', 'keterangan']));
            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran berhasil diperbarui.',
                'data' => $pengeluaran
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pengeluaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $pengeluaran = Pengeluaran::find($id);
        if (!$pengeluaran) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengeluaran tidak ditemukan.',
            ], 404);
        }

        try {
            $pengeluaran->delete();
            return response()->json([
                'success' => true,
                'message' => 'Data pengeluaran berhasil dihapus.',
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pengeluaran: ' . $e->getMessage(),
            ], 500);
        }
    }
}