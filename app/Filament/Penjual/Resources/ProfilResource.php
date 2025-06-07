<?php

namespace App\Filament\Penjual\Resources;

use App\Filament\Penjual\Resources\ProfilResource\Pages;
use App\Filament\Penjual\Resources\ProfilResource\RelationManagers;
use App\Models\Penjual;
use App\Services\WilayahApiService;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ProfilResource extends Resource
{
    protected static ?string $model = Penjual::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Profil Saya';
    protected static ?string $modelLabel = 'Profil';
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
        $user = auth()->user();
        if (!$user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }
        return parent::getEloquentQuery()->where('user_id', $user->id);
    }

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Hidden::make('user_id')
                ->default(function () {
                    return auth()->id();
                })->required(),
            Section::make('Informasi Status')
                ->description('Status persetujuan profil Anda')
                ->schema([
                    Placeholder::make('status_info')
                        ->label('')
                        ->content(function ($record) {
                            if (!$record) {
                                return 'Profil belum dibuat. Setelah membuat profil, admin akan meninjau dan menyetujui profil Anda.';
                            }
                            
                            return match($record->status) {
                                'pending' => '⏳ Status: Menunggu Persetujuan Admin. Anda belum dapat melakukan transaksi.',
                                'accepted' => '✅ Status: Disetujui. Anda dapat melakukan transaksi dengan kuota ' . $record->kuota . ' tabung.',
                                'rejected' => '❌ Status: Ditolak. Hubungi admin untuk informasi lebih lanjut.',
                                default => 'Status tidak diketahui.'
                            };
                        })
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => $record !== null),

            Section::make('Informasi Pribadi')
                ->description('Lengkapi data pribadi Anda')
                ->schema([
                    Grid::make(1)
                        ->schema([
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
                    Grid::make(2)
                        ->schema([
                            TextInput::make('user.name')
                                ->label('Nama Lengkap')
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(function ($record) {
                                    return $record ? $record->user->name : auth()->user()->name;
                                }),
                            TextInput::make('nama_pemilik')
                                ->required()
                                ->maxLength(255)
                                ->label('Nama Pemilik Usaha'),
                        ]),
                ]),

            Section::make('Alamat')
                ->description('Informasi alamat lengkap usaha')
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
                            TextInput::make('alamat')
                                ->label('Alamat Lengkap')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Jalan, RT/RW, Kelurahan, dll')
                                ->helperText('Masukkan alamat lengkap termasuk jalan, RT/RW, kelurahan'),
                        ]),
                ]),

            Section::make('Informasi Usaha')
                ->description('Data kategori dan kuota usaha')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('kategori')
                                ->options([
                                    'Agen' => 'Agen',
                                    'Pangkalan' => 'Pangkalan',
                                    'Sub-Pangkalan' => 'Sub-Pangkalan',
                                ])
                                ->required()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    switch ($state) {
                                        case 'Agen':
                                            $set('stok', 0);
                                            $set('kuota', 1000);
                                            break;
                                        case 'Pangkalan':
                                            $set('stok', 0);
                                            $set('kuota', 800);
                                            break;
                                        case 'Sub-Pangkalan':
                                            $set('stok', 0);
                                            $set('kuota', 125);
                                            break;
                                        default:
                                            $set('stok', 0);
                                            $set('kuota', 0);
                                            break;
                                    }
                                })
                                ->helperText('Pilih kategori usaha Anda'),
                            Hidden::make('kuota')
                                ->label('Kuota (Tabung)')
                                ->disabled()
                                ->dehydrated(true),
                        ]),
                    Grid::make(1)
                        ->schema([
                            Hidden::make('stok')->default(0),
                            Hidden::make('status')->default('pending'),
                        ]),
                ]),

            Section::make('Upload Dokumen')
                ->description('Upload dokumen yang diperlukan untuk verifikasi profil penjual')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            FileUpload::make('foto_ktp')
                                ->label('Foto KTP')
                                ->image()
                                ->directory('profil-penjual/ktp')
                                ->required()
                                ->maxSize(2048)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                                ->helperText('Format: JPG, PNG. Maksimal 2MB'),
                            FileUpload::make('foto_selfie')
                                ->label('Foto Selfie dengan KTP')
                                ->image()
                                ->directory('profil-penjual/selfie')
                                ->required()
                                ->maxSize(2048)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                                ->helperText('Foto diri sambil memegang KTP. Format: JPG, PNG. Maksimal 2MB'),
                        ]),
                    Grid::make(1)
                        ->schema([
                            FileUpload::make('foto_izin')
                                ->label('Foto Izin Usaha')
                                ->image()
                                ->directory('profil-penjual/izin')
                                ->required()
                                ->maxSize(2048)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                                ->helperText('Foto surat izin usaha/SIUP/TDP/NIB. Format: JPG, PNG. Maksimal 2MB'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama_pemilik')
                    ->label('Nama Pemilik')
                    ->searchable(),
                TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => 
                        substr($state, 0, 4) . '****' . substr($state, -4)
                    ),
                TextColumn::make('nama_provinsi')
                    ->label('Provinsi')
                    ->sortable(),
                TextColumn::make('nama_kota_kabupaten')
                    ->label('Kota/Kabupaten')
                    ->sortable(),
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Agen' => 'success',
                        'Pangkalan' => 'warning',
                        'Sub-Pangkalan' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('kuota')
                    ->label('Kuota')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu Persetujuan',
                        'accepted' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => 'Tidak Diketahui',
                    }),
            ])
            ->filters([
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
            ])
            ->emptyStateHeading('Profil Belum Dibuat')
            ->emptyStateDescription('Anda belum memiliki profil penjual. Silakan buat profil untuk dapat melakukan transaksi.')
            ->emptyStateIcon('heroicon-o-user-plus');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProfils::route('/'),
            'create' => Pages\CreateProfil::route('/create'),
            'view' => Pages\ViewProfil::route('/{record}'),
            'edit' => Pages\EditProfil::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return !Penjual::where('user_id', $user->id)->exists();
    }

    public static function canEdit($record): bool
    {
        return $record->user_id === auth()->id() && in_array($record->status, ['pending', 'accepted']);
    }

    public static function canView($record): bool
    {
        return $record->user_id === auth()->id();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canCreateTransaction(): bool
    {
        $penjual = static::getAuthenticatedPenjual();
        return $penjual && $penjual->status === 'accepted';
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $penjual = Penjual::where('user_id', $user->id)->first();
        if (!$penjual) {
            return '!';
        }
        if ($penjual->status === 'pending') {
            return '⏳';
        }
        if ($penjual->status === 'rejected') {
            return '❌';
        }
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $user = auth()->user();
        $penjual = Penjual::where('user_id', $user->id)->first();
        if (!$penjual) {
            return 'danger';
        }
        return match($penjual->status) {
            'pending' => 'warning',
            'rejected' => 'danger',
            default => null
        };
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $user = auth()->user();
        $penjual = Penjual::where('user_id', $user->id)->first();
        if (!$penjual) {
            return 'Profil belum dibuat - Diperlukan untuk transaksi';
        }
        return match($penjual->status) {
            'pending' => 'Profil menunggu persetujuan admin - Belum bisa transaksi',
            'rejected' => 'Profil ditolak - Hubungi admin untuk informasi',
            'accepted' => "Profil disetujui - Kuota: {$penjual->kuota} tabung",
            default => null
        };
    }
}