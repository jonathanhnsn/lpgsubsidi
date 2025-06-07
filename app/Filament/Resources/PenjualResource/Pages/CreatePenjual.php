<?php

namespace App\Filament\Resources\PenjualResource\Pages;

use App\Filament\Resources\PenjualResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePenjual extends CreateRecord
{
    protected static string $resource = PenjualResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
