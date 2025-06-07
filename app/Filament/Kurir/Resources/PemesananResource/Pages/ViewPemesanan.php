<?php

namespace App\Filament\Kurir\Resources\PemesananResource\Pages;

use App\Filament\Kurir\Resources\PemesananResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPemesanan extends ViewRecord
{
    protected static string $resource = PemesananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
