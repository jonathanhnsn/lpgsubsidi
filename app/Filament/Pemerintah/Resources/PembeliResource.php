<?php

namespace App\Filament\Pemerintah\Resources;

use App\Filament\Pemerintah\Resources\PembeliResource\Pages;
use App\Filament\Pemerintah\Resources\PembeliResource\RelationManagers;
use App\Models\Pembeli;
use App\Models\User;
use App\Models\KebijakanKuota;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PembeliResource extends Resource
{
    protected static ?string $model = Pembeli::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form schema kosong karena tidak perlu form untuk read-only
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
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu Persetujuan',
                        'accepted' => 'Diterima',
                        'rejected' => 'Ditolak',
                    ]),
                Tables\Filters\SelectFilter::make('pekerjaan')
                    ->label('Pekerjaan')
                    ->options(function () {
                        return Pembeli::distinct()->pluck('pekerjaan', 'pekerjaan')->toArray();
                    }),
            ])
            ->actions([
            ])
            ->bulkActions([
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
        ];
    }

    public static function canCreate(): bool
    {
        return false;
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
}