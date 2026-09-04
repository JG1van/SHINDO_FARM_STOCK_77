<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\Pengeluaran;
use App\Models\Kandang;
use App\Models\Telur;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class KalkulatorController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $kandangs = Kandang::orderBy('nama')->get();

        // ===== TAB 1: Produksi Harian (pivot per kandang) =====
        [
            'fullPivot'               => $fullPivot,
            'totalPerKandang'         => $totalPerKandang,
            'grandTotalProduksi'      => $grandTotalProduksi,
            'daysInMonth'             => $daysInMonth,
            'rataRataHarianProduksi'  => $rataRataHarianProduksi,
            'rataRataPerKandang'      => $rataRataPerKandang,
        ] = $this->hitungProduksi($bulan, $tahun, $kandangs);

        $totalAyam = Kandang::sum('jantan') + Kandang::sum('betina');

        // ===== TAB 4: Data Transaksi (checklist Penjualan & Pengeluaran) =====
        // SUM(jumlah_telur) dipakai untuk menandai baris yang BUKAN penjualan telur
        // (mis. "Uang bulanan Agustus" = 0 butir) supaya default-nya tidak tercentang.
        $penjualanGrouped = Penjualan::selectRaw('nama_pembeli as nama, SUM(total_harga) as total, SUM(jumlah_telur) as jumlah_telur')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->groupBy('nama_pembeli')
            ->orderByDesc('total')
            ->get();

        $pengeluaranGrouped = Pengeluaran::selectRaw('keterangan as nama, SUM(jumlah) as total')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->groupBy('keterangan')
            ->orderByDesc('total')
            ->get();

        // ===== Angka default siap-pakai, MASING-MASING cuma jadi nilai AWAL untuk
        // input manual di Tab 5 & Tab 6 — bukan variabel yang saling terhubung real-time.
        // FIX BUG: transaksi jumlah_telur = 0 (uang bulanan dll) dikeluarkan dari
        // omzet & dari jumlah butir terjual, supaya Harga Jual Rata-rata tidak melenceng.
        $penjualansTelurSaja = Penjualan::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->where('jumlah_telur', '>', 0)
            ->get();

        $omzetTelurBulanIni    = $penjualansTelurSaja->sum(fn ($p) => $p->total_harga ?? 0);
        $telurTerjualBulanIni  = (int) $penjualansTelurSaja->sum(fn ($p) => $p->jumlah_telur ?? 0);
        $rataRataHargaPerButir = $telurTerjualBulanIni > 0
            ? round($omzetTelurBulanIni / $telurTerjualBulanIni)
            : 0;

        $totalPengeluaranDefault = $pengeluaranGrouped->sum('total');

        // ===== TAB 7: Proyeksi Bulan Depan — FIX BUG: pakai hari penuh BULAN DEPAN,
        // dihitung sendiri di sini, sama sekali tidak terikat ke input "Jumlah Hari" Tab 1.
        $tanggalBulanDepan = Carbon::createFromDate($tahun, $bulan, 1)->addMonthNoOverflow();
        $daysInNextMonth   = $tanggalBulanDepan->daysInMonth;
        $namaBulanDepan    = $tanggalBulanDepan->translatedFormat('F Y');

        return view('kalkulator.index', compact(
            'kandangs',
            'fullPivot',
            'totalPerKandang',
            'grandTotalProduksi',
            'daysInMonth',
            'rataRataHarianProduksi',
            'rataRataPerKandang',
            'penjualanGrouped',
            'pengeluaranGrouped',
            'totalAyam',
            'bulan',
            'tahun',
            'rataRataHargaPerButir',
            'omzetTelurBulanIni',
            'totalPengeluaranDefault',
            'daysInNextMonth',
            'namaBulanDepan'
        ));
    }

    /**
     * Logic pivot produksi — sama persis dengan DashboardController, supaya angka
     * Total Produksi di Tab 1 Kalkulator selalu konsisten dengan Dashboard.
     */
    private function hitungProduksi(int $bulan, int $tahun, Collection $kandangs): array
    {
        $telurs = Telur::selectRaw('kandang_id, tanggal, SUM(jumlah_butir) as total')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->groupBy('kandang_id', 'tanggal')
            ->get();

        $pivot = [];
        foreach ($telurs as $t) {
            $tglKey = $t->tanggal instanceof Carbon ? $t->tanggal->format('Y-m-d') : $t->tanggal;
            $pivot[$tglKey][$t->kandang_id] = $t->total;
        }

        $startDate = Carbon::createFromDate($tahun, $bulan, 1);
        $daysInMonth = $startDate->daysInMonth;
        $fullPivot = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $tglKey = $startDate->copy()->day($day)->format('Y-m-d');
            $fullPivot[$tglKey] = $pivot[$tglKey] ?? [];
        }

        $totalPerKandang = [];
        foreach ($kandangs as $k) {
            $totalPerKandang[$k->id] = 0;
            foreach ($fullPivot as $row) {
                $totalPerKandang[$k->id] += $row[$k->id] ?? 0;
            }
        }
        $grandTotalProduksi = array_sum($totalPerKandang);

        $isBulanIni = ($bulan == now()->month && $tahun == now()->year);
        $hariPembagi = $isBulanIni ? now()->day : $daysInMonth;

        $rataRataHarianProduksi = $hariPembagi > 0
            ? round($grandTotalProduksi / $hariPembagi, 1)
            : 0;

        $rataRataPerKandang = [];
        foreach ($kandangs as $k) {
            $rataRataPerKandang[$k->id] = $hariPembagi > 0
                ? round(($totalPerKandang[$k->id] ?? 0) / $hariPembagi, 1)
                : 0;
        }

        return compact(
            'fullPivot',
            'totalPerKandang',
            'grandTotalProduksi',
            'daysInMonth',
            'rataRataHarianProduksi',
            'rataRataPerKandang'
        );
    }
}