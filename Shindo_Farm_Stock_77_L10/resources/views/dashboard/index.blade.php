@extends('layouts.app')

@section('title', 'Dashboard - SHINDO FARM 77')

@section('content')
   <div class="page-header-neo">
    <h2 class="fw-bold mb-0">Dashboard</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('kalkulator.index') }}" class="btn btn-neo btn-neo-secondary">
            <i class="bi bi-calculator"></i> Kalkulator
        </a>
        <a id="btnExportExcel" href="{{ route('dashboard.export', ['bulan' => $bulan, 'tahun' => $tahun]) }}"
            class="btn btn-neo btn-neo-primary">
            <i class="bi bi-file-earmark-excel"></i> Export Excel
        </a>
    </div>
</div>

    <!-- Filter Bulan & Tahun -->
    <div class="d-flex gap-2 mb-3">
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

    {{-- Grid 3x3 di desktop / 2 kolom di HP: Ayam & Produksi | Detail Telur | Keuangan --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="stat-card-neo accent-amber">
                <div class="stat-icon-neo icon-amber"><i class="bi bi-feather"></i></div>
                <div>
                    <div class="stat-label-neo">Total Ayam</div>
                    <div class="stat-value-neo">{{ number_format($totalAyam) }}</div>
                    <div class="stat-sub-neo">ekor</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card-neo accent-amber">
                <div class="stat-icon-neo icon-amber"><i class="bi bi-gender-ambiguous"></i></div>
                <div>
                    <div class="stat-label-neo">Jantan / Betina</div>
                    <div class="stat-value-neo">{{ number_format($totalJantan) }} / {{ number_format($totalBetina) }}</div>
                    <div class="stat-sub-neo">rasio kawanan</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card-neo accent-blue">
                <div class="stat-icon-neo icon-blue"><i class="bi bi-egg-fill"></i></div>
                <div>
                    <div class="stat-label-neo">Produksi Telur</div>
                    <div class="stat-value-neo" id="kpiProduksiTelur">{{ number_format($grandTotalProduksi) }}</div>
                    <div class="stat-sub-neo">butir bulan ini</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4">
            <div class="stat-card-neo accent-blue">
                <div class="stat-icon-neo icon-blue"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <div class="stat-label-neo">Rata-rata Harian</div>
                    <div class="stat-value-neo" id="kpiRataRataHarian">{{ number_format($rataRataHarianProduksi, 1) }}</div>
                    <div class="stat-sub-neo">butir/hari ({{ $hariPembagi }} hari)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card-neo accent-green">
                <div class="stat-icon-neo icon-green"><i class="bi bi-cart-check-fill"></i></div>
                <div>
                    <div class="stat-label-neo">Telur Terjual</div>
                    <div class="stat-value-neo">{{ number_format($telurTerjualBulanIni) }}</div>
                    <div class="stat-sub-neo">butir bulan ini</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card-neo accent-amber">
                <div class="stat-icon-neo icon-amber"><i class="bi bi-gift-fill"></i></div>
                <div>
                    <div class="stat-label-neo">Telur Bonus</div>
                    <div class="stat-value-neo">{{ number_format($bonusBulanIni) }}</div>
                    <div class="stat-sub-neo">butir bulan ini</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4">
            <div class="stat-card-neo {{ $stokBelumTerjual < 0 ? 'accent-red' : 'accent-purple' }}">
                <div class="stat-icon-neo {{ $stokBelumTerjual < 0 ? 'icon-red' : 'icon-purple' }}"><i class="bi bi-box-seam-fill"></i></div>
                <div>
                    <div class="stat-label-neo">Telur Belum Terjual</div>
                    <div class="stat-value-neo {{ $stokBelumTerjual < 0 ? 'text-danger' : '' }}">
                        {{ number_format($stokBelumTerjual) }}
                    </div>
                    <div class="stat-sub-neo">butir</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card-neo accent-green">
                <div class="stat-icon-neo icon-green"><i class="bi bi-cash-coin"></i></div>
                <div>
                    <div class="stat-label-neo">Penjualan</div>
                    <div class="stat-value-neo">Rp {{ number_format($omzetBulanIni, 0, ',', '.') }}</div>
                    <div class="stat-sub-neo">bulan ini</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card-neo accent-red">
                <div class="stat-icon-neo icon-red"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="stat-label-neo">Pengeluaran</div>
                    <div class="stat-value-neo">Rp {{ number_format($pengeluaranBulanIni, 0, ',', '.') }}</div>
                    <div class="stat-sub-neo">bulan ini</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4">
            <div class="stat-card-neo {{ $labaBersih >= 0 ? 'accent-green' : 'accent-red' }}">
                <div class="stat-icon-neo {{ $labaBersih >= 0 ? 'icon-green' : 'icon-red' }}"><i class="bi bi-wallet2"></i></div>
                <div>
                    <div class="stat-label-neo">Uang Tersedia</div>
                    <div class="stat-value-neo {{ $labaBersih >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp {{ number_format($labaBersih, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Produksi Harian per Kandang (pivot: tanggal x kandang) -->
    <div class="mb-4">
        <h5 class="fw-bold mb-3">Detail Produksi Harian per Kandang</h5>

        <!-- Filter kandang yang dihitung ke Total & Rata-rata (default: semua tercentang) -->
        <div class="mb-3 p-2" style="border: 1px dashed var(--color-border); border-radius: var(--radius);">
            <div class="fw-bold mb-2 small">Kandang dihitung ke Total & Rata-rata:</div>
            <div class="d-flex flex-wrap gap-3">
                @foreach ($kandangs as $k)
                    <div class="form-check">
                        <input class="form-check-input chk-kandang-hitung" type="checkbox"
                            value="{{ $k->id }}" id="chkKandang{{ $k->id }}" checked>
                        <label class="form-check-label small" for="chkKandang{{ $k->id }}">{{ $k->nama }}</label>
                    </div>
                @endforeach
            </div>
            <div class="form-text small mb-0">
                Uncheck kandang untuk menghitung ulang Total &amp; Rata-rata, grafik Tren, dan tabel Produktivitas
                di bawah tanpa data kandang tersebut.
            </div>
        </div>

        {{-- Wrapper ini yang membatasi tabel supaya tetap di dalam area putih (main-content-neo).
             Kalau tabelnya lebih lebar dari layar, cukup di-scroll ke samping (overflow-x: auto di CSS),
             tidak lagi di-scale/zoom karena itu yang dulu bikin tabel numpuk ke konten bawah. --}}
        <div class="pivot-scale-wrapper" id="pivotWrapper">
            <table class="table table-neo table-sm align-middle mb-0" id="pivotTable">
                <thead>
                    <tr>
                        <th class="text-center">Tanggal</th>
                        @foreach ($kandangs as $k)
                            <th class="text-center th-kandang" data-kandang-id="{{ $k->id }}">{{ $k->nama }}</th>
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
                                <td class="text-center cell-kandang" data-kandang-id="{{ $k->id }}" data-value="{{ $val }}">{{ $val ?: '-' }}</td>
                            @endforeach
                            <td class="text-center fw-bold cell-row-total" data-value="{{ $rowTotal }}">{{ $rowTotal }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $kandangs->count() + 2 }}" class="text-center py-3">Belum ada data
                                produksi bulan ini</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td class="text-center">Total</td>
                        @foreach ($kandangs as $k)
                            <td class="text-center cell-kandang-total" data-kandang-id="{{ $k->id }}">{{ number_format($totalPerKandang[$k->id] ?? 0) }}</td>
                        @endforeach
                        <td class="text-center" id="footerGrandTotal">{{ number_format($grandTotalProduksi) }}</td>
                    </tr>
                    <tr class="fst-italic text-muted">
                        <td class="text-center">Rata-rata/hari ({{ $hariPembagi }} hari)</td>
                        @foreach ($kandangs as $k)
                            <td class="text-center cell-kandang-avg" data-kandang-id="{{ $k->id }}">{{ number_format($rataRataPerKandang[$k->id] ?? 0, 1) }}</td>
                        @endforeach
                        <td class="text-center" id="footerGrandAvg">{{ number_format($rataRataHarianProduksi, 1) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Grafik Tren -->
    <div class="mb-4">
        <h5 class="fw-bold mb-3">Tren Harian Bulan Ini</h5>
        <div style="position: relative; height: 260px;">
            <canvas id="chartTren"></canvas>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Perbandingan Antar Kandang -->
        <div class="col-lg-6">
            <h5 class="fw-bold mb-3">Produktivitas per Kandang</h5>
            <div class="table-responsive">
                <table class="table table-neo align-middle mb-0" id="produktivitasTable">
                    <thead>
                        <tr>
                            <th>Kandang</th>
                            <th>Jenis</th>
                            <th>Telur (butir)</th>
                            <th>Rata-rata/hari</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($produktivitasKandang as $p)
                            <tr data-kandang-id="{{ $p['kandang_id'] }}">
                                <td>{{ $p['nama'] }}</td>
                                <td>{{ $p['jenis_ayam'] }}</td>
                                <td>{{ number_format($p['total_telur']) }}</td>
                                <td>{{ number_format($p['rata_rata_harian'], 1) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-3">Belum ada data kandang</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Pembeli -->
        <div class="col-lg-6">
            <h5 class="fw-bold mb-3">Top 5 Pembeli</h5>
            <div class="table-responsive">
                <table class="table table-neo align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Total Butir</th>
                            <th>Total Belanja</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topPembeli as $p)
                            <tr>
                                <td>{{ $p->nama_pembeli }}</td>
                                <td>{{ number_format($p->total_butir) }}</td>
                                <td>Rp {{ number_format($p->total_belanja, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-3">Belum ada penjualan bulan ini</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
           
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Breakdown Pengeluaran -->
        <div class="col-lg-6">
            <h5 class="fw-bold mb-3">Breakdown Pengeluaran</h5>
            <canvas id="chartPengeluaran" height="220"></canvas>
        </div>

        <!-- Aktivitas Terbaru -->
        {{-- FIX MASALAH 1 & 2: sekarang satu feed kronologis ($aktivitasTerbaru) yang sudah ikut
             filter bulan/tahun dan diurutkan lintas jenis berdasarkan created_at, bukan ditumpuk
             per jenis lagi. --}}
        <div class="col-lg-6">
            <h5 class="fw-bold mb-3">Aktivitas Terbaru</h5>
            <ul class="list-unstyled small">
                @forelse ($aktivitasTerbaru as $a)
                    @php
                        $dotClass = match ($a['tipe']) {
                            'penjualan'  => 'text-success',
                            'pengeluaran'=> 'text-danger',
                            'produksi'   => 'text-warning',
                            default      => 'text-muted',
                        };
                    @endphp
                    <li class="mb-2">
                        <span class="{{ $dotClass }}">●</span>
                        {{ $a['deskripsi'] }}
                        @if ($a['jumlah'])
                            — {{ $a['jumlah'] }}
                        @endif
                        <span class="text-muted">({{ \Carbon\Carbon::parse($a['tanggal'])->format('d M Y') }})</span>
                    </li>
                @empty
                    <li class="text-muted">Belum ada aktivitas bulan ini</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        const dashboardBaseUrl = "{{ route('dashboard.index') }}";

        // Filter bulan/tahun -> reload halaman dengan query string (dashboard render server-side)
        function reloadDashboard() {
            const bulan = document.getElementById('filterBulan').value;
            const tahun = document.getElementById('filterTahun').value;
            window.location.href = `${dashboardBaseUrl}?bulan=${bulan}&tahun=${tahun}`;
        }
        document.getElementById('filterBulan').addEventListener('change', reloadDashboard);
        document.getElementById('filterTahun').addEventListener('change', reloadDashboard);

        // ===== Filter kandang untuk Total & Rata-rata (checkbox, default semua tercentang) =====
        const hariPembagi = {{ $hariPembagi }};

        // FIX MASALAH 4: produksi harian per kandang (urutan sama seperti chartLabels/tanggal),
        // dipakai untuk menghitung ulang garis "Produksi" di grafik Tren saat checkbox berubah.
        const chartProduksiPerKandang = @json($chartProduksiPerKandang);

        function formatRibuan(n) {
            return new Intl.NumberFormat('id-ID').format(Math.round(n));
        }
        function formatSatuDesimal(n) {
            return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(n);
        }

        function hitungUlangTotalRataRata() {
            const checkedIds = Array.from(document.querySelectorAll('.chk-kandang-hitung:checked')).map(c => c.value);

            // Tandai redup kolom header & footer yang tidak dicentang
            document.querySelectorAll('.th-kandang, .cell-kandang-total, .cell-kandang-avg').forEach(function(el) {
                el.classList.toggle('kandang-excluded', !checkedIds.includes(el.dataset.kandangId));
            });

            let grandTotal = 0;

            // Hitung ulang tiap baris (per tanggal) pivot table
            document.querySelectorAll('tbody tr[data-tgl]').forEach(function(tr) {
                let rowTotal = 0;
                tr.querySelectorAll('.cell-kandang').forEach(function(td) {
                    const included = checkedIds.includes(td.dataset.kandangId);
                    td.classList.toggle('kandang-excluded', !included);
                    if (included) rowTotal += parseFloat(td.dataset.value || 0);
                });
                const totalCell = tr.querySelector('.cell-row-total');
                totalCell.textContent = rowTotal ? formatRibuan(rowTotal) : '-';
                grandTotal += rowTotal;
            });

            const rataRata = hariPembagi > 0 ? grandTotal / hariPembagi : 0;

            // Update footer tabel pivot
            document.getElementById('footerGrandTotal').textContent = formatRibuan(grandTotal);
            document.getElementById('footerGrandAvg').textContent = formatSatuDesimal(rataRata);

            // Update kartu KPI
            document.getElementById('kpiProduksiTelur').textContent = formatRibuan(grandTotal);
            document.getElementById('kpiRataRataHarian').textContent = formatSatuDesimal(rataRata);

            // (a) Filter ulang chart Tren: hitung ulang garis "Produksi" hanya dari kandang tercentang
            if (window.chartTren) {
                const jumlahHari = chartLabels.length;
                const produksiTerfilter = new Array(jumlahHari).fill(0);
                checkedIds.forEach(function(id) {
                    const dataKandang = chartProduksiPerKandang[id];
                    if (!dataKandang) return;
                    for (let i = 0; i < jumlahHari; i++) {
                        produksiTerfilter[i] += dataKandang[i] || 0;
                    }
                });
                window.chartTren.data.datasets[0].data = produksiTerfilter;
                window.chartTren.update();
            }

            // (b) Sembunyikan/highlight baris kandang yang di-uncheck di tabel Produktivitas per Kandang
            document.querySelectorAll('#produktivitasTable tbody tr[data-kandang-id]').forEach(function(tr) {
                tr.classList.toggle('kandang-excluded', !checkedIds.includes(tr.dataset.kandangId));
            });
        }

        document.querySelectorAll('.chk-kandang-hitung').forEach(function(chk) {
            chk.addEventListener('change', hitungUlangTotalRataRata);
        });

        // Catatan: fungsi fitPivotTable() (scale/zoom tabel) SUDAH DIHAPUS.
        // Sekarang tabel pivot cukup di-scroll ke samping (lihat CSS .pivot-scale-wrapper),
        // supaya tidak pernah keluar/numpuk ke konten lain seperti sebelumnya.

        // Grafik tren harian
        const chartLabels = @json($chartLabels);
        const chartProduksi = @json($chartProduksi);
        const chartPenjualan = @json($chartPenjualan);
        const chartPengeluaran = @json($chartPengeluaran);

        const isMobileNeo = window.matchMedia('(max-width: 767.98px)').matches;

        // Simpan instance chart di window.chartTren supaya bisa diupdate ulang oleh
        // hitungUlangTotalRataRata() saat checkbox kandang berubah (FIX MASALAH 4).
        window.chartTren = new Chart(document.getElementById('chartTren'), {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Produksi (butir)',
                        data: chartProduksi,
                        borderColor: '#f0ad4e',
                        backgroundColor: 'transparent',
                        borderWidth: isMobileNeo ? 1.5 : 2,
                        pointRadius: isMobileNeo ? 1 : 3,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Penjualan (Rp)',
                        data: chartPenjualan,
                        borderColor: '#28a745',
                        backgroundColor: 'transparent',
                        borderWidth: isMobileNeo ? 1.5 : 2,
                        pointRadius: isMobileNeo ? 1 : 3,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Pengeluaran (Rp)',
                        data: chartPengeluaran,
                        borderColor: '#dc3545',
                        backgroundColor: 'transparent',
                        borderWidth: isMobileNeo ? 1.5 : 2,
                        pointRadius: isMobileNeo ? 1 : 3,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: isMobileNeo ? 'bottom' : 'top',
                        labels: {
                            boxWidth: isMobileNeo ? 10 : 16,
                            font: { size: isMobileNeo ? 10 : 12 }
                        }
                    },
                    tooltip: {
                        titleFont: { size: isMobileNeo ? 11 : 13 },
                        bodyFont: { size: isMobileNeo ? 11 : 13 }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: { size: isMobileNeo ? 9 : 11 },
                            autoSkip: true,
                            maxRotation: 0,
                            maxTicksLimit: isMobileNeo ? 8 : 15
                        }
                    },
                    y: {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: !isMobileNeo,
                            text: 'Butir'
                        },
                        ticks: {
                            font: { size: isMobileNeo ? 9 : 11 }
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: !isMobileNeo,
                            text: 'Rupiah'
                        },
                        ticks: {
                            font: { size: isMobileNeo ? 9 : 11 },
                            // Rupiah dipersingkat di HP (mis. 150.000 -> 150rb) biar tidak numpuk
                            callback: function(value) {
                                if (!isMobileNeo) {
                                    return new Intl.NumberFormat('id-ID').format(value);
                                }
                                if (Math.abs(value) >= 1000000) {
                                    return (value / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + 'jt';
                                }
                                if (Math.abs(value) >= 1000) {
                                    return (value / 1000).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + 'rb';
                                }
                                return value;
                            }
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });

        // Breakdown pengeluaran (pie)
        const breakdownLabels = @json($breakdownPengeluaran->pluck('keterangan'));
        const breakdownValues = @json($breakdownPengeluaran->pluck('total'));

        new Chart(document.getElementById('chartPengeluaran'), {
            type: 'pie',
            data: {
                labels: breakdownLabels,
                datasets: [{
                    data: breakdownValues,
                    backgroundColor: [
                        '#f0ad4e', '#dc3545', '#28a745', '#17a2b8', '#6f42c1',
                        '#fd7e14', '#20c997', '#6610f2', '#e83e8c', '#795548'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>

    <style>
        /* Baris kandang yang di-uncheck di tabel Produktivitas per Kandang -> diredupkan (FIX MASALAH 4) */
        #produktivitasTable tbody tr.kandang-excluded {
            opacity: 0.4;
            text-decoration: line-through;
        }
    </style>
@endsection