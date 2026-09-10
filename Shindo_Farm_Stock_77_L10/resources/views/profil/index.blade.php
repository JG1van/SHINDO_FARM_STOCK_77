@extends('layouts.app')

@section('title', 'Profil - SHINDO FARM 77')

@php
    $roleMetas = config('roles.roles', []);
    $roleMeta = $roleMetas[$user->role ?? ''] ?? null;
    $tab = session('tab', 'edit');
@endphp

@section('content')
    <div class="page-header-neo">
        <h2 class="fw-bold mb-0">Profil Saya</h2>
        @if ($roleMeta)
            <span class="badge bg-{{ $roleMeta['badge'] }} fs-6">
                <i class="bi {{ $roleMeta['icon'] }}"></i> {{ $roleMeta['label'] }}
            </span>
        @else
            <span class="badge bg-secondary fs-6">Nonaktif</span>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card-neo p-4 text-center">
                <div class="mb-3">
                    <span
                        style="display:inline-flex;align-items:center;justify-content:center;width:96px;height:96px;border-radius:50%;background:var(--color-primary,#ffc93c);border:3px solid #1a1a1a;font-size:2.5rem;font-weight:700;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </span>
                </div>
                <h4 class="fw-bold mb-1">{{ $user->name }}</h4>
                <p class="text-muted mb-1">{{ $user->email }}</p>
                <p class="small text-muted mb-0">Terdaftar:
                    {{ $user->created_at ? $user->created_at->translatedFormat('d F Y') : '-' }}</p>
            </div>

            <div class="card-neo p-4 mt-3 border-danger">
                <h5 class="fw-bold text-danger mb-2">Nonaktifkan Akun</h5>
                <p class="small text-muted">Akun dinonaktifkan (role dikosongkan), data tetap aman. Anda akan langsung
                    logout.</p>
                <form action="{{ route('profil.deactivate') }}" method="POST"
                    onsubmit="return confirm('Yakin ingin menonaktifkan akun ini?')">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password (konfirmasi)</label>
                        <input type="password" name="password"
                            class="form-control form-control-neo @error('password') is-invalid @enderror" required>
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-neo btn-neo-danger w-100">Nonaktifkan Akun</button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card-neo p-4 mb-3">
                <h5 class="fw-bold mb-3">Edit Profil</h5>
                <form action="{{ route('profil.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                            class="form-control form-control-neo @error('name') is-invalid @enderror" required
                            maxlength="100">
                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                            class="form-control form-control-neo @error('email') is-invalid @enderror" required>
                        @error('email')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-neo btn-neo-primary">Simpan Perubahan</button>
                </form>
            </div>

            <div class="card-neo p-4">
                <h5 class="fw-bold mb-3">Ganti Password</h5>
                <form action="{{ route('profil.password') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password Lama</label>
                        <input type="password" name="current_password"
                            class="form-control form-control-neo @error('current_password') is-invalid @enderror"
                            required>
                        @error('current_password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password Baru (min. 6 karakter)</label>
                        <input type="password" name="password"
                            class="form-control form-control-neo @error('password') is-invalid @enderror" required>
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-neo"
                            required>
                    </div>
                    <button type="submit" class="btn btn-neo btn-neo-primary">Ubah Password</button>
                </form>
            </div>
        </div>
    </div>
@endsection
