<?php

namespace App\Filament\Pembeli\Resources\TransaksiResource\Pages;

use App\Filament\Pembeli\Resources\TransaksiResource;
use App\Models\Pembeli;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListTransaksis extends ListRecords
{
    protected static string $resource = TransaksiResource::class;

    public function mount(): void
    {
        parent::mount();
        
        $user = auth()->user();
        $pembeli = Pembeli::where('user_id', $user->id)->first();
        
        if (!$pembeli) {
            Notification::make()
                ->title('Profil Diperlukan')
                ->body('Silakan buat profil pembeli terlebih dahulu untuk dapat melakukan transaksi.')
                ->warning()
                ->persistent()
                ->send();
        } elseif ($pembeli->status === 'pending') {
            Notification::make()
                ->title('Menunggu Persetujuan')
                ->body('Profil Anda masih menunggu persetujuan admin. Anda belum dapat melakukan transaksi.')
                ->warning()
                ->persistent()
                ->send();
        } elseif ($pembeli->status === 'rejected') {
            Notification::make()
                ->title('Profil Ditolak')
                ->body('Profil Anda ditolak. Hubungi admin untuk informasi lebih lanjut.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $pembeli = Pembeli::where('user_id', $user->id)->first();
        
        // Hanya tampilkan tombol Create jika pembeli ada dan status accepted
        if (!$pembeli || $pembeli->status !== 'accepted' || $pembeli->kuota <= 0) {
            return [];
        }

        return [
            Actions\CreateAction::make()
                ->label('Buat Transaksi Baru')
                ->icon('heroicon-o-plus')
                ->color('success'),
        ];
    }
}