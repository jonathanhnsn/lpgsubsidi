<?php

namespace App\Filament\Penjual\Resources\ProfilResource\Pages;

use App\Filament\Penjual\Resources\ProfilResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProfils extends ListRecords
{
    protected static string $resource = ProfilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
