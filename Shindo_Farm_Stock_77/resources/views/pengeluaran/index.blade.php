@extends('layouts.app')

@section('title', 'Pengeluaran - SHINDO FARM 77')

@section('content')
    <div class="page-header-neo">
        <h2 class="fw-bold mb-0">Data Pengeluaran</h2>
        <button class="btn btn-neo btn-neo-primary px-4 py-2" onclick="bukaModalTambah()">+ Tambah Pengeluaran</button>
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
                    <th>Keterangan</th>
                    <th>Jumlah</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody id="tabelPengeluaran">
                <tr>
                    <td colspan="4" class="text-center py-4">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Modal Tambah/Edit -->
    <div class="modal fade modal-neo" id="modalPengeluaran" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formPengeluaran">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalPengeluaranTitle">Tambah Pengeluaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="pengeluaran_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date" class="form-control form-control-neo" id="tanggal" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Keterangan</label>
                            <input type="text" class="form-control form-control-neo" id="keterangan"
                                placeholder="Contoh: beli pakan, obat, listrik" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Jumlah</label>
                            <input type="number" class="form-control form-control-neo" id="jumlah" min="0"
                                step="0.01" required>
                        </div>
                        <div id="errorPengeluaran" class="text-danger small"></div>
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
    <div class="modal fade modal-neo" id="modalHapusPengeluaran" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Hapus Pengeluaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Yakin ingin menghapus data pengeluaran
                        <strong id="keteranganPengeluaranHapus"></strong>? Data ini tidak bisa dikembalikan.
                    </p>
                    <div id="errorHapusPengeluaran" class="text-danger small mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-neo btn-neo-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-neo btn-neo-danger" id="btnKonfirmasiHapusPengeluaran">Ya,
                        Hapus</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const modalPengeluaran = new bootstrap.Modal(document.getElementById('modalPengeluaran'));
        const modalHapusPengeluaran = new bootstrap.Modal(document.getElementById('modalHapusPengeluaran'));
        let idPengeluaranDihapus = null;

        function formatRupiah(angka) {
            return 'Rp ' + Number(angka).toLocaleString('id-ID');
        }

        // Ambil hanya "YYYY-MM-DD" walau backend kirim format lain (jaga-jaga)
        function formatTanggal(tgl) {
            return tgl ? tgl.substring(0, 10) : '-';
        }

        function muatPengeluaran() {
            const bulan = document.getElementById('filterBulan').value;
            const tahun = document.getElementById('filterTahun').value;

            fetch(`{{ route('pengeluaran.index') }}?bulan=${bulan}&tahun=${tahun}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    const tbody = document.getElementById('tabelPengeluaran');
                    if (!res.data.length) {
                        tbody.innerHTML =
                            '<tr><td colspan="4" class="text-center py-4">Belum ada data pengeluaran bulan ini</td></tr>';
                        return;
                    }
                    tbody.innerHTML = res.data.map(p => `
        <tr>
          <td>${formatTanggal(p.tanggal)}</td>
          <td>${p.keterangan}</td>
          <td>${formatRupiah(p.jumlah)}</td>
          <td class="text-end">
            <button class="btn btn-neo btn-neo-secondary btn-neo-sm" onclick="bukaModalEdit(${p.id})">Edit</button>
            <button class="btn btn-neo btn-neo-danger btn-neo-sm" onclick="bukaModalHapus(${p.id}, '${p.keterangan}')">Hapus</button>
          </td>
        </tr>
      `).join('');
                });
        }

        function bukaModalTambah() {
            document.getElementById('formPengeluaran').reset();
            document.getElementById('pengeluaran_id').value = '';
            document.getElementById('modalPengeluaranTitle').textContent = 'Tambah Pengeluaran';
            document.getElementById('errorPengeluaran').textContent = '';
            modalPengeluaran.show();
        }

        function bukaModalEdit(id) {
            fetch(`/pengeluaran/${id}/edit`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    const p = res.data;
                    document.getElementById('pengeluaran_id').value = p.id;
                    document.getElementById('tanggal').value = formatTanggal(p.tanggal);
                    document.getElementById('keterangan').value = p.keterangan;
                    document.getElementById('jumlah').value = p.jumlah;
                    document.getElementById('modalPengeluaranTitle').textContent = 'Edit Pengeluaran';
                    document.getElementById('errorPengeluaran').textContent = '';
                    modalPengeluaran.show();
                });
        }

        document.getElementById('formPengeluaran').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('pengeluaran_id').value;
            const url = id ? `/pengeluaran/${id}` : "{{ route('pengeluaran.store') }}";
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
                        keterangan: document.getElementById('keterangan').value,
                        jumlah: document.getElementById('jumlah').value
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        modalPengeluaran.hide();
                        muatPengeluaran();
                    } else {
                        document.getElementById('errorPengeluaran').textContent = res.message;
                    }
                });
        });

        // Buka modal konfirmasi hapus (menggantikan confirm() bawaan browser)
        function bukaModalHapus(id, keterangan) {
            idPengeluaranDihapus = id;
            document.getElementById('keteranganPengeluaranHapus').textContent = keterangan;
            document.getElementById('errorHapusPengeluaran').textContent = '';
            modalHapusPengeluaran.show();
        }

        // Jalan saat tombol "Ya, Hapus" di dalam modal diklik
        document.getElementById('btnKonfirmasiHapusPengeluaran').addEventListener('click', function() {
            if (!idPengeluaranDihapus) return;

            fetch(`/pengeluaran/${idPengeluaranDihapus}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        modalHapusPengeluaran.hide();
                        idPengeluaranDihapus = null;
                        muatPengeluaran();
                    } else {
                        document.getElementById('errorHapusPengeluaran').textContent = res.message;
                    }
                });
        });

        // Filter berubah -> reload data
        document.getElementById('filterBulan').addEventListener('change', muatPengeluaran);
        document.getElementById('filterTahun').addEventListener('change', muatPengeluaran);

        muatPengeluaran();
    </script>
@endsection
