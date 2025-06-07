<?php

namespace App\Filament\Penjual\Resources;

use App\Filament\Penjual\Resources\TransaksiResource\Pages;
use App\Models\Transaksi;
use App\Models\Penjual;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
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
        return parent::getEloquentQuery()->with(['pembeli.user', 'gas', 'penjual.user']);
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
                            'pending' => '⏳ Status profil Anda masih menunggu persetujuan admin. Anda belum dapat menangani transaksi.',
                            'rejected' => '❌ Profil Anda ditolak. Hubungi admin untuk informasi lebih lanjut.',
                            'accepted' => '✅ Profil Anda telah disetujui. Anda dapat menangani transaksi.',
                            default => '❓ Status profil tidak diketahui.'
                        };
                    })->columnSpanFull()
                    ->visible(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        return !$penjual || $penjual->status !== 'accepted';
                    }),
                Placeholder::make('stok_info')->label('Informasi Stok & Kuota')
                    ->content(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        if (!$penjual) {
                            return 'Profil penjual tidak ditemukan';
                        }
                        $sisaKuota = $penjual->getRemainingQuota();
                        return "Kategori: {$penjual->kategori} | Stok saat ini: {$penjual->stok} | Kuota maksimal: {$penjual->kuota} | Sisa kuota: {$sisaKuota}";
                    })->columnSpanFull()
                    ->visible(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && $penjual->status === 'accepted';
                    }),
                TextInput::make('kode_transaksi')->label('Kode Transaksi')->disabled()->formatStateUsing(fn ($record) => $record?->kode_transaksi ?? '')
                    ->visible(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && $penjual->status === 'accepted';
                    }),
                TextInput::make('nama_pembeli')->label('Nama Pembeli')->disabled()->formatStateUsing(fn ($record) => $record?->pembeli?->user?->name ?? 'Data tidak ditemukan')
                    ->visible(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && $penjual->status === 'accepted';
                    }),
                TextInput::make('jenis_gas')->label('Jenis Gas')->disabled()->formatStateUsing(fn ($record) => $record?->gas?->jenis ?? 'Data tidak ditemukan')
                    ->visible(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && $penjual->status === 'accepted';
                    }),
                TextInput::make('harga')->label('Harga')->prefix('Rp')->disabled()->formatStateUsing(fn ($record) => number_format($record?->harga ?? 0, 0, ',', '.'))
                    ->visible(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && $penjual->status === 'accepted';
                    }),
                DatePicker::make('tgl_beli')->label('Tanggal Beli')->disabled()
                    ->visible(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && $penjual->status === 'accepted';
                    }),
                DatePicker::make('tgl_kembali')->label('Tanggal Kembali')->disabled()
                    ->visible(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && $penjual->status === 'accepted';
                    }),
                Select::make('status')->label('Status')
                    ->options([
                        'Pending' => 'Pending',
                        'Confirmed' => 'Confirmed',
                        'Completed' => 'Completed',
                    ])->required()
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
                TextColumn::make('pembeli.user.name')->label('Nama Pembeli')->searchable()->sortable(),
                TextColumn::make('gas.jenis')->label('Jenis Gas')->sortable(),
                TextColumn::make('harga')->label('Harga')->money('IDR')->sortable(),
                TextColumn::make('tgl_beli')->label('Tanggal Beli')->date('d/m/Y')->sortable(),
                TextColumn::make('tgl_kembali')->label('Tanggal Kembali')->date('d/m/Y')->sortable()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'Belum selesai';
                        }
                        if (is_string($state)) {
                            return \Carbon\Carbon::parse($state)->format('d/m/Y');
                        }
                        return $state->format('d/m/Y');
                    }),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Confirmed' => 'info',
                        'Completed' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('penjual.user.name')->label('Ditangani Oleh')->default('Belum ditangani')->sortable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Pending' => 'Pending',
                        'Confirmed' => 'Confirmed',
                        'Completed' => 'Completed',
                    ]),
                Tables\Filters\Filter::make('my_transactions')->label('Transaksi Saya')
                    ->query(function (Builder $query): Builder {
                        $user = auth()->user();
                        $penjual = Penjual::where('user_id', $user->id)->first();
                        if ($penjual) {
                            return $query->where('penjual_id', $penjual->id);
                        }
                        return $query->whereNull('id');
                    }),
            ])
            ->actions([
                Action::make('confirm_transaction')->label('Konfirmasi')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(function (Transaksi $record): bool {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && 
                               $penjual->status === 'accepted' && 
                               $record->status === 'Pending';
                    })
                    ->form([
                        TextInput::make('verification_code')->label('Kode Verifikasi dari Pembeli')->required()->maxLength(6)->placeholder('Masukkan kode 6 digit')->helperText('Minta kode verifikasi dari pembeli')
                    ])
                    ->action(function (Transaksi $record, array $data): void {
                        if ($data['verification_code'] !== $record->kode_transaksi) {
                            Notification::make()->title('Kode Verifikasi Salah')->body('Kode yang Anda masukkan tidak sesuai dengan kode transaksi.')->danger()->send();
                            return;
                        }
                        $user = auth()->user();
                        $penjual = Penjual::where('user_id', $user->id)->first();
                        if (!$penjual) {
                            Notification::make()->title('Error')->body('Anda tidak memiliki profil penjual.')->danger()->send();
                            return;
                        }
                        if ($penjual->status !== 'accepted') {
                            Notification::make()->title('Profil Belum Disetujui')->body('Profil penjual Anda belum disetujui oleh admin. Anda tidak dapat menangani transaksi.')->danger()->send();
                            return;
                        }
                        if ($penjual->stok <= 0) {
                            Notification::make()->title('Stok Tidak Mencukupi')->body('Stok Anda tidak mencukupi untuk menangani transaksi ini. Stok saat ini: ' . $penjual->stok)->danger()->send();
                            return;
                        }
                        try {
                            $record->update([
                                'penjual_id' => $penjual->id,
                                'status' => 'Confirmed'
                            ]);
                            $penjual->refresh();
                            Notification::make()->title('Transaksi Dikonfirmasi')->body('Transaksi berhasil dikonfirmasi dan ditugaskan kepada Anda. Stok tersisa: ' . $penjual->stok)->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('complete_transaction')->label('Selesaikan')->icon('heroicon-o-check-badge')->color('info')
                    ->visible(function (Transaksi $record): bool {
                        $user = auth()->user();
                        $penjual = Penjual::where('user_id', $user->id)->first();
                        return $penjual && 
                               $penjual->status === 'accepted' &&
                               $record->status === 'Confirmed' && 
                               $record->penjual_id === $penjual->id;
                    })->requiresConfirmation()->modalHeading('Selesaikan Transaksi')->modalDescription('Apakah Anda yakin transaksi ini sudah selesai?')
                    ->action(function (Transaksi $record): void {
                        $penjual = static::getAuthenticatedPenjual();
                        if (!$penjual || $penjual->status !== 'accepted') {
                            Notification::make()->title('Error')->body('Profil penjual Anda belum disetujui untuk menyelesaikan transaksi.')->danger()->send();
                            return;
                        }
                        try {
                            $record->update([
                                'status' => 'Completed',
                                'tgl_kembali' => now()->toDateString()
                            ]);
                            Notification::make()->title('Transaksi Selesai')->body('Transaksi telah diselesaikan dengan tanggal kembali: ' . now()->format('d/m/Y'))->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\ViewAction::make()
                    ->visible(function (Transaksi $record): bool {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && $penjual->status === 'accepted';
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\Action::make('bulk_confirm')->label('Konfirmasi Multiple')->icon('heroicon-o-check-circle')->color('success')
                        ->visible(function (): bool {
                            $penjual = static::getAuthenticatedPenjual();
                            return $penjual && $penjual->status === 'accepted';
                        })
                        ->form([
                            Forms\Components\Textarea::make('verification_codes')
                                ->label('Kode Verifikasi (pisahkan dengan koma atau enter)')
                                ->required()
                                ->rows(5)
                                ->placeholder('Contoh: ABC123, DEF456, GHI789')
                                ->helperText('Masukkan semua kode verifikasi dari pembeli, pisahkan dengan koma atau enter')
                        ])
                        ->action(function (array $data): void {
                            $penjual = static::getAuthenticatedPenjual();
                            if (!$penjual || $penjual->status !== 'accepted') {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Profil penjual tidak valid.')
                                    ->danger()
                                    ->send();
                                return;
                            }
    
                            // Parse kode verifikasi
                            $codes = collect(preg_split('/[,\n\r]+/', $data['verification_codes']))
                                ->map(fn($code) => trim($code))
                                ->filter()
                                ->unique();
    
                            if ($codes->isEmpty()) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Tidak ada kode verifikasi yang valid.')
                                    ->danger()
                                    ->send();
                                return;
                            }
    
                            $successCount = 0;
                            $failedCodes = [];
                            $insufficientStock = false;
    
                            foreach ($codes as $code) {
                                // Cari transaksi berdasarkan kode
                                $transaksi = Transaksi::where('kode_transaksi', $code)
                                    ->where('status', 'Pending')
                                    ->first();
    
                                if (!$transaksi) {
                                    $failedCodes[] = "$code (tidak ditemukan atau sudah dikonfirmasi)";
                                    continue;
                                }
    
                                // Cek stok
                                if ($penjual->stok <= 0) {
                                    $insufficientStock = true;
                                    break;
                                }
    
                                try {
                                    // Update transaksi ke status Confirmed
                                    $transaksi->update([
                                        'penjual_id' => $penjual->id,
                                        'status' => 'Confirmed'
                                    ]);
                                    $successCount++;
                                    
                                    // Refresh penjual untuk update stok
                                    $penjual->refresh();
                                    
                                } catch (\Exception $e) {
                                    $failedCodes[] = "$code (error: {$e->getMessage()})";
                                }
                            }
    
                            // Notifikasi hasil
                            $message = "Berhasil memproses $successCount transaksi.";
                            
                            if (!empty($failedCodes)) {
                                $message .= " Gagal: " . implode(', ', $failedCodes);
                            }
                            
                            if ($insufficientStock) {
                                $message .= " Proses dihentikan karena stok tidak mencukupi.";
                            }
    
                            $message .= " Stok tersisa: {$penjual->stok}";
    
                            Notification::make()
                                ->title($successCount > 0 ? 'Konfirmasi Berhasil' : 'Konfirmasi Gagal')
                                ->body($message)
                                ->color($successCount > 0 ? 'success' : 'danger')
                                ->send();
                        })
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('stok_info')->label('Info Stok')->icon('heroicon-o-information-circle')->color('info')
                    ->action(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        if (!$penjual) {
                            return;
                        }
                        if ($penjual->status !== 'accepted') {
                            Notification::make()
                                ->title('Profil Belum Disetujui')
                                ->body('Profil penjual Anda belum disetujui oleh admin.')
                                ->warning()
                                ->send();
                            return;
                        }
                        $transaksiDitangani = Transaksi::where('penjual_id', $penjual->id)->count();
                        $transaksiSelesai = Transaksi::where('penjual_id', $penjual->id)->where('status', 'Completed')->count();
                        $sisaKuota = $penjual->getRemainingQuota();
                        Notification::make()
                            ->title('Informasi Stok & Transaksi')
                            ->body("Kategori: {$penjual->kategori} | Stok: {$penjual->stok} | Kuota: {$penjual->kuota} | Sisa kuota: {$sisaKuota} | Transaksi ditangani: {$transaksiDitangani} | Selesai: {$transaksiSelesai}")
                            ->info()
                            ->send();
                    })
                    ->visible(function () {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual !== null;
                    }),
                Tables\Actions\Action::make('bulk_confirm')
                    ->label('Konfirmasi Multiple')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function (): bool {
                        $penjual = static::getAuthenticatedPenjual();
                        return $penjual && $penjual->status === 'accepted';
                    })
                    ->form([
                        Forms\Components\Textarea::make('verification_codes')
                            ->label('Kode Verifikasi (pisahkan dengan koma atau enter)')
                            ->required()
                            ->rows(5)
                            ->placeholder('Contoh: ABC123, DEF456, GHI789')
                            ->helperText('Masukkan semua kode verifikasi dari pembeli, pisahkan dengan koma atau enter')
                    ])
                    ->action(function (array $data): void {
                        $penjual = static::getAuthenticatedPenjual();
                        if (!$penjual || $penjual->status !== 'accepted') {
                            Notification::make()
                                ->title('Error')
                                ->body('Profil penjual tidak valid.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Parse kode verifikasi
                        $codes = collect(preg_split('/[,\n\r]+/', $data['verification_codes']))
                            ->map(fn($code) => trim($code))
                            ->filter()
                            ->unique();

                        if ($codes->isEmpty()) {
                            Notification::make()
                                ->title('Error')
                                ->body('Tidak ada kode verifikasi yang valid.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $successCount = 0;
                        $failedCodes = [];
                        $insufficientStock = false;

                        foreach ($codes as $code) {
                            // Cari transaksi berdasarkan kode
                            $transaksi = Transaksi::where('kode_transaksi', $code)
                                ->where('status', 'Pending')
                                ->first();

                            if (!$transaksi) {
                                $failedCodes[] = "$code (tidak ditemukan atau sudah dikonfirmasi)";
                                continue;
                            }

                            // Cek stok
                            if ($penjual->stok <= 0) {
                                $insufficientStock = true;
                                $failedCodes[] = "$code (stok tidak mencukupi)";
                                break;
                            }

                            try {
                                // Update transaksi ke status Confirmed
                                $transaksi->update([
                                    'penjual_id' => $penjual->id,
                                    'status' => 'Confirmed'
                                ]);
                                $successCount++;
                                
                                // Refresh penjual untuk update stok
                                $penjual->refresh();
                                
                            } catch (\Exception $e) {
                                $failedCodes[] = "$code (error: {$e->getMessage()})";
                            }
                        }

                        // Notifikasi hasil
                        $message = "Berhasil memproses $successCount transaksi.";
                        
                        if (!empty($failedCodes)) {
                            $message .= " Gagal: " . implode(', ', array_slice($failedCodes, 0, 3));
                            if (count($failedCodes) > 3) {
                                $message .= " dan " . (count($failedCodes) - 3) . " lainnya";
                            }
                        }
                        
                        if ($insufficientStock) {
                            $message .= " Proses dihentikan karena stok tidak mencukupi.";
                        }

                        $message .= " Stok tersisa: {$penjual->stok}";

                        Notification::make()
                            ->title($successCount > 0 ? 'Konfirmasi Berhasil' : 'Konfirmasi Gagal')
                            ->body($message)
                            ->color($successCount > 0 ? 'success' : 'danger')
                            ->duration(8000) // Notifikasi lebih lama karena banyak info
                            ->send();
                    })
            ])->defaultSort('created_at', 'desc')
            ->emptyStateHeading(function () {
                $penjual = static::getAuthenticatedPenjual();
                if (!$penjual) {
                    return 'Profil Diperlukan';
                }
                
                return match($penjual->status) {
                    'pending' => 'Menunggu Persetujuan Admin',
                    'rejected' => 'Profil Ditolak',
                    default => 'Belum Ada Transaksi'
                };
            })
            ->emptyStateDescription(function () {
                $penjual = static::getAuthenticatedPenjual();
                if (!$penjual) {
                    return 'Silakan buat profil penjual terlebih dahulu untuk dapat menangani transaksi.';
                }
                
                return match($penjual->status) {
                    'pending' => 'Profil Anda masih menunggu persetujuan admin. Anda belum dapat menangani transaksi.',
                    'rejected' => 'Profil Anda ditolak. Hubungi admin untuk informasi lebih lanjut.',
                    default => 'Belum ada transaksi yang dapat Anda tangani.'
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
                    default => 'heroicon-o-clipboard-document-list'
                };
            });
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksis::route('/'),
            'view' => Pages\ViewTransaksi::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canView($record): bool
    {
        $penjual = static::getAuthenticatedPenjual();
        return $penjual && $penjual->status === 'accepted';
    }
    
    public static function canEdit($record): bool
    {
        $penjual = static::getAuthenticatedPenjual();
        return $penjual && $penjual->status === 'accepted';
    }
    
    public static function canDelete($record): bool
    {
        return false;
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
        $pendingCount = static::getModel()::where('status', 'Pending')->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
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
            return 'Profil diperlukan untuk menangani transaksi';
        }
        return match($penjual->status) {
            'pending' => 'Profil menunggu persetujuan - Tidak dapat menangani transaksi',
            'rejected' => 'Profil ditolak - Hubungi admin',
            'accepted' => "Transaksi pending: " . static::getModel()::where('status', 'Pending')->count() . " | Stok Anda: " . $penjual->stok,
            default => null
        };
    }
}