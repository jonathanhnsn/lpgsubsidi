<?php

namespace App\Filament\Penjual\Resources\ProfilResource\Pages;

use App\Filament\Penjual\Resources\ProfilResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProfil extends CreateRecord
{
    protected static string $resource = ProfilResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
