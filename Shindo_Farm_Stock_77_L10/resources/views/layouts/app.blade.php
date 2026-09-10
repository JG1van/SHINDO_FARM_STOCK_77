<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SHINDO FARM 77 - Admin Stok')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin-neo.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body>

    <aside class="sidebar-neo" id="sidebarNeo">
        <a href="{{ route('kandang.index') }}" class="sidebar-brand "> SHINDO-FARM-<span style="color:#e8871e">
                77</span></a>
        <div class="sidebar-menu">
            @php
                $userRole = auth()->user()->role ?? '';
                // Daftar menu sidebar + role yang boleh melihatnya.
                // Label/warna/badge role terpusat di config('roles.roles').
                $sidebarMenus = [
                    [
                        'label' => 'Dashboard',
                        'route' => 'dashboard.index',
                        'icon' => 'bi-speedometer2',
                        'active' => ['dashboard.*', 'kalkulator.*'],
                        'roles' => ['super_admin', 'admin', 'staf_ayam', 'staf_keuangan'],
                    ],
                    [
                        'label' => 'Kandang',
                        'route' => 'kandang.index',
                        'icon' => 'bi-house-door',
                        'active' => ['kandang.*'],
                        'roles' => ['super_admin', 'admin', 'staf_ayam'],
                    ],
                    [
                        'label' => 'Telur',
                        'route' => 'telur.index',
                        'icon' => 'bi-egg',
                        'active' => ['telur.*'],
                        'roles' => ['super_admin', 'admin', 'staf_ayam'],
                    ],
                    [
                        'label' => 'Penjualan',
                        'route' => 'penjualan.index',
                        'icon' => 'bi-cash-coin',
                        'active' => ['penjualan.*'],
                        'roles' => ['super_admin', 'admin', 'staf_keuangan'],
                    ],
                    [
                        'label' => 'Pengeluaran',
                        'route' => 'pengeluaran.index',
                        'icon' => 'bi-graph-down-arrow',
                        'active' => ['pengeluaran.*'],
                        'roles' => ['super_admin', 'admin', 'staf_keuangan'],
                    ],
                    [
                        'label' => 'Pengguna',
                        'route' => 'user.index',
                        'icon' => 'bi-people',
                        'active' => ['user.*'],
                        'roles' => ['super_admin'],
                    ],
                ];
                $roleMeta = config('roles.roles')[$userRole] ?? null;
            @endphp
            @if ($roleMeta)
                <div class="px-3 mt-2 mb-2 text-center">
                    <span class="badge bg-{{ $roleMeta['badge'] }} w-100 py-2 fs-6">
                        <i class="bi {{ $roleMeta['icon'] }}"></i> {{ $roleMeta['label'] }}
                    </span>
                </div>
            @endif
            @foreach ($sidebarMenus as $menu)
                @if (in_array($userRole, $menu['roles']))
                    <a href="{{ route($menu['route']) }}"
                        class="sidebar-link {{ request()->routeIs(...$menu['active']) ? 'active-neo' : '' }}">
                        <span class="icon"><i class="bi {{ $menu['icon'] }}"></i></span> {{ $menu['label'] }}
                    </a>
                @endif
            @endforeach
            <form id="formLogout" action="{{ route('logout') }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" class="sidebar-link w-100 border-0 bg-transparent text-start">
                    <span class="icon"><i class="bi bi-box-arrow-right"></i></span> Logout
                </button>
            </form>
        </div>
    </aside>

    <div class="topbar-neo">
        <button class="sidebar-toggle-neo" id="btnSidebarToggle">☰</button>
        <h1 class="fs-5 fw-bold mb-0">@yield('title', 'SHINDO FARM 77')</h1>
    </div>

    <div class="main-content-neo">
        <div class="container-fluid px-4 py-4">
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var btnToggle = document.getElementById('btnSidebarToggle');
            var sidebar = document.getElementById('sidebarNeo');
            if (btnToggle && sidebar) {
                btnToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show-neo');
                });
            }
        });
    </script>
    @yield('scripts')
</body>

</html>
