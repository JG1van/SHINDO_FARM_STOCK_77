@extends('layouts.app')

@section('title', 'User - SHINDO FARM 77')

@section('content')
    <div class="page-header-neo">
        <h2 class="fw-bold mb-0">Data User</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-neo btn-neo-secondary px-4 py-2" onclick="bukaModalRiwayat()">Riwayat</button>
            <button class="btn btn-neo btn-neo-primary px-4 py-2" onclick="bukaModalTambah()">+ Tambah User</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-neo align-middle mb-0">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody id="tabelUser">
                <tr>
                    <td colspan="4" class="text-center py-4">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Modal Tambah/Edit -->
    <div class="modal fade modal-neo" id="modalUser" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formUser">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalUserTitle">Tambah User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="user_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama</label>
                            <input type="text" class="form-control form-control-neo" id="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" class="form-control form-control-neo" id="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Password</label>
                            <input type="text" class="form-control form-control-neo" id="password" value="ADMIN77">
                            <small class="text-muted" id="passwordHint">Default: <code>ADMIN77</code> (bisa diubah)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Role</label>
                            <select class="form-control form-control-neo" id="role" required>
                                @foreach (config('roles.roles') as $key => $meta)
                                    <option value="{{ $key }}">{{ $meta['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="errorUser" class="text-danger small"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-neo btn-neo-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-neo btn-neo-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Reset Password -->
    <div class="modal fade modal-neo" id="modalResetUser" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Reset password user
                        <strong id="namaUserReset"></strong> ke <code>ADMIN77</code>?
                    </p>
                    <div id="errorResetUser" class="text-danger small mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-neo btn-neo-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-neo btn-neo-primary" id="btnKonfirmasiResetUser">Ya,
                        Reset</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Statistik -->
    <div class="modal fade modal-neo" id="modalStatistikUser" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalStatistikTitle">Statistik User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">Total aktivitas: <strong id="statistikTotal">0</strong> (12 bulan
                        terakhir per model)</p>
                    <div style="height:280px;"><canvas id="chartStatistik"></canvas></div>
                    <h6 class="fw-bold mt-3">5 Aktivitas Terbaru</h6>
                    <ul class="list-group" id="statistikRecent"></ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-neo btn-neo-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Riwayat Aktivitas -->
    <div class="modal fade modal-neo" id="modalRiwayat" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalRiwayatTitle">Riwayat Aktivitas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <label class="form-label fw-bold mb-0">Bulan:</label>
                        <select class="form-control form-control-neo" id="riwayatBulan" style="width:auto;" onchange="muatRiwayat()">
                        </select>
                    </div>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                        <table class="table table-neo align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>User</th>
                                    <th>Aksi</th>
                                    <th>Model</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody id="riwayatBody">
                                <tr><td colspan="5" class="text-center py-3">Memuat...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="riwayatPaginasi" class="mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-neo btn-neo-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade modal-neo" id="modalHapusUser" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Hapus User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Yakin ingin menghapus user
                        <strong id="namaUserHapus"></strong>? Data ini tidak bisa dikembalikan.
                    </p>
                    <div id="errorHapusUser" class="text-danger small mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-neo btn-neo-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-neo btn-neo-danger" id="btnKonfirmasiHapusUser">Ya,
                        Hapus</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const userBaseUrl = "{{ route('user.index') }}";
        const modalUser = new bootstrap.Modal(document.getElementById('modalUser'));
        const modalHapusUser = new bootstrap.Modal(document.getElementById('modalHapusUser'));
        const modalResetUser = new bootstrap.Modal(document.getElementById('modalResetUser'));
        const modalStatistikUser = new bootstrap.Modal(document.getElementById('modalStatistikUser'));
        const modalRiwayat = new bootstrap.Modal(document.getElementById('modalRiwayat'));
        let idUserReset = null;
        let chartStatistik = null;
        const roleMetas = @json(config('roles.roles'));
        let idUserDihapus = null;

        function badgeRole(role) {
            if (!role) return `<span class="badge bg-secondary">Nonaktif</span>`;
            const meta = roleMetas[role] || {
                label: role,
                badge: 'secondary'
            };
            return `<span class="badge bg-${meta.badge}">${meta.label}</span>`;
        }

        function muatUser() {
            fetch(userBaseUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    const tbody = document.getElementById('tabelUser');
                    if (!res.data.length) {
                        tbody.innerHTML =
                            '<tr><td colspan="4" class="text-center py-4">Belum ada data user</td></tr>';
                        return;
                    }
                    tbody.innerHTML = res.data.map(u => `
        <tr>
          <td>${u.name}</td>
          <td>${u.email}</td>
          <td>${badgeRole(u.role)}</td>
          <td class="text-end">
            <button class="btn btn-neo btn-neo-secondary btn-neo-sm" onclick="bukaModalEdit(${u.id})">Edit</button>
            <button class="btn btn-neo btn-neo-secondary btn-neo-sm" onclick="bukaModalStatistik(${u.id})">Statistik</button>
            <button class="btn btn-neo btn-neo-secondary btn-neo-sm" onclick="bukaModalReset(${u.id}, '${u.name}')">Reset</button>
            <button class="btn btn-neo btn-neo-danger btn-neo-sm" onclick="bukaModalHapus(${u.id}, '${u.name}')">Hapus</button>
          </td>
        </tr>
      `).join('');
                });
        }

        function bukaModalTambah() {
            document.getElementById('formUser').reset();
            document.getElementById('user_id').value = '';
            document.getElementById('password').value = 'ADMIN77';
            document.getElementById('role').value = 'admin';
            document.getElementById('modalUserTitle').textContent = 'Tambah User';
            document.getElementById('password').required = true;
            document.getElementById('passwordHint').textContent = 'Default: ADMIN77 (bisa diubah)';
            document.getElementById('errorUser').textContent = '';
            modalUser.show();
        }

        function bukaModalEdit(id) {
            fetch(`${userBaseUrl}/${id}/edit`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    const u = res.data;
                    document.getElementById('user_id').value = u.id;
                    document.getElementById('name').value = u.name;
                    document.getElementById('email').value = u.email;
                    document.getElementById('password').value = '';
                    document.getElementById('role').value = u.role || 'admin';
                    document.getElementById('password').required = false;
                    document.getElementById('passwordHint').textContent = 'Kosongkan jika tidak ingin mengubah password';
                    document.getElementById('modalUserTitle').textContent = 'Edit User';
                    document.getElementById('errorUser').textContent = '';
                    modalUser.show();
                });
        }

        document.getElementById('formUser').addEventListener('submit', function(e) {
            e.preventDefault();

            const btnSubmit = e.target.querySelector('button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Menyimpan...';

            const id = document.getElementById('user_id').value;
            const url = id ? `${userBaseUrl}/${id}` : userBaseUrl;
            const method = id ? 'PUT' : 'POST';

            fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        name: document.getElementById('name').value,
                        email: document.getElementById('email').value,
                        password: document.getElementById('password').value,
                        role: document.getElementById('role').value
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        modalUser.hide();
                        muatUser();
                    } else {
                        document.getElementById('errorUser').textContent = res.message;
                    }
                })
                .finally(() => {
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'Simpan';
                });
        });

        function bukaModalHapus(id, nama) {
            idUserDihapus = id;
            document.getElementById('namaUserHapus').textContent = nama;
            document.getElementById('errorHapusUser').textContent = '';
            modalHapusUser.show();
        }

        document.getElementById('btnKonfirmasiHapusUser').addEventListener('click', function() {
            if (!idUserDihapus) return;

            const btnHapus = document.getElementById('btnKonfirmasiHapusUser');
            btnHapus.disabled = true;
            btnHapus.textContent = 'Menghapus...';

            fetch(`${userBaseUrl}/${idUserDihapus}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        modalHapusUser.hide();
                        idUserDihapus = null;
                        muatUser();
                    } else {
                        document.getElementById('errorHapusUser').textContent = res.message;
                    }
                })
                .finally(() => {
                    btnHapus.disabled = false;
                    btnHapus.textContent = 'Ya, Hapus';
                });
        });

        function bukaModalReset(id, nama) {
            idUserReset = id;
            document.getElementById('namaUserReset').textContent = nama;
            document.getElementById('errorResetUser').textContent = '';
            modalResetUser.show();
        }

        document.getElementById('btnKonfirmasiResetUser').addEventListener('click', function() {
            if (!idUserReset) return;

            const btnReset = document.getElementById('btnKonfirmasiResetUser');
            btnReset.disabled = true;
            btnReset.textContent = 'Mereset...';

            fetch(`${userBaseUrl}/${idUserReset}/reset-password`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        modalResetUser.hide();
                        idUserReset = null;
                        alert(res.message);
                    } else {
                        document.getElementById('errorResetUser').textContent = res.message;
                    }
                })
                .finally(() => {
                    btnReset.disabled = false;
                    btnReset.textContent = 'Ya, Reset';
                });
        });

        function bukaModalStatistik(id) {
            fetch(`${userBaseUrl}/${id}/statistics`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    if (!res.success) {
                        alert(res.message);
                        return;
                    }
                    const d = res.data;
                    document.getElementById('modalStatistikTitle').textContent = `Statistik - ${d.user.name}`;
                    document.getElementById('statistikTotal').textContent = d.total;

                    const colors = {
                        Kandang: '#ffc93c',
                        Telur: '#198754',
                        Penjualan: '#0d6efd',
                        Pengeluaran: '#dc3545',
                        User: '#6f42c1'
                    };
                    const datasets = Object.keys(d.stats).map(m => ({
                        label: m,
                        data: d.stats[m],
                        backgroundColor: colors[m] || '#6c757d'
                    }));

                    if (chartStatistik) chartStatistik.destroy();
                    chartStatistik = new Chart(document.getElementById('chartStatistik'), {
                        type: 'bar',
                        data: {
                            labels: d.labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });

                    const recent = document.getElementById('statistikRecent');
                    recent.innerHTML = d.recent.length ? d.recent.map(a => `
            <li class="list-group-item">
              <span class="badge bg-secondary">${a.action}</span>
              <span class="badge bg-info text-dark">${a.model}</span>
              <small class="text-muted">${a.created_at}</small>
              <div class="small mt-1">${a.description || '-'}</div>
            </li>
          `).join('') : '<li class="list-group-item text-center">Belum ada aktivitas</li>';

                    modalStatistikUser.show();
                });
        }

        /* ---- Riwayat Aktivitas Global ---- */
        function bukaModalRiwayat() {
            document.getElementById('riwayatBody').innerHTML = '<tr><td colspan="5" class="text-center py-3">Memuat...</td></tr>';
            document.getElementById('riwayatPaginasi').innerHTML = '';

            const activityBaseUrl = "{{ route('activityHistory') }}";
            fetch(activityBaseUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.json())
                .then(res => {
                    if (!res.success) return;
                    const d = res.data;
                    document.getElementById('modalRiwayatTitle').textContent = 'Riwayat Aktivitas';

                    const sel = document.getElementById('riwayatBulan');
                    if (d.availableMonths.length === 0) {
                        sel.innerHTML = '<option value="">Belum ada data</option>';
                        document.getElementById('riwayatBody').innerHTML = '<tr><td colspan="5" class="text-center py-3">Belum ada aktivitas</td></tr>';
                        return;
                    }
                    sel.innerHTML = d.availableMonths.map(m =>
                        `<option value="${m.year}-${String(m.month).padStart(2,'0')}" ${m.year == d.currentYear && m.month == d.currentMonth ? 'selected' : ''}>${m.label}</option>`
                    ).join('');

                    muatRiwayat();
                    modalRiwayat.show();
                });
        }

        function muatRiwayat(page) {
            const [yr, mo] = (document.getElementById('riwayatBulan').value || '').split('-');
            if (!yr || !mo) return;

            const activityBaseUrl = "{{ route('activityHistory') }}";
            const url = `${activityBaseUrl}?bulan=${mo}&tahun=${yr}${page ? '&page=' + page : ''}`;
            document.getElementById('riwayatBody').innerHTML = '<tr><td colspan="5" class="text-center py-3">Memuat...</td></tr>';

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.json())
                .then(res => {
                    if (!res.success) return;
                    const logs = res.data.logs;
                    const tbody = document.getElementById('riwayatBody');

                    if (!logs.data || logs.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3">Tidak ada aktivitas di bulan ini</td></tr>';
                        document.getElementById('riwayatPaginasi').innerHTML = '';
                        return;
                    }

                    const actionBadge = a => {
                        const colors = { CREATE: 'success', UPDATE: 'warning', DELETE: 'danger' };
                        return `<span class="badge bg-${colors[a] || 'secondary'}">${a}</span>`;
                    };

                    tbody.innerHTML = logs.data.map(l => `
            <tr>
              <td class="small">${new Date(l.created_at).toLocaleString('id-ID', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'})}</td>
              <td>${l.user_name || '<em>-</em>'}</td>
              <td>${actionBadge(l.action)}</td>
              <td><span class="badge bg-info text-dark">${l.model}</span></td>
             <td class="small" style="white-space:normal;word-break:break-word;">${l.description || '-'}</td>
            </tr>
          `).join('');

                    let pagHtml = '<nav><ul class="pagination pagination-sm mb-0">';
                    if (logs.current_page > 1)
                        pagHtml += `<li class="page-item"><a class="page-link" href="#" onclick="muatRiwayat(${logs.current_page - 1});return false;">Prev</a></li>`;
                    for (let p = 1; p <= logs.last_page; p++) {
                        pagHtml += `<li class="page-item ${p === logs.current_page ? 'active' : ''}"><a class="page-link" href="#" onclick="muatRiwayat(${p});return false;">${p}</a></li>`;
                    }
                    if (logs.current_page < logs.last_page)
                        pagHtml += `<li class="page-item"><a class="page-link" href="#" onclick="muatRiwayat(${logs.current_page + 1});return false;">Next</a></li>`;
                    pagHtml += '</ul></nav>';
                    document.getElementById('riwayatPaginasi').innerHTML = logs.last_page > 1 ? pagHtml : '';
                });
        }

        muatUser();
    </script>
@endsection