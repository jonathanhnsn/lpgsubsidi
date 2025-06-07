<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PembeliResource\Pages;
use App\Filament\Resources\PembeliResource\RelationManagers;
use App\Models\Pembeli;
use App\Models\User;
use App\Models\KebijakanKuota;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;

class PembeliResource extends Resource
{
    protected static ?string $model = Pembeli::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';
    
    protected static function getProvinsiOptions(): array
    {
        return Cache::remember('api_provinces', 3600, function () {
            try {
                $response = Http::timeout(5)->get('https://emsifa.github.io/api-wilayah-indonesia/api/provinces.json');
                if ($response->successful()) {
                    $provinces = $response->json();
                    return collect($provinces)->pluck('name', 'id')->toArray();
                }
            } catch (\Exception $e) {
                \Log::error('Error fetching provinces: ' . $e->getMessage());
            }
            return [];
        });
    }

    protected static function getKabupatenOptions($provinceId): array
    {
        if (empty($provinceId)) {
            return [];
        }
        return Cache::remember("api_regencies_{$provinceId}", 3600, function () use ($provinceId) {
            try {
                $response = Http::timeout(5)->get("https://emsifa.github.io/api-wilayah-indonesia/api/regencies/{$provinceId}.json");  
                if ($response->successful()) {
                    $regencies = $response->json();
                    return collect($regencies)->pluck('name', 'id')->toArray();
                }
            } catch (\Exception $e) {
                \Log::error("Error fetching regencies for province {$provinceId}: " . $e->getMessage());
            } 
            return [];
        });
    }

    protected static function getKecamatanOptions($regencyId): array
    {
        if (empty($regencyId)) {
            return [];
        }
        return Cache::remember("api_districts_{$regencyId}", 3600, function () use ($regencyId) {
            try {
                $response = Http::timeout(5)->get("https://emsifa.github.io/api-wilayah-indonesia/api/districts/{$regencyId}.json");    
                if ($response->successful()) {
                    $districts = $response->json();
                    return collect($districts)->pluck('name', 'id')->toArray();
                }
            } catch (\Exception $e) {
                \Log::error("Error fetching districts for regency {$regencyId}: " . $e->getMessage());
            }
            return [];
        });
    }

