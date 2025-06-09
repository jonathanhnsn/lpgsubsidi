<?php

namespace App\Filament\Pembeli\Resources\ProfilResource\Pages;

use App\Filament\Pembeli\Resources\ProfilResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EditProfil extends EditRecord
{
    protected static string $resource = ProfilResource::class;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        $this->authorizeAccess();

        $this->fillForm();
    }

    protected function authorizeAccess(): void
    {
        if ($this->record->user_id !== auth()->id()) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki akses untuk mengedit profil ini.')
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Pastikan user_id tidak berubah
        $data['user_id'] = $this->record->user_id;
        
        // Jika profil sebelumnya ditolak dan sekarang sedang diedit,
        // ubah status menjadi pending untuk ditinjau ulang
        if ($this->record->status === 'rejected') {
            $data['status'] = 'pending';
            // Tidak menghapus rejection_note, tetap menyimpannya untuk referensi admin
        }
        
        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        $message = 'Perubahan profil Anda telah berhasil disimpan.';
        
        // Jika status berubah dari rejected ke pending, beri informasi tambahan
        if ($this->record->status === 'rejected') {
            $message .= ' Status profil telah diubah menjadi "Menunggu Persetujuan" dan akan ditinjau ulang oleh admin.';
        }
        
        return Notification::make()
            ->success()
            ->title('Profil Berhasil Diperbarui')
            ->body($message);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Lihat Profil'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}