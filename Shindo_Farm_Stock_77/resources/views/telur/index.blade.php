@extends('layouts.app')

@section('title', 'Data Telur - SHINDO FARM 77')

@section('content')
    <div class="page-header-neo">
        <h2 class="fw-bold mb-0">Data Telur</h2>
        <button class="btn btn-neo btn-neo-primary px-4 py-2" onclick="bukaModalTambah()">+ Tambah Data Telur</button>
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
                    <th>Kandang</th>
                    <th>Jumlah Butir</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody id="tabelTelur">
                <tr>
                    <td colspan="4" class="text-center py-4">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Modal Tambah/Edit -->
    <div class="modal fade modal-neo" id="modalTelur" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formTelur">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalTelurTitle">Tambah Data Telur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="telur_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kandang</label>
                            <select class="form-select form-control-neo" id="kandang_id" required>
                                <option value="">-- Pilih Kandang --</option>
                                @foreach ($kandangs as $kandang)
                                    <option value="{{ $kandang->id }}">{{ $kandang->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date" class="form-control form-control-neo" id="tanggal" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Jumlah Butir</label>
                            <input type="number" class="form-control form-control-neo" id="jumlah_butir" min="0"
                                required>
                        </div>
                        <div id="errorTelur" class="text-danger small"></div>
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
    <div class="modal fade modal-neo" id="modalHapusTelur" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Hapus Data Telur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Yakin ingin menghapus data telur tanggal
                        <strong id="tanggalTelurHapus"></strong>? Data ini tidak bisa dikembalikan.
                    </p>
                    <div id="errorHapusTelur" class="text-danger small mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-neo btn-neo-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-neo btn-neo-danger" id="btnKonfirmasiHapusTelur">Ya,
                        Hapus</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const modalTelur = new bootstrap.Modal(document.getElementById('modalTelur'));
        const modalHapusTelur = new bootstrap.Modal(document.getElementById('modalHapusTelur'));
        let idTelurDihapus = null;

        function muatTelur() {
            const bulan = document.getElementById('filterBulan').value;
            const tahun = document.getElementById('filterTahun').value;

            fetch(`{{ route('telur.index') }}?bulan=${bulan}&tahun=${tahun}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    const tbody = document.getElementById('tabelTelur');
                    if (!res.data.length) {
                        tbody.innerHTML =
                            '<tr><td colspan="4" class="text-center py-4">Belum ada data telur bulan ini</td></tr>';
                        return;
                    }
                    tbody.innerHTML = res.data.map(t => `
        <tr>
          <td>${t.tanggal}</td>
          <td>${t.kandang ? t.kandang.nama : '-'}</td>
          <td>${t.jumlah_butir}</td>
          <td class="text-end">
            <button class="btn btn-neo btn-neo-secondary btn-neo-sm" onclick="bukaModalEdit(${t.id})">Edit</button>
            <button class="btn btn-neo btn-neo-danger btn-neo-sm" onclick="bukaModalHapus(${t.id}, '${t.tanggal}')">Hapus</button>
          </td>
        </tr>
      `).join('');
                });
        }

        function bukaModalTambah() {
            document.getElementById('formTelur').reset();
            document.getElementById('telur_id').value = '';
            document.getElementById('modalTelurTitle').textContent = 'Tambah Data Telur';
            document.getElementById('errorTelur').textContent = '';
            modalTelur.show();
        }

        function bukaModalEdit(id) {
            fetch(`/telur/${id}/edit`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    const t = res.data;
                    document.getElementById('telur_id').value = t.id;
                    document.getElementById('kandang_id').value = t.kandang_id;
                    document.getElementById('tanggal').value = t.tanggal;
                    document.getElementById('jumlah_butir').value = t.jumlah_butir;
                    document.getElementById('modalTelurTitle').textContent = 'Edit Data Telur';
                    document.getElementById('errorTelur').textContent = '';
                    modalTelur.show();
                });
        }

        document.getElementById('formTelur').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('telur_id').value;
            const url = id ? `/telur/${id}` : "{{ route('telur.store') }}";
            const method = id ? 'PUT' : 'POST';

            fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        kandang_id: document.getElementById('kandang_id').value,
                        tanggal: document.getElementById('tanggal').value,
                        jumlah_butir: document.getElementById('jumlah_butir').value
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        modalTelur.hide();
                        muatTelur();
                    } else {
                        document.getElementById('errorTelur').textContent = res.message;
                    }
                });
        });

        // Buka modal konfirmasi hapus (menggantikan confirm() bawaan browser)
        function bukaModalHapus(id, tanggal) {
            idTelurDihapus = id;
            document.getElementById('tanggalTelurHapus').textContent = tanggal;
            document.getElementById('errorHapusTelur').textContent = '';
            modalHapusTelur.show();
        }

        // Jalan saat tombol "Ya, Hapus" di dalam modal diklik
        document.getElementById('btnKonfirmasiHapusTelur').addEventListener('click', function() {
            if (!idTelurDihapus) return;

            fetch(`/telur/${idTelurDihapus}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        modalHapusTelur.hide();
                        idTelurDihapus = null;
                        muatTelur();
                    } else {
                        document.getElementById('errorHapusTelur').textContent = res.message;
                    }
                });
        });

        // Filter berubah -> reload data
        document.getElementById('filterBulan').addEventListener('change', muatTelur);
        document.getElementById('filterTahun').addEventListener('change', muatTelur);

        muatTelur();
    </script>
@endsection
