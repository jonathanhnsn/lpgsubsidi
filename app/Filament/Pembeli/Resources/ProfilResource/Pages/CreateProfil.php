<?php

namespace App\Filament\Pembeli\Resources\ProfilResource\Pages;

use App\Filament\Pembeli\Resources\ProfilResource;
use App\Models\Pembeli;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateProfil extends CreateRecord
{
    protected static string $resource = ProfilResource::class;
    public function mount(): void
    {
        $user = auth()->user();
        if (Pembeli::where('user_id', $user->id)->exists()) {
            Notification::make()
                ->title('Profil Sudah Ada')
                ->body('Anda sudah memiliki profil. Silakan edit profil yang sudah ada.')
                ->warning()
                ->send();

            $this->redirect(static::getResource()::getUrl('index'));
            return;
        }

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        
        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Profil Berhasil Dibuat')
            ->body('Profil Anda telah berhasil dibuat. Mohon tunggu persetujuan dari admin.')
            ->persistent();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}