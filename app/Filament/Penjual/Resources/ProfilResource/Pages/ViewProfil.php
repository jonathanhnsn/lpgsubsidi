<?php

namespace App\Filament\Penjual\Resources\ProfilResource\Pages;

use App\Filament\Penjual\Resources\ProfilResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProfil extends ViewRecord
{
    protected static string $resource = ProfilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')->label('Kembali')->icon('heroicon-o-arrow-left')->color('gray')->url($this->getResource()::getUrl('index')),
            Actions\EditAction::make(),
        ];
    }
}
