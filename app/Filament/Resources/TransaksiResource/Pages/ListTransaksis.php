<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTransaksis extends ListRecords
{
    protected static string $resource = TransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Admin tidak memiliki action untuk membuat transaksi baru
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(fn () => $this->getModel()::count()),

            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Pending'))
                ->badge(fn () => $this->getModel()::where('status', 'Pending')->count())
                ->badgeColor('warning'),

            'confirmed' => Tab::make('Dikonfirmasi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Confirmed'))
                ->badge(fn () => $this->getModel()::where('status', 'Confirmed')->count())
                ->badgeColor('info'),

            'completed' => Tab::make('Selesai')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Completed'))
                ->badge(fn () => $this->getModel()::where('status', 'Completed')->count())
                ->badgeColor('success'),

            'without_penjual' => Tab::make('Belum Ada Penjual')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('penjual_id'))
                ->badge(fn () => $this->getModel()::whereNull('penjual_id')->count())
                ->badgeColor('danger'),
        ];
    }
}
