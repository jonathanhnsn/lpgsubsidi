<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')->label('Kembali')->icon('heroicon-o-arrow-left')->color('gray')->url($this->getResource()::getUrl('index')),
        ];
    }
}
