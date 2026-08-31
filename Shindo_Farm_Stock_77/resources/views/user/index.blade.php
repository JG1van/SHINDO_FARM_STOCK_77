@extends('layouts.app')

@section('title', 'User - SHINDO FARM 77')

@section('content')
    <div class="page-header-neo">
        <h2 class="fw-bold mb-0">Data User</h2>
        <button class="btn btn-neo btn-neo-primary px-4 py-2" onclick="bukaModalTambah()">+ Tambah User</button>
    </div>

    <div class="table-responsive">
        <table class="table table-neo align-middle mb-0">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody id="tabelUser">
                <tr>
                    <td colspan="3" class="text-center py-4">Memuat data...</td>
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
                            <input type="password" class="form-control form-control-neo" id="password">
                            <small class="text-muted" id="passwordHint">Kosongkan jika tidak ingin mengubah
                                password</small>
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
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const modalUser = new bootstrap.Modal(document.getElementById('modalUser'));
        const modalHapusUser = new bootstrap.Modal(document.getElementById('modalHapusUser'));
        let idUserDihapus = null;

        function muatUser() {
            fetch(`{{ route('user.index') }}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    const tbody = document.getElementById('tabelUser');
                    if (!res.data.length) {
                        tbody.innerHTML =
                            '<tr><td colspan="3" class="text-center py-4">Belum ada data user</td></tr>';
                        return;
                    }
                    tbody.innerHTML = res.data.map(u => `
        <tr>
          <td>${u.name}</td>
          <td>${u.email}</td>
          <td class="text-end">
            <button class="btn btn-neo btn-neo-secondary btn-neo-sm" onclick="bukaModalEdit(${u.id})">Edit</button>
            <button class="btn btn-neo btn-neo-danger btn-neo-sm" onclick="bukaModalHapus(${u.id}, '${u.name}')">Hapus</button>
          </td>
        </tr>
      `).join('');
                });
        }

        function bukaModalTambah() {
            document.getElementById('formUser').reset();
            document.getElementById('user_id').value = '';
            document.getElementById('modalUserTitle').textContent = 'Tambah User';
            document.getElementById('password').required = true;
            document.getElementById('passwordHint').classList.add('d-none');
            document.getElementById('errorUser').textContent = '';
            modalUser.show();
        }

        function bukaModalEdit(id) {
            fetch(`/user/${id}/edit`, {
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
                    document.getElementById('password').required = false;
                    document.getElementById('passwordHint').classList.remove('d-none');
                    document.getElementById('modalUserTitle').textContent = 'Edit User';
                    document.getElementById('errorUser').textContent = '';
                    modalUser.show();
                });
        }

        document.getElementById('formUser').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('user_id').value;
            const url = id ? `/user/${id}` : "{{ route('user.store') }}";
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
                        password: document.getElementById('password').value
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

            fetch(`/user/${idUserDihapus}`, {
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
                });
        });

        muatUser();
    </script>
@endsection
