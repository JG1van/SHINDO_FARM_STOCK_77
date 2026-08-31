@extends('layouts.app')

@section('title', 'Penjualan - SHINDO FARM 77')

@section('content')
    <div class="page-header-neo">
        <h2 class="fw-bold mb-0">Data Penjualan</h2>
        <button class="btn btn-neo btn-neo-primary px-4 py-2" onclick="bukaModalTambah()">+ Tambah Penjualan</button>
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
        const modalPenjualan = new bootstrap.Modal(document.getElementById('modalPenjualan'));
        const modalHapusPenjualan = new bootstrap.Modal(document.getElementById('modalHapusPenjualan'));
        let idPenjualanDihapus = null;

        function formatRupiah(angka) {
            return 'Rp ' + Number(angka).toLocaleString('id-ID');
        }

        // Ambil hanya "YYYY-MM-DD" walau backend kirim format lain (jaga-jaga)
        function formatTanggal(tgl) {
            return tgl ? tgl.substring(0, 10) : '-';
        }

        function muatPenjualan() {
            const bulan = document.getElementById('filterBulan').value;
            const tahun = document.getElementById('filterTahun').value;

            fetch(`{{ route('penjualan.index') }}?bulan=${bulan}&tahun=${tahun}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    const tbody = document.getElementById('tabelPenjualan');
                    if (!res.data.length) {
                        tbody.innerHTML =
                            '<tr><td colspan="5" class="text-center py-4">Belum ada data penjualan bulan ini</td></tr>';
                        return;
                    }
                    tbody.innerHTML = res.data.map(p => `
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
                });
        }

        function bukaModalTambah() {
            document.getElementById('formPenjualan').reset();
            document.getElementById('penjualan_id').value = '';
            document.getElementById('modalPenjualanTitle').textContent = 'Tambah Penjualan';
            document.getElementById('errorPenjualan').textContent = '';
            modalPenjualan.show();
        }

        function bukaModalEdit(id) {
            fetch(`/penjualan/${id}/edit`, {
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
            const id = document.getElementById('penjualan_id').value;
            const url = id ? `/penjualan/${id}` : "{{ route('penjualan.store') }}";
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

            fetch(`/penjualan/${idPenjualanDihapus}`, {
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
                });
        });

        // Filter berubah -> reload data
        document.getElementById('filterBulan').addEventListener('change', muatPenjualan);
        document.getElementById('filterTahun').addEventListener('change', muatPenjualan);

        muatPenjualan();
    </script>
@endsection
