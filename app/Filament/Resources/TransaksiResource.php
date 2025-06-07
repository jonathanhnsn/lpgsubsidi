<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiResource\Pages;
use App\Filament\Resources\TransaksiResource\RelationManagers;
use App\Models\Transaksi;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Transaksi';
    protected static ?string $navigationGroup = 'Manajemen Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('kode_transaksi')->label('Kode Transaksi')->disabled(),
                TextInput::make('pembeli.user.name')->label('Nama Pembeli')->disabled(),
                TextInput::make('gas.jenis')->label('Jenis Gas')->disabled(),
                TextInput::make('harga')->label('Harga')->prefix('Rp')->disabled(),
                DatePicker::make('tgl_beli')->label('Tanggal Beli')->disabled(),
                DatePicker::make('tgl_kembali')->label('Tanggal Kembali')->disabled(),
                TextInput::make('status')->label('Status')->disabled(),
                TextInput::make('penjual.user.name')->label('Ditangani Oleh')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pembeli.user.name')->label('Nama Pembeli')->searchable()->sortable(),
                TextColumn::make('pembeli.nik')->label('NIK Pembeli')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gas.jenis')->label('Jenis Gas')->sortable(),
                TextColumn::make('harga')->label('Harga')->money('IDR')->sortable(),
                TextColumn::make('tgl_beli')->label('Tanggal Beli')->date('d/m/Y')->sortable(),
                TextColumn::make('tgl_kembali')->label('Tanggal Kembali')->date('d/m/Y')->sortable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Confirmed' => 'info',
                        'Completed' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('penjual.user.name')->label('Ditangani Oleh')->default('Belum ditangani')->sortable(),
                TextColumn::make('penjual.nama_pemilik')->label('Nama Pemilik Toko')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('updated_at')->label('Diperbarui')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')
                    ->options([
                        'Pending' => 'Pending',
                        'Confirmed' => 'Confirmed',
                        'Completed' => 'Completed',
                    ]),
                Tables\Filters\SelectFilter::make('gas_jenis')->label('Jenis Gas')->relationship('gas', 'jenis'),
                Tables\Filters\Filter::make('created_at')->label('Tanggal Dibuat')
                    ->form([
                        DatePicker::make('created_from')->label('Dari Tanggal'),
                        DatePicker::make('created_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('without_penjual')->label('Belum Ada Penjual')->query(fn (Builder $query): Builder => $query->whereNull('penjual_id')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat Detail'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ])->defaultSort('created_at', 'desc')->striped()->paginated([10, 25, 50, 100]);
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
            'index' => Pages\ListTransaksis::route('/'),
            'view' => Pages\ViewTransaksi::route('/{record}'),
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

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'Pending')->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['pembeli.user', 'penjual.user', 'gas']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['kode_transaksi', 'pembeli.user.name', 'penjual.user.name'];
    }
}