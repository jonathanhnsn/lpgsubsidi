<?php

namespace App\Filament\Kurir\Resources;

use App\Filament\Kurir\Resources\ProfilResource\Pages;
use App\Filament\Kurir\Resources\ProfilResource\RelationManagers;
use App\Models\Kurir;
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
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ProfilResource extends Resource
{
    protected static ?string $model = Kurir::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Profil Saya';
    protected static ?string $modelLabel = 'Profil';
    protected static function getAuthenticatedKurir(): ?Kurir
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }
        return Kurir::where('user_id', $user->id)->first();
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
                                    'pending' => '⏳ Status: Menunggu Persetujuan Admin. Anda belum dapat melakukan pengiriman.',
                                    'tersedia' => '✅ Status: Disetujui dan Tersedia. Anda dapat menerima tugas pengiriman.',
                                    'sedang_bertugas' => '🚚 Status: Sedang Bertugas. Anda sedang menjalankan pengiriman.',
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
                        Grid::make(2)
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
                                TextInput::make('user.name')->label('Nama Lengkap')->disabled()->dehydrated(false)
                                    ->formatStateUsing(function ($record) {
                                        return $record ? $record->user->name : auth()->user()->name;
                                    }),
                            ]),
                        Grid::make(1)
                            ->schema([
                                TextInput::make('no_telp')->label('Nomor Telepon')->required()->tel()->helperText('Nomor telepon aktif yang bisa dihubungi'),
                            ]),
                    ]),
                    
                Section::make('Alamat')
                    ->description('Informasi alamat lengkap')
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
                                TextInput::make('alamat')->label('Alamat Lengkap')->required()->columnSpanFull()->placeholder('Jalan, RT/RW, Kelurahan, dll')->helperText('Masukkan alamat lengkap termasuk jalan, RT/RW, kelurahan'),
                            ]),
                    ]),
                Section::make('Upload Dokumen')->description('Upload dokumen yang diperlukan untuk menjadi kurir')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('foto_ktp')->label('Foto KTP')->image()->directory('profil-kurir/ktp')->required()->maxSize(2048)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])->helperText('Format: JPG, PNG. Maksimal 2MB'),
                                FileUpload::make('foto_sim')->label('Foto SIM')->image()->directory('profil-kurir/sim')->required()->maxSize(2048)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])->helperText('SIM A atau C yang masih berlaku. Format: JPG, PNG. Maksimal 2MB'),
                            ]),
                    ]),
                Forms\Components\Hidden::make('status')->default('pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Nama Lengkap')->searchable()->sortable(),
                TextColumn::make('nik')->label('NIK')->searchable()
                    ->formatStateUsing(fn (string $state): string => 
                        substr($state, 0, 4) . '****' . substr($state, -4)
                    ),
                TextColumn::make('no_telp')->label('No. Telepon')->searchable()
                    ->formatStateUsing(fn (string $state): string => 
                        substr($state, 0, 4) . '****' . substr($state, -3)
                    ),
                TextColumn::make('nama_provinsi')->label('Provinsi')->sortable(),
                TextColumn::make('nama_kota_kabupaten')->label('Kota/Kabupaten')->sortable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'tersedia' => 'success',
                        'sedang_bertugas' => 'info',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'tersedia' => 'Tersedia',
                        'sedang_bertugas' => 'Bertugas',
                        'rejected' => 'Ditolak',
                        default => $state,
                    }),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
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
            ->emptyStateDescription('Anda belum memiliki profil kurir. Silakan buat profil untuk dapat menerima tugas pengiriman.')
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
        return !Kurir::where('user_id', $user->id)->exists();
    }

    public static function canEdit($record): bool
    {
        return $record->user_id === auth()->id() && in_array($record->status, ['pending', 'tersedia']);
    }

    public static function canView($record): bool
    {
        return $record->user_id === auth()->id();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canAcceptDelivery(): bool
    {
        $kurir = static::getAuthenticatedKurir();
        return $kurir && $kurir->isTersedia();
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $kurir = Kurir::where('user_id', $user->id)->first();
        if (!$kurir) {
            return '!';
        }
        if ($kurir->status === 'pending') {
            return '⏳';
        }
        if ($kurir->status === 'rejected') {
            return '❌';
        }
        if ($kurir->status === 'sedang_bertugas') {
            return '🚚';
        }
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $user = auth()->user();
        $kurir = Kurir::where('user_id', $user->id)->first();
        if (!$kurir) {
            return 'danger';
        }
        return match($kurir->status) {
            'pending' => 'warning',
            'rejected' => 'danger',
            'sedang_bertugas' => 'info',
            default => null
        };
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $user = auth()->user();
        $kurir = Kurir::where('user_id', $user->id)->first();
        if (!$kurir) {
            return 'Profil belum dibuat - Diperlukan untuk menerima tugas';
        }
        return match($kurir->status) {
            'pending' => 'Profil menunggu persetujuan admin - Belum bisa menerima tugas',
            'rejected' => 'Profil ditolak - Hubungi admin untuk informasi',
            'tersedia' => 'Profil disetujui - Siap menerima tugas pengiriman',
            'sedang_bertugas' => 'Sedang menjalankan tugas pengiriman',
            default => null
        };
    }
}