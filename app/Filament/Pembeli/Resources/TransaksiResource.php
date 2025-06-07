<?php

namespace App\Filament\Pembeli\Resources;

use App\Filament\Pembeli\Resources\TransaksiResource\Pages;
use App\Models\Transaksi;
use App\Models\Gas;
use App\Models\Pembeli;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Transaksi Saya';

    protected static function getAuthenticatedPembeli(): ?Pembeli
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }
        return Pembeli::where('user_id', $user->id)->first();
    }

    public static function getEloquentQuery(): Builder
    {
        $pembeli = static::getAuthenticatedPembeli();
        if (!$pembeli) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }
        return parent::getEloquentQuery()->where('pembeli_id', $pembeli->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('status_warning')->label('')
                    ->content(function () {
                        $pembeli = static::getAuthenticatedPembeli();
                        if (!$pembeli) {
                            return '❌ Profil pembeli tidak ditemukan. Silakan buat profil terlebih dahulu.';
                        }
                        
                        return match($pembeli->status) {
                            'pending' => '⏳ Status profil Anda masih menunggu persetujuan admin. Anda belum dapat melakukan transaksi.',
                            'rejected' => '❌ Profil Anda ditolak. Hubungi admin untuk informasi lebih lanjut.',
                            'accepted' => '✅ Profil Anda telah disetujui. Anda dapat melakukan transaksi.',
                            default => '❓ Status profil tidak diketahui.'
                        };
                    })->columnSpanFull()
                    ->visible(function () {
                        $pembeli = static::getAuthenticatedPembeli();
                        return !$pembeli || $pembeli->status !== 'accepted';
                    }),    
                Placeholder::make('kuota_info')->label('Informasi Kuota')
                    ->content(function () {
                        $pembeli = static::getAuthenticatedPembeli();
                        if (!$pembeli) {
                            return 'Profil pembeli tidak ditemukan';
                        }
                        $sisaKuota = $pembeli->kuota;
                        $transaksiAktif = Transaksi::where('pembeli_id', $pembeli->id)->whereIn('status', ['Pending', 'Confirmed'])->count();
                        return "Sisa kuota: {$sisaKuota} | Transaksi aktif: {$transaksiAktif}";
                    })->columnSpanFull()
                    ->visible(function () {
                        $pembeli = static::getAuthenticatedPembeli();
                        return $pembeli && $pembeli->status === 'accepted';
                    }),
                Hidden::make('pembeli_id')
                    ->default(function () {
                        $pembeli = static::getAuthenticatedPembeli();
                        return $pembeli ? $pembeli->id : null;
                    })->required(),
                TextInput::make('nama_pembeli')->label('Nama Pembeli')
                    ->formatStateUsing(function ($record) {
                        if ($record) {
                            return $record->nama_pembeli;
                        }
                        $user = auth()->user();
                        return $user ? $user->name : '';
                    })->disabled()->dehydrated(false)
                    ->visible(function () {
                        $pembeli = static::getAuthenticatedPembeli();
                        return $pembeli && $pembeli->status === 'accepted';
                    }),
                Select::make('gas_jenis')->label('Jenis Gas')->options(Gas::all()->pluck('jenis', 'id'))->required()->searchable()->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $gas = Gas::find($state);
                            if ($gas) {
                                $set('harga', $gas->harga);
                            }
                        }
                    })
                    ->visible(function () {
                        $pembeli = static::getAuthenticatedPembeli();
                        return $pembeli && $pembeli->status === 'accepted';
                    }),
                TextInput::make('qty')
                    ->label('Jumlah Transaksi')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(function () {
                        $pembeli = static::getAuthenticatedPembeli();
                        return $pembeli ? $pembeli->kuota : 1;
                    })
                    ->required()
                    ->helperText(function () {
                        $pembeli = static::getAuthenticatedPembeli();
                        if (!$pembeli) return '';
                        return "Maksimal {$pembeli->kuota} transaksi (sesuai sisa kuota Anda)";
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $harga = $get('harga');
                        if ($harga && $state) {
                            $set('total_harga', $harga * $state);
                        }
                    })
                    ->visible(function () {
                        $pembeli = static::getAuthenticatedPembeli();
                        return $pembeli && $pembeli->status === 'accepted';
                    }),
                TextInput::make('harga')->label('Harga per Unit')->numeric()->prefix('Rp')->disabled()->dehydrated(true)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $qty = $get('qty') ?: 1;
                        if ($state) {
                            $set('total_harga', $state * $qty);
                        }
                    })
                    ->visible(function () {
                        $pembeli = static::getAuthenticatedPembeli();
                        return $pembeli && $pembeli->status === 'accepted';
                    }),
                TextInput::make('total_harga')
                    ->label('Total Harga')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(function () {
                        $pembeli = static::getAuthenticatedPembeli();
                        return $pembeli && $pembeli->status === 'accepted';
                    }),
                DatePicker::make('tgl_beli')->label('Tanggal Beli')->default(now())->required()->disabled()
                    ->visible(function () {
                        $pembeli = static::getAuthenticatedPembeli();
                        return $pembeli && $pembeli->status === 'accepted';
                    }),
                Hidden::make('status')->default('Pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_transaksi')->label('Kode Transaksi')->searchable()->sortable()->copyable()->weight('bold')->color('primary'),
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
                TextColumn::make('penjual.user.name')->label('Penjual')->default('Belum ada penjual')->sortable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Pending' => 'Pending',
                        'Confirmed' => 'Confirmed',
                        'Completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(function (Transaksi $record): bool {
                        $pembeli = static::getAuthenticatedPembeli();
                        return $pembeli && $record->pembeli_id === $pembeli->id;
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(function (Transaksi $record): bool {
                        $pembeli = static::getAuthenticatedPembeli();
                        return $pembeli && 
                               $record->pembeli_id === $pembeli->id && 
                               $record->status === 'Pending';
                    }),
            ])
            ->bulkActions([
            ])->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('quota_info')->label('Info Kuota')->icon('heroicon-o-information-circle')->color('info')
                    ->action(function () {
                        $pembeli = static::getAuthenticatedPembeli();
                        if (!$pembeli) {
                            return;
                        }
                        $sisaKuota = $pembeli->kuota;
                        $transaksiAktif = Transaksi::where('pembeli_id', $pembeli->id)->whereIn('status', ['Pending', 'Confirmed'])->count();
                        $transaksiSelesai = Transaksi::where('pembeli_id', $pembeli->id)->where('status', 'Completed')->count();
                        Notification::make()->title('Informasi Kuota')->body("Sisa kuota: {$sisaKuota} | Transaksi aktif: {$transaksiAktif} | Transaksi selesai: {$transaksiSelesai}")->info()->send();
                    })
                    ->visible(function () {
                        $pembeli = static::getAuthenticatedPembeli();
                        return $pembeli && $pembeli->status === 'accepted';
                    })
            ])
            ->emptyStateHeading(function () {
                $pembeli = static::getAuthenticatedPembeli();
                if (!$pembeli) {
                    return 'Profil Diperlukan';
                }
                return match($pembeli->status) {
                    'pending' => 'Menunggu Persetujuan',
                    'rejected' => 'Profil Ditolak',
                    default => 'Belum Ada Transaksi'
                };
            })
            ->emptyStateDescription(function () {
                $pembeli = static::getAuthenticatedPembeli();
                if (!$pembeli) {
                    return 'Silakan buat profil pembeli terlebih dahulu untuk dapat melakukan transaksi.';
                }
                return match($pembeli->status) {
                    'pending' => 'Profil Anda masih menunggu persetujuan admin. Anda belum dapat melakukan transaksi.',
                    'rejected' => 'Profil Anda ditolak. Hubungi admin untuk informasi lebih lanjut.',
                    default => 'Anda belum memiliki transaksi apapun.'
                };
            })
            ->emptyStateIcon(function () {
                $pembeli = static::getAuthenticatedPembeli();
                if (!$pembeli) {
                    return 'heroicon-o-user-plus';
                }
                return match($pembeli->status) {
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
            'index' => Pages\ListTransaksis::route('/'),
            'create' => Pages\CreateTransaksi::route('/create'),
            'view' => Pages\ViewTransaksi::route('/{record}'),
            'edit' => Pages\EditTransaksi::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        $pembeli = static::getAuthenticatedPembeli();
        if (!$pembeli) {
            return false;
        }
        return $pembeli->status === 'accepted' && $pembeli->kuota > 0;
    }

    public static function canView($record): bool
    {
        $pembeli = static::getAuthenticatedPembeli();
        return $pembeli && $record->pembeli_id === $pembeli->id;
    }

    public static function canEdit($record): bool
    {
        $pembeli = static::getAuthenticatedPembeli();
        return $pembeli && 
               $pembeli->status === 'accepted' &&
               $record->pembeli_id === $pembeli->id && 
               $record->status === 'Pending';
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pembeli = static::getAuthenticatedPembeli();
        if (!$pembeli) {
            return '!';
        }
        if ($pembeli->status !== 'accepted') {
            return '⏸️';
        }
        $pendingCount = static::getModel()::where('pembeli_id', $pembeli->id)->where('status', 'Pending')->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pembeli = static::getAuthenticatedPembeli();
        if (!$pembeli) {
            return 'danger';
        }
        return match($pembeli->status) {
            'pending' => 'warning',
            'rejected' => 'danger',
            'accepted' => 'warning',
            default => 'gray'
        };
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $pembeli = static::getAuthenticatedPembeli();
        if (!$pembeli) {
            return 'Profil diperlukan untuk transaksi';
        }
        return match($pembeli->status) {
            'pending' => 'Profil menunggu persetujuan - Tidak dapat transaksi',
            'rejected' => 'Profil ditolak - Hubungi admin',
            'accepted' => "Transaksi pending: " . static::getModel()::where('pembeli_id', $pembeli->id)->where('status', 'Pending')->count(),
            default => null
        };
    }
}