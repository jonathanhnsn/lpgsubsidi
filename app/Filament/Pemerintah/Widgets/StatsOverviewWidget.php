<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Kurir;
use App\Models\Penjual;
use App\Models\Pembeli;
use App\Models\Transaksi;
use App\Models\Pemesanan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalUsers = User::count();
        $totalTransaksi = Transaksi::withoutUserRestriction()->count();
        $transaksiHariIni = Transaksi::withoutUserRestriction()
            ->whereDate('created_at', today())->count();
        $revenueHariIni = Transaksi::withoutUserRestriction()
            ->whereDate('created_at', today())
            ->where('status', 'Completed')
            ->sum('harga');
        $pendingApprovals = Pembeli::pending()->count() + Kurir::pending()->count();

        return [
            Stat::make('Total Users', $totalUsers)
                ->description('Semua pengguna terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Total Transaksi', $totalTransaksi)
                ->description('Seluruh transaksi')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),

            Stat::make('Transaksi Hari Ini', $transaksiHariIni)
                ->description('Transaksi hari ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),

            Stat::make('Pending Approvals', $pendingApprovals)
                ->description('Menunggu persetujuan')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingApprovals > 0 ? 'danger' : 'success'),

            Stat::make('Total Pembeli', Pembeli::count())
                ->description('Pembeli terdaftar')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Total Penjual', Penjual::count())
                ->description('Penjual terdaftar')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('primary'),

            Stat::make('Total Kurir', Kurir::count())
                ->description('Kurir terdaftar')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),
        ];
    }
}