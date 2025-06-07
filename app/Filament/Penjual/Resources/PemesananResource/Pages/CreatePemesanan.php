<?php

namespace App\Filament\Penjual\Resources\PemesananResource\Pages;

use App\Filament\Penjual\Resources\PemesananResource;
use App\Models\Penjual;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class CreatePemesanan extends CreateRecord
{
    protected static string $resource = PemesananResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $penjual = Penjual::where('user_id', Auth::id())->first();
        
        $data['penjual_id'] = $penjual->id;
        $data['status'] = 'pending';
        $data['tanggal_pesanan'] = now();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Pemesanan berhasil dibuat')
            ->body('Pesanan Anda akan diproses oleh admin.');
    }
}   
