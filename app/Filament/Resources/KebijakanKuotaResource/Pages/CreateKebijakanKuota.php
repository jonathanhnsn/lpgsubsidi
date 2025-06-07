<?php

namespace App\Filament\Resources\KebijakanKuotaResource\Pages;

use App\Filament\Resources\KebijakanKuotaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateKebijakanKuota extends CreateRecord
{
    protected static string $resource = KebijakanKuotaResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
