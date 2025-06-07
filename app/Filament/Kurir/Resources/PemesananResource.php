<?php

namespace App\Filament\Kurir\Resources;

use App\Filament\Kurir\Resources\PemesananResource\Pages;
use App\Models\Pemesanan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PemesananResource extends Resource
{
    protected static ?string $model = Pemesanan::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Pemesanan';
    protected static ?string $modelLabel = 'Pengiriman';
    protected static ?string $pluralModelLabel = 'Tugas Pengiriman';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('penjual.user.name')->label('Penjual')->disabled(),
                Forms\Components\TextInput::make('jumlah_pesanan')->label('Jumlah Pesanan')->disabled()->suffix(' tabung'),
                Forms\Components\Select::make('status')->label('Status')
                    ->options([
                        'disetujui' => 'Siap Kirim',
                        'dalam_perjalanan' => 'Dalam Perjalanan',
                        'selesai' => 'Selesai',
                    ])->disabled(),
                Forms\Components\DateTimePicker::make('tanggal_pesanan')->label('Tanggal Pesanan')->disabled(),
                Forms\Components\Textarea::make('keterangan')->label('Keterangan')->disabled()->rows(3),
                Forms\Components\TextInput::make('penjual.alamat')->label('Alamat Tujuan')->disabled()->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                try {
                    $user = Auth::user();
                    if (!$user) {
                        \Log::info('Kurir PemesananResource: No authenticated user');
                        return $query->whereRaw('1 = 0');
                    }
                    $kurir = \App\Models\Kurir::where('user_id', $user->id)->first();
                    if (!$kurir) {
                        \Log::info('Kurir PemesananResource: User tidak memiliki profil kurir', ['user_id' => $user->id]);
                        return $query->whereRaw('1 = 0');
                    }
                    \Log::info('Kurir PemesananResource: Filtering untuk kurir', ['kurir_id' => $kurir->id]);
                    return $query->where('kurir_id', $kurir->id)
                                ->whereIn('status', ['disetujui', 'dalam_perjalanan', 'selesai'])
                                ->with(['penjual.user', 'kurir.user']);      
                } catch (\Exception $e) {
                    \Log::error('Kurir PemesananResource Query Error: ' . $e->getMessage());
                    return $query->whereRaw('1 = 0');
                }
            })
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('penjual.user.name')->label('Penjual')->sortable()->searchable(),
                TextColumn::make('penjual.kategori')->label('Kategori')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Agen' => 'success',
                        'Pangkalan' => 'warning',
                        'Sub-Pangkalan' => 'info',
                        default => 'secondary',
                    }),
                TextColumn::make('jumlah_pesanan')->label('Jumlah')->sortable()->suffix(' tabung'),
                BadgeColumn::make('status')->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'disetujui' => 'Siap Kirim',
                        'dalam_perjalanan' => 'Dalam Perjalanan',
                        'selesai' => 'Selesai',
                        default => $state
                    })
                    ->colors([
                        'success' => 'disetujui',
                        'warning' => 'dalam_perjalanan',
                        'primary' => 'selesai',
                    ]),
                TextColumn::make('penjual.alamat')->label('Alamat Tujuan')->limit(50)->tooltip(fn ($record) => $record->penjual->alamat ?? 'Alamat tidak tersedia'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'disetujui' => 'Siap Kirim',
                        'dalam_perjalanan' => 'Dalam Perjalanan',
                        'selesai' => 'Selesai',
                    ]),
                Tables\Filters\SelectFilter::make('penjual.kategori')->label('Kategori Penjual')
                    ->options([
                        'Agen' => 'Agen',
                        'Pangkalan' => 'Pangkalan',
                        'Sub-Pangkalan' => 'Sub-Pangkalan',
                    ]),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
                Action::make('mulai_pengiriman')->label('Mulai Pengiriman')->icon('heroicon-o-play')->color('warning')->visible(fn (Pemesanan $record) => $record->status === 'disetujui')->requiresConfirmation()->modalHeading('Mulai Pengiriman')
                    ->modalDescription(fn (Pemesanan $record) => 
                        "Apakah Anda siap memulai pengiriman ke {$record->penjual->user->name}?\n\n" .
                        "Alamat: {$record->penjual->alamat}\n" .
                        "Jumlah: {$record->jumlah_pesanan} tabung\n"
                    )
                    ->action(function (Pemesanan $record) {
                        $record->update([
                            'status' => 'dalam_perjalanan',
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('Pengiriman dimulai')
                            ->body('Status pengiriman telah diubah menjadi "Dalam Perjalanan". Selamat bertugas!')
                            ->send();
                    }),
                
                    Action::make('selesai_pengiriman')
                    ->label('Selesai')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Pemesanan $record) => $record->status === 'dalam_perjalanan')
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Pengiriman')
                    ->modalDescription(fn (Pemesanan $record) => 
                        "Apakah pengiriman ke {$record->penjual->user->name} sudah selesai dan barang telah diserahkan?\n\n" .
                        "Pastikan Anda sudah:\n" .
                        "✓ Barang telah diserahkan ke penjual\n" .
                        "✓ Penjual telah menerima barang\n" .
                        "✓ Dokumen pengiriman telah ditandatangani (jika ada)\n\n" .
                        "⚠️ Catatan: Stok akan ditambahkan setelah penjual mengkonfirmasi penerimaan barang."
                    )
                    ->form([
                        Forms\Components\Textarea::make('catatan_pengiriman')
                            ->label('Catatan Pengiriman (Opsional)')
                            ->placeholder('Tambahkan catatan terkait pengiriman ini...')
                            ->rows(3),
                    ])
                    ->action(function (Pemesanan $record, array $data) {
                        $updateData = [
                            'status' => 'selesai',
                        ];
                        
                        if (isset($data['catatan_pengiriman']) && !empty($data['catatan_pengiriman'])) {
                            $keteranganLama = $record->keterangan ?? '';
                            $keteranganBaru = $keteranganLama 
                                ? $keteranganLama . "\n\nCatatan Pengiriman: " . $data['catatan_pengiriman']
                                : "Catatan Pengiriman: " . $data['catatan_pengiriman'];
                            $updateData['keterangan'] = $keteranganBaru;
                        }
                        
                        $record->update($updateData);
                        
                        if ($record->kurir) {
                            $record->kurir->update(['status' => 'tersedia']);
                        }
                        
                        Notification::make()
                            ->success()
                            ->title('Pengiriman selesai')
                            ->body("Pengiriman telah selesai. Menunggu konfirmasi penerimaan dari penjual {$record->penjual->user->name}. Status Anda kembali tersedia untuk tugas berikutnya.")
                            ->send();
                    }),
            ])
            ->bulkActions([
            ])
            ->defaultSort('tanggal_pesanan', 'asc')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPemesanans::route('/'),
            // 'view' => Pages\ViewPemesanan::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return null;
            }
            
            $kurir = \App\Models\Kurir::where('user_id', $user->id)->first();
            if (!$kurir) {
                return null;
            }
            
            $tugasAktif = static::getModel()::where('kurir_id', $kurir->id)
                ->whereIn('status', ['disetujui', 'dalam_perjalanan'])
                ->count();
                
            return $tugasAktif > 0 ? (string) $tugasAktif : null;
        } catch (\Exception $e) {
            \Log::error('Navigation badge error: ' . $e->getMessage());
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return null;
            }
            
            $kurir = \App\Models\Kurir::where('user_id', $user->id)->first();
            if (!$kurir) {
                return null;
            }
            
            $tugasAktif = static::getModel()::where('kurir_id', $kurir->id)
                ->whereIn('status', ['disetujui', 'dalam_perjalanan'])
                ->count();
                
            return $tugasAktif > 0 ? 'warning' : null;
        } catch (\Exception $e) {
            \Log::error('Navigation badge color error: ' . $e->getMessage());
            return null;
        }
    }
}