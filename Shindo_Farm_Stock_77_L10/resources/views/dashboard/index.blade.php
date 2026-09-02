@extends('layouts.app')

@section('title', 'Dashboard - SHINDO FARM 77')

@section('content')
    <div class="page-header-neo">
        <h2 class="fw-bold mb-0">Dashboard</h2>
        <a id="btnExportExcel" href="{{ route('dashboard.export', ['bulan' => $bulan, 'tahun' => $tahun]) }}"
            class="btn btn-neo btn-neo-primary">
            <i class="bi bi-file-earmark-excel"></i> Export Excel
        </a>
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
        </div>

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
                <table class="table table-neo align-middle mb-0">
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
                            <tr>
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
            <div class="small text-muted mt-2">
                Rata-rata harga jual: Rp {{ number_format($rataRataHargaPerButir, 0, ',', '.') }} / butir
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
        <div class="col-lg-6">
            <h5 class="fw-bold mb-3">Aktivitas Terbaru</h5>
            <ul class="list-unstyled small">
                @foreach ($penjualanTerbaru as $p)
                    <li class="mb-2">
                        <span class="text-success">●</span>
                        Penjualan {{ $p->jumlah_telur }} butir ke <strong>{{ $p->nama_pembeli }}</strong>
                        — Rp {{ number_format($p->total_harga, 0, ',', '.') }}
                        <span class="text-muted">({{ \Carbon\Carbon::parse($p->tanggal)->format('d M Y') }})</span>
                    </li>
                @endforeach
                @foreach ($pengeluaranTerbaru as $p)
                    <li class="mb-2">
                        <span class="text-danger">●</span>
                        Pengeluaran <strong>{{ $p->keterangan }}</strong>
                        — Rp {{ number_format($p->jumlah, 0, ',', '.') }}
                        <span class="text-muted">({{ \Carbon\Carbon::parse($p->tanggal)->format('d M Y') }})</span>
                    </li>
                @endforeach
                @foreach ($telurTerbaru as $t)
                    <li class="mb-2">
                        <span class="text-warning">●</span>
                        Input produksi {{ $t->jumlah_butir }} butir di kandang <strong>{{ $t->kandang_nama }}</strong>
                        <span class="text-muted">({{ \Carbon\Carbon::parse($t->tanggal)->format('d M Y') }})</span>
                    </li>
                @endforeach
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

            // Hitung ulang tiap baris (per tanggal)
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

            // Update footer tabel
            document.getElementById('footerGrandTotal').textContent = formatRibuan(grandTotal);
            document.getElementById('footerGrandAvg').textContent = formatSatuDesimal(rataRata);

            // Update kartu KPI
            document.getElementById('kpiProduksiTelur').textContent = formatRibuan(grandTotal);
            document.getElementById('kpiRataRataHarian').textContent = formatSatuDesimal(rataRata);
        }

        document.querySelectorAll('.chk-kandang-hitung').forEach(function(chk) {
            chk.addEventListener('change', hitungUlangTotalRataRata);
        });

        // ===== Auto-shrink tabel pivot Produksi Harian supaya selalu pas di lebar layar =====
        // Prinsipnya: bukan di-scroll, tapi di-scale (zoom out) proporsional kalau
        // lebar asli tabel (scrollWidth) lebih besar dari lebar wrapper-nya.
        function fitPivotTable() {
            const wrapper = document.getElementById('pivotWrapper');
            const table = document.getElementById('pivotTable');
            if (!wrapper || !table) return;

            // Reset dulu supaya ukuran yang diukur adalah ukuran asli (belum di-scale)
            table.style.transform = 'none';
            wrapper.style.height = 'auto';

            const wrapperWidth = wrapper.clientWidth;
            const tableWidth = table.scrollWidth;
            const tableHeight = table.offsetHeight;

            if (tableWidth > wrapperWidth && wrapperWidth > 0) {
                // Batas minimal 0.4 (40%) supaya teksnya tidak sampai tidak kebaca sama sekali
                // kalau kandangnya sangat banyak.
                const scale = Math.max(wrapperWidth / tableWidth, 0.4);
                table.style.transform = `scale(${scale})`;
                // Tinggi wrapper disesuaikan hasil scale, biar tidak ada ruang kosong di bawah tabel
                wrapper.style.height = (tableHeight * scale) + 'px';
            }
        }

        // Jalankan setelah semua konten (termasuk font) selesai dimuat, supaya ukuran akurat
        window.addEventListener('load', fitPivotTable);

        // Jalankan ulang saat ukuran layar berubah (resize window / rotate HP), di-debounce
        let _fitPivotTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(_fitPivotTimeout);
            _fitPivotTimeout = setTimeout(fitPivotTable, 150);
        });

        // Grafik tren harian
        const chartLabels = @json($chartLabels);
        const chartProduksi = @json($chartProduksi);
        const chartPenjualan = @json($chartPenjualan);
        const chartPengeluaran = @json($chartPengeluaran);

        const isMobileNeo = window.matchMedia('(max-width: 767.98px)').matches;

        new Chart(document.getElementById('chartTren'), {
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
@endsection