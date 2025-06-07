<?php

namespace App\Filament\Resources\KebijakanKuotaResource\Pages;

use App\Filament\Resources\KebijakanKuotaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKebijakanKuota extends EditRecord
{
    protected static string $resource = KebijakanKuotaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
