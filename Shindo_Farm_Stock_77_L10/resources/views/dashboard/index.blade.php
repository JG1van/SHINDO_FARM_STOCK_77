@extends('layouts.app')

@section('title', 'Dashboard - SHINDO FARM 77')

@section('content')
    <div class="page-header-neo">
        <h2 class="fw-bold mb-0">Dashboard</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('kalkulator.index') }}" class="btn btn-neo btn-neo-secondary">
                <i class="bi bi-calculator"></i> Kalkulator
            </a>
            <a href="{{ route('dashboard.export', ['bulan' => $bulan, 'tahun' => $tahun]) }}"
                class="btn btn-neo btn-neo-primary" title="Export laporan {{ $labelBulan }}">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
        </div>
    </div>

    {{-- ===================== A. RINGKASAN KESELURUHAN (tidak terikat bulan) ===================== --}}
    <h5 class="fw-bold mb-3">Ringkasan Keseluruhan <small class="text-muted fw-normal">tidak terikat bulan</small></h5>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-6">
            <div class="stat-card-neo accent-amber">
                <div class="stat-icon-neo icon-amber"><i class="bi bi-feather"></i></div>
                <div>
                    <div class="stat-label-neo">Total Ayam</div>
                    <div class="stat-value-neo">{{ number_format($totalAyam) }}</div>
                    <div class="stat-sub-neo">ekor</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-6">
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
            <div class="stat-card-neo accent-green">
                <div class="stat-icon-neo icon-green"><i class="bi bi-cash-coin"></i></div>
                <div>
                    <div class="stat-label-neo">Penjualan</div>
                    <div class="stat-value-neo">Rp {{ number_format($omzetTotal, 0, ',', '.') }}</div>
                    <div class="stat-sub-neo">total keseluruhan</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card-neo accent-red">
                <div class="stat-icon-neo icon-red"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="stat-label-neo">Pengeluaran</div>
                    <div class="stat-value-neo">Rp {{ number_format($pengeluaranTotal, 0, ',', '.') }}</div>
                    <div class="stat-sub-neo">total keseluruhan</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card-neo {{ $uangTersedia >= 0 ? 'accent-green' : 'accent-red' }}">
                <div class="stat-icon-neo {{ $uangTersedia >= 0 ? 'icon-green' : 'icon-red' }}"><i class="bi bi-wallet2"></i></div>
                <div>
                    <div class="stat-label-neo">Uang Tersedia</div>
                    <div class="stat-value-neo {{ $uangTersedia >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp {{ number_format($uangTersedia, 0, ',', '.') }}
                    </div>
                    <div class="stat-sub-neo">total keseluruhan</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== B. PRODUKSI & PENJUALAN TELUR (mengikuti bulan) ===================== --}}
    <h5 class="fw-bold mb-3">Produksi &amp; Penjualan Telur</h5>
    <div class="egg-card mb-4" id="kartuTelur">
        <div class="egg-card-head">
            <b>Telur</b>
            <div class="d-flex gap-2">
                <select id="filterBulan" class="form-select form-control-neo" style="min-width:150px" aria-label="Bulan">
                    @foreach ($namaBulanList as $no => $nama)
                        <option value="{{ $no }}" {{ $no == $bulan ? 'selected' : '' }}>{{ $nama }}</option>
                    @endforeach
                </select>
                <select id="filterTahun" class="form-select form-control-neo" style="min-width:100px" aria-label="Tahun">
                    @foreach (range(now()->year, now()->year - 3) as $t)
                        <option value="{{ $t }}" {{ $t == $tahun ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Klik area ini: bergantian tampil bulan terpilih <-> bulan sebelumnya --}}
        <div class="egg-card-body" id="eggBody" tabindex="0" role="button"
            aria-label="Klik untuk menampilkan bulan sebelumnya">
            <div id="panelBulan">
                <h6 class="fw-bold mb-2">{{ $labelBulan }}</h6>
                <div class="egg-grid">
                    <div class="egg-stat"><span>Produksi Telur (butir)</span><b id="kpiProduksiTelur">{{ number_format($grandTotalProduksi) }}</b></div>
                    <div class="egg-stat"><span>Telur Terjual (butir)</span><b>{{ number_format($telurTerjualBulanIni) }}</b></div>
                    <div class="egg-stat"><span>Telur Bonus (butir)</span><b>{{ number_format($bonusBulanIni) }}</b></div>
                    <div class="egg-stat"><span>Rata-rata Harian (butir/hari, {{ $hariPembagi }} hari)</span><b id="kpiRataRataHarian">{{ number_format($rataRataHarianProduksi, 1) }}</b></div>
                    <div class="egg-stat egg-stat-sisa"><span>Belum Terjual (produksi − terjual − bonus)</span><b id="kpiBelumTerjual" class="{{ $stokBelumTerjual < 0 ? 'text-danger' : '' }}">{{ number_format($stokBelumTerjual) }}</b></div>
                    <div class="egg-stat egg-stat-uang"><span>Penjualan (bulan ini)</span><b>Rp {{ number_format($penjualanBulanIni, 0, ',', '.') }}</b></div>
                    <div class="egg-stat egg-stat-uang"><span>Pengeluaran (bulan ini)</span><b>Rp {{ number_format($pengeluaranBulanIni, 0, ',', '.') }}</b></div>
                    <div class="egg-stat egg-stat-uang"><span>Uang Tersedia (penjualan − pengeluaran)</span><b class="{{ $uangBulanIni >= 0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format($uangBulanIni, 0, ',', '.') }}</b></div>
                </div>
                <p class="egg-hint">Klik untuk melihat bulan sebelumnya</p>
            </div>

            <div id="panelLalu" hidden>
                <h6 class="fw-bold mb-2">{{ $labelBulanLalu }} <small class="text-muted fw-normal">(bulan sebelumnya)</small></h6>
                <div class="egg-grid egg-grid-lalu">
                    <div class="egg-stat"><span>Produksi Telur (butir)</span><b>{{ number_format($ringkasanLalu['produksi']) }}</b></div>
                    <div class="egg-stat"><span>Telur Terjual (butir)</span><b>{{ number_format($ringkasanLalu['terjual']) }}</b></div>
                    <div class="egg-stat"><span>Telur Bonus (butir)</span><b>{{ number_format($ringkasanLalu['bonus']) }}</b></div>
                    <div class="egg-stat"><span>Rata-rata Harian (butir/hari, {{ $ringkasanLalu['hari'] }} hari)</span><b>{{ number_format($ringkasanLalu['rata_harian'], 1) }}</b></div>
                    <div class="egg-stat egg-stat-sisa"><span>Belum Terjual (produksi − terjual − bonus)</span><b class="{{ $ringkasanLalu['belum_terjual'] < 0 ? 'text-danger' : '' }}">{{ number_format($ringkasanLalu['belum_terjual']) }}</b></div>
                    <div class="egg-stat egg-stat-uang"><span>Penjualan (bulan ini)</span><b>Rp {{ number_format($ringkasanLalu['penjualan'], 0, ',', '.') }}</b></div>
                    <div class="egg-stat egg-stat-uang"><span>Pengeluaran (bulan ini)</span><b>Rp {{ number_format($ringkasanLalu['pengeluaran'], 0, ',', '.') }}</b></div>
                    <div class="egg-stat egg-stat-uang"><span>Uang Tersedia (penjualan − pengeluaran)</span><b class="{{ $ringkasanLalu['uang'] >= 0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format($ringkasanLalu['uang'], 0, ',', '.') }}</b></div>
                </div>
                <p class="egg-hint">Klik lagi untuk kembali</p>
            </div>
        </div>

        {{-- Rincian data bulan terpilih, satu kotak dengan kartu di atas --}}
        <div class="egg-card-detail">
            <h6 class="fw-bold mb-3">Rincian Data Bulan <span class="egg-tag">{{ $labelBulan }}</span></h6>

            <div class="mb-4">
                <h6 class="fw-bold mb-2">Detail Produksi Harian per Kandang</h6>

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
                        Uncheck kandang untuk menghitung ulang Produksi &amp; Rata-rata di kartu, tabel ini, grafik Tren,
                        dan tabel Produktivitas tanpa kandang tersebut.
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
                                    <td colspan="{{ $kandangs->count() + 2 }}" class="text-center py-3">Belum ada data produksi bulan ini</td>
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

            <div class="mb-4">
                <h6 class="fw-bold mb-2">Tren Harian Bulan Ini</h6>
                <div style="position: relative; height: 260px;">
                    <canvas id="chartTren"></canvas>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-7">
                    <h6 class="fw-bold mb-2">Produktivitas per Kandang</h6>
                    <div class="table-responsive">
                        <table class="table table-neo align-middle mb-0 tabel-kecil" id="produktivitasTable">
                            <thead>
                                <tr>
                                    <th>Kandang</th>
                                    <th>Jenis</th>
                                    <th>Telur</th>
                                    <th>Rata²/hr</th>
                                    <th>Target</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($produktivitasKandang as $p)
                                    <tr data-kandang-id="{{ $p['kandang_id'] }}">
                                        <td>{{ $p['nama'] }}</td>
                                        <td>{{ $p['jenis_ayam'] }}</td>
                                        <td>{{ number_format($p['total_telur']) }}</td>
                                        <td>{{ number_format($p['rata_rata_harian'], 1) }}</td>
                                        <td>{{ number_format($p['target']) }}</td>
                                        <td class="fw-bold" style="color:{{ $p['persen_warna'] }}">{{ number_format($p['persen'], 1) }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-3">Belum ada data kandang</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-5">
                    <h6 class="fw-bold mb-2">Top 5 Pembeli</h6>
                    <div class="table-responsive">
                        <table class="table table-neo align-middle mb-0 tabel-kecil">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Butir</th>
                                    <th>Belanja</th>
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
        </div>
    </div>

    {{-- ===================== C. RINCIAN KESELURUHAN (tidak terikat bulan) ===================== --}}
    <h5 class="fw-bold mb-3">Rincian Keseluruhan <small class="text-muted fw-normal">tidak terikat bulan</small></h5>
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <h6 class="fw-bold mb-3">Breakdown Pengeluaran</h6>
            <canvas id="chartPengeluaran" height="220"></canvas>
        </div>

        <div class="col-lg-6">
            <h6 class="fw-bold mb-3">Aktivitas Terbaru</h6>
            <ul class="list-unstyled small">
                @forelse ($aktivitasTerbaru as $a)
                    @php
                        $dotClass = match ($a['tipe']) {
                            'penjualan'   => 'text-success',
                            'pengeluaran' => 'text-danger',
                            'produksi'    => 'text-warning',
                            default       => 'text-muted',
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
                    <li class="text-muted">Belum ada aktivitas</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        const dashboardBaseUrl = "{{ route('dashboard.index') }}";

        // Filter bulan/tahun (di dalam kartu Telur) -> reload, lalu kembali ke kartu Telur
        function reloadDashboard() {
            const bulan = document.getElementById('filterBulan').value;
            const tahun = document.getElementById('filterTahun').value;
            window.location.href = `${dashboardBaseUrl}?bulan=${bulan}&tahun=${tahun}#kartuTelur`;
        }
        document.getElementById('filterBulan').addEventListener('change', reloadDashboard);
        document.getElementById('filterTahun').addEventListener('change', reloadDashboard);

        // Klik kartu: bergantian bulan terpilih <-> bulan sebelumnya
        const eggBody = document.getElementById('eggBody');
        function flipKartuTelur() {
            const panelBulan = document.getElementById('panelBulan');
            const panelLalu = document.getElementById('panelLalu');
            const tampilLalu = panelLalu.hidden;
            panelLalu.hidden = !tampilLalu;
            panelBulan.hidden = tampilLalu;
        }
        eggBody.addEventListener('click', flipKartuTelur);
        eggBody.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                flipKartuTelur();
            }
        });

        // ===== Data grafik Tren =====
        const hariPembagi = {{ $hariPembagi }};
        const telurTerjualBulan = {{ (int) $telurTerjualBulanIni }};
        const bonusBulan = {{ (int) $bonusBulanIni }};
        const chartLabels = @json($chartLabels);
        const chartProduksi = @json($chartProduksi);
        const chartPenjualan = @json($chartPenjualan);
        const chartPengeluaran = @json($chartPengeluaran);
        const chartProduksiPerKandang = @json($chartProduksiPerKandang);

        function formatRibuan(n) {
            return new Intl.NumberFormat('id-ID').format(Math.round(n));
        }
        function formatSatuDesimal(n) {
            return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(n);
        }

        // ===== Filter kandang untuk Total & Rata-rata (default semua tercentang) =====
        function hitungUlangTotalRataRata() {
            const checkedIds = Array.from(document.querySelectorAll('.chk-kandang-hitung:checked')).map(c => c.value);

            document.querySelectorAll('.th-kandang, .cell-kandang-total, .cell-kandang-avg').forEach(function(el) {
                el.classList.toggle('kandang-excluded', !checkedIds.includes(el.dataset.kandangId));
            });

            let grandTotal = 0;

            document.querySelectorAll('#pivotTable tbody tr[data-tgl]').forEach(function(tr) {
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

            document.getElementById('footerGrandTotal').textContent = formatRibuan(grandTotal);
            document.getElementById('footerGrandAvg').textContent = formatSatuDesimal(rataRata);

            // Kartu Telur (bulan terpilih)
            document.getElementById('kpiProduksiTelur').textContent = formatRibuan(grandTotal);
            document.getElementById('kpiRataRataHarian').textContent = formatSatuDesimal(rataRata);
            const belumTerjual = grandTotal - telurTerjualBulan - bonusBulan;
            const elSisa = document.getElementById('kpiBelumTerjual');
            elSisa.textContent = formatRibuan(belumTerjual);
            elSisa.classList.toggle('text-danger', belumTerjual < 0);

            // Grafik Tren: hitung ulang garis Produksi hanya dari kandang tercentang
            if (window.chartTren) {
                const produksiTerfilter = new Array(chartLabels.length).fill(0);
                checkedIds.forEach(function(id) {
                    const dataKandang = chartProduksiPerKandang[id];
                    if (!dataKandang) return;
                    for (let i = 0; i < chartLabels.length; i++) {
                        produksiTerfilter[i] += dataKandang[i] || 0;
                    }
                });
                window.chartTren.data.datasets[0].data = produksiTerfilter;
                window.chartTren.update();
            }

            // Tabel Produktivitas: redupkan kandang yang di-uncheck
            document.querySelectorAll('#produktivitasTable tbody tr[data-kandang-id]').forEach(function(tr) {
                tr.classList.toggle('kandang-excluded', !checkedIds.includes(tr.dataset.kandangId));
            });
        }

        document.querySelectorAll('.chk-kandang-hitung').forEach(function(chk) {
            chk.addEventListener('change', hitungUlangTotalRataRata);
        });

        // ===== Grafik tren harian =====
        const isMobileNeo = window.matchMedia('(max-width: 767.98px)').matches;

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
                interaction: { mode: 'index', intersect: false },
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
                        title: { display: !isMobileNeo, text: 'Butir' },
                        ticks: { font: { size: isMobileNeo ? 9 : 11 } }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        title: { display: !isMobileNeo, text: 'Rupiah' },
                        ticks: {
                            font: { size: isMobileNeo ? 9 : 11 },
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
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });

        // ===== Breakdown pengeluaran (pie, keseluruhan) =====
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
            options: { responsive: true }
        });
    </script>

    <style>
        /* Kartu Telur: satu kotak besar berisi 4 angka bulan + rincian data bulan */
        .egg-card {
            border: 2px solid var(--color-border);
            background: #fff;
            box-shadow: 5px 5px 0 var(--color-border);
        }
        .egg-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            padding: .75rem 1rem;
            border-bottom: 2px solid var(--color-border);
            background: #ffc93c;
        }
        .egg-card-body {
            padding: 1rem;
            cursor: pointer;
        }
        .egg-card-body:focus-visible {
            outline: 3px solid #17a2b8;
            outline-offset: -3px;
        }
        .egg-card-body [hidden] { display: none; }
        .egg-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: .75rem;
        }
        .egg-stat {
            border: 2px solid var(--color-border);
            padding: .6rem .8rem;
            display: flex;
            flex-direction: column;
            gap: .2rem;
        }
        .egg-stat span { font-size: .75rem; color: #6b6b6b; }
        .egg-stat b { font-size: 1.5rem; line-height: 1.1; }
        .egg-stat-sisa { background: #f1ecfb; }
        .egg-stat-uang { background: #eaf7ee; }
        .egg-stat-uang b { font-size: 1.25rem; }
        .egg-grid-lalu .egg-stat { background: #f7f4fd; }
        .egg-grid-lalu .egg-stat-sisa { background: #e6dcf9; }
        .egg-hint {
            margin: .6rem 0 0;
            text-align: center;
            font-size: .75rem;
            color: #6b6b6b;
        }
        .egg-card-detail {
            border-top: 2px solid var(--color-border);
            padding: 1rem;
        }
        .egg-tag {
            display: inline-block;
            border: 2px solid var(--color-border);
            background: #7fd6ea;
            padding: 0 .5rem;
            font-size: .8rem;
        }

        /* Kandang yang di-uncheck -> diredupkan */
        .kandang-excluded { opacity: 0.4; }
        #produktivitasTable tbody tr.kandang-excluded {
            opacity: 0.4;
            text-decoration: line-through;
        }

        /* Perkecil tulisan tabel Produktivitas & Top Pembeli */
        .tabel-kecil {
            font-size: 0.62rem;
            table-layout: fixed;
            width: 100%;
        }
        .tabel-kecil th,
        .tabel-kecil td {
            padding: 0.25rem 0.3rem;
            white-space: normal;
            word-break: break-word;
            line-height: 1.15;
        }
    </style>
@endsection