    protected static function getWilayahFromNik($nik): array
    {
        if (empty($nik) || strlen($nik) < 6) {
            return [
                'provinsi_id' => '',
                'kabupaten_id' => '',
                'kecamatan_id' => '',
                'nama_provinsi' => '',
                'nama_kabupaten' => '',
                'nama_kecamatan' => ''
            ];
        }
        $nikPrefix = substr($nik, 0, 6);
        return Cache::remember("wilayah_nik_{$nikPrefix}", 1800, function () use ($nik, $nikPrefix) {
            $kodeProvinsi = substr($nik, 0, 2);
            $kodeKabupaten = substr($nik, 0, 4);
            $kodeKecamatan = substr($nik, 0, 6);
            try {
                $responses = Http::pool(fn ($pool) => [
                    $pool->timeout(5)->get('https://emsifa.github.io/api-wilayah-indonesia/api/provinces.json'),
                ]);
                $provinsiId = '';
                $provinsiName = '';
                if ($responses[0]->successful()) {
                    $provinces = collect($responses[0]->json());
                    $provinsi = $provinces->firstWhere('id', $kodeProvinsi);
                    if ($provinsi) {
                        $provinsiId = $provinsi['id'];
                        $provinsiName = $provinsi['name'];
                    }
                }
                if (!$provinsiId) {
                    return [
                        'provinsi_id' => '',
                        'kabupaten_id' => '',
                        'kecamatan_id' => '',
                        'nama_provinsi' => '',
                        'nama_kabupaten' => '',
                        'nama_kecamatan' => ''
                    ];
                }
                $kabupatenResponse = Http::timeout(5)->get("https://emsifa.github.io/api-wilayah-indonesia/api/regencies/{$provinsiId}.json");
                $kabupatenId = '';
                $kabupatenName = '';
                if ($kabupatenResponse->successful()) {
                    $regencies = collect($kabupatenResponse->json());
                    $kabupaten = $regencies->firstWhere('id', $kodeKabupaten);
                    if ($kabupaten) {
                        $kabupatenId = $kabupaten['id'];
                        $kabupatenName = $kabupaten['name'];
                    }
                }
                if (!$kabupatenId) {
                    return [
                        'provinsi_id' => $provinsiId,
                        'kabupaten_id' => '',
                        'kecamatan_id' => '',
                        'nama_provinsi' => $provinsiName,
                        'nama_kabupaten' => '',
                        'nama_kecamatan' => ''
                    ];
                }
                $kecamatanResponse = Http::timeout(5)->get("https://emsifa.github.io/api-wilayah-indonesia/api/districts/{$kabupatenId}.json");
                $kecamatanId = '';
                $kecamatanName = '';
                if ($kecamatanResponse->successful()) {
                    $districts = collect($kecamatanResponse->json());
                    $kecamatan = $districts->first(function ($district) use ($kodeKecamatan) {
                        return str_starts_with($district['id'], $kodeKecamatan);
                    });
                    if (!$kecamatan) {
                        $kecamatan = $districts->first(function ($district) use ($kodeKecamatan) {
                            return str_contains($district['id'], $kodeKecamatan);
                        });
                    }
                    if (!$kecamatan && $districts->isNotEmpty()) {
                        $kecamatan = $districts->first();
                    }
                    if ($kecamatan) {
                        $kecamatanId = $kecamatan['id'];
                        $kecamatanName = $kecamatan['name'];
                    }
                }
                return [
                    'provinsi_id' => $provinsiId,
                    'kabupaten_id' => $kabupatenId,
                    'kecamatan_id' => $kecamatanId,
                    'nama_provinsi' => $provinsiName,
                    'nama_kabupaten' => $kabupatenName,
                    'nama_kecamatan' => $kecamatanName
                ];
            } catch (\Exception $e) {
                \Log::error('Error fetching wilayah data from NIK: ' . $e->getMessage(), [
                    'nik' => $nik,
                    'kode_provinsi' => $kodeProvinsi,
                    'kode_kabupaten' => $kodeKabupaten,
                    'kode_kecamatan' => $kodeKecamatan
                ]);
                return [
                    'provinsi_id' => '',
                    'kabupaten_id' => '',
                    'kecamatan_id' => '',
                    'nama_provinsi' => '',
                    'nama_kabupaten' => '',
                    'nama_kecamatan' => ''
                ];
            }
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')->label('Nama')->options(User::all()->pluck('name', 'id'))->required()->searchable()->preload(),
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
                            $wilayahData = static::getWilayahFromNik($state);
                            $set('provinsi_id', $wilayahData['provinsi_id']);
                            $set('kabupaten_id', $wilayahData['kabupaten_id']);
                            $set('kecamatan_id', $wilayahData['kecamatan_id']);
                            $set('nama_provinsi', $wilayahData['nama_provinsi']);
                            $set('nama_kota_kabupaten', $wilayahData['nama_kabupaten']);
                            $set('nama_kecamatan', $wilayahData['nama_kecamatan']);
                        }
                    }),
                Select::make('nama_provinsi')->label('Provinsi')->options(fn () => static::getProvinsiOptions())->searchable()->preload()->live()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    $set('nama_kota_kabupaten', '');
                    $set('nama_kecamatan', '');
                    $set('kabupaten_id', '');
                    $set('kecamatan_id', '');
                    $provinsiOptions = static::getProvinsiOptions();
                    $provinsiId = array_search($state, $provinsiOptions);
                    $set('provinsi_id', $provinsiId);
                }),
                Forms\Components\Hidden::make('provinsi_id'),
                Select::make('nama_kota_kabupaten')->label('Kota/Kabupaten')
                    ->options(function (callable $get) {
                        $provinsiId = $get('provinsi_id');
                        if (!$provinsiId) {
                            $namaProvinsi = $get('nama_provinsi');
                            $provinsiOptions = static::getProvinsiOptions();
                            $provinsiId = array_search($namaProvinsi, $provinsiOptions);
                        }
                        return static::getKabupatenOptions($provinsiId);
                    })->searchable()->preload()->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $set('nama_kecamatan', '');
                        $set('kecamatan_id', '');
                        $provinsiId = $get('provinsi_id');
                        $kabupatenOptions = static::getKabupatenOptions($provinsiId);
                        $kabupatenId = array_search($state, $kabupatenOptions);
                        $set('kabupaten_id', $kabupatenId);
                    }),
                Forms\Components\Hidden::make('kabupaten_id'),
                Select::make('nama_kecamatan')->label('Kecamatan')
                    ->options(function (callable $get) {
                        $kabupatenId = $get('kabupaten_id');
                        if (!$kabupatenId) {
                            $namaKabupaten = $get('nama_kota_kabupaten');
                            $provinsiId = $get('provinsi_id');
                            $kabupatenOptions = static::getKabupatenOptions($provinsiId);
                            $kabupatenId = array_search($namaKabupaten, $kabupatenOptions);
                        }
                        return static::getKecamatanOptions($kabupatenId);
                    })->searchable()->preload()->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $kabupatenId = $get('kabupaten_id');
                        $kecamatanOptions = static::getKecamatanOptions($kabupatenId);
                        $kecamatanId = array_search($state, $kecamatanOptions);
                        $set('kecamatan_id', $kecamatanId);
                    }),  
                Forms\Components\Hidden::make('kecamatan_id'),
                TextInput::make('alamat')->required()->maxLength(255)->label('Alamat'),
                Select::make('pekerjaan')
                    ->options([
                        'Ibu Rumah Tangga' => 'Ibu Rumah Tangga',
                        'UMKM' => 'UMKM',
                        'Swasta' => 'Swasta',
                        'Negeri' => 'Negeri',
                    ])
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('gaji', '');
                        $set('kuota', 0);

                        $kuotaDariKebijakan = KebijakanKuota::getKuotaPembeli($state);

                        if ($kuotaDariKebijakan !== null) {
                            $set('kuota', $kuotaDariKebijakan);
                        } else {
                            switch ($state) {
                                case 'Ibu Rumah Tangga':
                                    $set('kuota', 3);
                                    break;
                                case 'UMKM':
                                    $set('kuota', 10);
                                    break;
                                case 'Swasta':
                                    $set('kuota', 5);
                                    break;
                                case 'Negeri':
                                    $set('kuota', 5);
                                    break;
                                default:
                                    $set('kuota', 0);
                                    break;
                            }
                        }
                    })
                    ->label('Pekerjaan'),

                Select::make('gaji')
                    ->options([
                        '< 3 juta' => '< 3 juta',
                        '> 3 juta' => '> 3 juta',
                    ])
                    ->required()
                    ->native(false)
                    ->label(function (callable $get) {
                        $pekerjaan = $get('pekerjaan');
                        if ($pekerjaan === 'UMKM') {
                            return 'Kisaran Pendapatan per Hari';
                        } else {
                            return 'Kisaran Pendapatan per Bulan';
                        }
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $pekerjaan = $get('pekerjaan');

                        if ($pekerjaan && $state) {
                            $kuota = KebijakanKuota::getKuotaPembeli($pekerjaan, $state);

                            if ($kuota !== null) {
                                $set('kuota', $kuota);
                            } else {
                                switch ($pekerjaan) {
                                    case 'Ibu Rumah Tangga':
                                        $set('kuota', 3);
                                        break;
                                    case 'UMKM':
                                        $set('kuota', 10);
                                        break;
                                    case 'Swasta':
                                        $set('kuota', 5);
                                        break;
                                    case 'Negeri':
                                        $set('kuota', 5);
                                        break;
                                    default:
                                        $set('kuota', 0);
                                        break;
                                }
                            }
                        } else {
                            $set('kuota', 0);
                        }
                    }),
                TextInput::make('kuota')->required()->numeric()->minValue(0)->default(0)->label('Kuota Per Bulan')->readOnly(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending - Menunggu Persetujuan',
                        'accepted' => 'Accepted - Disetujui',
                        'rejected' => 'Rejected - Ditolak',
                    ])->required()->default('pending')->label('Status Persetujuan'),
                FileUpload::make('foto_ktp')->label('Foto KTP')->image()->directory('profil-pembeli/ktp')->required()->maxSize(2048)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg']),
                FileUpload::make('foto_selfie')->label('Foto Selfie dengan KTP')->image()->directory('profil-pembeli/selfie')->required()->maxSize(2048)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg']),
                FileUpload::make('foto_kk')->label('Foto Kartu Keluarga (KK)')->image()->directory('profil-pembeli/kk')->required()->maxSize(2048)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg']),
                FileUpload::make('foto_usaha')->label('Foto Usaha')->image()->directory('profil-pembeli/usaha')->maxSize(2048)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])->required(fn (callable $get) => $get('pekerjaan') === 'UMKM')->visible(fn (callable $get) => $get('pekerjaan') === 'UMKM'),
                FileUpload::make('foto_izin')->label('Foto Izin Usaha')->image()->directory('profil-pembeli/izin')->maxSize(2048)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])->required(fn (callable $get) => $get('pekerjaan') === 'UMKM')->visible(fn (callable $get) => $get('pekerjaan') === 'UMKM'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Nama')->sortable()->searchable(),
                TextColumn::make('nik')->label('NIK')
                    ->formatStateUsing(fn (string $state): string => 
                        substr($state, 0, 4) . '****' . substr($state, -4)
                    ),
                TextColumn::make('nama_provinsi')->label('Provinsi'),
                TextColumn::make('nama_kota_kabupaten')->label('Kota/Kabupaten'),
                TextColumn::make('pekerjaan')->label('Pekerjaan')->badge()->color('info'),
                TextColumn::make('kuota')->label('Kuota')->sortable()->suffix(' tabung/bulan')->badge()->color('primary')
                    ->description(function (Pembeli $record): ?string {
                        $kuotaKebijakan = KebijakanKuota::getKuotaPembeli($record->pekerjaan, $record->gaji);
                        if ($kuotaKebijakan && $kuotaKebijakan !== $record->kuota) {
                            return "Kebijakan: {$kuotaKebijakan}";
                        }
                        return null;
                    }),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'Menunggu Persetujuan',
                        'accepted' => 'Diterima',
                        'rejected' => 'Ditolak',
                        default => 'Tidak Diketahui'
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('pekerjaan')
                    ->options([
                        'Ibu Rumah Tangga' => 'Ibu Rumah Tangga',
                        'UMKM' => 'UMKM',
                        'Swasta' => 'Swasta',
                        'Negeri' => 'Negeri',
                    ]),
            ])
            ->actions([
                Action::make('sync_quota')->label('Sinkron Kuota')->icon('heroicon-o-arrow-path')->color('info')
                    ->action(function (Pembeli $record) {
                        $kuotaKebijakan = KebijakanKuota::getKuotaPembeli($record->pekerjaan, $record->gaji);
                        if ($kuotaKebijakan) {
                            $record->update(['kuota' => $kuotaKebijakan]);
                            Notification::make()
                                ->title('Kuota Berhasil Disinkronkan')
                                ->body("Kuota {$record->pekerjaan} diperbarui menjadi {$kuotaKebijakan}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Tidak Ada Kebijakan Aktif')
                                ->body("Tidak ditemukan kebijakan aktif untuk {$record->pekerjaan} dengan gaji {$record->gaji}")
                                ->warning()
                                ->send();
                        }
                    })
                    ->visible(function (Pembeli $record): bool {
                        $kuotaKebijakan = KebijakanKuota::getKuotaPembeli($record->pekerjaan, $record->gaji);
                        return $kuotaKebijakan && $kuotaKebijakan !== $record->kuota;
                    })->requiresConfirmation()->modalHeading('Sinkronkan Kuota dengan Kebijakan')
                    ->modalDescription(function (Pembeli $record): string {
                        $kuotaKebijakan = KebijakanKuota::getKuotaPembeli($record->pekerjaan, $record->gaji);
                        return "Kuota akan diubah dari {$record->kuota} menjadi {$kuotaKebijakan} sesuai kebijakan aktif.";
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')->label('Setujui')->icon('heroicon-o-check-circle')->color('success')->visible(fn (Pembeli $record): bool => $record->status === 'pending')->requiresConfirmation()->modalHeading('Setujui Pembeli')->modalDescription('Apakah Anda yakin ingin menyetujui pembeli ini? Pembeli akan dapat melakukan transaksi.')
                    ->action(function (Pembeli $record) {
                        $record->update(['status' => 'accepted']);
                        Notification::make()
                            ->title('Pembeli Disetujui')
                            ->body("Pembeli {$record->user->name} telah disetujui dan dapat melakukan transaksi.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Pembeli $record): bool => $record->status !== 'rejected')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Pembeli')
                    ->modalDescription('Apakah Anda yakin ingin menolak pembeli ini? Pembeli tidak akan dapat melakukan transaksi.')
                    ->action(function (Pembeli $record) {
                        $record->update(['status' => 'rejected']);
                        Notification::make()
                            ->title('Pembeli Ditolak')
                            ->body("Pembeli {$record->user->name} telah ditolak dan tidak dapat melakukan transaksi.")
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('sync_all_quota')
                    ->label('Sinkron Semua Kuota')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function ($records) {
                        $updated = 0;
                        foreach ($records as $record) {
                            $kuotaKebijakan = KebijakanKuota::getKuotaPembeli($record->pekerjaan, $record->gaji);
                            if ($kuotaKebijakan && $kuotaKebijakan !== $record->kuota) {
                                $record->update(['kuota' => $kuotaKebijakan]);
                                $updated++;
                            }
                        }
                        if ($updated > 0) {
                            Notification::make()
                                ->title('Kuota Berhasil Disinkronkan')
                                ->body("{$updated} pembeli berhasil diperbarui kuotanya")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Tidak Ada Perubahan')
                                ->body('Semua kuota sudah sesuai dengan kebijakan')
                                ->info()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Sinkronkan Semua Kuota')
                    ->modalDescription('Kuota semua pembeli terpilih akan disesuaikan dengan kebijakan aktif yang berlaku.'),

            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPembelis::route('/'),
            'create' => Pages\CreatePembeli::route('/create'),
            'edit' => Pages\EditPembeli::route('/{record}/edit'),
            'view' => Pages\ViewPembeli::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();
        return $pendingCount > 0 ? "{$pendingCount} pembeli menunggu persetujuan" : null;
    }
}