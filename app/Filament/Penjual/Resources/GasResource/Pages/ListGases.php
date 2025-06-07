<?php

namespace App\Filament\Penjual\Resources\GasResource\Pages;

use App\Filament\Penjual\Resources\GasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGases extends ListRecords
{
    protected static string $resource = GasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
