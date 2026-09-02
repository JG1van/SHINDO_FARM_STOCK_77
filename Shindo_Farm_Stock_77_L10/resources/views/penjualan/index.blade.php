@extends('layouts.app')

@section('title', 'Penjualan - SHINDO FARM 77')

@section('content')
    <div class="page-header-neo">
        <h2 class="fw-bold mb-0">Data Penjualan</h2>
        <button class="btn btn-neo btn-neo-primary px-4 py-2" onclick="bukaModalTambah()">+ Tambah Penjualan</button>
    </div>

    <!-- Filter Bulan & Tahun & Search -->
    <div class="row g-2 mb-3">
    <div class="col-12 col-md-4">
        <select id="filterBulan" class="form-select form-control-neo w-100">
            @foreach (range(1, 12) as $b)
                <option value="{{ $b }}" {{ $b == $bulan ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-4">
        <select id="filterTahun" class="form-select form-control-neo w-100">
            @foreach (range(now()->year, now()->year - 3) as $t)
                <option value="{{ $t }}" {{ $t == $tahun ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-4">
        <input type="text" id="searchPembeli" class="form-control form-control-neo w-100" placeholder="Cari nama pembeli...">
    </div>
</div>
    <div class="table-responsive">
        <table class="table table-neo align-middle mb-0">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Pembeli</th>
                    <th>Jumlah Telur</th>
                    <th>Total Harga</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody id="tabelPenjualan">
                <tr>
                    <td colspan="5" class="text-center py-4">Memuat data...</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="fw-bold" style="border-top: 2px solid #000;">
                    <td colspan="2" class="text-end">Total</td>
                    <td id="totalJumlahTelur">0</td>
                    <td id="totalHarga">Rp 0</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Modal Tambah/Edit -->
    <div class="modal fade modal-neo" id="modalPenjualan" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formPenjualan">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalPenjualanTitle">Tambah Penjualan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="penjualan_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date" class="form-control form-control-neo" id="tanggal" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Pembeli</label>
                            <input type="text" class="form-control form-control-neo" id="nama_pembeli" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Jumlah Telur</label>
                            <input type="number" class="form-control form-control-neo" id="jumlah_telur" min="1"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Total Harga</label>
                            <input type="number" class="form-control form-control-neo" id="total_harga" min="0"
                                step="0.01" required>
                        </div>
                        <div id="errorPenjualan" class="text-danger small"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-neo btn-neo-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-neo btn-neo-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade modal-neo" id="modalHapusPenjualan" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Hapus Penjualan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Yakin ingin menghapus data penjualan
                        <strong id="namaPenjualanHapus"></strong>? Data ini tidak bisa dikembalikan.
                    </p>
                    <div id="errorHapusPenjualan" class="text-danger small mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-neo btn-neo-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-neo btn-neo-danger" id="btnKonfirmasiHapusPenjualan">Ya,
                        Hapus</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const penjualanBaseUrl = "{{ route('penjualan.index') }}";
        const modalPenjualan = new bootstrap.Modal(document.getElementById('modalPenjualan'));
        const modalHapusPenjualan = new bootstrap.Modal(document.getElementById('modalHapusPenjualan'));
        let idPenjualanDihapus = null;
        let dataPenjualanAll = [];

        function formatRupiah(angka) {
            return 'Rp ' + Number(angka).toLocaleString('id-ID');
        }

        // Ambil hanya "YYYY-MM-DD" walau backend kirim format lain (jaga-jaga)
        function formatTanggal(tgl) {
            return tgl ? tgl.substring(0, 10) : '-';
        }

        // Hitung ulang baris Total di bawah tabel berdasarkan data yang SEDANG TAMPIL
        // (jadi kalau lagi di-search, totalnya cuma menjumlahkan hasil pencarian itu saja)
        function renderTotalPenjualan(data) {
            const totalTelur = data.reduce((sum, p) => sum + (Number(p.jumlah_telur) || 0), 0);
            const totalRp = data.reduce((sum, p) => sum + (Number(p.total_harga) || 0), 0);
            document.getElementById('totalJumlahTelur').textContent = totalTelur.toLocaleString('id-ID');
            document.getElementById('totalHarga').textContent = formatRupiah(totalRp);
        }

        function renderTabelPenjualan(data) {
            const tbody = document.getElementById('tabelPenjualan');
            if (!data.length) {
                tbody.innerHTML =
                    '<tr><td colspan="5" class="text-center py-4">Tidak ada data yang cocok</td></tr>';
                renderTotalPenjualan(data); // tetap tampilkan Total = 0, bukan dikosongkan
                return;
            }
            tbody.innerHTML = data.map(p => `
        <tr>
          <td>${formatTanggal(p.tanggal)}</td>
          <td>${p.nama_pembeli}</td>
          <td>${p.jumlah_telur}</td>
          <td>${formatRupiah(p.total_harga)}</td>
          <td class="text-end">
            <button class="btn btn-neo btn-neo-secondary btn-neo-sm" onclick="bukaModalEdit(${p.id})">Edit</button>
            <button class="btn btn-neo btn-neo-danger btn-neo-sm" onclick="bukaModalHapus(${p.id}, '${p.nama_pembeli}')">Hapus</button>
          </td>
        </tr>
      `).join('');
            renderTotalPenjualan(data);
        }

        function filterDanRender() {
            const kataKunci = document.getElementById('searchPembeli').value.trim().toLowerCase();
            if (!kataKunci) {
                renderTabelPenjualan(dataPenjualanAll);
                return;
            }
            const hasil = dataPenjualanAll.filter(p =>
                p.nama_pembeli.toLowerCase().includes(kataKunci)
            );
            renderTabelPenjualan(hasil);
        }

        function muatPenjualan() {
            const bulan = document.getElementById('filterBulan').value;
            const tahun = document.getElementById('filterTahun').value;

            fetch(`${penjualanBaseUrl}?bulan=${bulan}&tahun=${tahun}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    dataPenjualanAll = res.data;
                    filterDanRender();
                });
        }

        function bukaModalTambah() {
            document.getElementById('formPenjualan').reset();
            document.getElementById('penjualan_id').value = '';
            document.getElementById('tanggal').value = new Date().toLocaleDateString('sv-SE');
            document.getElementById('modalPenjualanTitle').textContent = 'Tambah Penjualan';
            document.getElementById('errorPenjualan').textContent = '';
            modalPenjualan.show();
        }

        function bukaModalEdit(id) {
            fetch(`${penjualanBaseUrl}/${id}/edit`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    const p = res.data;
                    document.getElementById('penjualan_id').value = p.id;
                    document.getElementById('tanggal').value = formatTanggal(p.tanggal);
                    document.getElementById('nama_pembeli').value = p.nama_pembeli;
                    document.getElementById('jumlah_telur').value = p.jumlah_telur;
                    document.getElementById('total_harga').value = p.total_harga;
                    document.getElementById('modalPenjualanTitle').textContent = 'Edit Penjualan';
                    document.getElementById('errorPenjualan').textContent = '';
                    modalPenjualan.show();
                });
        }

        document.getElementById('formPenjualan').addEventListener('submit', function(e) {
            e.preventDefault();

            const btnSubmit = e.target.querySelector('button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Menyimpan...';

            const id = document.getElementById('penjualan_id').value;
            const url = id ? `${penjualanBaseUrl}/${id}` : penjualanBaseUrl;
            const method = id ? 'PUT' : 'POST';

            fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        tanggal: document.getElementById('tanggal').value,
                        nama_pembeli: document.getElementById('nama_pembeli').value,
                        jumlah_telur: document.getElementById('jumlah_telur').value,
                        total_harga: document.getElementById('total_harga').value
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        modalPenjualan.hide();
                        muatPenjualan();
                    } else {
                        document.getElementById('errorPenjualan').textContent = res.message;
                    }
                })
                .finally(() => {
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'Simpan';
                });
        });

        // Buka modal konfirmasi hapus (menggantikan confirm() bawaan browser)
        function bukaModalHapus(id, nama) {
            idPenjualanDihapus = id;
            document.getElementById('namaPenjualanHapus').textContent = nama;
            document.getElementById('errorHapusPenjualan').textContent = '';
            modalHapusPenjualan.show();
        }

        // Jalan saat tombol "Ya, Hapus" di dalam modal diklik
        document.getElementById('btnKonfirmasiHapusPenjualan').addEventListener('click', function() {
            if (!idPenjualanDihapus) return;

            const btnHapus = document.getElementById('btnKonfirmasiHapusPenjualan');
            btnHapus.disabled = true;
            btnHapus.textContent = 'Menghapus...';

            fetch(`${penjualanBaseUrl}/${idPenjualanDihapus}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        modalHapusPenjualan.hide();
                        idPenjualanDihapus = null;
                        muatPenjualan();
                    } else {
                        document.getElementById('errorHapusPenjualan').textContent = res.message;
                    }
                })
                .finally(() => {
                    btnHapus.disabled = false;
                    btnHapus.textContent = 'Ya, Hapus';
                });
        });

        // Filter bulan/tahun berubah -> reload data dari server
        document.getElementById('filterBulan').addEventListener('change', muatPenjualan);
        document.getElementById('filterTahun').addEventListener('change', muatPenjualan);

        // Search berubah -> filter data yang sudah ada di memori (tanpa reload server)
        document.getElementById('searchPembeli').addEventListener('input', filterDanRender);

        muatPenjualan();
    </script>
@endsection