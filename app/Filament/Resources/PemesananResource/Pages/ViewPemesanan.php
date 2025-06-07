<?php

namespace App\Filament\Resources\PemesananResource\Pages;

use App\Filament\Resources\PemesananResource;
use App\Models\Pemesanan;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;

class ViewPemesanan extends ViewRecord
{
    protected static string $resource = PemesananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === 'pending'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Detail Pemesanan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('ID Pemesanan'),
                                
                                TextEntry::make('penjual.user.name')
                                    ->label('Nama Penjual'),
                                
                                TextEntry::make('penjual.kategori')
                                    ->label('Kategori Penjual')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Agen' => 'success',
                                        'Pangkalan' => 'warning',
                                        'Sub-Pangkalan' => 'info',
                                        default => 'secondary',
                                    }),
                                
                                TextEntry::make('jumlah_pesanan')
                                    ->label('Jumlah Pesanan')
                                    ->suffix(' unit'),
                                
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'pending' => 'Menunggu Persetujuan',
                                        'disetujui' => 'Disetujui',
                                        'ditolak' => 'Ditolak',
                                        'selesai' => 'Selesai',
                                        default => $state
                                    })
                                    ->badge()
                                    ->color(fn ($state) => match($state) {
                                        'pending' => 'warning',
                                        'disetujui' => 'success',
                                        'ditolak' => 'danger',
                                        'selesai' => 'primary',
                                        default => 'secondary'
                                    }),
                                
                                TextEntry::make('tanggal_pesanan')
                                    ->label('Tanggal Pesanan')
                                    ->dateTime(),
                                
                                TextEntry::make('tanggal_diproses')
                                    ->label('Tanggal Diproses')
                                    ->dateTime()
                                    ->placeholder('-'),
                                
                                TextEntry::make('keterangan')
                                    ->label('Keterangan')
                                    ->placeholder('Tidak ada keterangan')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Informasi Stok Penjual')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('penjual.kuota')
                                    ->label('Kuota Total')
                                    ->suffix(' unit')
                                    ->color('primary'),
                                
                                TextEntry::make('penjual.stok')
                                    ->label('Stok Saat Ini')
                                    ->suffix(' unit')
                                    ->color('success'),
                                
                                TextEntry::make('total_pending')
                                    ->label('Total Pending')
                                    ->suffix(' unit')
                                    ->color('warning')
                                    ->state(function () {
                                        return Pemesanan::where('penjual_id', $this->record->penjual_id)
                                            ->where('status', 'pending')
                                            ->sum('jumlah_pesanan');
                                    }),
                                
                                TextEntry::make('sisa_kuota')
                                    ->label('Sisa Kuota')
                                    ->suffix(' unit')
                                    ->color(function () {
                                        $penjual = $this->record->penjual;
                                        $totalPending = Pemesanan::where('penjual_id', $penjual->id)
                                            ->where('status', 'pending')
                                            ->sum('jumlah_pesanan');
                                        $sisaKuota = $penjual->kuota - $penjual->stok - $totalPending;
                                        
                                        return match (true) {
                                            $sisaKuota > 50 => 'success',
                                            $sisaKuota > 20 => 'warning',
                                            $sisaKuota >= 0 => 'danger',
                                            default => 'gray'
                                        };
                                    })
                                    ->state(function () {
                                        $penjual = $this->record->penjual;
                                        $totalPending = Pemesanan::where('penjual_id', $penjual->id)
                                            ->where('status', 'pending')
                                            ->sum('jumlah_pesanan');
                                        return $penjual->kuota - $penjual->stok - $totalPending;
                                    }),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}