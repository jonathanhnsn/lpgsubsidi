<?php

namespace App\Filament\Penjual\Resources\GasResource\Pages;

use App\Filament\Penjual\Resources\GasResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGas extends ViewRecord
{
    protected static string $resource = GasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')->label('Kembali')->icon('heroicon-o-arrow-left')->color('gray')->url($this->getResource()::getUrl('index')),
        ];
    }
}
