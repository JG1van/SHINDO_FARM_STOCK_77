<?php

namespace App\Http\Controllers;

use App\Models\Kandang;
use App\Models\Telur;
use App\Models\Penjualan;
use App\Models\Pengeluaran;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $kandangs = Kandang::orderBy('nama')->get();

        // ===== 1. Produksi (pivot, total, rata-rata) — logic ditarik ke hitungProduksi() [FIX MASALAH 3] =====
        [
            'fullPivot'              => $fullPivot,
            'daysInMonth'            => $daysInMonth,
            'totalPerKandang'        => $totalPerKandang,
            'grandTotalProduksi'     => $grandTotalProduksi,
            'hariPembagi'            => $hariPembagi,
            'rataRataHarianProduksi' => $rataRataHarianProduksi,
            'rataRataPerKandang'     => $rataRataPerKandang,
            'chartProduksiPerKandang' => $chartProduksiPerKandang,
        ] = $this->hitungProduksi($bulan, $tahun, $kandangs);

        // ===== 2. KPI Cards =====
        $totalAyam   = $kandangs->sum(fn ($k) => $k->jantan + $k->betina);
        $totalJantan = $kandangs->sum('jantan');
        $totalBetina = $kandangs->sum('betina');

        // Ringkasan finansial: FIX MASALAH 6 — pakai pendekatan collection->sum() sama seperti
        // exportExcel(), supaya konsisten dan collection-nya bisa dipakai ulang kalau perlu detail transaksi.
        $penjualans   = Penjualan::whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->get();
        $pengeluarans = Pengeluaran::whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->get();

        $omzetBulanIni        = $penjualans->sum(fn ($p) => $p->total_harga ?? 0);
        $telurTerjualBulanIni = (int) $penjualans->sum(fn ($p) => $p->jumlah_telur ?? 0);
        $bonusBulanIni        = (int) $penjualans->sum(fn ($p) => $p->bonus ?? 0);
        $pengeluaranBulanIni  = $pengeluarans->sum(fn ($p) => $p->jumlah ?? 0);

        $labaBersih = $omzetBulanIni - $pengeluaranBulanIni;

        // Stok belum terjual memperhitungkan telur yang keluar sebagai bonus juga
        $stokBelumTerjual = $grandTotalProduksi - $telurTerjualBulanIni - $bonusBulanIni;

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

        // ===== 4. Perbandingan antar kandang (produktivitas) =====
        // FIX MASALAH 5: field jantan, betina, rasio_label, rasio_warn DIHAPUS karena tidak pernah
        // dirender di view — mengurangi komputasi percuma. 'kandang_id' ditambahkan (bukan dihapus
        // dari daftar semula) supaya baris tabel di view bisa disinkronkan dengan checkbox filter
        // kandang (FIX MASALAH 4).
        $produktivitasKandang = $kandangs->map(function ($k) use ($totalPerKandang, $rataRataPerKandang) {
            return [
                'kandang_id'       => $k->id,
                'nama'             => $k->nama,
                'jenis_ayam'       => $k->jenis_ayam,
                'total_telur'      => $totalPerKandang[$k->id] ?? 0,
                'rata_rata_harian' => $rataRataPerKandang[$k->id] ?? 0,
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

        // ===== 7. Aktivitas terbaru =====
        // FIX MASALAH 1: ketiga query sekarang ikut filter whereMonth/whereYear($bulan, $tahun),
        // konsisten dengan bagian dashboard lainnya.
        // FIX MASALAH 2: ketiga jenis aktivitas digabung jadi satu collection dengan struktur
        // seragam, diurutkan berdasarkan created_at asli (bukan ditumpuk per jenis), lalu diambil
        // 5 teratas saja -> dikirim ke view sebagai satu variabel $aktivitasTerbaru.
        $penjualanTerbaru = Penjualan::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->latest('created_at')
            ->limit(5)
            ->get();

        $pengeluaranTerbaru = Pengeluaran::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->latest('created_at')
            ->limit(5)
            ->get();

        $telurTerbaru = Telur::query()
            ->join('kandang', 'kandang.id', '=', 'telur.kandang_id')
            ->whereMonth('telur.tanggal', $bulan)
            ->whereYear('telur.tanggal', $tahun)
            ->orderByDesc('telur.created_at')
            ->limit(5)
            ->get(['telur.*', 'kandang.nama as kandang_nama']);

        $aktivitasTerbaru = $this->gabungkanAktivitasTerbaru($penjualanTerbaru, $pengeluaranTerbaru, $telurTerbaru);

        return view('dashboard.index', compact(
            'kandangs',
            'fullPivot',
            'bulan',
            'tahun',
            'totalPerKandang',
            'grandTotalProduksi',
            'rataRataHarianProduksi',
            'rataRataPerKandang',
            'chartProduksiPerKandang',
            'hariPembagi',
            'totalAyam',
            'totalJantan',
            'totalBetina',
            'omzetBulanIni',
            'telurTerjualBulanIni',
            'bonusBulanIni',
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
            'aktivitasTerbaru'
        ));
    }

    /**
     * FIX MASALAH 3: logic pivot produksi (grid tanggal x kandang, total per kandang, grand total,
     * rata-rata harian) yang sebelumnya di-duplikat persis di index() dan exportExcel() sekarang
     * ditarik ke satu private method ini. Dipanggil dari kedua method supaya tidak bisa divergen.
     *
     * Juga menghasilkan $chartProduksiPerKandang: produksi harian per kandang (array numerik,
     * urutan sama dengan tanggal di $fullPivot) — dipakai di view untuk menghitung ulang grafik
     * Tren saat user uncheck kandang tertentu (FIX MASALAH 4).
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

        // Produksi harian per kandang, urutan sama seperti tanggal di $fullPivot (dipakai FIX MASALAH 4)
        $chartProduksiPerKandang = [];
        foreach ($kandangs as $k) {
            $chartProduksiPerKandang[$k->id] = [];
            foreach ($fullPivot as $row) {
                $chartProduksiPerKandang[$k->id][] = (int) ($row[$k->id] ?? 0);
            }
        }

        return compact(
            'fullPivot',
            'daysInMonth',
            'totalPerKandang',
            'grandTotalProduksi',
            'hariPembagi',
            'rataRataHarianProduksi',
            'rataRataPerKandang',
            'chartProduksiPerKandang'
        );
    }

    /**
     * FIX MASALAH 2: gabungkan penjualan/pengeluaran/produksi telur jadi satu feed kronologis
     * sungguhan — struktur seragam (tipe, deskripsi, jumlah, tanggal, created_at), diurutkan
     * berdasarkan created_at descending lintas jenis, lalu diambil 5 teratas saja.
     */
    private function gabungkanAktivitasTerbaru(
        Collection $penjualanTerbaru,
        Collection $pengeluaranTerbaru,
        Collection $telurTerbaru
    ): Collection {
        $aktivitas = collect();

        foreach ($penjualanTerbaru as $p) {
            $bonusLabel = ($p->bonus ?? 0) > 0 ? " (+{$p->bonus} bonus)" : '';
            $aktivitas->push([
                'tipe'       => 'penjualan',
                'deskripsi'  => "Penjualan {$p->jumlah_telur} butir ke " . ($p->nama_pembeli ?: '-') . $bonusLabel,
                'jumlah'     => 'Rp ' . number_format($p->total_harga ?? 0, 0, ',', '.'),
                'tanggal'    => $p->tanggal,
                'created_at' => $p->created_at,
            ]);
        }

        foreach ($pengeluaranTerbaru as $p) {
            $aktivitas->push([
                'tipe'       => 'pengeluaran',
                'deskripsi'  => 'Pengeluaran ' . ($p->keterangan ?: '-'),
                'jumlah'     => 'Rp ' . number_format($p->jumlah ?? 0, 0, ',', '.'),
                'tanggal'    => $p->tanggal,
                'created_at' => $p->created_at,
            ]);
        }

        foreach ($telurTerbaru as $t) {
            $aktivitas->push([
                'tipe'       => 'produksi',
                'deskripsi'  => "Input produksi {$t->jumlah_butir} butir di kandang " . ($t->kandang_nama ?: '-'),
                'jumlah'     => null,
                'tanggal'    => $t->tanggal,
                'created_at' => $t->created_at,
            ]);
        }

        return $aktivitas->sortByDesc('created_at')->take(5)->values();
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

        // FIX MASALAH 3: pakai method bersama, tidak lagi duplikat dari index()
        [
            'fullPivot'              => $fullPivot,
            'totalPerKandang'        => $totalPerKandang,
            'grandTotalProduksi'     => $grandTotalProduksi,
            'hariPembagi'            => $hariPembagi,
            'rataRataHarianProduksi' => $rataRataHarianProduksi,
            'rataRataPerKandang'     => $rataRataPerKandang,
        ] = $this->hitungProduksi($bulan, $tahun, $kandangs);

        $penjualans = Penjualan::whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->orderBy('tanggal')->get();
        $pengeluarans = Pengeluaran::whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->orderBy('tanggal')->get();

        // PENTING: pakai ?? 0 supaya nilai null dari database (mis. total_harga kosong
        // karena barter/gratis) tidak bikin sum() jadi meleset dan tidak nongol kosong di Excel.
        $omzetBulanIni = $penjualans->sum(fn ($p) => $p->total_harga ?? 0);
        $telurTerjualBulanIni = $penjualans->sum(fn ($p) => $p->jumlah_telur ?? 0);
        $bonusBulanIni = $penjualans->sum(fn ($p) => $p->bonus ?? 0);
        $pengeluaranBulanIni = $pengeluarans->sum(fn ($p) => $p->jumlah ?? 0);
        $labaBersih = $omzetBulanIni - $pengeluaranBulanIni;
        $stokBelumTerjual = $grandTotalProduksi - $telurTerjualBulanIni - $bonusBulanIni;

        $namaBulan = Carbon::create()->month($bulan)->translatedFormat('F');

        // FIX MASALAH 7: exportExcel() sekarang jadi orchestrator singkat — tiap sheet dibuat
        // oleh private method sendiri-sendiri.
        $data = compact(
            'kandangs',
            'fullPivot',
            'totalPerKandang',
            'grandTotalProduksi',
            'hariPembagi',
            'rataRataHarianProduksi',
            'rataRataPerKandang',
            'penjualans',
            'pengeluarans',
            'omzetBulanIni',
            'telurTerjualBulanIni',
            'bonusBulanIni',
            'pengeluaranBulanIni',
            'labaBersih',
            'stokBelumTerjual',
            'namaBulan',
            'tahun'
        );

        $spreadsheet = new Spreadsheet();

        $this->buatSheetRingkasan($spreadsheet, $data);
        $this->buatSheetProduksiHarian($spreadsheet, $data);
        $this->buatSheetPenjualan($spreadsheet, $data);
        $this->buatSheetPengeluaran($spreadsheet, $data);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = "Laporan_SHINDO_FARM_77_{$namaBulan}_{$tahun}.xlsx";

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * FIX MASALAH 7: Sheet 1 — Ringkasan.
     */
    private function buatSheetRingkasan(Spreadsheet $spreadsheet, array $d): void
    {
        $s1 = $spreadsheet->getActiveSheet();
        $s1->setTitle('Ringkasan');
        $s1->fromArray([
            ["Ringkasan Bulan {$d['namaBulan']} {$d['tahun']}"],
            [],
            ['Total Ayam', $d['kandangs']->sum(fn ($k) => $k->jantan + $k->betina) ?? 0],
            ['Jantan', $d['kandangs']->sum('jantan') ?? 0],
            ['Betina', $d['kandangs']->sum('betina') ?? 0],
            ['Produksi Telur (butir)', $d['grandTotalProduksi'] ?? 0],
            ['Rata-rata Produksi/hari (butir)', $d['rataRataHarianProduksi'] ?? 0],
            ['Penjualan (Rp)', $d['omzetBulanIni'] ?? 0],
            ['Telur Terjual (butir)', $d['telurTerjualBulanIni'] ?? 0],
            ['Telur Bonus (butir)', $d['bonusBulanIni'] ?? 0],
            ['Pengeluaran (Rp)', $d['pengeluaranBulanIni'] ?? 0],
            ['Uang Tersedia (Rp)', $d['labaBersih'] ?? 0],
            ['Belum Terjual (butir)', $d['stokBelumTerjual'] ?? 0],
        ], null, 'A1', true); // true = strict null comparison, supaya nilai 0 tidak ikut dianggap kosong
        $s1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $s1->getStyle('A3:A13')->getFont()->setBold(true);
        $s1->getColumnDimension('A')->setWidth(30);
        $s1->getColumnDimension('B')->setWidth(18);
        // Garis pembatas untuk tabel ringkasan (baris 3 s/d 13)
        $this->applyBorder($s1, 'A3:B13');
        // Baris Rupiah (Penjualan, Pengeluaran, Uang Tersedia) pakai format Rp + strip
        $this->formatRupiahAtauStrip($s1, 'B8:B8');
        $this->formatRupiahAtauStrip($s1, 'B11:B12');
        // Baris angka biasa (Total Ayam s/d Rata-rata Produksi, Telur Terjual, Telur Bonus, Belum Terjual) pakai format strip
        $this->formatAngkaAtauStrip($s1, 'B3:B7');
        $this->formatAngkaAtauStrip($s1, 'B9:B10');
        $this->formatAngkaAtauStrip($s1, 'B13:B13');
    }

    /**
     * FIX MASALAH 7: Sheet 2 — Produksi per Kandang (pivot harian).
     */
    private function buatSheetProduksiHarian(Spreadsheet $spreadsheet, array $d): void
    {
        $kandangs = $d['kandangs'];
        $fullPivot = $d['fullPivot'];
        $totalPerKandang = $d['totalPerKandang'];
        $grandTotalProduksi = $d['grandTotalProduksi'];
        $hariPembagi = $d['hariPembagi'];
        $rataRataPerKandang = $d['rataRataPerKandang'];
        $rataRataHarianProduksi = $d['rataRataHarianProduksi'];

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
    }

    /**
     * FIX MASALAH 7: Sheet 3 — Penjualan.
     */
    private function buatSheetPenjualan(Spreadsheet $spreadsheet, array $d): void
    {
        $penjualans = $d['penjualans'];

        $s3 = $spreadsheet->createSheet();
        $s3->setTitle('Penjualan');
        $s3->fromArray([['Tanggal', 'Pembeli', 'Jumlah Telur', 'Bonus', 'Total Harga']], null, 'A1', true);
        $this->styleHeader($s3, 'A1:E1');
        $r = 2;
        if ($penjualans->isEmpty()) {
            // Jangan biarkan kosong total, kasih keterangan supaya tidak nongol blank
            $s3->fromArray([['-', 'Tidak ada data penjualan bulan ini', '-', 0, 0]], null, 'A2', true);
            $r = 3;
        } else {
            foreach ($penjualans as $p) {
                $s3->fromArray([[
                    Carbon::parse($p->tanggal)->format('d-m-Y'),
                    $p->nama_pembeli ?: '-',
                    $p->jumlah_telur ?? 0,       // kosong -> 0
                    $p->bonus ?? 0,              // kosong -> 0
                    (float) ($p->total_harga ?? 0), // kosong -> 0 (mis. barter/gratis)
                ]], null, 'A' . $r, true);
                $r++;
            }
        }
        $lastRowS3 = $r - 1;
        // Baris total di bawah tabel Penjualan
        $rowNumS3Total = $lastRowS3 + 1;
        $s3->fromArray([[
            'Total', '', $d['telurTerjualBulanIni'] ?? 0, $d['bonusBulanIni'] ?? 0, (float) ($d['omzetBulanIni'] ?? 0)
        ]], null, 'A' . $rowNumS3Total, true);
        $s3->getStyle("A{$rowNumS3Total}:E{$rowNumS3Total}")->getFont()->setBold(true);

        foreach (['A' => 14, 'B' => 22, 'C' => 14, 'D' => 12, 'E' => 16] as $col => $w) {
            $s3->getColumnDimension($col)->setWidth($w);
        }
        $this->applyBorder($s3, "A1:E{$rowNumS3Total}");
        // Kolom Jumlah Telur & Bonus -> angka+strip, kolom Total Harga -> Rupiah+strip
        $this->formatAngkaAtauStrip($s3, "C2:D{$rowNumS3Total}");
        $this->formatRupiahAtauStrip($s3, "E2:E{$rowNumS3Total}");
    }

    /**
     * FIX MASALAH 7: Sheet 4 — Pengeluaran.
     */
    private function buatSheetPengeluaran(Spreadsheet $spreadsheet, array $d): void
    {
        $pengeluarans = $d['pengeluarans'];

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
    }
}