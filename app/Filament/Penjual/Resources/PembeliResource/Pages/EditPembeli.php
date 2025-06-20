<?php

namespace App\Filament\Penjual\Resources\PembeliResource\Pages;

use App\Filament\Penjual\Resources\PembeliResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPembeli extends EditRecord
{
    protected static string $resource = PembeliResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
