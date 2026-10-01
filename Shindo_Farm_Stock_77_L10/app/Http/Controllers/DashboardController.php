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
    public const ALLOWED_ROLES = ['super_admin', 'admin', 'staf_ayam', 'staf_keuangan'];

    public const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function index(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $kandangs = Kandang::orderBy('nama')->get();

        // =====================================================================
        // A. RINGKASAN KESELURUHAN (tidak terikat bulan)
        // =====================================================================
        $totalAyam   = $kandangs->sum(fn ($k) => $k->jantan + $k->betina);
        $totalJantan = $kandangs->sum('jantan');
        $totalBetina = $kandangs->sum('betina');

        $omzetTotal       = (float) Penjualan::sum('total_harga');
        $pengeluaranTotal = (float) Pengeluaran::sum('jumlah');
        $uangTersedia     = $omzetTotal - $pengeluaranTotal;

        // =====================================================================
        // B. KARTU TELUR + RINCIAN DATA BULAN (mengikuti bulan/tahun terpilih)
        // =====================================================================
        [
            'fullPivot'               => $fullPivot,
            'daysInMonth'             => $daysInMonth,
            'totalPerKandang'         => $totalPerKandang,
            'grandTotalProduksi'      => $grandTotalProduksi,
            'hariPembagi'             => $hariPembagi,
            'rataRataHarianProduksi'  => $rataRataHarianProduksi,
            'rataRataPerKandang'      => $rataRataPerKandang,
            'chartProduksiPerKandang' => $chartProduksiPerKandang,
        ] = $this->hitungProduksi($bulan, $tahun, $kandangs);

        $ringkasanBulan = $this->ringkasanTelur($bulan, $tahun);
        $telurTerjualBulanIni = $ringkasanBulan['terjual'];
        $bonusBulanIni        = $ringkasanBulan['bonus'];

        // Belum terjual per bulan: produksi - terjual - bonus (bulan terpilih saja)
        $stokBelumTerjual = $grandTotalProduksi - $telurTerjualBulanIni - $bonusBulanIni;

        // Keuangan bulan terpilih (tampil di dalam kartu Telur)
        $penjualanBulanIni   = $ringkasanBulan['penjualan'];
        $pengeluaranBulanIni = $ringkasanBulan['pengeluaran'];
        $uangBulanIni        = $ringkasanBulan['uang'];

        // Bulan sebelumnya (klik kartu -> tampil bulan kemarin)
        $tglLalu = Carbon::createFromDate($tahun, $bulan, 1)->subMonthNoOverflow();
        $ringkasanLalu = $this->ringkasanTelur($tglLalu->month, $tglLalu->year);
        $labelBulan     = self::NAMA_BULAN[$bulan] . ' ' . $tahun;
        $labelBulanLalu = self::NAMA_BULAN[$tglLalu->month] . ' ' . $tglLalu->year;

        // Grafik tren harian bulan terpilih
        $penjualanHarian = Penjualan::selectRaw('tanggal, SUM(total_harga) as total')
            ->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)
            ->groupBy('tanggal')->pluck('total', 'tanggal');

        $pengeluaranHarian = Pengeluaran::selectRaw('tanggal, SUM(jumlah) as total')
            ->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)
            ->groupBy('tanggal')->pluck('total', 'tanggal');

        $chartLabels = [];
        $chartProduksi = [];
        $chartPenjualan = [];
        $chartPengeluaran = [];
        foreach ($fullPivot as $tgl => $row) {
            $chartLabels[]      = Carbon::parse($tgl)->format('d');
            $chartProduksi[]    = (int) array_sum($row);
            $chartPenjualan[]   = (float) ($penjualanHarian[$tgl] ?? 0);
            $chartPengeluaran[] = (float) ($pengeluaranHarian[$tgl] ?? 0);
        }

        // Produktivitas per kandang
        $produktivitasKandang = $kandangs->map(function ($k) use ($totalPerKandang, $rataRataPerKandang, $daysInMonth) {
            $betina = (int) ($k->betina ?? 0);
            return [
                'kandang_id'       => $k->id,
                'nama'             => $k->nama,
                'jenis_ayam'       => $k->jenis_ayam,
                'jantan'           => (int) ($k->jantan ?? 0),
                'betina'           => $betina,
                'total_telur'      => $totalPerKandang[$k->id] ?? 0,
                'rata_rata_harian' => $rataRataPerKandang[$k->id] ?? 0,
                'target'           => $betina * $daysInMonth, // target bulanan = betina x jumlah hari
            ];
        })
            ->filter(fn ($p) => ($p['jantan'] + $p['betina']) > 0)
            ->map(function ($p) {
                $p['persen'] = $p['target'] > 0 ? round($p['total_telur'] / $p['target'] * 100, 1) : 0;
                $p['persen_warna'] = $p['persen'] >= 100 ? '#198754'
                    : ($p['persen'] >= 70 ? '#e8871e' : ($p['persen'] >= 50 ? '#fd7e14' : '#dc3545'));
                return $p;
            })
            ->sortByDesc('total_telur')->values();

        // Top 5 pembeli bulan terpilih (nama mirip digabung, butir 0 diabaikan)
        $topPembeli = $this->hitungTopPembeli($bulan, $tahun);

        // =====================================================================
        // C. RINCIAN KESELURUHAN (tidak terikat bulan)
        // =====================================================================
        $breakdownPengeluaran = Pengeluaran::selectRaw('keterangan, SUM(jumlah) as total')
            ->groupBy('keterangan')
            ->orderByDesc('total')
            ->get();

        $penjualanTerbaru = Penjualan::latest('created_at')->limit(5)->get();
        $pengeluaranTerbaru = Pengeluaran::latest('created_at')->limit(5)->get();
        $telurTerbaru = Telur::query()
            ->join('kandang', 'kandang.id', '=', 'telur.kandang_id')
            ->orderByDesc('telur.created_at')
            ->limit(5)
            ->get(['telur.*', 'kandang.nama as kandang_nama']);

        $aktivitasTerbaru = $this->gabungkanAktivitasTerbaru($penjualanTerbaru, $pengeluaranTerbaru, $telurTerbaru);

        $namaBulanList = self::NAMA_BULAN;

        return view('dashboard.index', compact(
            'kandangs',
            'bulan',
            'tahun',
            'namaBulanList',
            'labelBulan',
            'labelBulanLalu',
            // keseluruhan
            'totalAyam',
            'totalJantan',
            'totalBetina',
            'omzetTotal',
            'pengeluaranTotal',
            'uangTersedia',
            // bulan terpilih
            'stokBelumTerjual',
            'penjualanBulanIni',
            'pengeluaranBulanIni',
            'uangBulanIni',
            'fullPivot',
            'totalPerKandang',
            'grandTotalProduksi',
            'rataRataHarianProduksi',
            'rataRataPerKandang',
            'chartProduksiPerKandang',
            'hariPembagi',
            'telurTerjualBulanIni',
            'bonusBulanIni',
            'ringkasanLalu',
            'chartLabels',
            'chartProduksi',
            'chartPenjualan',
            'chartPengeluaran',
            'produktivitasKandang',
            'topPembeli',
            // keseluruhan (rincian)
            'breakdownPengeluaran',
            'aktivitasTerbaru'
        ));
    }

    /**
     * Top 5 pembeli bulan terpilih.
     * - Transaksi dengan jumlah telur 0 tidak dihitung dan tidak ditampilkan.
     * - Nama yang mirip (beda huruf besar/kecil, spasi, tanda baca, atau salah ketik ringan) digabung.
     *   Nama yang ditampilkan = variasi dengan butir terbanyak.
     */
    private function hitungTopPembeli(int $bulan, int $tahun, int $limit = 5): Collection
    {
        $rows = Penjualan::selectRaw('nama_pembeli, SUM(total_harga) as total_belanja, SUM(jumlah_telur) as total_butir')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->where('jumlah_telur', '>', 0)
            ->groupBy('nama_pembeli')
            ->get()
            ->sortByDesc('total_butir');

        $groups = [];
        foreach ($rows as $r) {
            $nama = trim((string) $r->nama_pembeli) ?: '-';
            $key  = $this->normalisasiNama($nama);

            $target = null;
            foreach (array_keys($groups) as $gk) {
                if ($this->namaMirip($key, (string) $gk)) {
                    $target = $gk;
                    break;
                }
            }
            if ($target === null) {
                $groups[$key] = ['nama' => $nama, 'butir' => 0, 'belanja' => 0.0];
                $target = $key;
            }
            $groups[$target]['butir']   += (int) $r->total_butir;
            $groups[$target]['belanja'] += (float) $r->total_belanja;
        }

        return collect($groups)
            ->map(fn ($g) => (object) [
                'nama_pembeli'  => $g['nama'],
                'total_butir'   => $g['butir'],
                'total_belanja' => $g['belanja'],
            ])
            ->filter(fn ($g) => $g->total_butir > 0)
            ->sortByDesc('total_belanja')
            ->take($limit)
            ->values();
    }

    private function normalisasiNama(string $nama): string
    {
        $nama = mb_strtolower($nama);
        $nama = preg_replace('/[^\p{L}\p{N}\s]/u', '', $nama); // buang tanda baca
        return trim(preg_replace('/\s+/u', ' ', $nama));        // rapikan spasi
    }

    private function namaMirip(string $a, string $b): bool
    {
        if ($a === $b) return true;
        if ($a === '' || $b === '' || $a === '-' || $b === '-') return false;

        // Angka beda = orang beda (mis. "Warung 1" vs "Warung 2")
        if (preg_replace('/\D/', '', $a) !== preg_replace('/\D/', '', $b)) return false;

        similar_text($a, $b, $persen);
        return $persen >= 85;
    }

    /**
     * Jumlah hari pembagi rata-rata: bulan berjalan = tanggal hari ini, bulan lain = jumlah hari dalam bulan.
     */
    private function hariPembagi(int $bulan, int $tahun): int
    {
        $isBulanIni = ($bulan == now()->month && $tahun == now()->year);
        return $isBulanIni ? now()->day : Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;
    }

    /**
     * Ringkasan telur satu bulan: produksi, terjual, bonus, rata-rata harian.
     */
    private function ringkasanTelur(int $bulan, int $tahun): array
    {
        $produksi = (int) Telur::whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->sum('jumlah_butir');

        $jual = Penjualan::selectRaw('COALESCE(SUM(jumlah_telur),0) as terjual, COALESCE(SUM(bonus),0) as bonus, COALESCE(SUM(total_harga),0) as omzet')
            ->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)
            ->first();

        $hari = $this->hariPembagi($bulan, $tahun);

        $omzet  = (float) ($jual->omzet ?? 0);
        $keluar = (float) Pengeluaran::whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->sum('jumlah');

        return [
            'penjualan'   => $omzet,
            'pengeluaran' => $keluar,
            'uang'        => $omzet - $keluar,
            'produksi'    => $produksi,
            'terjual'     => (int) ($jual->terjual ?? 0),
            'bonus'       => (int) ($jual->bonus ?? 0),
            'belum_terjual' => $produksi - (int) ($jual->terjual ?? 0) - (int) ($jual->bonus ?? 0),
            'rata_harian' => $hari > 0 ? round($produksi / $hari, 1) : 0,
            'hari'        => $hari,
        ];
    }

    /**
     * Pivot produksi (tanggal x kandang), total, rata-rata. Dipakai index() dan exportExcel().
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

        $hariPembagi = $this->hariPembagi($bulan, $tahun);

        $rataRataHarianProduksi = $hariPembagi > 0 ? round($grandTotalProduksi / $hariPembagi, 1) : 0;

        $rataRataPerKandang = [];
        foreach ($kandangs as $k) {
            $rataRataPerKandang[$k->id] = $hariPembagi > 0
                ? round(($totalPerKandang[$k->id] ?? 0) / $hariPembagi, 1)
                : 0;
        }

        // Produksi harian per kandang (untuk hitung ulang grafik Tren saat checkbox berubah)
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
     * Gabungkan penjualan/pengeluaran/produksi jadi satu feed kronologis, ambil 5 teratas.
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

    // =========================================================================
    // EXPORT EXCEL (tetap per bulan terpilih)
    // =========================================================================

    private function applyBorder($sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private function styleHeader($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E0E0E0');
    }

    private function formatAngkaAtauStrip($sheet, string $range): void
    {
        $sheet->getStyle($range)->getNumberFormat()
            ->setFormatCode('#,##0;-#,##0;"-"');
    }

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

        // ?? 0 supaya nilai null dari database (mis. total_harga kosong karena barter/gratis) tidak meleset
        $omzetBulanIni = $penjualans->sum(fn ($p) => $p->total_harga ?? 0);
        $telurTerjualBulanIni = $penjualans->sum(fn ($p) => $p->jumlah_telur ?? 0);
        $bonusBulanIni = $penjualans->sum(fn ($p) => $p->bonus ?? 0);
        $pengeluaranBulanIni = $pengeluarans->sum(fn ($p) => $p->jumlah ?? 0);
        $labaBersih = $omzetBulanIni - $pengeluaranBulanIni;
        $stokBelumTerjual = $grandTotalProduksi - $telurTerjualBulanIni - $bonusBulanIni;

        $namaBulan = self::NAMA_BULAN[$bulan];

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
        ], null, 'A1', true);
        $s1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $s1->getStyle('A3:A13')->getFont()->setBold(true);
        $s1->getColumnDimension('A')->setWidth(30);
        $s1->getColumnDimension('B')->setWidth(18);
        $this->applyBorder($s1, 'A3:B13');
        $this->formatRupiahAtauStrip($s1, 'B8:B8');
        $this->formatRupiahAtauStrip($s1, 'B11:B12');
        $this->formatAngkaAtauStrip($s1, 'B3:B7');
        $this->formatAngkaAtauStrip($s1, 'B9:B10');
        $this->formatAngkaAtauStrip($s1, 'B13:B13');
    }

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
                $val = $row[$k->id] ?? 0;
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

        $avgLine = ["Rata-rata/hari ({$hariPembagi} hari)"];
        foreach ($kandangs as $k) $avgLine[] = $rataRataPerKandang[$k->id] ?? 0;
        $avgLine[] = $rataRataHarianProduksi ?? 0;
        $s2->fromArray([$avgLine], null, 'A' . $rowNum, true);
        $s2->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->getFont()->setItalic(true);
        $avgRowNum = $rowNum;

        foreach (range('A', $lastCol) as $col) {
            $s2->getColumnDimension($col)->setWidth(14);
        }
        $this->applyBorder($s2, "A1:{$lastCol}{$avgRowNum}");
        $colBAwal = $s2->getCell([2, 1])->getColumn();
        $this->formatAngkaAtauStrip($s2, "{$colBAwal}2:{$lastCol}{$avgRowNum}");
    }

    private function buatSheetPenjualan(Spreadsheet $spreadsheet, array $d): void
    {
        $penjualans = $d['penjualans'];

        $s3 = $spreadsheet->createSheet();
        $s3->setTitle('Penjualan');
        $s3->fromArray([['Tanggal', 'Pembeli', 'Jumlah Telur', 'Bonus', 'Total Harga']], null, 'A1', true);
        $this->styleHeader($s3, 'A1:E1');
        $r = 2;
        if ($penjualans->isEmpty()) {
            $s3->fromArray([['-', 'Tidak ada data penjualan bulan ini', '-', 0, 0]], null, 'A2', true);
            $r = 3;
        } else {
            foreach ($penjualans as $p) {
                $s3->fromArray([[
                    Carbon::parse($p->tanggal)->format('d-m-Y'),
                    $p->nama_pembeli ?: '-',
                    $p->jumlah_telur ?? 0,
                    $p->bonus ?? 0,
                    (float) ($p->total_harga ?? 0),
                ]], null, 'A' . $r, true);
                $r++;
            }
        }
        $lastRowS3 = $r - 1;
        $rowNumS3Total = $lastRowS3 + 1;
        $s3->fromArray([[
            'Total', '', $d['telurTerjualBulanIni'] ?? 0, $d['bonusBulanIni'] ?? 0, (float) ($d['omzetBulanIni'] ?? 0)
        ]], null, 'A' . $rowNumS3Total, true);
        $s3->getStyle("A{$rowNumS3Total}:E{$rowNumS3Total}")->getFont()->setBold(true);

        foreach (['A' => 14, 'B' => 22, 'C' => 14, 'D' => 12, 'E' => 16] as $col => $w) {
            $s3->getColumnDimension($col)->setWidth($w);
        }
        $this->applyBorder($s3, "A1:E{$rowNumS3Total}");
        $this->formatAngkaAtauStrip($s3, "C2:D{$rowNumS3Total}");
        $this->formatRupiahAtauStrip($s3, "E2:E{$rowNumS3Total}");
    }

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
                    (float) ($p->jumlah ?? 0),
                ]], null, 'A' . $r, true);
                $r++;
            }
        }
        $lastRowS4 = $r - 1;
        foreach (['A' => 14, 'B' => 26, 'C' => 16] as $col => $w) {
            $s4->getColumnDimension($col)->setWidth($w);
        }
        $this->applyBorder($s4, "A1:C{$lastRowS4}");
        $this->formatRupiahAtauStrip($s4, "C2:C{$lastRowS4}");
    }
}