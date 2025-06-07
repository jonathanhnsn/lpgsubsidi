<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KebijakanKuotaResource\Pages;
use App\Models\KebijakanKuota;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class KebijakanKuotaResource extends Resource
{
    protected static ?string $model = KebijakanKuota::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Kebijakan Kuota';
    protected static ?string $modelLabel = 'Kebijakan Kuota';
    protected static ?string $navigationGroup = 'Aturan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Kebijakan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('tipe')
                                    ->label('Tipe Kebijakan')
                                    ->options([
                                        'penjual' => 'Penjual',
                                        'pembeli' => 'Pembeli',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('kategori_atau_pekerjaan', null);
                                        $set('sub_kategori', null);
                                        $set('kuota', 0);
                                    }),

                                Select::make('kategori_atau_pekerjaan')
                                    ->label(function (callable $get) {
                                        return $get('tipe') === 'penjual' ? 'Kategori Penjual' : 'Pekerjaan Pembeli';
                                    })
                                    ->options(function (callable $get) {
                                        $tipe = $get('tipe');
                                        if ($tipe === 'penjual') {
                                            return [
                                                'Agen' => 'Agen',
                                                'Pangkalan' => 'Pangkalan',
                                                'Sub-Pangkalan' => 'Sub-Pangkalan',
                                            ];
                                        } elseif ($tipe === 'pembeli') {
                                            return [
                                                'Ibu Rumah Tangga' => 'Ibu Rumah Tangga',
                                                'UMKM' => 'UMKM',
                                                'Swasta' => 'Swasta',
                                                'Negeri' => 'Negeri',
                                            ];
                                        }
                                        return [];
                                    })
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $set('sub_kategori', null);
                                        // Set default kuota berdasarkan kategori
                                        if ($get('tipe') === 'penjual') {
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
                            ]),

                        Select::make('sub_kategori')
                            ->label('Sub Kategori (Khusus Pembeli)')
                            ->options([
                                '< 3 juta' => '< 3 juta',
                                '> 3 juta' => '> 3 juta',
                            ])
                            ->native(false)
                            ->visible(fn (callable $get) => $get('tipe') === 'pembeli')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $pekerjaan = $get('kategori_atau_pekerjaan');
                                if ($get('tipe') === 'pembeli' && $pekerjaan) {
                                    if ($pekerjaan === 'UMKM') {
                                        if ($state === '< 3 juta') {
                                            $set('kuota', 8);
                                        } elseif ($state === '> 3 juta') {
                                            $set('kuota', 12);
                                        }
                                    } else {
                                        $set('kuota', 4);
                                    }
                                }
                            }),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('kuota')
                                    ->label('Kuota')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix(function (callable $get) {
                                        $tipe = $get('tipe');
                                        $pekerjaan = $get('kategori_atau_pekerjaan');
                                        return 'tabung/bulan';
                                    }),

                                Toggle::make('is_aktif')
                                    ->label('Status Aktif')
                                    ->default(true)
                                    ->helperText('Nonaktifkan untuk menonaktifkan kebijakan ini')->hidden(),
                            ]),

                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3)
                            ->placeholder('Keterangan tambahan untuk kebijakan ini (opsional)'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tipe_label')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Penjual' => 'success',
                        'Pembeli' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('kategori_atau_pekerjaan')
                    ->label('Kategori/Pekerjaan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sub_kategori')
                    ->label('Sub Kategori')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('kuota')
                    ->label('Kuota')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        return $state . ' tabung/bulan';
                    })
                    ->badge()
                    ->color('primary'),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipe')
                    ->label('Tipe Kebijakan')
                    ->options([
                        'penjual' => 'Penjual', 
                        'pembeli' => 'Pembeli',
                    ]),

                SelectFilter::make('kategori_atau_pekerjaan')
                    ->label('Kategori/Pekerjaan')
                    ->options([
                        // Penjual
                        'Agen' => 'Agen',
                        'Pangkalan' => 'Pangkalan',
                        'Sub-Pangkalan' => 'Sub-Pangkalan',
                        // Pembeli
                        'Ibu Rumah Tangga' => 'Ibu Rumah Tangga',
                        'UMKM' => 'UMKM',
                        'Swasta' => 'Swasta',
                        'Negeri' => 'Negeri',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
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
            'index' => Pages\ListKebijakanKuotas::route('/'),
            'create' => Pages\CreateKebijakanKuota::route('/create'),
            'edit' => Pages\EditKebijakanKuota::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}