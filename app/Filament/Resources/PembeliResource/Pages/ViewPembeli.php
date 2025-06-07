<?php

namespace App\Filament\Resources\PembeliResource\Pages;

use App\Filament\Resources\PembeliResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPembeli extends ViewRecord
{
    protected static string $resource = PembeliResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')->label('Kembali')->icon('heroicon-o-arrow-left')->color('gray')->url($this->getResource()::getUrl('index')),
            Actions\DeleteAction::make(),
        ];
    }
}
