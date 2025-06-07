<?php

namespace App\Filament\Pemerintah\Resources\PenjualResource\Pages;

use App\Filament\Pemerintah\Resources\PenjualResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenjual extends EditRecord
{
    protected static string $resource = PenjualResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
