<?php

namespace App\Filament\Pembeli\Resources\ProfilResource\Pages;

use App\Filament\Pembeli\Resources\ProfilResource;
use App\Models\Pembeli;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Notifications\Notification;

class ListProfils extends ListRecords
{
    protected static string $resource = ProfilResource::class;

    public function mount(): void
    {
        parent::mount();
        $user = auth()->user();
        $pembeli = Pembeli::where('user_id', $user->id)->first();
        
        if (!$pembeli) {
            Notification::make()
                ->title('Profil Diperlukan')
                ->body('Silakan lengkapi profil Anda terlebih dahulu untuk dapat menggunakan layanan.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(function () {
                    $user = auth()->user();
                    return !Pembeli::where('user_id', $user->id)->exists();
                })
                ->label('Buat Profil'),
        ];
    }
}