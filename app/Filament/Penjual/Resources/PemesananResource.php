<?php

namespace App\Filament\Penjual\Resources;

use App\Filament\Penjual\Resources\PemesananResource\Pages;
use App\Models\Pemesanan;
use App\Models\Penjual;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class PemesananResource extends Resource
{
    protected static ?string $model = Pemesanan::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Pemesanan Stok';
    protected static ?string $modelLabel = 'Pemesanan';
    protected static ?string $pluralModelLabel = 'Pemesanan';

    protected static function getAuthenticatedPenjual(): ?Penjual
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }
        return Penjual::where('user_id', $user->id)->first();
    }

    public static function getEloquentQuery(): Builder
    {
        $penjual = static::getAuthenticatedPenjual();
        if (!$penjual) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }
        return parent::getEloquentQuery()->where('penjual_id', $penjual->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('status_warning')->label('')
                    ->content(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        if (!$penjual) {
                            return '❌ Profil penjual tidak ditemukan. Silakan buat profil terlebih dahulu.';
                        }
                        return match($penjual->status) {
                            'pending' => '⏳ Status profil Anda masih menunggu persetujuan admin. Anda belum dapat melakukan pemesanan stok.',
                            'rejected' => '❌ Profil Anda ditolak. Hubungi admin untuk informasi lebih lanjut.',
                            'accepted' => '✅ Profil Anda telah disetujui. Anda dapat melakukan pemesanan stok.',
                            default => '❓ Status profil tidak diketahui.'
                        };
                    })->columnSpanFull()
                    ->visible(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        return !$penjual || $penjual->status !== 'accepted';
                    }),
                Placeholder::make('kuota_info')->label('Informasi Stok & Kuota')
                    ->content(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        if (!$penjual) {
                            return 'Profil penjual tidak ditemukan';
                        }
                        $totalPending = Pemesanan::where('penjual_id', $penjual->id)->where('status', 'pending')->sum('jumlah_pesanan');
                        $maxPesanan = $penjual->kuota - $penjual->stok - $totalPending;
                        return "Kuota: {$penjual->kuota} | Stok saat ini: {$penjual->stok} | Pesanan pending: {$totalPending} | Maksimal pesanan: {$maxPesanan}";
                    })->columnSpanFull()
                    ->visible(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && $penjual->status === 'accepted';
                    }),
                TextInput::make('jumlah_pesanan')->label('Jumlah Pesanan')->required()->numeric()->minValue(1)
                    ->rules([
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                $penjual = static::getAuthenticatedPenjual();
                                if (!$penjual) {
                                    $fail('Data penjual tidak ditemukan.');
                                    return;
                                }
                                if ($penjual->status !== 'accepted') {
                                    $fail('Profil Anda belum disetujui. Tidak dapat melakukan pemesanan.');
                                    return;
                                }
                                $totalPending = Pemesanan::where('penjual_id', $penjual->id)->where('status', 'pending')->sum('jumlah_pesanan');
                                $maxPesanan = $penjual->kuota - $penjual->stok - $totalPending;
                                if ($value > $maxPesanan) {
                                    $fail("Jumlah pesanan melebihi batas. Maksimal yang bisa dipesan: {$maxPesanan} tabung (Kuota: {$penjual->kuota}, Stok saat ini: {$penjual->stok}, Pesanan pending: {$totalPending})");
                                }
                            };
                        },
                    ])
                    ->helperText(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        if (!$penjual || $penjual->status !== 'accepted') {
                            return 'Profil harus disetujui terlebih dahulu';
                        }
                        $totalPending = Pemesanan::where('penjual_id', $penjual->id)->where('status', 'pending')->sum('jumlah_pesanan');
                        $maxPesanan = $penjual->kuota - $penjual->stok - $totalPending;
                        return "Kuota: {$penjual->kuota} | Stok saat ini: {$penjual->stok} | Pesanan pending: {$totalPending} | Maksimal pesanan: {$maxPesanan}";
                    })
                    ->visible(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && $penjual->status === 'accepted';
                    }),
                Textarea::make('keterangan')->label('Keterangan')->rows(3)->columnSpanFull()
                    ->visible(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && $penjual->status === 'accepted';
                    }),
                DateTimePicker::make('tanggal_pesanan')->label('Tanggal Pesanan')->default(now())->required()->disabled()->dehydrated(true)
                    ->visible(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && $penjual->status === 'accepted';
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('jumlah_pesanan')->label('Jumlah')->sortable()->suffix(' tabung'),
                BadgeColumn::make('status')->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'dalam_perjalanan' => 'Dalam Perjalanan',
                        'selesai' => 'Selesai',
                        default => $state
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                        'info' => 'dalam_perjalanan',
                        'primary' => 'selesai',
                    ]),
                TextColumn::make('kurir.user.name')->label('Kurir')->placeholder('-')->sortable(),
                TextColumn::make('tanggal_pesanan')->label('Tanggal Pesanan')->dateTime()->sortable(),
                TextColumn::make('tanggal_diproses')->label('Tanggal Diproses')->dateTime()->placeholder('-')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'dalam_perjalanan' => 'Dalam Perjalanan',
                        'selesai' => 'Selesai',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(function (Pemesanan $record): bool {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && $record->penjual_id === $penjual->id;
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(function (Pemesanan $record): bool {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && 
                               $penjual->status === 'accepted' &&
                               $record->penjual_id === $penjual->id && 
                               $record->status === 'pending';
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(function (Pemesanan $record): bool {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && 
                               $penjual->status === 'accepted' &&
                               $record->penjual_id === $penjual->id && 
                               $record->status === 'pending';
                    }),
                
                // Button konfirmasi penerimaan barang
                Action::make('konfirmasi_penerimaan')
                    ->label('Konfirmasi Terima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function (Pemesanan $record) {
                        return $record->status === 'selesai' && 
                               (is_null($record->keterangan) || 
                                !str_contains($record->keterangan, '=== BARANG TELAH DIKONFIRMASI DITERIMA ==='));
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Penerimaan Barang')
                    ->modalDescription(fn (Pemesanan $record) => 
                        "Silakan crosscheck jumlah barang yang Anda terima:\n\n" .
                        "📦 Jumlah pesanan: {$record->jumlah_pesanan} tabung\n" .
                        "🚚 Kurir: {$record->kurir->user->name}\n" .
                        "📅 Tanggal pengiriman selesai: " . ($record->updated_at ? $record->updated_at->format('d/m/Y H:i') : '-') . "\n\n" .
                        "Apakah jumlah barang yang diterima sesuai dengan pesanan?"
                    )
                    ->modalSubmitActionLabel('Ya, Konfirmasi Penerimaan')
                    ->modalCancelActionLabel('Batal')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('jumlah_dipesan')
                                    ->label('Jumlah Dipesan')
                                    ->default(fn (Pemesanan $record) => $record->jumlah_pesanan)
                                    ->disabled()
                                    ->suffix(' tabung'),
                                    
                                Forms\Components\TextInput::make('jumlah_diterima')
                                    ->label('Jumlah Diterima')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(fn (Pemesanan $record) => $record->jumlah_pesanan)
                                    ->suffix(' tabung')
                                    ->helperText('Masukkan jumlah tabung yang benar-benar Anda terima'),
                            ]),
                            
                        Forms\Components\Textarea::make('catatan_penerimaan')
                            ->label('Catatan Penerimaan (Opsional)')
                            ->placeholder('Tambahkan catatan jika ada yang perlu dicatat...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->action(function (Pemesanan $record, array $data) {
                        $jumlahDiterima = (int) $data['jumlah_diterima'];
                        $jumlahDipesan = $record->jumlah_pesanan;
                        
                        // Update keterangan dengan informasi konfirmasi
                        $updateData = [
                            'tanggal_dikonfirmasi' => now(),
                        ];
                        
                        // Tambahkan catatan konfirmasi ke keterangan
                        $keteranganKonfirmasi = "\n\n=== KONFIRMASI PENERIMAAN ===\n";
                        $keteranganKonfirmasi .= "Tanggal: " . now()->format('d/m/Y H:i:s') . "\n";
                        $keteranganKonfirmasi .= "Jumlah dipesan: {$jumlahDipesan} tabung\n";
                        $keteranganKonfirmasi .= "Jumlah diterima: {$jumlahDiterima} tabung\n";
                        
                        if ($jumlahDiterima !== $jumlahDipesan) {
                            $selisih = $jumlahDiterima - $jumlahDipesan;
                            $keteranganKonfirmasi .= "Selisih: " . ($selisih > 0 ? "+{$selisih}" : $selisih) . " tabung\n";
                        } else {
                            $keteranganKonfirmasi .= "Status: Sesuai pesanan\n";
                        }
                        
                        // Tambahkan catatan penerimaan jika ada
                        if (isset($data['catatan_penerimaan']) && !empty($data['catatan_penerimaan'])) {
                            $keteranganKonfirmasi .= "Catatan: " . $data['catatan_penerimaan'] . "\n";
                        }
                        
                        $keteranganKonfirmasi .= "=== BARANG TELAH DIKONFIRMASI DITERIMA ===";
                        
                        $keteranganLama = $record->keterangan ?? '';
                        $updateData['keterangan'] = $keteranganLama . $keteranganKonfirmasi;
                        
                        $record->update($updateData);
                        
                        // Update stok penjual berdasarkan jumlah yang benar-benar diterima
                        $record->penjual->increment('stok', $jumlahDiterima);
                        
                        // Notifikasi sukses
                        $message = $jumlahDiterima === $jumlahDipesan 
                            ? "Penerimaan barang dikonfirmasi. Stok Anda bertambah {$jumlahDiterima} tabung."
                            : "Penerimaan barang dikonfirmasi dengan selisih. Stok bertambah {$jumlahDiterima} tabung (dari {$jumlahDipesan} tabung yang dipesan).";
                            
                        Notification::make()
                            ->success()
                            ->title('Konfirmasi Penerimaan Berhasil')
                            ->body($message)
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('status_info')->label('Info Status')->icon('heroicon-o-information-circle')->color('info')
                    ->action(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        if (!$penjual) {
                            return;
                        }
                        $totalPending = Pemesanan::where('penjual_id', $penjual->id)->where('status', 'pending')->sum('jumlah_pesanan');
                        $totalDisetujui = Pemesanan::where('penjual_id', $penjual->id)->where('status', 'disetujui')->sum('jumlah_pesanan');
                        $totalSelesai = Pemesanan::where('penjual_id', $penjual->id)
                            ->where('status', 'selesai')
                            ->where(function($query) {
                                $query->where('keterangan', 'not like', '%=== BARANG TELAH DIKONFIRMASI DITERIMA ===%')
                                      ->orWhereNull('keterangan');
                            })
                            ->count();
                        $maxPesanan = $penjual->kuota - $penjual->stok - $totalPending;
                        
                        Notification::make()
                            ->title('Informasi Status Pemesanan')
                            ->body("Status profil: " . ucfirst($penjual->status) . 
                                   " | Kuota: {$penjual->kuota}" . 
                                   " | Stok: {$penjual->stok}" . 
                                   " | Pending: {$totalPending}" . 
                                   " | Disetujui: {$totalDisetujui}" . 
                                   " | Perlu konfirmasi: {$totalSelesai}" .
                                   " | Bisa pesan: {$maxPesanan}")
                            ->info()
                            ->send();
                    })
                    ->visible(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && $penjual->status === 'accepted';
                    })
            ])
            ->emptyStateHeading(function () {
                $penjual = static::getAuthenticatedPenjual();
                if (!$penjual) {
                    return 'Profil Diperlukan';
                }
                return match($penjual->status) {
                    'pending' => 'Menunggu Persetujuan',
                    'rejected' => 'Profil Ditolak',
                    default => 'Belum Ada Pemesanan'
                };
            })
            ->emptyStateDescription(function () {
                $penjual = static::getAuthenticatedPenjual();
                if (!$penjual) {
                    return 'Silakan buat profil penjual terlebih dahulu untuk dapat melakukan pemesanan stok.';
                }
                return match($penjual->status) {
                    'pending' => 'Profil Anda masih menunggu persetujuan admin. Anda belum dapat melakukan pemesanan stok.',
                    'rejected' => 'Profil Anda ditolak. Hubungi admin untuk informasi lebih lanjut.',
                    default => 'Anda belum memiliki pemesanan stok apapun.'
                };
            })
            ->emptyStateIcon(function () {
                $penjual = static::getAuthenticatedPenjual();
                if (!$penjual) {
                    return 'heroicon-o-user-plus';
                }
                return match($penjual->status) {
                    'pending' => 'heroicon-o-clock',
                    'rejected' => 'heroicon-o-x-circle',
                    default => 'heroicon-o-shopping-cart'
                };
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPemesanans::route('/'),
            'create' => Pages\CreatePemesanan::route('/create'),
            'view' => Pages\ViewPemesanan::route('/{record}'),
            'edit' => Pages\EditPemesanan::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        $penjual = static::getAuthenticatedPenjual();
        if (!$penjual || $penjual->status !== 'accepted') {
            return false;
        }
        $totalPending = Pemesanan::where('penjual_id', $penjual->id)
            ->where('status', 'pending')
            ->sum('jumlah_pesanan');
        
        $maxPesanan = $penjual->kuota - $penjual->stok - $totalPending;
        return $maxPesanan > 0;
    }

    public static function canView($record): bool
    {
        $penjual = static::getAuthenticatedPenjual();
        return $penjual && $record->penjual_id === $penjual->id;
    }

    public static function canEdit($record): bool
    {
        $penjual = static::getAuthenticatedPenjual();
        return $penjual && 
               $penjual->status === 'accepted' &&
               $record->penjual_id === $penjual->id && 
               $record->status === 'pending';
    }

    public static function canDelete($record): bool
    {
        $penjual = static::getAuthenticatedPenjual();
        return $penjual && 
               $penjual->status === 'accepted' &&
               $record->penjual_id === $penjual->id && 
               $record->status === 'pending';
    }

    public static function getNavigationBadge(): ?string
    {
        $penjual = static::getAuthenticatedPenjual();
        if (!$penjual) {
            return '!';
        }
        if ($penjual->status !== 'accepted') {
            return '⏸️';
        }
        $pendingCount = static::getModel()::where('penjual_id', $penjual->id)->where('status', 'pending')->count();
        $selesaiCount = static::getModel()::where('penjual_id', $penjual->id)
            ->where('status', 'selesai')
            ->where(function($query) {
                $query->where('keterangan', 'not like', '%=== BARANG TELAH DIKONFIRMASI DITERIMA ===%')
                      ->orWhereNull('keterangan');
            })
            ->count();
        $totalBadge = $pendingCount + $selesaiCount;
        return $totalBadge > 0 ? (string) $totalBadge : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $penjual = static::getAuthenticatedPenjual();
        if (!$penjual) {
            return 'danger';
        }
        return match($penjual->status) {
            'pending' => 'warning',
            'rejected' => 'danger',
            'accepted' => 'warning',
            default => 'gray'
        };
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $penjual = static::getAuthenticatedPenjual();
        if (!$penjual) {
            return 'Profil diperlukan untuk pemesanan';
        }
        if ($penjual->status !== 'accepted') {
            return match($penjual->status) {
                'pending' => 'Profil menunggu persetujuan - Tidak dapat pesan stok',
                'rejected' => 'Profil ditolak - Hubungi admin',
                default => null
            };
        }
        
        $pendingCount = static::getModel()::where('penjual_id', $penjual->id)->where('status', 'pending')->count();
        $selesaiCount = static::getModel()::where('penjual_id', $penjual->id)->where('status', 'selesai')->count();
        
        $tooltip = [];
        if ($pendingCount > 0) {
            $tooltip[] = "Pending: {$pendingCount}";
        }
        if ($selesaiCount > 0) {
            $tooltip[] = "Perlu konfirmasi: {$selesaiCount}";
        }
        
        return !empty($tooltip) ? implode(' | ', $tooltip) : null;
    }
}