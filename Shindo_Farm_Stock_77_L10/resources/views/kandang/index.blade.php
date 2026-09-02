@extends('layouts.app')

@section('title', 'Kandang - SHINDO FARM 77')

@section('content')
    <div class="page-header-neo">
        <h2 class="fw-bold mb-0">Data Kandang</h2>
        <button class="btn btn-neo btn-neo-primary px-4 py-2" onclick="bukaModalTambah()">+ Tambah Kandang</button>
    </div>

    <div class="table-responsive">
        <table class="table table-neo align-middle mb-0">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Jenis Ayam</th>
                    <th>Jantan</th>
                    <th>Betina</th>
                    <th>Total</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody id="tabelKandang">
                <tr>
                    <td colspan="6" class="text-center py-4">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Modal Tambah/Edit -->
    <div class="modal fade modal-neo" id="modalKandang" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formKandang">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalKandangTitle">Tambah Kandang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="kandang_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Kandang</label>
                            <input type="text" class="form-control form-control-neo" id="nama" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Jenis Ayam</label>
                            <input type="text" class="form-control form-control-neo" id="jenis_ayam" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Jumlah Jantan</label>
                            <input type="number" class="form-control form-control-neo" id="jantan" min="0"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Jumlah Betina</label>
                            <input type="number" class="form-control form-control-neo" id="betina" min="0"
                                required>
                        </div>
                        <div id="errorKandang" class="text-danger small"></div>
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
    <div class="modal fade modal-neo" id="modalHapusKandang" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Hapus Kandang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Yakin ingin menghapus kandang <strong id="namaKandangHapus"></strong>? Data ini
                        tidak bisa dikembalikan.</p>
                    <div id="errorHapusKandang" class="text-danger small mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-neo btn-neo-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-neo btn-neo-danger" id="btnKonfirmasiHapus">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const kandangBaseUrl = "{{ route('kandang.index') }}";
        const modalKandang = new bootstrap.Modal(document.getElementById('modalKandang'));
        const modalHapusKandang = new bootstrap.Modal(document.getElementById('modalHapusKandang'));
        let idKandangDihapus = null;

        function muatKandang() {
            fetch(kandangBaseUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    const tbody = document.getElementById('tabelKandang');
                    if (!res.data.length) {
                        tbody.innerHTML =
                            '<tr><td colspan="6" class="text-center py-4">Belum ada data kandang</td></tr>';
                        return;
                    }
                    tbody.innerHTML = res.data.map(k => `
        <tr>
          <td>${k.nama}</td>
          <td>${k.jenis_ayam}</td>
          <td>${k.jantan}</td>
          <td>${k.betina}</td>
          <td>${Number(k.jantan) + Number(k.betina)}</td>
          <td class="text-end">
            <button class="btn btn-neo btn-neo-secondary btn-neo-sm" onclick="bukaModalEdit(${k.id})">Edit</button>
            <button class="btn btn-neo btn-neo-danger btn-neo-sm" onclick="bukaModalHapus(${k.id}, '${k.nama}')">Hapus</button>
          </td>
        </tr>
      `).join('');
                });
        }

        function bukaModalTambah() {
            document.getElementById('formKandang').reset();
            document.getElementById('kandang_id').value = '';
            document.getElementById('modalKandangTitle').textContent = 'Tambah Kandang';
            document.getElementById('errorKandang').textContent = '';
            modalKandang.show();
        }

        function bukaModalEdit(id) {
            fetch(`${kandangBaseUrl}/${id}/edit`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    const k = res.data;
                    document.getElementById('kandang_id').value = k.id;
                    document.getElementById('nama').value = k.nama;
                    document.getElementById('jenis_ayam').value = k.jenis_ayam;
                    document.getElementById('jantan').value = k.jantan;
                    document.getElementById('betina').value = k.betina;
                    document.getElementById('modalKandangTitle').textContent = 'Edit Kandang';
                    document.getElementById('errorKandang').textContent = '';
                    modalKandang.show();
                });
        }

        document.getElementById('formKandang').addEventListener('submit', function(e) {
            e.preventDefault();

            const btnSubmit = e.target.querySelector('button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Menyimpan...';

            const id = document.getElementById('kandang_id').value;
            const url = id ? `${kandangBaseUrl}/${id}` : kandangBaseUrl;
            const method = id ? 'PUT' : 'POST';

            fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        nama: document.getElementById('nama').value,
                        jenis_ayam: document.getElementById('jenis_ayam').value,
                        jantan: document.getElementById('jantan').value,
                        betina: document.getElementById('betina').value
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        modalKandang.hide();
                        muatKandang();
                    } else {
                        document.getElementById('errorKandang').textContent = res.message;
                    }
                })
                .finally(() => {
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'Simpan';
                });
        });

        // Buka modal konfirmasi hapus (menggantikan confirm() bawaan browser)
        function bukaModalHapus(id, nama) {
            idKandangDihapus = id;
            document.getElementById('namaKandangHapus').textContent = nama;
            document.getElementById('errorHapusKandang').textContent = '';
            modalHapusKandang.show();
        }

        // Jalan saat tombol "Ya, Hapus" di dalam modal diklik
        document.getElementById('btnKonfirmasiHapus').addEventListener('click', function() {
            if (!idKandangDihapus) return;

            const btnHapus = document.getElementById('btnKonfirmasiHapus');
            btnHapus.disabled = true;
            btnHapus.textContent = 'Menghapus...';

            fetch(`${kandangBaseUrl}/${idKandangDihapus}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        modalHapusKandang.hide();
                        idKandangDihapus = null;
                        muatKandang();
                    } else {
                        document.getElementById('errorHapusKandang').textContent = res.message;
                    }
                })
                .finally(() => {
                    btnHapus.disabled = false;
                    btnHapus.textContent = 'Ya, Hapus';
                });
        });

        muatKandang();
    </script>
@endsection