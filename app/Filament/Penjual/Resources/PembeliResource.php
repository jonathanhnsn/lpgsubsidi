<?php

namespace App\Filament\Penjual\Resources;

use App\Filament\Penjual\Resources\PembeliResource\Pages;
use App\Filament\Penjual\Resources\PembeliResource\RelationManagers;
use App\Models\Pembeli;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PembeliResource extends Resource
{
    protected static ?string $model = Pembeli::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Daftar Pembeli';
    protected static ?int $navigationSort = 2;
    public static function canCreate(): bool
    {
        return false;
    }
    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->label('ID')->searchable(),
                TextColumn::make('user.name')->label('Nama')->sortable()->searchable(),   
                // TextColumn::make('nik')->label('NIK')->searchable()->tooltip(fn ($record) => $record->nik),
                TextColumn::make('nama_provinsi')->label('Provinsi')->searchable(),
                TextColumn::make('nama_kota_kabupaten')->label('Kota/Kabupaten')->searchable()->wrap(),
                TextColumn::make('nama_kecamatan')->label('Kecamatan')->searchable(),
                TextColumn::make('alamat')->label('Alamat')->limit(30)->wrap()
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),
                TextColumn::make('pekerjaan')->label('Pekerjaan')->badge()->color('info'),
                TextColumn::make('gaji')->label('Pendapatan')->badge()->color('warning'),
                TextColumn::make('kuota')->label('Kuota')->alignCenter()->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 2 => 'success',
                        $state == 1 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pekerjaan')
                    ->label('Pekerjaan')
                    ->options([
                        'Ibu Rumah Tangga' => 'Ibu Rumah Tangga',
                        'UMKM' => 'UMKM',
                        'Swasta' => 'Swasta',
                        'Negeri' => 'Negeri',
                    ]),
                Tables\Filters\SelectFilter::make('gaji')
                    ->label('Kisaran Pendapatan')
                    ->options([
                        '< 3 juta' => '< 3 juta',
                        '> 3 juta' => '> 3 juta',
                    ]),
                Tables\Filters\SelectFilter::make('nama_provinsi')
                    ->label('Provinsi')
                    ->options(function () {
                        return Pembeli::distinct()
                            ->pluck('nama_provinsi', 'nama_provinsi')
                            ->toArray();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail Pembeli')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->fillForm(function ($record) {
                        $record->load('user');
                        return [
                            'nama_lengkap' => $record->user->name ?? 'Tidak ada nama',
                            'nik' => $record->nik,
                            'nama_provinsi' => $record->nama_provinsi,
                            'nama_kota_kabupaten' => $record->nama_kota_kabupaten,
                            'nama_kecamatan' => $record->nama_kecamatan,
                            'alamat' => $record->alamat,
                            'pekerjaan' => $record->pekerjaan,
                            'gaji' => $record->gaji,
                            'kuota' => $record->kuota,
                            'foto' => $record->foto,
                        ];
                    })
                    ->form([
                        \Filament\Forms\Components\Section::make('Informasi Pribadi')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('nama_lengkap')
                                    ->label('Nama Lengkap')
                                    ->disabled()
                                    ->columnSpanFull(),
                                // \Filament\Forms\Components\TextInput::make('nik')
                                //     ->label('NIK')
                                //     ->disabled(),
                            ])
                            ->columns(2),
                        \Filament\Forms\Components\Section::make('Alamat')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('nama_provinsi')
                                    ->label('Provinsi')
                                    ->disabled(),
                                \Filament\Forms\Components\TextInput::make('nama_kota_kabupaten')
                                    ->label('Kota/Kabupaten')
                                    ->disabled(),
                                \Filament\Forms\Components\TextInput::make('nama_kecamatan')
                                    ->label('Kecamatan')
                                    ->disabled(),
                                \Filament\Forms\Components\Textarea::make('alamat')
                                    ->label('Alamat Lengkap')
                                    ->disabled()
                                    ->rows(3),
                            ])
                            ->columns(2),
                        \Filament\Forms\Components\Section::make('Informasi Ekonomi')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('pekerjaan')
                                    ->label('Pekerjaan')
                                    ->disabled(),
                                \Filament\Forms\Components\TextInput::make('gaji')
                                    ->label('Kisaran Pendapatan')
                                    ->disabled(),
                                \Filament\Forms\Components\TextInput::make('kuota')
                                    ->label('Kuota')
                                    ->disabled()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        // \Filament\Forms\Components\Section::make('Foto')
                        //     ->schema([
                        //         \Filament\Forms\Components\FileUpload::make('foto')
                        //             ->label('Foto Pendukung')
                        //             ->disabled()
                        //             ->disk('public')
                        //             ->visibility('public')
                        //             ->image()
                        //             ->imagePreviewHeight('200')
                        //             ->hiddenLabel(),
                        //     ])
                        //     ->visible(fn ($record) => $record->foto),
                    ]),
            ])
            ->bulkActions([
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
        ];
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function canRestore($record): bool
    {
        return false;
    }

    public static function canRestoreAny(): bool
    {
        return false;
    }
}