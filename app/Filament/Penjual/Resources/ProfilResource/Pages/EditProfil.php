<?php

namespace App\Filament\Penjual\Resources\ProfilResource\Pages;

use App\Filament\Penjual\Resources\ProfilResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProfil extends EditRecord
{
    protected static string $resource = ProfilResource::class;

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
