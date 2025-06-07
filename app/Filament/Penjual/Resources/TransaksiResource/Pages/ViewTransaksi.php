<?php

namespace App\Filament\Penjual\Resources\TransaksiResource\Pages;

use App\Filament\Penjual\Resources\TransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewTransaksi extends ViewRecord
{
    protected static string $resource = TransaksiResource::class;

    protected function resolveRecord($key): Model
    {
        return static::getResource()::resolveRecordRouteBinding($key)
            ->load(['pembeli.user', 'gas', 'penjual.user']);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pastikan data relasi dimuat dengan benar
        $record = $this->getRecord();
        
        // Load relasi jika belum dimuat
        if (!$record->relationLoaded('pembeli')) {
            $record->load('pembeli.user');
        }
        if (!$record->relationLoaded('gas')) {
            $record->load('gas');
        }
        if (!$record->relationLoaded('penjual')) {
            $record->load('penjual.user');
        }

        // Set data untuk form
        $data['pembeli.user.name'] = $record->pembeli?->user?->name ?? 'Data tidak ditemukan';
        $data['gas.jenis'] = $record->gas?->jenis ?? 'Data tidak ditemukan';
        
        return $data;
    }
}