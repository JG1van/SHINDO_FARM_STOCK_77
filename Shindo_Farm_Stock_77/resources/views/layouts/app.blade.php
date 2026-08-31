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
            <a href="{{ route('dashboard.index') }}"
                class="sidebar-link {{ request()->routeIs('dashboard.*') ? 'active-neo' : '' }}">
                <span class="icon"><i class="bi bi-speedometer2"></i></span> Dashboard
            </a>
            <a href="{{ route('kandang.index') }}"
                class="sidebar-link {{ request()->routeIs('kandang.*') ? 'active-neo' : '' }}">
                <span class="icon"><i class="bi bi-house-door"></i></span> Kandang
            </a>
            <a href="{{ route('telur.index') }}"
                class="sidebar-link {{ request()->routeIs('telur.*') ? 'active-neo' : '' }}">
                <span class="icon"><i class="bi bi-egg"></i></span> Telur
            </a>
            <a href="{{ route('penjualan.index') }}"
                class="sidebar-link {{ request()->routeIs('penjualan.*') ? 'active-neo' : '' }}">
                <span class="icon"><i class="bi bi-cash-coin"></i></span> Penjualan
            </a>
            <a href="{{ route('pengeluaran.index') }}"
                class="sidebar-link {{ request()->routeIs('pengeluaran.*') ? 'active-neo' : '' }}">
                <span class="icon"><i class="bi bi-graph-down-arrow"></i></span> Pengeluaran
            </a>
            <a href="{{ route('user.index') }}"
                class="sidebar-link {{ request()->routeIs('user.*') ? 'active-neo' : '' }}">
                <span class="icon"><i class="bi bi-people"></i></span> Pengguna
            </a>
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
