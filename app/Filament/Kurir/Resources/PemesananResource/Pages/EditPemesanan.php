<?php

namespace App\Filament\Kurir\Resources\PemesananResource\Pages;

use App\Filament\Kurir\Resources\PemesananResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPemesanan extends EditRecord
{
    protected static string $resource = PemesananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
