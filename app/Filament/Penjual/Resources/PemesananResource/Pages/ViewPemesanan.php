<?php

namespace App\Filament\Penjual\Resources\PemesananResource\Pages;

use App\Filament\Penjual\Resources\PemesananResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPemesanan extends ViewRecord
{
    protected static string $resource = PemesananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')->label('Kembali')->icon('heroicon-o-arrow-left')->color('gray')->url($this->getResource()::getUrl('index')),
            Actions\EditAction::make()->visible(fn () => $this->record->status === 'pending'),
        ];
    }
}
