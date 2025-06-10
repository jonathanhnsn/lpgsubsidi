<?php

namespace App\Filament\Penjual\Resources;

use App\Filament\Penjual\Resources\GasResource\Pages;
use App\Filament\Penjual\Resources\GasResource\RelationManagers;
use App\Models\Gas;
use App\Models\Penjual;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class GasResource extends Resource
{
    protected static ?string $model = Gas::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('jenis')
                    ->required()
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('harga')
                    ->required()
                    ->numeric()
                    ->prefix('Rp. ')
                    ->minValue(0)
                    ->disabled()
                    ->dehydrated(false),
                // TextInput::make('stok_penjual')
                //     ->label('Stok')
                //     ->required()
                //     ->numeric()
                //     ->minValue(0)
                //     ->formatStateUsing(function ($state, $record) {
                //         $penjual = Penjual::where('user_id', Auth::id())->first();
                //         return $penjual ? $penjual->stok : 0;
                //     })
                //     ->afterStateUpdated(function ($state) {
                //         $penjual = Penjual::where('user_id', Auth::id())->first();
                //         if ($penjual) {
                //             $penjual->update(['stok' => $state]);
                //         }
                //     })
                //     ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('jenis')->sortable(),
                TextColumn::make('harga')->sortable()->prefix('Rp. '),
                TextColumn::make('stok_penjual')
                    ->label('Stok')
                    ->getStateUsing(function (Gas $record) {
                        $penjual = Penjual::where('user_id', Auth::id())->first();
                        return $penjual ? $penjual->stok : '-';
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
            ])
            ->bulkActions([
                //
            ]);
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
            'index' => Pages\ListGases::route('/'),
            'create' => Pages\CreateGas::route('/create'),
            'edit' => Pages\EditGas::route('/{record}/edit'),
            'view' => Pages\ViewGas::route('/{record}'),
        ];
    }
}
