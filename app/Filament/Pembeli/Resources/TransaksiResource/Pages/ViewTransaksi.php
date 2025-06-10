<?php

namespace App\Filament\Pembeli\Resources\TransaksiResource\Pages;

use App\Filament\Pembeli\Resources\TransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransaksi extends ViewRecord
{
    protected static string $resource = TransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')->label('Kembali')->icon('heroicon-o-arrow-left')->color('gray')->url($this->getResource()::getUrl('index')),
        ];
    }
}
