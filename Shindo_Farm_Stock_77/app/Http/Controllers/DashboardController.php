<?php

namespace App\Http\Controllers;

use App\Models\Telur;
use App\Models\Kandang;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $kandangs = Kandang::orderBy('nama')->get();

        $telurs = Telur::selectRaw('kandang_id, tanggal, SUM(jumlah_butir) as total')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->groupBy('kandang_id', 'tanggal')
            ->get();

        // pivot: [tanggal][kandang_id] = total
        $pivot = [];
        foreach ($telurs as $t) {
            $tgl = $t->tanggal instanceof Carbon ? $t->tanggal->format('Y-m-d') : $t->tanggal;
            $pivot[$tgl][$t->kandang_id] = $t->total;
        }

        // generate semua tanggal 1 s/d akhir bulan, walau tidak ada data
        $startDate = Carbon::createFromDate($tahun, $bulan, 1);
        $daysInMonth = $startDate->daysInMonth;

        $fullPivot = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $tglKey = $startDate->copy()->day($day)->format('Y-m-d');
            $fullPivot[$tglKey] = $pivot[$tglKey] ?? [];
        }

        // total per kandang (untuk baris footer)
        $totalPerKandang = [];
        foreach ($kandangs as $k) {
            $totalPerKandang[$k->id] = 0;
            foreach ($fullPivot as $row) {
                $totalPerKandang[$k->id] += $row[$k->id] ?? 0;
            }
        }
        $grandTotal = array_sum($totalPerKandang);

        return view('dashboard.index', compact(
            'kandangs',
            'fullPivot',
            'bulan',
            'tahun',
            'totalPerKandang',
            'grandTotal'
        ));
    }
}