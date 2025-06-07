<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenjualResource\Pages;
use App\Filament\Resources\PenjualResource\RelationManagers;
use App\Models\Penjual;
use App\Models\User;
use App\Models\KebijakanKuota;
use App\Services\WilayahApiService;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;

class PenjualResource extends Resource
{
    protected static ?string $model = Penjual::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi User')
                    ->schema([
                        Select::make('user_id')->label('Nama')->options(User::all()->pluck('name', 'id'))->required()->searchable()->preload(),
                    ]),
                Section::make('Informasi Pribadi')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nama_pemilik')->required()->maxLength(255)->label('Nama Pemilik'),
                                TextInput::make('nik')->required()->numeric()->minLength(16)->maxLength(16)->minValue(0)->label('NIK')->live(debounce: 1000)
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (empty($state) || strlen($state) < 6) {
                                            $set('provinsi_id', '');
                                            $set('kabupaten_id', '');
                                            $set('kecamatan_id', '');
                                            $set('nama_provinsi', '');
                                            $set('nama_kota_kabupaten', '');
                                            $set('nama_kecamatan', '');
                                        } else if (strlen($state) >= 6) {
                                            $wilayahData = WilayahApiService::getWilayahFromNik($state);
                                            $set('provinsi_id', $wilayahData['provinsi_id']);
                                            $set('kabupaten_id', $wilayahData['kabupaten_id']);
                                            $set('kecamatan_id', $wilayahData['kecamatan_id']);
                                            $set('nama_provinsi', $wilayahData['nama_provinsi']);
                                            $set('nama_kota_kabupaten', $wilayahData['nama_kabupaten']);
                                            $set('nama_kecamatan', $wilayahData['nama_kecamatan']);
                                        }
                                    }),
                            ]),
                    ]),
                Section::make('Alamat')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('nama_provinsi')->label('Provinsi')->options(fn () => WilayahApiService::getProvinsiOptions())->searchable()->preload()->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $set('nama_kota_kabupaten', '');
                                        $set('nama_kecamatan', '');
                                        $set('kabupaten_id', '');
                                        $set('kecamatan_id', '');
                                        $provinsiOptions = WilayahApiService::getProvinsiOptions();
                                        $provinsiId = array_search($state, $provinsiOptions);
                                        $set('provinsi_id', $provinsiId);
                                    }),
                                Forms\Components\Hidden::make('provinsi_id')->default(''),
                                Select::make('nama_kota_kabupaten')->label('Kota/Kabupaten')
                                    ->options(function (callable $get) {
                                        $provinsiId = $get('provinsi_id');
                                        if (!$provinsiId) {
                                            $namaProvinsi = $get('nama_provinsi');
                                            $provinsiOptions = WilayahApiService::getProvinsiOptions();
                                            $provinsiId = array_search($namaProvinsi, $provinsiOptions);
                                        }
                                        return WilayahApiService::getKabupatenOptions($provinsiId);
                                    })->searchable()->preload()->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $set('nama_kecamatan', '');
                                        $set('kecamatan_id', '');
                                        $provinsiId = $get('provinsi_id');
                                        $kabupatenOptions = WilayahApiService::getKabupatenOptions($provinsiId);
                                        $kabupatenId = array_search($state, $kabupatenOptions);
                                        $set('kabupaten_id', $kabupatenId);
                                    }),
                                Forms\Components\Hidden::make('kabupaten_id')->default(''),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Select::make('nama_kecamatan')->label('Kecamatan')
                                    ->options(function (callable $get) {
                                        $kabupatenId = $get('kabupaten_id');
                                        if (!$kabupatenId) {
                                            $namaKabupaten = $get('nama_kota_kabupaten');
                                            $provinsiId = $get('provinsi_id');
                                            $kabupatenOptions = WilayahApiService::getKabupatenOptions($provinsiId);
                                            $kabupatenId = array_search($namaKabupaten, $kabupatenOptions);
                                        }
                                        return WilayahApiService::getKecamatanOptions($kabupatenId);
                                    })->searchable()->preload()->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $kabupatenId = $get('kabupaten_id');
                                        $kecamatanOptions = WilayahApiService::getKecamatanOptions($kabupatenId);
                                        $kecamatanId = array_search($state, $kecamatanOptions);
                                        $set('kecamatan_id', $kecamatanId);
                                    }),  
                                Forms\Components\Hidden::make('kecamatan_id')->default(''),
                                TextInput::make('alamat')->required()->maxLength(255)->label('Alamat'),
                            ]),
                    ]),
                Section::make('Informasi Usaha')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('kategori')
                                    ->options([
                                        'Agen' => 'Agen',
                                        'Pangkalan' => 'Pangkalan',
                                        'Sub-Pangkalan' => 'Sub-Pangkalan',
                                    ])->required()->native(false)->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('stok', 0);
                                        $kuotaDariKebijakan = KebijakanKuota::getKuotaPenjual($state);
                                        if ($kuotaDariKebijakan !== null) {
                                            $set('kuota', $kuotaDariKebijakan);
                                        } else {
                                            switch ($state) {
                                                case 'Agen':
                                                    $set('kuota', 1000);
                                                    break;
                                                case 'Pangkalan':
                                                    $set('kuota', 800);
                                                    break;
                                                case 'Sub-Pangkalan':
                                                    $set('kuota', 125);
                                                    break;
                                                default:
                                                    $set('kuota', 0);
                                                    break;
                                            }
                                        }
                                    }),
                                TextInput::make('stok')->required()->numeric()->minValue(0)->label('Stok'),
                                TextInput::make('kuota')->required()->numeric()->minValue(0)->label('Kuota')->helperText('Kuota otomatis diatur berdasarkan kebijakan yang aktif'),
                                FileUpload::make('foto_ktp')->label('Foto KTP')->image()->directory('profil-penjual/ktp')->required()->maxSize(2048)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg']),
                                FileUpload::make('foto_selfie')->label('Foto Selfie dengan KTP')->image()->directory('profil-penjual/selfie')->required()->maxSize(2048)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg']),
                                FileUpload::make('foto_izin')->label('Foto Izin Usaha')->image()->directory('profil-penjual/izin')->required()->maxSize(2048)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg']),
                            ]),
                    ]),
                Section::make('Status Persetujuan')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Menunggu Persetujuan',
                                'accepted' => 'Disetujui',
                                'rejected' => 'Ditolak',
                            ])->required()->native(false)->default('pending')->label('Status'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Nama')->sortable()->searchable(),
                TextColumn::make('nama_pemilik')->label('Nama Pemilik')->searchable(),
                TextColumn::make('nik')->label('NIK')
                    ->formatStateUsing(fn (string $state): string => 
                        substr($state, 0, 4) . '****' . substr($state, -4)
                    ),
                TextColumn::make('nama_provinsi')->label('Provinsi'),
                TextColumn::make('nama_kota_kabupaten')->label('Kota/Kabupaten'),
                TextColumn::make('kategori')->label('Kategori')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Agen' => 'success',
                        'Pangkalan' => 'warning',
                        'Sub-Pangkalan' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('kuota')->label('Kuota')->badge()->color('primary')
                    ->description(function (Penjual $record): ?string {
                        $kuotaKebijakan = KebijakanKuota::getKuotaPenjual($record->kategori);
                        if ($kuotaKebijakan && $kuotaKebijakan !== $record->kuota) {
                            return "Kebijakan: {$kuotaKebijakan}";
                        }
                        return null;
                    }),
                TextColumn::make('stok')->label('Stok'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'accepted' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => 'Tidak Diketahui',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu Persetujuan',
                        'accepted' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->label('Status'),
                SelectFilter::make('kategori')
                    ->options([
                        'Agen' => 'Agen',
                        'Pangkalan' => 'Pangkalan',
                        'Sub-Pangkalan' => 'Sub-Pangkalan',
                    ])
                    ->label('Kategori'),
            ])
            ->actions([
                Action::make('sync_quota')->label('Sinkron Kuota')->icon('heroicon-o-arrow-path')->color('info')
                    ->action(function (Penjual $record) {
                        $kuotaKebijakan = KebijakanKuota::getKuotaPenjual($record->kategori);
                        if ($kuotaKebijakan) {
                            $record->update(['kuota' => $kuotaKebijakan]);
                            Notification::make()->title('Kuota Berhasil Disinkronkan')->body("Kuota {$record->kategori} diperbarui menjadi {$kuotaKebijakan}")->success()->send();
                        } else {
                            Notification::make()->title('Tidak Ada Kebijakan Aktif')->body("Tidak ditemukan kebijakan aktif untuk kategori {$record->kategori}")->warning()->send();
                        }
                    })
                    ->visible(function (Penjual $record): bool {
                        $kuotaKebijakan = KebijakanKuota::getKuotaPenjual($record->kategori);
                        return $kuotaKebijakan && $kuotaKebijakan !== $record->kuota;
                    })->requiresConfirmation()->modalHeading('Sinkronkan Kuota dengan Kebijakan')
                    ->modalDescription(function (Penjual $record): string {
                        $kuotaKebijakan = KebijakanKuota::getKuotaPenjual($record->kategori);
                        return "Kuota akan diubah dari {$record->kuota} menjadi {$kuotaKebijakan} sesuai kebijakan aktif.";
                    }),
                Action::make('approve')->label('Setujui')->icon('heroicon-o-check-circle')->color('success')->visible(fn (Penjual $record): bool => $record->status === 'pending')
                    ->action(function (Penjual $record) {
                        $record->update(['status' => 'accepted']);
                        Notification::make()
                            ->title('Profil Disetujui')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Action::make('reject')->label('Tolak')->icon('heroicon-o-x-circle')->color('danger')->visible(fn (Penjual $record): bool => $record->status !== 'rejected')
                    ->action(function (Penjual $record) {
                        $record->update(['status' => 'rejected']);
                        Notification::make()
                            ->title('Profil Ditolak')
                            ->success()
                            ->send();
                    })->requiresConfirmation(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('sync_all_quota')->label('Sinkron Semua Kuota')->icon('heroicon-o-arrow-path')->color('info')
                        ->action(function ($records) {
                            $updated = 0;
                            foreach ($records as $record) {
                                $kuotaKebijakan = KebijakanKuota::getKuotaPenjual($record->kategori);
                                if ($kuotaKebijakan && $kuotaKebijakan !== $record->kuota) {
                                    $record->update(['kuota' => $kuotaKebijakan]);
                                    $updated++;
                                }
                            }
                            if ($updated > 0) {
                                Notification::make()->title('Kuota Berhasil Disinkronkan')->body("{$updated} penjual berhasil diperbarui kuotanya")->success()->send();
                            } else {
                                Notification::make()->title('Tidak Ada Perubahan')->body('Semua kuota sudah sesuai dengan kebijakan')->info()->send();
                            }
                        })->requiresConfirmation()->modalHeading('Sinkronkan Semua Kuota')->modalDescription('Kuota semua penjual terpilih akan disesuaikan dengan kebijakan aktif yang berlaku.'),
                ])
            ])->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenjuals::route('/'),
            'create' => Pages\CreatePenjual::route('/create'),
            'view' => Pages\ViewPenjual::route('/{record}'),
            'edit' => Pages\EditPenjual::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() > 0 ? 'warning' : null;
    }
}