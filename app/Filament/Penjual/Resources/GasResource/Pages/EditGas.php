<?php

namespace App\Filament\Penjual\Resources\GasResource\Pages;

use App\Filament\Penjual\Resources\GasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGas extends EditRecord
{
    protected static string $resource = GasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
