<?php

namespace App\Http\Controllers;

use App\Models\Kandang;
use App\Models\Telur;
use App\Models\Penjualan;
use App\Models\Pengeluaran;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $kandangs = Kandang::orderBy('nama')->get();

        // ===== 1. Grid produksi telur per kandang per tanggal (pola asli kamu, dipertahankan) =====
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

        // ===== 1b. Rata-rata produksi harian =====
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

        // ===== 2. KPI Cards =====
        $totalAyam   = $kandangs->sum(fn ($k) => $k->jantan + $k->betina);
        $totalJantan = $kandangs->sum('jantan');
        $totalBetina = $kandangs->sum('betina');

        $omzetBulanIni = Penjualan::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->sum('total_harga');

        $telurTerjualBulanIni = (int) Penjualan::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->sum('jumlah_telur');

        $pengeluaranBulanIni = Pengeluaran::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->sum('jumlah');

        $labaBersih = $omzetBulanIni - $pengeluaranBulanIni;

        $stokBelumTerjual = $grandTotalProduksi - $telurTerjualBulanIni;

        // ===== 3. Grafik tren harian (produksi vs penjualan vs pengeluaran) =====
        $penjualanHarian = Penjualan::selectRaw('tanggal, SUM(total_harga) as total')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        $pengeluaranHarian = Pengeluaran::selectRaw('tanggal, SUM(jumlah) as total')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        $produksiHarian = Telur::selectRaw('tanggal, SUM(jumlah_butir) as total')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        $tanggalList = array_keys($fullPivot);
        $chartLabels = [];
        $chartProduksi = [];
        $chartPenjualan = [];
        $chartPengeluaran = [];
        foreach ($tanggalList as $tgl) {
            $chartLabels[]      = Carbon::parse($tgl)->format('d');
            $chartProduksi[]    = (int) ($produksiHarian[$tgl] ?? 0);
            $chartPenjualan[]   = (float) ($penjualanHarian[$tgl] ?? 0);
            $chartPengeluaran[] = (float) ($pengeluaranHarian[$tgl] ?? 0);
        }

        // ===== 4. Perbandingan antar kandang (produktivitas + rasio jantan:betina) =====
        $produktivitasKandang = $kandangs->map(function ($k) use ($totalPerKandang, $rataRataPerKandang) {
            return [
                'nama'             => $k->nama,
                'jenis_ayam'       => $k->jenis_ayam,
                'total_telur'      => $totalPerKandang[$k->id] ?? 0,
                'rata_rata_harian' => $rataRataPerKandang[$k->id] ?? 0,
                'jantan'           => $k->jantan,
                'betina'           => $k->betina,
                'rasio_label'      => $k->jantan . ':' . $k->betina,
                'rasio_warn'       => $k->betina > 0 && ($k->jantan / max($k->betina, 1)) > 0.3,
            ];
        })->sortByDesc('total_telur')->values();

        // ===== 5. Top 5 pembeli & rata-rata harga per butir =====
        $topPembeli = Penjualan::selectRaw('nama_pembeli, SUM(total_harga) as total_belanja, SUM(jumlah_telur) as total_butir')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->groupBy('nama_pembeli')
            ->orderByDesc('total_belanja')
            ->limit(5)
            ->get();

        $rataRataHargaPerButir = $telurTerjualBulanIni > 0
            ? round($omzetBulanIni / $telurTerjualBulanIni)
            : 0;

        // ===== 6. Breakdown pengeluaran per keterangan =====
        $breakdownPengeluaran = Pengeluaran::selectRaw('keterangan, SUM(jumlah) as total')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->groupBy('keterangan')
            ->orderByDesc('total')
            ->get();

        // ===== 7. Aktivitas terbaru (tidak difilter bulan, selalu 5 terbaru global) =====
        $penjualanTerbaru = Penjualan::latest('created_at')->limit(5)->get();
        $pengeluaranTerbaru = Pengeluaran::latest('created_at')->limit(5)->get();
        $telurTerbaru = Telur::query()
            ->join('kandang', 'kandang.id', '=', 'telur.kandang_id')
            ->orderByDesc('telur.created_at')
            ->limit(5)
            ->get(['telur.*', 'kandang.nama as kandang_nama']);

        return view('dashboard.index', compact(
            'kandangs',
            'fullPivot',
            'bulan',
            'tahun',
            'totalPerKandang',
            'grandTotalProduksi',
            'rataRataHarianProduksi',
            'rataRataPerKandang',
            'hariPembagi',
            'totalAyam',
            'totalJantan',
            'totalBetina',
            'omzetBulanIni',
            'telurTerjualBulanIni',
            'pengeluaranBulanIni',
            'labaBersih',
            'stokBelumTerjual',
            'chartLabels',
            'chartProduksi',
            'chartPenjualan',
            'chartPengeluaran',
            'produktivitasKandang',
            'topPembeli',
            'rataRataHargaPerButir',
            'breakdownPengeluaran',
            'penjualanTerbaru',
            'pengeluaranTerbaru',
            'telurTerbaru'
        ));
    }

    /**
     * Helper: kasih garis pembatas (border) tipis ke semua sel dalam sebuah range,
     * misalnya applyBorder($sheet, 'A1:D10').
     */
    private function applyBorder($sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    /**
     * Helper: kasih warna abu-abu + bold ke baris header.
     */
    private function styleHeader($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E0E0E0');
    }

    /**
     * Helper: nilai tetap disimpan sebagai angka 0 (supaya SUM/rumus tetap akurat),
     * tapi TAMPILANNYA di Excel otomatis jadi "-" kalau nilainya 0/kosong.
     * Dipakai untuk kolom angka biasa (jumlah telur, jumlah butir, dst).
     */
    private function formatAngkaAtauStrip($sheet, string $range): void
    {
        $sheet->getStyle($range)->getNumberFormat()
            ->setFormatCode('#,##0;-#,##0;"-"');
    }

    /**
     * Sama seperti di atas, tapi untuk kolom Rupiah (pakai pemisah ribuan "Rp").
     */
    private function formatRupiahAtauStrip($sheet, string $range): void
    {
        $sheet->getStyle($range)->getNumberFormat()
            ->setFormatCode('"Rp"#,##0;-"Rp"#,##0;"-"');
    }

    public function exportExcel(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $kandangs = Kandang::orderBy('nama')->get();

        // Pivot produksi (sama seperti index())
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

        // Rata-rata produksi harian (sama seperti index())
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

        $penjualans = Penjualan::whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->orderBy('tanggal')->get();
        $pengeluarans = Pengeluaran::whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->orderBy('tanggal')->get();

        // PENTING: pakai ?? 0 supaya nilai null dari database (mis. total_harga kosong
        // karena barter/gratis) tidak bikin sum() jadi meleset dan tidak nongol kosong di Excel.
        $omzetBulanIni = $penjualans->sum(fn ($p) => $p->total_harga ?? 0);
        $telurTerjualBulanIni = $penjualans->sum(fn ($p) => $p->jumlah_telur ?? 0);
        $pengeluaranBulanIni = $pengeluarans->sum(fn ($p) => $p->jumlah ?? 0);
        $labaBersih = $omzetBulanIni - $pengeluaranBulanIni;
        $stokBelumTerjual = $grandTotalProduksi - $telurTerjualBulanIni;

        $namaBulan = Carbon::create()->month($bulan)->translatedFormat('F');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // ===== SHEET 1: Ringkasan =====
        $s1 = $spreadsheet->getActiveSheet();
        $s1->setTitle('Ringkasan');
        $s1->fromArray([
            ["Ringkasan Bulan {$namaBulan} {$tahun}"],
            [],
            ['Total Ayam', $totalAyamRingkasan = $kandangs->sum(fn($k) => $k->jantan + $k->betina) ?? 0],
            ['Jantan', $kandangs->sum('jantan') ?? 0],
            ['Betina', $kandangs->sum('betina') ?? 0],
            ['Produksi Telur (butir)', $grandTotalProduksi ?? 0],
            ['Rata-rata Produksi/hari (butir)', $rataRataHarianProduksi ?? 0],
            ['Penjualan (Rp)', $omzetBulanIni ?? 0],
            ['Pengeluaran (Rp)', $pengeluaranBulanIni ?? 0],
            ['Uang Tersedia (Rp)', $labaBersih ?? 0],
            ['Belum Terjual (butir)', $stokBelumTerjual ?? 0],
        ], null, 'A1', true); // true = strict null comparison, supaya nilai 0 tidak ikut dianggap kosong
        $s1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $s1->getStyle('A3:A11')->getFont()->setBold(true);
        $s1->getColumnDimension('A')->setWidth(30);
        $s1->getColumnDimension('B')->setWidth(18);
        // Garis pembatas untuk tabel ringkasan (baris 3 s/d 11)
        $this->applyBorder($s1, 'A3:B11');
        // Baris Rupiah (Penjualan, Pengeluaran, Uang Tersedia) pakai format Rp + strip
        $this->formatRupiahAtauStrip($s1, 'B8:B10');
        // Baris angka biasa (Total Ayam s/d Rata-rata Produksi, Belum Terjual) pakai format strip
        $this->formatAngkaAtauStrip($s1, 'B3:B7');
        $this->formatAngkaAtauStrip($s1, 'B11:B11');

        // ===== SHEET 2: Produksi per Kandang (pivot harian) =====
        $s2 = $spreadsheet->createSheet();
        $s2->setTitle('Produksi Harian');
        $header = ['Tanggal'];
        foreach ($kandangs as $k) $header[] = $k->nama;
        $header[] = 'Total';
        $lastCol = $s2->getCell([count($header), 1])->getColumn();
        $s2->fromArray([$header], null, 'A1', true);
        $this->styleHeader($s2, "A1:{$lastCol}1");

        $rowNum = 2;
        foreach ($fullPivot as $tgl => $row) {
            $line = [Carbon::parse($tgl)->format('d-m-Y')];
            $rowTotal = 0;
            foreach ($kandangs as $k) {
                $val = $row[$k->id] ?? 0; // sel kosong -> 0, bukan blank
                $rowTotal += $val;
                $line[] = $val;
            }
            $line[] = $rowTotal;
            $s2->fromArray([$line], null, 'A' . $rowNum, true);
            $rowNum++;
        }
        $totalLine = ['Total'];
        foreach ($kandangs as $k) $totalLine[] = $totalPerKandang[$k->id] ?? 0;
        $totalLine[] = $grandTotalProduksi ?? 0;
        $s2->fromArray([$totalLine], null, 'A' . $rowNum, true);
        $s2->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->getFont()->setBold(true);
        $totalRowNum = $rowNum;
        $rowNum++;

        // Baris rata-rata per hari per kandang
        $avgLine = ["Rata-rata/hari ({$hariPembagi} hari)"];
        foreach ($kandangs as $k) $avgLine[] = $rataRataPerKandang[$k->id] ?? 0;
        $avgLine[] = $rataRataHarianProduksi ?? 0;
        $s2->fromArray([$avgLine], null, 'A' . $rowNum, true);
        $s2->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->getFont()->setItalic(true);
        $avgRowNum = $rowNum;

        foreach (range('A', $lastCol) as $col) {
            $s2->getColumnDimension($col)->setWidth(14);
        }
        // Garis pembatas untuk seluruh tabel produksi harian, termasuk baris total & rata-rata
        $this->applyBorder($s2, "A1:{$lastCol}{$avgRowNum}");
        // Semua kolom angka (kandang B s/d Total) tampil "-" kalau 0/kosong
        $colBAwal = $s2->getCell([2, 1])->getColumn();
        $this->formatAngkaAtauStrip($s2, "{$colBAwal}2:{$lastCol}{$avgRowNum}");

        // ===== SHEET 3: Penjualan =====
        $s3 = $spreadsheet->createSheet();
        $s3->setTitle('Penjualan');
        $s3->fromArray([['Tanggal', 'Pembeli', 'Jumlah Telur', 'Total Harga']], null, 'A1', true);
        $this->styleHeader($s3, 'A1:D1');
        $r = 2;
        if ($penjualans->isEmpty()) {
            // Jangan biarkan kosong total, kasih keterangan supaya tidak nampak blank
            $s3->fromArray([['-', 'Tidak ada data penjualan bulan ini', '-', 0]], null, 'A2', true);
            $r = 3;
        } else {
            foreach ($penjualans as $p) {
                $s3->fromArray([[
                    Carbon::parse($p->tanggal)->format('d-m-Y'),
                    $p->nama_pembeli ?: '-',
                    $p->jumlah_telur ?? 0,       // kosong -> 0
                    (float) ($p->total_harga ?? 0), // kosong -> 0 (mis. barter/gratis)
                ]], null, 'A' . $r, true);
                $r++;
            }
        }
        $lastRowS3 = $r - 1;
        foreach (['A' => 14, 'B' => 22, 'C' => 14, 'D' => 16] as $col => $w) {
            $s3->getColumnDimension($col)->setWidth($w);
        }
        $this->applyBorder($s3, "A1:D{$lastRowS3}");
        // Kolom Jumlah Telur -> angka+strip, kolom Total Harga -> Rupiah+strip
        $this->formatAngkaAtauStrip($s3, "C2:C{$lastRowS3}");
        $this->formatRupiahAtauStrip($s3, "D2:D{$lastRowS3}");

        // ===== SHEET 4: Pengeluaran =====
        $s4 = $spreadsheet->createSheet();
        $s4->setTitle('Pengeluaran');
        $s4->fromArray([['Tanggal', 'Keterangan', 'Jumlah']], null, 'A1', true);
        $this->styleHeader($s4, 'A1:C1');
        $r = 2;
        if ($pengeluarans->isEmpty()) {
            $s4->fromArray([['-', 'Tidak ada data pengeluaran bulan ini', 0]], null, 'A2', true);
            $r = 3;
        } else {
            foreach ($pengeluarans as $p) {
                $s4->fromArray([[
                    Carbon::parse($p->tanggal)->format('d-m-Y'),
                    $p->keterangan ?: '-',
                    (float) ($p->jumlah ?? 0), // kosong -> 0
                ]], null, 'A' . $r, true);
                $r++;
            }
        }
        $lastRowS4 = $r - 1;
        foreach (['A' => 14, 'B' => 26, 'C' => 16] as $col => $w) {
            $s4->getColumnDimension($col)->setWidth($w);
        }
        $this->applyBorder($s4, "A1:C{$lastRowS4}");
        // Kolom Jumlah -> Rupiah + strip
        $this->formatRupiahAtauStrip($s4, "C2:C{$lastRowS4}");

        $spreadsheet->setActiveSheetIndex(0);

        $filename = "Laporan_SHINDO_FARM_77_{$namaBulan}_{$tahun}.xlsx";

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}