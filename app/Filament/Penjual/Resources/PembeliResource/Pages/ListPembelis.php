<?php

namespace App\Filament\Penjual\Resources\PembeliResource\Pages;

use App\Filament\Penjual\Resources\PembeliResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPembelis extends ListRecords
{
    protected static string $resource = PembeliResource::class;
    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
        ];
    }
}