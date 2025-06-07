<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;

class ViewTransaksi extends ViewRecord
{
    protected static string $resource = TransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Admin tidak memiliki action untuk edit
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Transaksi')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('kode_transaksi')
                                    ->label('Kode Transaksi')
                                    ->copyable()
                                    ->copyMessage('Kode transaksi berhasil disalin'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Pending' => 'warning',
                                        'Confirmed' => 'info',
                                        'Completed' => 'success',
                                        default => 'gray',
                                    }),

                                TextEntry::make('gas.jenis')
                                    ->label('Jenis Gas'),

                                TextEntry::make('harga')
                                    ->label('Harga')
                                    ->money('IDR'),

                                TextEntry::make('tgl_beli')
                                    ->label('Tanggal Beli')
                                    ->date('d F Y'),

                                TextEntry::make('tgl_kembali')
                                    ->label('Tanggal Kembali')
                                    ->date('d F Y'),
                            ]),
                    ]),

                Section::make('Informasi Pembeli')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('pembeli.user.name')
                                    ->label('Nama Pembeli'),

                                TextEntry::make('pembeli.nik')
                                    ->label('NIK'),

                                TextEntry::make('pembeli.pekerjaan')
                                    ->label('Pekerjaan'),

                                TextEntry::make('pembeli.gaji')
                                    ->label('Gaji')
                                    ->money('IDR'),

                                TextEntry::make('pembeli.nama_provinsi')
                                    ->label('Provinsi'),

                                TextEntry::make('pembeli.nama_kota_kabupaten')
                                    ->label('Kota/Kabupaten'),

                                TextEntry::make('pembeli.nama_kecamatan')
                                    ->label('Kecamatan'),

                                TextEntry::make('pembeli.alamat')
                                    ->label('Alamat')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Informasi Penjual')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('penjual.user.name')
                                    ->label('Nama Akun Penjual')
                                    ->default('Belum ada penjual'),

                                TextEntry::make('penjual.nama_pemilik')
                                    ->label('Nama Pemilik Toko')
                                    ->default('Belum ada penjual'),

                                TextEntry::make('penjual.nik')
                                    ->label('NIK Penjual')
                                    ->default('Belum ada penjual'),

                                TextEntry::make('penjual.kategori')
                                    ->label('Kategori Toko')
                                    ->default('Belum ada penjual'),

                                TextEntry::make('penjual.nama_provinsi')
                                    ->label('Provinsi Toko')
                                    ->default('Belum ada penjual'),

                                TextEntry::make('penjual.nama_kota_kabupaten')
                                    ->label('Kota/Kabupaten Toko')
                                    ->default('Belum ada penjual'),

                                TextEntry::make('penjual.nama_kecamatan')
                                    ->label('Kecamatan Toko')
                                    ->default('Belum ada penjual'),

                                TextEntry::make('penjual.alamat')
                                    ->label('Alamat Toko')
                                    ->columnSpanFull()
                                    ->default('Belum ada penjual'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->penjual_id !== null),

                Section::make('Informasi Waktu')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Dibuat Pada')
                                    ->dateTime('d F Y, H:i'),

                                TextEntry::make('updated_at')
                                    ->label('Diperbarui Pada')
                                    ->dateTime('d F Y, H:i'),
                            ]),
                    ]),
            ]);
    }
}