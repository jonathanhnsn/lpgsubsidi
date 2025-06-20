<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KurirResource\Pages;
use App\Filament\Resources\KurirResource\RelationManagers;
use App\Models\Kurir;
use App\Models\User;
use App\Services\WilayahApiService;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;

class KurirResource extends Resource
{
    protected static ?string $model = Kurir::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Kurir';
    protected static ?string $pluralModelLabel = 'Kurir';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')->label('Nama Kurir')->options(User::all()->pluck('name', 'id'))->required()->searchable()->preload(),
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
                TextInput::make('no_telp')->label('Nomor Telepon')->tel()->required()->maxLength(15),
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
                Forms\Components\Textarea::make('alamat')->label('Alamat Lengkap')->required()->rows(3),
                FileUpload::make('foto_ktp')->label('Foto KTP')->image()->directory('profil-kurir/ktp')->required()->maxSize(2048)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg']),
                FileUpload::make('foto_sim')->label('Foto SIM')->image()->directory('profil-kurir/sim')->required()->maxSize(2048)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg']),
                Select::make('status')->label('Status')
                    ->options([
                        'pending' => 'Pending - Menunggu Persetujuan',
                        'accepted' => 'Accepted - Disetujui',
                        'rejected' => 'Rejected - Ditolak',
                        'tersedia' => 'Tersedia',
                        'sedang_bertugas' => 'Sedang Bertugas',
                    ])->default('pending')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Nama Kurir')->searchable()->sortable(),
                TextColumn::make('nik')->label('NIK')->searchable()
                    ->formatStateUsing(fn (string $state): string => 
                        substr($state, 0, 4) . '****' . substr($state, -4)
                    ),
                TextColumn::make('no_telp')->label('No. Telepon')->searchable(),
                TextColumn::make('nama_provinsi')->label('Provinsi')->searchable()->limit(20),
                TextColumn::make('nama_kota_kabupaten')->label('Kota/Kabupaten')->searchable()->limit(20),
                TextColumn::make('nama_kecamatan')->label('Kecamatan')->searchable()->limit(20),
                BadgeColumn::make('status')->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => ['accepted', 'tersedia'],
                        'danger' => 'rejected',
                        'info' => 'sedang_bertugas',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu Persetujuan',
                        'accepted' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'tersedia' => 'Tersedia',
                        'sedang_bertugas' => 'Sedang Bertugas',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu Persetujuan',
                        'accepted' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'tersedia' => 'Tersedia',
                        'sedang_bertugas' => 'Sedang Bertugas',
                    ]),
                
                Tables\Filters\SelectFilter::make('nama_provinsi')
                    ->label('Provinsi')
                    ->options(fn (): array => Kurir::distinct()->pluck('nama_provinsi', 'nama_provinsi')->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                // Action untuk menyetujui kurir
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Kurir $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Kurir')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui kurir ini? Status akan berubah menjadi "Tersedia" dan kurir dapat mulai bertugas.')
                    ->action(function (Kurir $record) {
                        $record->update(['status' => 'tersedia']);
                        Notification::make()
                            ->title('Kurir Disetujui')
                            ->body("Kurir {$record->user->name} telah disetujui dan dapat mulai bertugas.")
                            ->success()
                            ->send();
                    }),

                // Action untuk menolak kurir
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Kurir $record): bool => in_array($record->status, ['pending', 'accepted', 'tersedia']))
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Kurir')
                    ->modalDescription('Apakah Anda yakin ingin menolak kurir ini? Kurir tidak akan dapat bertugas.')
                    ->action(function (Kurir $record) {
                        $record->update(['status' => 'rejected']);
                        Notification::make()
                            ->title('Kurir Ditolak')
                            ->body("Kurir {$record->user->name} telah ditolak dan tidak dapat bertugas.")
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Setujui Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $approved = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->update(['status' => 'tersedia']);
                                    $approved++;
                                }
                            }
                            if ($approved > 0) {
                                Notification::make()
                                    ->title('Kurir Disetujui')
                                    ->body("{$approved} kurir berhasil disetujui")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Tidak Ada Perubahan')
                                    ->body('Tidak ada kurir yang dapat disetujui')
                                    ->info()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Setujui Kurir Terpilih')
                        ->modalDescription('Semua kurir terpilih yang berstatus pending akan disetujui.'),

                    // Bulk action untuk menolak beberapa kurir sekaligus
                    Tables\Actions\BulkAction::make('bulk_reject')
                        ->label('Tolak Terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $rejected = 0;
                            foreach ($records as $record) {
                                if (in_array($record->status, ['pending', 'accepted', 'tersedia'])) {
                                    $record->update(['status' => 'rejected']);
                                    $rejected++;
                                }
                            }
                            if ($rejected > 0) {
                                Notification::make()
                                    ->title('Kurir Ditolak')
                                    ->body("{$rejected} kurir berhasil ditolak")
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Tidak Ada Perubahan')
                                    ->body('Tidak ada kurir yang dapat ditolak')
                                    ->info()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Tolak Kurir Terpilih')
                        ->modalDescription('Semua kurir terpilih akan ditolak dan tidak dapat bertugas.'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKurirs::route('/'),
            'create' => Pages\CreateKurir::route('/create'),
            'view' => Pages\ViewKurir::route('/{record}'),
            'edit' => Pages\EditKurir::route('/{record}/edit'),
        ];
    }

    // Badge untuk menampilkan jumlah kurir yang pending
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
        return $pendingCount > 0 ? "{$pendingCount} kurir menunggu persetujuan" : null;
    }
}