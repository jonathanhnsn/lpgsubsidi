<?php

namespace App\Filament\Widgets;

use App\Models\Transaksi;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTransactionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Transaksi Terbaru';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaksi::withoutUserRestriction()
                    ->with(['pembeli.user', 'penjual.user', 'gas'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('kode_transaksi')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('pembeli.user.name')
                    ->label('Pembeli')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('penjual.user.name')
                    ->label('Penjual')
                    ->default('Belum ada penjual')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('gas.jenis')
                    ->label('Jenis Gas')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('harga')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'Pending',
                        'success' => 'Confirmed',
                        'primary' => 'Completed',
                    ]),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }
}