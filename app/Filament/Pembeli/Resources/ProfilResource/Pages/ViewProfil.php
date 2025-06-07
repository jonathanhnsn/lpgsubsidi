<?php

namespace App\Filament\Pembeli\Resources\ProfilResource\Pages;

use App\Filament\Pembeli\Resources\ProfilResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ViewProfil extends ViewRecord
{
    protected static string $resource = ProfilResource::class;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        // Validasi ownership
        $this->authorizeAccess();

        $this->fillForm();
        // $this->fillFormWithDataAndCallHooks();
    }

    protected function authorizeAccess(): void
    {
        if ($this->record->user_id !== auth()->id()) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki akses untuk melihat profil ini.')
                ->danger()
                ->send();

            $this->redirect(static::$resource::getUrl('index'));
            return;
        }
    }

    protected function resolveRecord(int | string $key): \Illuminate\Database\Eloquent\Model
    {
        try {
            $record = static::getResource()::getModel()::findOrFail($key);
            return $record;
        } catch (ModelNotFoundException $e) {
            Notification::make()
                ->title('Profil Tidak Ditemukan')
                ->body('Profil yang Anda cari tidak ditemukan.')
                ->danger()
                ->send();

            $this->redirect(static::$resource::getUrl('index'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Profil'),
        ];
    }
}
