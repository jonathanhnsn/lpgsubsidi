<?php

namespace App\Filament\Resources\KebijakanKuotaResource\Pages;

use App\Filament\Resources\KebijakanKuotaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKebijakanKuota extends ViewRecord
{
    protected static string $resource = KebijakanKuotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
