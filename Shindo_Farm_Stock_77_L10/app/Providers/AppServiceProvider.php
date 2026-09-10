<?php

namespace App\Providers;

use App\Models\Kandang;
use App\Models\Pengeluaran;
use App\Models\Penjualan;
use App\Models\Telur;
use App\Models\User;
use App\Observers\GlobalObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Helpers/ActivityHelper.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Log otomatis CREATE/UPDATE/DELETE ke activity_logs
        foreach ([Kandang::class, Telur::class, Penjualan::class, Pengeluaran::class, User::class] as $model) {
            $model::observe(GlobalObserver::class);
        }
    }
}
