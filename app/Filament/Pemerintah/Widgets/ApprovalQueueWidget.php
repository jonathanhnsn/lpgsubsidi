<?php

namespace App\Filament\Widgets;

use App\Models\Pembeli;
use App\Models\Kurir;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ApprovalQueueWidget extends BaseWidget
{
    protected static ?string $heading = 'Antrian Persetujuan';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->colors([
                        'primary' => 'Pembeli',
                        'success' => 'Kurir',
                    ]),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('additional_info')
                    ->label('Info Tambahan'),
                    
                Tables\Columns\TextColumn::make('nama_kota_kabupaten')
                    ->label('Lokasi')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Daftar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function ($record) {
                        $record->update(['status' => 'accepted']);
                    }),
                    
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function ($record) {
                        $record->update(['status' => 'rejected']);
                    }),
            ])
            ->paginated([5, 10, 25]);
    }

    protected function getQuery(): Builder
    {
        $pembeli = Pembeli::pending()
            ->with('user')
            ->select([
                'id',
                'user_id',
                'pekerjaan as additional_info',
                'nama_kota_kabupaten',
                'created_at',
                \DB::raw("'Pembeli' as type"),
                \DB::raw("'pembeli' as table_type")
            ]);

        $kurir = Kurir::pending()
            ->with('user')
            ->select([
                'id',
                'user_id',
                'no_telp as additional_info',
                'nama_kota_kabupaten',
                'created_at',
                \DB::raw("'Kurir' as type"),
                \DB::raw("'kurir' as table_type")
            ]);

        return $pembeli->union($kurir)->orderBy('created_at', 'desc');
    }
}