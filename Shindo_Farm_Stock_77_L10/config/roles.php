<?php

return [
    'roles' => [
        'super_admin'  => [
            'label'  => 'Super Admin',
            'color'  => '#dc3545',
            'badge'  => 'danger',
            'icon'   => 'bi-shield-check',
        ],
        'admin'        => [
            'label'  => 'Admin',
            'color'  => '#fd7e14',
            'badge'  => 'warning',
            'icon'   => 'bi-person-badge',
        ],
        'staf_ayam'    => [
            'label'  => 'Staf Ayam',
            'color'  => '#198754',
            'badge'  => 'success',
            'icon'   => 'bi-egg',
        ],
        'staf_keuangan'=> [
            'label'  => 'Staf Keuangan',
            'color'  => '#0d6efd',
            'badge'  => 'primary',
            'icon'   => 'bi-cash-coin',
        ],
    ],
    'menus' => [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard.index',
            'roles' => ['super_admin', 'admin', 'staf_ayam', 'staf_keuangan'],
        ],
        [
            'label' => 'Kalkulator',
            'route' => 'kalkulator.index',
            'roles' => ['super_admin', 'admin', 'staf_ayam', 'staf_keuangan'],
        ],
        [
            'label' => 'Pengguna',
            'route' => 'user.index',
            'roles' => ['super_admin'],
        ],
        [
            'label' => 'Kandang',
            'route' => 'kandang.index',
            'roles' => ['super_admin', 'admin', 'staf_ayam'],
        ],
        [
            'label' => 'Telur',
            'route' => 'telur.index',
            'roles' => ['super_admin', 'admin', 'staf_ayam'],
        ],
        [
            'label' => 'Penjualan',
            'route' => 'penjualan.index',
            'roles' => ['super_admin', 'admin', 'staf_keuangan'],
        ],
        [
            'label' => 'Pengeluaran',
            'route' => 'pengeluaran.index',
            'roles' => ['super_admin', 'admin', 'staf_keuangan'],
        ],
    ],
];