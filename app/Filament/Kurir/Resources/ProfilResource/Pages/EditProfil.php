<?php

namespace App\Filament\Kurir\Resources\ProfilResource\Pages;

use App\Filament\Kurir\Resources\ProfilResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProfil extends EditRecord
{
    protected static string $resource = ProfilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
