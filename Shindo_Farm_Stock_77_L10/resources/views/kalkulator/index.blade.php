@extends('layouts.app')

@section('title', 'Kalkulator Pakan & Laba - SHINDO FARM 77')

@section('content')
    <div class="page-header-neo d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="fw-bold mb-0">Kalkulator Pakan &amp; Laba</h2>
        <div class="d-flex gap-2">
            <select id="filterBulan" class="form-select form-control-neo" style="max-width:160px">
                @foreach (range(1, 12) as $b)
                    <option value="{{ $b }}" {{ $b == $bulan ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
            <select id="filterTahun" class="form-select form-control-neo" style="max-width:120px">
                @foreach (range(now()->year, now()->year - 3) as $t)
                    <option value="{{ $t }}" {{ $t == $tahun ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="alert alert-secondary small mb-3" style="padding:10px 12px;">
        Beberapa angka sekarang <strong>otomatis mengalir antar tab</strong>, supaya nggak perlu ketik ulang:
        Total Produksi &amp; Jumlah Hari (Tab 1) → Tab 2 &amp; Tab 5, Biaya Pakan (Tab 2) → Tab 5, Tab 6 &amp;
        Tab 7, dan Pemasukan/Pengeluaran/Harga Jual (Tab 4) → Tab 5 &amp; Tab 6. Kolom yang otomatis ini
        tetap bisa kamu ketik manual kalau mau simulasi angka lain — begitu diedit manual, kolom itu
        <strong>berhenti ikut otomatis</strong> sampai kamu klik link "pakai otomatis lagi" di bawahnya.
        Semua tab dihitung sejak halaman dibuka, jadi pindah-pindah tab tidak akan bikin hasil kosong.
    </div>

	<ul class="nav flex-nowrap overflow-auto mb-3" id="kalkTabNav" role="tablist" style="white-space:nowrap;">
        <li class="nav-item"><button type="button" class="nav-link active" data-tab="tab1">1. Produksi Harian</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-tab="tab2">2. Efisiensi Pakan</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-tab="tab3">3. Kesehatan Kawanan</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-tab="tab4">4. Data Transaksi</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-tab="tab5">5. HPP &amp; BEP</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-tab="tab6">6. Laba Bersih</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-tab="tab7">7. Proyeksi Bulan Depan</button></li>
    </ul>

    <!-- =====================================================================
         TAB 1 — PRODUKSI HARIAN
         Dihitung: total & rata-rata produksi telur harian per kandang.
         Dipakai untuk: sumber otomatis Total Produksi di Tab 2 & Tab 5.
    ====================================================================== -->
    <div class="kalk-tab-pane" id="tab1">
        <div class="alert alert-info small mb-3" style="padding:10px 12px;">
            <strong>Yang dihitung di sini:</strong> total &amp; rata-rata produksi telur harian per kandang
            selama sebulan. <strong>Dipakai untuk:</strong> jadi sumber otomatis "Total Produksi Telur" di
            Tab 2 (Efisiensi Pakan) dan Tab 5 (HPP &amp; BEP).
        </div>
        <div class="card-neo p-3 mb-3">
            <div class="mb-3 p-2" style="border: 1px dashed var(--color-border); border-radius: var(--radius);">
                <div class="fw-bold mb-2 small">Kandang dihitung ke Total &amp; Rata-rata:</div>
                <div class="d-flex flex-wrap gap-3">
                    @foreach ($kandangs as $k)
                        <div class="form-check">
                            <input class="form-check-input t1-chk-kandang" type="checkbox"
                                value="{{ $k->id }}" id="t1ChkKandang{{ $k->id }}" checked>
                            <label class="form-check-label small" for="t1ChkKandang{{ $k->id }}">{{ $k->nama }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="alert alert-warning small mt-2 mb-0" style="padding:8px 10px;">
                    ⚠️ Checkbox kandang di sini mengubah Total &amp; Rata-rata Produksi di tabel ini —
                    dan angka itulah yang otomatis dikirim ke Tab 2 &amp; Tab 5 sebagai Total Produksi.
                    Tidak mempengaruhi Telur Terjual/Bonus di Dashboard (beda sumber tabel).
                </div>
            </div>

            <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                <label class="form-label fw-bold mb-0" for="t1HariInput">Jumlah Hari dalam 1 Bulan</label>
                <input type="number" class="form-control form-control-neo" id="t1HariInput" min="1" max="31"
                    style="max-width:100px" value="{{ $daysInMonth }}">
                <small class="text-muted">Dipakai hanya untuk Rata-rata/hari di tabel Tab 1 ini. Default ikut kalender ({{ $daysInMonth }} hari).</small>
            </div>

            <div class="pivot-scale-wrapper" id="pivotWrapperKalk">
                <table class="table table-neo table-sm align-middle mb-0" id="pivotTableKalk">
                    <thead>
                        <tr>
                            <th class="text-center">Tanggal</th>
                            @foreach ($kandangs as $k)
                                <th class="text-center t1-th-kandang" data-kandang-id="{{ $k->id }}">{{ $k->nama }}</th>
                            @endforeach
                            <th class="text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($fullPivot as $tgl => $row)
                            <tr data-tgl="{{ $tgl }}">
                                <td class="text-center">{{ \Carbon\Carbon::parse($tgl)->format('d M') }}</td>
                                @php $rowTotal = 0; @endphp
                                @foreach ($kandangs as $k)
                                    @php
                                        $val = $row[$k->id] ?? 0;
                                        $rowTotal += $val;
                                    @endphp
                                    <td class="text-center t1-cell-kandang" data-kandang-id="{{ $k->id }}" data-value="{{ $val }}">{{ $val ?: '-' }}</td>
                                @endforeach
                                <td class="text-center fw-bold t1-cell-row-total" data-value="{{ $rowTotal }}">{{ $rowTotal }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $kandangs->count() + 2 }}" class="text-center py-3">Belum ada data produksi bulan ini</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td class="text-center">Total</td>
                            @foreach ($kandangs as $k)
                                <td class="text-center t1-cell-kandang-total" data-kandang-id="{{ $k->id }}">{{ number_format($totalPerKandang[$k->id] ?? 0) }}</td>
                            @endforeach
                            <td class="text-center" id="t1FooterGrandTotal">{{ number_format($grandTotalProduksi) }}</td>
                        </tr>
                        <tr class="fst-italic text-muted">
                            <td class="text-center" id="t1LabelRataRata">Rata-rata/hari ({{ $daysInMonth }} hari)</td>
                            @foreach ($kandangs as $k)
                                <td class="text-center t1-cell-kandang-avg" data-kandang-id="{{ $k->id }}">{{ number_format($rataRataPerKandang[$k->id] ?? 0, 1) }}</td>
                            @endforeach
                            <td class="text-center" id="t1FooterGrandAvg">{{ number_format($rataRataHarianProduksi, 1) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 2 — EFISIENSI PAKAN
         Dihitung: biaya pakan, FCR, rasio pakan/butir, HDP.
         Dipakai untuk: Biaya Pakan otomatis mengalir ke Tab 5 & Tab 6;
         Total Produksi di sini ikut otomatis dari Tab 1.
    ====================================================================== -->
    <div class="kalk-tab-pane" id="tab2" style="display:none;">
        <div class="alert alert-info small mb-3" style="padding:10px 12px;">
            <strong>Yang dihitung:</strong> kebutuhan &amp; biaya pakan, FCR (efisiensi pakan berbasis berat),
            rasio pakan per butir, dan HDP (produktivitas ayam). <strong>Dipakai untuk:</strong> Biaya Pakan
            hasil hitungan di sini otomatis mengalir ke Tab 5 (HPP) dan Tab 6 (Laba Bersih); Total Produksi
            dan Jumlah Hari di sini ikut otomatis dari Tab 1 (supaya jumlah hari sebulan konsisten di kedua tab).
        </div>
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card-neo p-3 mb-3">
                    <h5 class="fw-bold mb-3">Input — Tab 2</h5>
                    <p class="text-muted small mb-3">Harga Pakan, Berat Pakan, Konsumsi, dan Jumlah Ayam diketik
                        manual di sini (tidak disimpan ke database). Total Produksi ikut otomatis dari Tab 1.</p>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Harga Pakan (Rp)</label>
                        <input type="number" class="form-control form-control-neo" id="t2HargaPakan" min="0" placeholder="Contoh: 377500">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Berat Pakan (kg)</label>
                        <input type="number" class="form-control form-control-neo" id="t2BeratPakan" min="0" step="0.1" placeholder="Contoh: 50">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Konsumsi (gram/ekor/hari)</label>
                        <input type="number" class="form-control form-control-neo" id="t2Konsumsi" min="0" value="70">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah Ayam</label>
                        <input type="number" class="form-control form-control-neo" id="t2JumlahAyam" min="0" value="{{ $totalAyam }}">
                        <small class="text-muted d-block">Default dari total kandang saat halaman dibuka.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah Hari</label>
                        <input type="number" class="form-control form-control-neo" id="t2JumlahHari" min="1" max="31" value="{{ $daysInMonth }}">
                        <small class="text-muted d-block" id="t2JumlahHariStatus">Sinkron dengan Jumlah Hari dalam 1 Bulan Tab 1…</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Total Produksi Telur Bulan Ini (butir)</label>
                        <input type="number" class="form-control form-control-neo" id="t2TotalProduksi" min="0" value="{{ $grandTotalProduksi }}">
                        <small class="text-muted d-block" id="t2TotalProduksiStatus">Sinkron dengan Total Produksi Tab 1…</small>
                    </div>
                    <div class="row g-2 mb-0">
                        <div class="col-6">
                            <label class="form-label fw-bold">Bobot Telur Terkecil (gram)</label>
                            <input type="number" class="form-control form-control-neo" id="t2BobotMin" min="0" value="30">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Bobot Telur Terbesar (gram)</label>
                            <input type="number" class="form-control form-control-neo" id="t2BobotMax" min="0" value="60">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card-neo p-3 mb-3">
                    <h5 class="fw-bold mb-3">Rumus Biaya Pakan</h5>
                    <div class="mb-2 small">
                        <div class="text-muted">Harga per Gram</div>
                        <div class="fw-bold" id="t2RumusHargaPerGram">Harga Pakan ÷ (Berat Pakan × 1000) = Rp 0/gram</div>
                    </div>
                    <div class="mb-2 small">
                        <div class="text-muted">Kebutuhan Pakan Sebulan</div>
                        <div class="fw-bold" id="t2RumusKebutuhan">Jumlah Ayam × Konsumsi × Jumlah Hari = 0 kg</div>
                    </div>
                    <div class="mb-0 small">
                        <div class="text-muted">Biaya Pakan</div>
                        <div class="fw-bold" id="t2RumusBiayaPakan">Harga per Gram × Kebutuhan (gram) = Rp 0</div>
                        <div class="text-muted" style="font-size:11px;">Angka ini otomatis dikirim ke Tab 5 &amp; Tab 6 sebagai Biaya Pakan.</div>
                    </div>
                </div>

                <div class="card-neo p-3 mb-3">
                    <h5 class="fw-bold mb-3">Efisiensi Pakan</h5>
                    <div class="mb-2 small">
                        <div class="text-muted">Rata-rata Bobot Telur</div>
                        <div class="fw-bold" id="t2RumusBobotRata">(Min + Max) ÷ 2 = 0 gram</div>
                    </div>
                    <div class="mb-2 small">
                        <div class="text-muted">Total Berat Telur Bulan Ini</div>
                        <div class="fw-bold" id="t2RumusBeratTelur">(Total Produksi × Bobot Rata-rata) ÷ 1000 = 0 kg</div>
                    </div>
                    <div class="mb-2 small">
                        <div class="text-muted">FCR (berbasis berat)</div>
                        <div class="fw-bold" id="t2RumusFcr">Kebutuhan Pakan (kg) ÷ Berat Telur (kg) = 0</div>
                        <div class="text-muted" style="font-size:11px;">Standar ayam petelur umumnya 2,0–2,5.</div>
                    </div>
                    <hr>
                    <div class="mb-0 small">
                        <div class="text-muted">Rasio Pakan per Butir</div>
                        <div class="fw-bold" id="t2RumusRasioButir">Kebutuhan Pakan (gram) ÷ Total Produksi = 0 gram/butir</div>
                    </div>
                </div>

                <div class="card-neo p-3">
                    <h5 class="fw-bold mb-3">Produktivitas Ayam (HDP)</h5>
                    <div class="mb-0 small">
                        <div class="text-muted">Hen Day Production</div>
                        <div class="fw-bold" id="t2RumusHdp">Produksi ÷ (Ayam × Hari) × 100% = 0%</div>
                        <div class="text-muted" style="font-size:11px;">≥70% bagus, 50–69% waspada, &lt;50% perlu dicek.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 3 — KESEHATAN KAWANAN — berdiri sendiri
    ====================================================================== -->
    <div class="kalk-tab-pane" id="tab3" style="display:none;">
        <div class="alert alert-info small mb-3" style="padding:10px 12px;">
            <strong>Yang dihitung:</strong> mortalitas (tingkat kematian) dan daya hidup kawanan ayam.
            <strong>Dipakai untuk:</strong> pantau kesehatan kandang saja — tab ini berdiri sendiri, tidak
            mengirim atau menerima angka dari tab lain.
        </div>
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card-neo p-3 mb-3">
                    <h5 class="fw-bold mb-3">Input — Tab 3</h5>
                    <p class="text-muted small mb-3">Manual, tidak disimpan ke database, dan tidak terhubung ke tab lain.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah Ayam Awal Periode (ekor)</label>
                        <input type="number" class="form-control form-control-neo" id="t3AyamAwal" min="0" value="{{ $totalAyam }}">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Jumlah Ayam Mati Periode Ini (ekor)</label>
                        <input type="number" class="form-control form-control-neo" id="t3AyamMati" min="0" value="0">
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card-neo p-3">
                    <h5 class="fw-bold mb-3">Hasil</h5>
                    <div class="mb-2 small">
                        <div class="text-muted">Mortalitas / Deplesi</div>
                        <div class="fw-bold" id="t3RumusMortalitas">Mati ÷ Awal × 100% = 0%</div>
                        <div class="text-muted" style="font-size:11px;">Batas aman industri umumnya di bawah 5%.</div>
                    </div>
                    <div class="mb-0 small">
                        <div class="text-muted">Daya Hidup (Livability)</div>
                        <div class="fw-bold" id="t3RumusDayaHidup">100% − Mortalitas = 0%</div>
                    </div>
                </div>
                <div class="alert alert-secondary small mt-3 mb-0" style="padding:8px 10px;">
                    IP (Indeks Performa) sengaja tidak dipakai — metrik itu khusus ayam pedaging/broiler.
                    Untuk petelur, tolok ukurnya FCR & HDP (Tab 2) dan Mortalitas di atas.
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 4 — DATA TRANSAKSI
         Dihitung: total pemasukan (penjualan) & pengeluaran lain dari transaksi tercentang.
         Dipakai untuk: sumber otomatis Pengeluaran Lain & Harga Jual (Tab 5),
         Pemasukan & Pengeluaran Lain (Tab 6).
    ====================================================================== -->
    <div class="kalk-tab-pane" id="tab4" style="display:none;">
        <div class="alert alert-info small mb-3" style="padding:10px 12px;">
            <strong>Yang dihitung:</strong> rekap total pemasukan (penjualan) dan pengeluaran lain bulan ini,
            berdasarkan transaksi yang tercentang. <strong>Dipakai untuk:</strong> hasil di sini otomatis
            mengalir jadi Pengeluaran Lain &amp; Harga Jual di Tab 5, serta Pemasukan &amp; Pengeluaran Lain
            di Tab 6.
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <h5 class="fw-bold mb-1">Pemasukan (Penjualan)</h5>
                <p class="text-muted small mb-2">
                    Baris dengan <strong>0 butir</strong> (mis. uang bulanan/kiriman yang tercatat lewat form
                    Penjualan) otomatis <strong>tidak tercentang</strong>.
                </p>
                <div class="table-responsive">
                    <table class="table table-neo align-middle mb-0" id="t4TabelPenjualan">
                        <thead>
                            <tr>
                                <th style="width:40px"></th>
                                <th>Pembeli</th>
                                <th class="text-end">Jumlah Telur</th>
                                <th class="text-end">Bonus</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($penjualanGrouped as $p)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input t4-chk-penjualan"
                                            {{ $p->jumlah_telur > 0 ? 'checked' : '' }}
                                            data-nilai="{{ $p->total }}" data-jumlah="{{ $p->jumlah_telur }}"
                                            data-bonus="{{ $p->bonus ?? 0 }}">
                                    </td>
                                    <td>
                                        {{ $p->nama }}
                                        @if ($p->jumlah_telur <= 0)
                                            <span class="badge bg-secondary" style="font-size:10px;">bukan penjualan telur</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $p->jumlah_telur > 0 ? number_format($p->jumlah_telur) . ' butir' : '-' }}</td>
                                    <td class="text-end">{{ ($p->bonus ?? 0) > 0 ? 'Rp ' . number_format($p->bonus, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">Rp {{ number_format($p->total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3">Belum ada penjualan bulan ini</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td></td>
                                <td>Total (tercentang)</td>
                                <td class="text-end" id="t4FooterJumlahTelur">0 butir</td>
                                <td class="text-end" id="t4FooterBonus">Rp 0</td>
                                <td class="text-end" id="t4FooterTotalPemasukan">Rp 0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="alert alert-secondary small mt-2 mb-0" style="padding:8px 10px;">
                    Kolom <strong>Bonus</strong> membaca <code>bonus</code> dari data penjualan. Kalau kolom
                    ini selalu tampil "-", berarti query/controller belum menyertakan field <code>bonus</code>
                    untuk <code>$penjualanGrouped</code> — tambahkan di sana dulu.
                </div>
                <div class="card-neo p-3 mt-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small text-muted">Total Pemasukan (tercentang)</span>
                        <strong id="t4HasilPemasukan">Rp 0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small text-muted">Total Telur Terjual (tercentang)</span>
                        <strong id="t4HasilJumlahTelur">0 butir</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small text-muted">Total Bonus (tercentang)</span>
                        <strong id="t4HasilBonus">Rp 0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-0">
                        <span class="small text-muted">Harga Jual Rata-rata per Butir</span>
                        <strong id="t4HasilHargaButir">Rp 0</strong>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <h5 class="fw-bold mb-1">Pengeluaran Lain</h5>
                <p class="text-muted small mb-2">
                    Pengeluaran yang tidak dicentang <strong>tidak dihapus dari data</strong>, hanya dikecualikan
                    dari Total di bawah.
                </p>
                <div class="table-responsive">
                    <table class="table table-neo align-middle mb-0" id="t4TabelPengeluaran">
                        <thead>
                            <tr>
                                <th style="width:40px"></th>
                                <th>Keterangan</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pengeluaranGrouped as $p)
                                <tr>
                                    <td><input type="checkbox" class="form-check-input t4-chk-pengeluaran" checked data-nilai="{{ $p->total }}"></td>
                                    <td>{{ $p->nama }}</td>
                                    <td class="text-end">Rp {{ number_format($p->total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-3">Belum ada pengeluaran bulan ini</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-neo p-3 mt-3">
                    <div class="d-flex justify-content-between mb-0">
                        <span class="small text-muted">Total Pengeluaran Lain (tercentang)</span>
                        <strong id="t4HasilPengeluaran">Rp 0</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 5 — HPP & BEP
         Dihitung: HPP per butir & titik impas (BEP).
         Sumber: Total Produksi (Tab 1), Biaya Pakan (Tab 2), Pengeluaran Lain & Harga Jual (Tab 4).
    ====================================================================== -->
    <div class="kalk-tab-pane" id="tab5" style="display:none;">
        <div class="alert alert-info small mb-3" style="padding:10px 12px;">
            <strong>Yang dihitung:</strong> HPP (harga pokok produksi per butir telur) dan titik impas (BEP).
            <strong>Sumber angka:</strong> Biaya Pakan ikut Tab 2, Total Produksi ikut Tab 1, Pengeluaran Lain
            &amp; Harga Jual ikut Tab 4 — semua tetap bisa diedit manual untuk simulasi.
        </div>
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card-neo p-3">
                    <h5 class="fw-bold mb-3">Input — Tab 5</h5>
                    <p class="text-muted small mb-3">Kolom di bawah otomatis terisi dari tab lain. Edit manual
                        kapan saja untuk simulasi — nanti muncul link untuk kembali ke otomatis.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Biaya Pakan Bulan Ini (Rp)</label>
                        <input type="number" class="form-control form-control-neo" id="t5BiayaPakan" min="0" value="0">
                        <small class="text-muted d-block" id="t5BiayaPakanStatus">Sinkron dengan Biaya Pakan Tab 2…</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pengeluaran Lain (Rp)</label>
                        <input type="number" class="form-control form-control-neo" id="t5PengeluaranLain" min="0" value="{{ $totalPengeluaranDefault }}">
                        <small class="text-muted d-block" id="t5PengeluaranLainStatus">Sinkron dengan Pengeluaran Lain Tab 4…</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Total Produksi Telur (butir)</label>
                        <input type="number" class="form-control form-control-neo" id="t5TotalProduksi" min="0" value="{{ $grandTotalProduksi }}">
                        <small class="text-muted d-block" id="t5TotalProduksiStatus">Sinkron dengan Total Produksi Tab 1…</small>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Harga Jual per Butir (Rp)</label>
                        <input type="number" class="form-control form-control-neo" id="t5HargaJual" min="0" value="{{ $rataRataHargaPerButir }}">
                        <small class="text-muted d-block" id="t5HargaJualStatus">Sinkron dengan Harga Jual Rata-rata Tab 4…</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card-neo p-3 mb-3">
                    <h5 class="fw-bold mb-3">HPP (Harga Pokok Produksi)</h5>
                    <div class="mb-0 small">
                        <div class="text-muted">HPP per Butir</div>
                        <div class="fw-bold" id="t5RumusHpp">(Biaya Pakan + Pengeluaran Lain) ÷ Total Produksi = Rp 0</div>
                    </div>
                </div>
                <div class="card-neo p-3">
                    <h5 class="fw-bold mb-3">Titik Impas (BEP)</h5>
                    <div class="mb-2 small">
                        <div class="text-muted">Margin per Butir</div>
                        <div class="fw-bold" id="t5RumusMargin">Harga Jual − HPP = Rp 0 / butir</div>
                        <div id="t5StatusBep" class="text-muted" style="font-size:12px;"></div>
                    </div>
                    <hr>
                    <div class="mb-2 small">
                        <div class="text-muted">Biaya Variabel per Butir (Biaya Pakan ÷ Produksi)</div>
                        <div class="fw-bold" id="t5RumusBiayaVariabel">Rp 0 / butir</div>
                    </div>
                    <div class="mb-0 small">
                        <div class="text-muted">BEP Produksi (butir)</div>
                        <div class="fw-bold" id="t5RumusBep">Pengeluaran Lain ÷ (Harga Jual − Biaya Variabel) = 0 butir</div>
                        <div class="text-muted" style="font-size:11px;">Target jumlah telur minimum yang harus terjual di harga saat ini supaya balik modal.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 6 — LABA BERSIH
         Dihitung: laba bersih & margin per ekor ayam.
         Sumber: Pemasukan & Pengeluaran Lain (Tab 4), Biaya Pakan (Tab 2).
    ====================================================================== -->
    <div class="kalk-tab-pane" id="tab6" style="display:none;">
        <div class="alert alert-info small mb-3" style="padding:10px 12px;">
            <strong>Yang dihitung:</strong> laba bersih bulan ini (Pemasukan − Biaya Pakan − Pengeluaran Lain)
            dan margin laba per ekor ayam. <strong>Sumber angka:</strong> Pemasukan &amp; Pengeluaran Lain ikut
            Tab 4, Biaya Pakan ikut Tab 2 — bisa diedit manual untuk simulasi.
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-lg-5">
                <div class="card-neo p-3">
                    <h5 class="fw-bold mb-3">Input — Tab 6</h5>
                    <p class="text-muted small mb-3">Kolom di bawah otomatis terisi dari tab lain, tetap bisa
                        diedit manual untuk simulasi.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Total Pemasukan (Rp)</label>
                        <input type="number" class="form-control form-control-neo" id="t6Pemasukan" min="0" value="{{ $omzetTelurBulanIni }}">
                        <small class="text-muted d-block" id="t6PemasukanStatus">Sinkron dengan Total Pemasukan Tab 4…</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Biaya Pakan (Rp)</label>
                        <input type="number" class="form-control form-control-neo" id="t6BiayaPakan" min="0" value="0">
                        <small class="text-muted d-block" id="t6BiayaPakanStatus">Sinkron dengan Biaya Pakan Tab 2…</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pengeluaran Lain (Rp)</label>
                        <input type="number" class="form-control form-control-neo" id="t6PengeluaranLain" min="0" value="{{ $totalPengeluaranDefault }}">
                        <small class="text-muted d-block" id="t6PengeluaranLainStatus">Sinkron dengan Pengeluaran Lain Tab 4…</small>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Jumlah Ayam</label>
                        <input type="number" class="form-control form-control-neo" id="t6JumlahAyam" min="0" value="{{ $totalAyam }}">
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card-neo p-3" style="background: var(--color-accent);">
                    <h5 class="fw-bold mb-3">Hasil</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Pemasukan</span>
                        <strong id="t6HasilPemasukan">Rp 0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Biaya Pakan</span>
                        <strong id="t6HasilBiayaPakan">Rp 0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Pengeluaran Lain</span>
                        <strong id="t6HasilPengeluaran">Rp 0</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold fs-5">Laba Bersih</span>
                        <strong class="fs-5" id="t6HasilLaba">Rp 0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-0">
                        <span>Margin per Ekor Ayam</span>
                        <strong id="t6HasilMargin">Rp 0</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 7 — PROYEKSI BULAN DEPAN
         Dihitung: proyeksi kebutuhan & biaya pakan bulan depan.
         Sumber default: Harga Pakan, Berat Pakan, Konsumsi, Jumlah Ayam ikut Tab 2.
    ====================================================================== -->
    <div class="kalk-tab-pane" id="tab7" style="display:none;">
        <div class="alert alert-info small mb-3" style="padding:10px 12px;">
            <strong>Yang dihitung:</strong> proyeksi kebutuhan &amp; biaya pakan bulan depan, memakai hari
            kalender penuh bulan depan (tidak bisa diubah, supaya proyeksi selalu penuh sebulan).
            <strong>Sumber angka default:</strong> Harga Pakan, Berat Pakan, Konsumsi, dan Jumlah Ayam
            mengikuti angka yang kamu isi di Tab 2 — bisa diedit manual kalau proyeksi bulan depan mau beda.
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-lg-7">
                <div class="card-neo p-3 mb-3">
                    <h5 class="fw-bold mb-3">Input — Tab 7 ({{ $namaBulanDepan }})</h5>
                    <p class="text-muted small mb-3">
                        Jumlah hari proyeksi <strong>otomatis {{ $daysInNextMonth }} hari</strong> (hari penuh
                        kalender bulan depan) dan tidak bisa diubah — supaya tidak ketimpa angka kecil dari
                        Tab 1/2 seperti bug sebelumnya.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Harga Pakan (Rp)</label>
                        <input type="number" class="form-control form-control-neo" id="t7HargaPakan" min="0" placeholder="Contoh: 377500">
                        <small class="text-muted d-block" id="t7HargaPakanStatus">Sinkron dengan Harga Pakan Tab 2…</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Berat Pakan (kg)</label>
                        <input type="number" class="form-control form-control-neo" id="t7BeratPakan" min="0" step="0.1" placeholder="Contoh: 50">
                        <small class="text-muted d-block" id="t7BeratPakanStatus">Sinkron dengan Berat Pakan Tab 2…</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Konsumsi (gram/ekor/hari)</label>
                        <input type="number" class="form-control form-control-neo" id="t7Konsumsi" min="0" value="70">
                        <small class="text-muted d-block" id="t7KonsumsiStatus">Sinkron dengan Konsumsi Tab 2…</small>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Jumlah Ayam Proyeksi</label>
                        <input type="number" class="form-control form-control-neo" id="t7JumlahAyam" min="0" value="{{ $totalAyam }}">
                        <small class="text-muted d-block" id="t7JumlahAyamStatus">Sinkron dengan Jumlah Ayam Tab 2…</small>
                    </div>
                </div>
                <div class="card-neo p-3">
                    <h5 class="fw-bold mb-3">Hasil Proyeksi</h5>
                    <div class="mb-2 small">
                        <div class="text-muted">Proyeksi Kebutuhan Pakan</div>
                        <div class="fw-bold" id="t7RumusKebutuhan">0 kg</div>
                    </div>
                    <div class="mb-0 small">
                        <div class="text-muted">Proyeksi Biaya Pakan</div>
                        <div class="fw-bold" id="t7RumusBiaya">Rp 0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const kalkulatorBaseUrl = "{{ route('kalkulator.index') }}";
        function reloadKalkulator() {
            const bulan = document.getElementById('filterBulan').value;
            const tahun = document.getElementById('filterTahun').value;
            window.location.href = `${kalkulatorBaseUrl}?bulan=${bulan}&tahun=${tahun}`;
        }
        document.getElementById('filterBulan').addEventListener('change', reloadKalkulator);
        document.getElementById('filterTahun').addEventListener('change', reloadKalkulator);

        // Tab switching murni CSS display toggle. Tidak ada penghitungan ulang di sini —
        // semua tab sudah dihitung sendiri-sendiri saat halaman dimuat (lihat init() paling bawah).
        document.querySelectorAll('#kalkTabNav .nav-link').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('#kalkTabNav .nav-link').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.kalk-tab-pane').forEach(p => p.style.display = 'none');
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab).style.display = '';
            });
        });

        function formatRupiah(angka) { return 'Rp ' + Math.round(angka).toLocaleString('id-ID'); }
        function formatKg(gram) { return (gram / 1000).toLocaleString('id-ID', { maximumFractionDigits: 1 }); }
        function formatDesimal(n, digit = 1) { return n.toLocaleString('id-ID', { minimumFractionDigits: digit, maximumFractionDigits: digit }); }
        function formatRibuan(n) { return new Intl.NumberFormat('id-ID').format(Math.round(n)); }
        function formatSatuDesimal(n) { return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(n); }

        // =====================================================================
        // MEKANISME SINKRON ANTAR-TAB
        // Field yang "linked" otomatis ikut nilai hitungan tab sumber, KECUALI
        // sudah pernah diedit manual oleh user (ditandai dataset.manual='true').
        // Klik link "pakai otomatis lagi" akan mengembalikan ke mode otomatis.
        // =====================================================================
        const syncRegistry = {};

        function registerSync(fieldId, statusElId, getValue, sourceLabel) {
            syncRegistry[fieldId] = { getValue, statusElId, sourceLabel };
            const el = document.getElementById(fieldId);
            el.addEventListener('input', function () {
                if (!el.dataset.syncing) {
                    el.dataset.manual = 'true';
                    renderSyncStatus(fieldId);
                }
            });
        }

        function applySync(fieldId) {
            const reg = syncRegistry[fieldId];
            if (!reg) return;
            const el = document.getElementById(fieldId);
            if (el.dataset.manual === 'true') {
                renderSyncStatus(fieldId);
                return;
            }
            const val = reg.getValue();
            el.dataset.syncing = 'true';
            el.value = val;
            el.dataset.syncing = '';
            renderSyncStatus(fieldId);
        }

        function renderSyncStatus(fieldId) {
            const reg = syncRegistry[fieldId];
            if (!reg) return;
            const statusEl = document.getElementById(reg.statusElId);
            if (!statusEl) return;
            const el = document.getElementById(fieldId);
            if (el.dataset.manual === 'true') {
                statusEl.innerHTML = `✏️ Diisi manual. <a href="#" class="resync-link" data-field="${fieldId}">Klik untuk pakai otomatis dari ${reg.sourceLabel} lagi</a>`;
            } else {
                statusEl.innerHTML = `🔗 Otomatis ikut ${reg.sourceLabel}`;
            }
        }

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('resync-link')) {
                e.preventDefault();
                const fieldId = e.target.dataset.field;
                document.getElementById(fieldId).dataset.manual = 'false';
                applySync(fieldId);
                // recalculate the tab(s) that depend on this field's value
                recalcAfterResync(fieldId);
            }
        });

        function recalcAfterResync(fieldId) {
            if (fieldId === 't2TotalProduksi' || fieldId === 't2JumlahHari') hitungTab2();
            else if (['t5TotalProduksi', 't5BiayaPakan', 't5PengeluaranLain', 't5HargaJual'].includes(fieldId)) hitungTab5();
            else if (['t6Pemasukan', 't6BiayaPakan', 't6PengeluaranLain'].includes(fieldId)) hitungTab6();
            else if (['t7HargaPakan', 't7BeratPakan', 't7Konsumsi', 't7JumlahAyam'].includes(fieldId)) hitungTab7();
        }

        // Nilai hasil hitungan tab sumber, dipakai oleh field-field yang sinkron
        let currentTab1Total = 0;
        let currentTab1Hari = 0;
        let currentTab2 = { biayaPakan: 0, hargaPakan: 0, beratPakan: 0, konsumsi: 0, jumlahAyam: 0 };
        let currentTab4 = { pemasukan: 0, hargaRata: 0, pengeluaran: 0, jumlahTelur: 0, bonus: 0 };

        // =====================================================================
        // TAB 1 — hitung sendiri, hanya baca/tulis elemen di dalam #tab1
        // Setelah selesai: sinkronkan Total Produksi ke Tab 2 & Tab 5.
        // =====================================================================
        function hitungTab1() {
            const hari = parseFloat(document.getElementById('t1HariInput').value) || 0;
            const checkedIds = Array.from(document.querySelectorAll('.t1-chk-kandang:checked')).map(c => c.value);

            document.querySelectorAll('.t1-th-kandang, .t1-cell-kandang-total, .t1-cell-kandang-avg').forEach(function (el) {
                el.classList.toggle('kandang-excluded', !checkedIds.includes(el.dataset.kandangId));
            });

            let grandTotal = 0;
            const totalPerKandangId = {};
            document.querySelectorAll('#pivotTableKalk tbody tr[data-tgl]').forEach(function (tr) {
                let rowTotal = 0;
                tr.querySelectorAll('.t1-cell-kandang').forEach(function (td) {
                    const kid = td.dataset.kandangId;
                    const included = checkedIds.includes(kid);
                    const val = parseFloat(td.dataset.value || 0);
                    td.classList.toggle('kandang-excluded', !included);
                    if (included) rowTotal += val;
                    totalPerKandangId[kid] = (totalPerKandangId[kid] || 0) + val;
                });
                const totalCell = tr.querySelector('.t1-cell-row-total');
                totalCell.textContent = rowTotal ? formatRibuan(rowTotal) : '-';
                grandTotal += rowTotal;
            });

            const rataRata = hari > 0 ? grandTotal / hari : 0;
            document.getElementById('t1FooterGrandTotal').textContent = formatRibuan(grandTotal);
            document.getElementById('t1FooterGrandAvg').textContent = formatSatuDesimal(rataRata);
            document.getElementById('t1LabelRataRata').textContent = `Rata-rata/hari (${hari || 0} hari)`;

            document.querySelectorAll('.t1-cell-kandang-total').forEach(function (td) {
                td.textContent = formatRibuan(totalPerKandangId[td.dataset.kandangId] || 0);
            });
            document.querySelectorAll('.t1-cell-kandang-avg').forEach(function (td) {
                const avgKandang = hari > 0 ? (totalPerKandangId[td.dataset.kandangId] || 0) / hari : 0;
                td.textContent = formatSatuDesimal(avgKandang);
            });

            currentTab1Total = grandTotal;
            currentTab1Hari = hari;
            applySync('t2TotalProduksi');
            applySync('t5TotalProduksi');
            applySync('t2JumlahHari');
            hitungTab2();
            hitungTab5();
        }
        document.querySelectorAll('.t1-chk-kandang').forEach(chk => chk.addEventListener('change', hitungTab1));
        document.getElementById('t1HariInput').addEventListener('input', hitungTab1);

        // =====================================================================
        // TAB 2 — hitung sendiri, hanya baca/tulis elemen di dalam #tab2
        // Setelah selesai: sinkronkan Biaya Pakan ke Tab 5 & 6, dan default
        // Harga Pakan/Berat Pakan/Konsumsi/Jumlah Ayam ke Tab 7.
        // =====================================================================
        function hitungTab2() {
            const hargaPakan = parseFloat(document.getElementById('t2HargaPakan').value) || 0;
            const beratPakan = parseFloat(document.getElementById('t2BeratPakan').value) || 0;
            const konsumsi = parseFloat(document.getElementById('t2Konsumsi').value) || 0;
            const jumlahAyam = parseFloat(document.getElementById('t2JumlahAyam').value) || 0;
            const hari = parseFloat(document.getElementById('t2JumlahHari').value) || 0;
            const totalProduksi = parseFloat(document.getElementById('t2TotalProduksi').value) || 0;
            const bobotMin = parseFloat(document.getElementById('t2BobotMin').value) || 0;
            const bobotMax = parseFloat(document.getElementById('t2BobotMax').value) || 0;

            let hargaPerGram = 0, biayaPakan = 0;
            const kebutuhanGram = jumlahAyam * konsumsi * hari;
            if (beratPakan > 0) {
                hargaPerGram = hargaPakan / (beratPakan * 1000);
                biayaPakan = hargaPerGram * kebutuhanGram;
            }

            const bobotRata = (bobotMin + bobotMax) / 2;
            const totalBeratTelurKg = (totalProduksi * bobotRata) / 1000;
            const kebutuhanPakanKg = kebutuhanGram / 1000;
            const fcr = totalBeratTelurKg > 0 ? kebutuhanPakanKg / totalBeratTelurKg : 0;
            const rasioButir = totalProduksi > 0 ? kebutuhanGram / totalProduksi : 0;
            const hdp = (jumlahAyam > 0 && hari > 0) ? (totalProduksi / (jumlahAyam * hari)) * 100 : 0;

            document.getElementById('t2RumusHargaPerGram').textContent =
                `${formatRupiah(hargaPakan)} ÷ (${beratPakan || 0} × 1000) = Rp ${hargaPerGram.toFixed(2)}/gram`;
            document.getElementById('t2RumusKebutuhan').textContent =
                `${jumlahAyam || 0} ekor × ${konsumsi || 0} gram × ${hari || 0} hari = ${formatKg(kebutuhanGram)} kg`;
            document.getElementById('t2RumusBiayaPakan').textContent =
                `Rp ${hargaPerGram.toFixed(2)} × ${Math.round(kebutuhanGram).toLocaleString('id-ID')} gram = ${formatRupiah(biayaPakan)}`;
            document.getElementById('t2RumusBobotRata').textContent =
                `(${bobotMin || 0} + ${bobotMax || 0}) ÷ 2 = ${formatDesimal(bobotRata)} gram`;
            document.getElementById('t2RumusBeratTelur').textContent =
                `(${Math.round(totalProduksi).toLocaleString('id-ID')} butir × ${formatDesimal(bobotRata)} gram) ÷ 1000 = ${formatDesimal(totalBeratTelurKg)} kg`;
            document.getElementById('t2RumusFcr').textContent =
                `${formatDesimal(kebutuhanPakanKg)} kg ÷ ${formatDesimal(totalBeratTelurKg)} kg = ${formatDesimal(fcr, 2)}`;
            document.getElementById('t2RumusRasioButir').textContent =
                `${Math.round(kebutuhanGram).toLocaleString('id-ID')} gram ÷ ${Math.round(totalProduksi).toLocaleString('id-ID')} butir = ${formatDesimal(rasioButir, 1)} gram/butir`;
            document.getElementById('t2RumusHdp').textContent =
                `${Math.round(totalProduksi).toLocaleString('id-ID')} ÷ (${jumlahAyam || 0} × ${hari || 0} hari) × 100% = ${formatDesimal(hdp)}%`;

            currentTab2 = { biayaPakan, hargaPakan, beratPakan, konsumsi, jumlahAyam };
            applySync('t5BiayaPakan');
            applySync('t6BiayaPakan');
            applySync('t7HargaPakan');
            applySync('t7BeratPakan');
            applySync('t7Konsumsi');
            applySync('t7JumlahAyam');
            hitungTab5();
            hitungTab6();
            hitungTab7();
        }
        ['t2HargaPakan','t2BeratPakan','t2Konsumsi','t2JumlahAyam','t2JumlahHari','t2TotalProduksi','t2BobotMin','t2BobotMax']
            .forEach(id => document.getElementById(id).addEventListener('input', hitungTab2));

        // =====================================================================
        // TAB 3 — hitung sendiri
        // =====================================================================
        function hitungTab3() {
            const awal = parseFloat(document.getElementById('t3AyamAwal').value) || 0;
            const mati = parseFloat(document.getElementById('t3AyamMati').value) || 0;
            const mortalitas = awal > 0 ? (mati / awal) * 100 : 0;
            const dayaHidup = 100 - mortalitas;
            document.getElementById('t3RumusMortalitas').textContent =
                `${mati || 0} ÷ ${awal || 0} × 100% = ${formatDesimal(mortalitas, 2)}%`;
            document.getElementById('t3RumusDayaHidup').textContent =
                `100% − ${formatDesimal(mortalitas, 2)}% = ${formatDesimal(dayaHidup, 2)}%`;
        }
        ['t3AyamAwal','t3AyamMati'].forEach(id => document.getElementById(id).addEventListener('input', hitungTab3));

        // =====================================================================
        // TAB 4 — hitung sendiri (checklist -> total, semua di dalam #tab4)
        // Setelah selesai: sinkronkan Pengeluaran Lain & Harga Jual ke Tab 5,
        // dan Pemasukan & Pengeluaran Lain ke Tab 6.
        // =====================================================================
        function hitungTab4() {
            let totalPemasukan = 0, totalButir = 0, totalBonus = 0;
            document.querySelectorAll('.t4-chk-penjualan:checked').forEach(chk => {
                totalPemasukan += parseFloat(chk.dataset.nilai) || 0;
                totalButir += parseFloat(chk.dataset.jumlah) || 0;
                totalBonus += parseFloat(chk.dataset.bonus) || 0;
            });
            const hargaRata = totalButir > 0 ? Math.round(totalPemasukan / totalButir) : 0;

            let totalPengeluaran = 0;
            document.querySelectorAll('.t4-chk-pengeluaran:checked').forEach(chk => {
                totalPengeluaran += parseFloat(chk.dataset.nilai) || 0;
            });

            document.getElementById('t4HasilPemasukan').textContent = formatRupiah(totalPemasukan);
            document.getElementById('t4HasilJumlahTelur').textContent = formatRibuan(totalButir) + ' butir';
            document.getElementById('t4HasilBonus').textContent = formatRupiah(totalBonus);
            document.getElementById('t4HasilHargaButir').textContent = formatRupiah(hargaRata);
            document.getElementById('t4HasilPengeluaran').textContent = formatRupiah(totalPengeluaran);

            document.getElementById('t4FooterJumlahTelur').textContent = formatRibuan(totalButir) + ' butir';
            document.getElementById('t4FooterBonus').textContent = formatRupiah(totalBonus);
            document.getElementById('t4FooterTotalPemasukan').textContent = formatRupiah(totalPemasukan);

            currentTab4 = { pemasukan: totalPemasukan, hargaRata, pengeluaran: totalPengeluaran, jumlahTelur: totalButir, bonus: totalBonus };
            applySync('t5PengeluaranLain');
            applySync('t5HargaJual');
            applySync('t6Pemasukan');
            applySync('t6PengeluaranLain');
            hitungTab5();
            hitungTab6();
        }
        document.querySelectorAll('.t4-chk-penjualan, .t4-chk-pengeluaran').forEach(chk => chk.addEventListener('change', hitungTab4));

        // =====================================================================
        // TAB 5 — hitung sendiri. Input Biaya Pakan/Total Produksi/Pengeluaran
        // Lain/Harga Jual otomatis ikut Tab 1/2/4 kecuali sudah diedit manual.
        // =====================================================================
        function hitungTab5() {
            const biayaPakan = parseFloat(document.getElementById('t5BiayaPakan').value) || 0;
            const pengeluaranLain = parseFloat(document.getElementById('t5PengeluaranLain').value) || 0;
            const totalProduksi = parseFloat(document.getElementById('t5TotalProduksi').value) || 0;
            const hargaJual = parseFloat(document.getElementById('t5HargaJual').value) || 0;

            const hpp = totalProduksi > 0 ? (biayaPakan + pengeluaranLain) / totalProduksi : 0;
            const margin = hargaJual - hpp;
            const biayaVariabel = totalProduksi > 0 ? biayaPakan / totalProduksi : 0;
            const penyebut = hargaJual - biayaVariabel;
            const bepButir = penyebut > 0 ? pengeluaranLain / penyebut : null;

            document.getElementById('t5RumusHpp').textContent =
                `(${formatRupiah(biayaPakan)} + ${formatRupiah(pengeluaranLain)}) ÷ ${Math.round(totalProduksi).toLocaleString('id-ID')} butir = ${formatRupiah(hpp)}`;
            document.getElementById('t5RumusMargin').textContent =
                `${formatRupiah(hargaJual)} − ${formatRupiah(hpp)} = ${formatRupiah(margin)} / butir`;

            const statusEl = document.getElementById('t5StatusBep');
            if (hargaJual <= 0) {
                statusEl.textContent = 'Isi Harga Jual per Butir untuk melihat status untung/rugi.';
                statusEl.className = 'text-muted';
            } else if (margin >= 0) {
                statusEl.textContent = `✓ Untung Rp ${formatRibuan(margin)} per butir`;
                statusEl.className = 'text-success';
            } else {
                statusEl.textContent = `✗ Rugi Rp ${formatRibuan(Math.abs(margin))} per butir`;
                statusEl.className = 'text-danger';
            }
            statusEl.style.fontSize = '12px';

            document.getElementById('t5RumusBiayaVariabel').textContent = formatRupiah(biayaVariabel) + ' / butir';
            document.getElementById('t5RumusBep').textContent = bepButir !== null
                ? `${formatRupiah(pengeluaranLain)} ÷ (${formatRupiah(hargaJual)} − ${formatRupiah(biayaVariabel)}) = ${Math.round(bepButir).toLocaleString('id-ID')} butir`
                : 'Tidak bisa dihitung — Harga Jual per Butir harus lebih besar dari Biaya Variabel per Butir';
        }
        ['t5BiayaPakan','t5PengeluaranLain','t5TotalProduksi','t5HargaJual'].forEach(id => document.getElementById(id).addEventListener('input', hitungTab5));

        // =====================================================================
        // TAB 6 — hitung sendiri. Pemasukan/Biaya Pakan/Pengeluaran Lain
        // otomatis ikut Tab 2/4 kecuali sudah diedit manual.
        // =====================================================================
        function hitungTab6() {
            const pemasukan = parseFloat(document.getElementById('t6Pemasukan').value) || 0;
            const biayaPakan = parseFloat(document.getElementById('t6BiayaPakan').value) || 0;
            const pengeluaranLain = parseFloat(document.getElementById('t6PengeluaranLain').value) || 0;
            const jumlahAyam = parseFloat(document.getElementById('t6JumlahAyam').value) || 0;

            const totalPengeluaran = biayaPakan + pengeluaranLain;
            const laba = pemasukan - totalPengeluaran;
            const marginEkor = jumlahAyam > 0 ? laba / jumlahAyam : 0;

            document.getElementById('t6HasilPemasukan').textContent = formatRupiah(pemasukan);
            document.getElementById('t6HasilBiayaPakan').textContent = formatRupiah(biayaPakan);
            document.getElementById('t6HasilPengeluaran').textContent = formatRupiah(pengeluaranLain);
            document.getElementById('t6HasilLaba').textContent = formatRupiah(laba);
            document.getElementById('t6HasilMargin').textContent = formatRupiah(marginEkor);
        }
        ['t6Pemasukan','t6BiayaPakan','t6PengeluaranLain','t6JumlahAyam'].forEach(id => document.getElementById(id).addEventListener('input', hitungTab6));

        // =====================================================================
        // TAB 7 — hitung sendiri. Jumlah hari FIX dari server (hari penuh bulan depan),
        // tidak ada input, tidak bisa ketimpa manual dari tab lain — ini fix bug lama.
        // Harga Pakan/Berat Pakan/Konsumsi/Jumlah Ayam default ikut Tab 2.
        // =====================================================================
        const t7HariProyeksi = {{ (int) $daysInNextMonth }};
        function hitungTab7() {
            const hargaPakan = parseFloat(document.getElementById('t7HargaPakan').value) || 0;
            const beratPakan = parseFloat(document.getElementById('t7BeratPakan').value) || 0;
            const konsumsi = parseFloat(document.getElementById('t7Konsumsi').value) || 0;
            const jumlahAyam = parseFloat(document.getElementById('t7JumlahAyam').value) || 0;

            let hargaPerGram = 0;
            if (beratPakan > 0) hargaPerGram = hargaPakan / (beratPakan * 1000);
            const kebutuhanGram = jumlahAyam * konsumsi * t7HariProyeksi;
            const biaya = hargaPerGram * kebutuhanGram;

            document.getElementById('t7RumusKebutuhan').textContent =
                `${jumlahAyam || 0} ekor × ${konsumsi || 0} gram × ${t7HariProyeksi} hari = ${formatKg(kebutuhanGram)} kg`;
            document.getElementById('t7RumusBiaya').textContent = formatRupiah(biaya);
        }
        ['t7HargaPakan','t7BeratPakan','t7Konsumsi','t7JumlahAyam'].forEach(id => document.getElementById(id).addEventListener('input', hitungTab7));

        // ===== Daftarkan semua field yang sinkron sebelum hitungan pertama jalan =====
        registerSync('t2TotalProduksi', 't2TotalProduksiStatus', () => Math.round(currentTab1Total), 'Total Produksi Tab 1');
        registerSync('t2JumlahHari', 't2JumlahHariStatus', () => Math.round(currentTab1Hari), 'Jumlah Hari dalam 1 Bulan Tab 1');
        registerSync('t5TotalProduksi', 't5TotalProduksiStatus', () => Math.round(currentTab1Total), 'Total Produksi Tab 1');
        registerSync('t5BiayaPakan', 't5BiayaPakanStatus', () => Math.round(currentTab2.biayaPakan), 'Biaya Pakan Tab 2');
        registerSync('t5PengeluaranLain', 't5PengeluaranLainStatus', () => Math.round(currentTab4.pengeluaran), 'Pengeluaran Lain Tab 4');
        registerSync('t5HargaJual', 't5HargaJualStatus', () => Math.round(currentTab4.hargaRata), 'Harga Jual Rata-rata Tab 4');
        registerSync('t6Pemasukan', 't6PemasukanStatus', () => Math.round(currentTab4.pemasukan), 'Total Pemasukan Tab 4');
        registerSync('t6BiayaPakan', 't6BiayaPakanStatus', () => Math.round(currentTab2.biayaPakan), 'Biaya Pakan Tab 2');
        registerSync('t6PengeluaranLain', 't6PengeluaranLainStatus', () => Math.round(currentTab4.pengeluaran), 'Pengeluaran Lain Tab 4');
        registerSync('t7HargaPakan', 't7HargaPakanStatus', () => currentTab2.hargaPakan, 'Harga Pakan Tab 2');
        registerSync('t7BeratPakan', 't7BeratPakanStatus', () => currentTab2.beratPakan, 'Berat Pakan Tab 2');
        registerSync('t7Konsumsi', 't7KonsumsiStatus', () => currentTab2.konsumsi, 'Konsumsi Tab 2');
        registerSync('t7JumlahAyam', 't7JumlahAyamStatus', () => currentTab2.jumlahAyam, 'Jumlah Ayam Tab 2');

        // ===== Hitung SEMUA tab sekali saat halaman dibuka, supaya tidak ada tab yang kosong =====
        // hitungTab1() akan cascade otomatis ke Tab 2 → Tab 5/6 → Tab 7.
        // hitungTab4() akan cascade otomatis ke Tab 5 & Tab 6.
        function initSemuaTab() {
            hitungTab1();
            hitungTab3();
            hitungTab4();
        }
        initSemuaTab();
    </script>

    <style>
        #pivotTableKalk .kandang-excluded { opacity: 0.4; text-decoration: line-through; }
        #kalkTabNav .nav-link { cursor: pointer; border: 1px solid transparent; }
        #kalkTabNav .nav-link.active { font-weight: 700; }
    </style>
@endsection