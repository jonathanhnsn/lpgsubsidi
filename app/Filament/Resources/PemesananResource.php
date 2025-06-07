<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PemesananResource\Pages;
use App\Models\Pemesanan;
use App\Models\Penjual;
use App\Models\Kurir;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class PemesananResource extends Resource
{
    protected static ?string $model = Pemesanan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Kelola Pemesanan';
    protected static ?string $modelLabel = 'Pemesanan';
    protected static ?string $pluralModelLabel = 'Pemesanan';
    protected static ?string $navigationGroup = 'Manajemen Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('penjual_id')
                    ->label('Penjual')
                    ->relationship('penjual.user', 'name')
                    ->required()
                    ->disabled()
                    ->dehydrated(true),

                Select::make('kurir_id')
                    ->label('Kurir')
                    ->relationship('kurir.user', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name . ' - ' . $record->nama_kota_kabupaten)
                    ->options(function () {
                        return Kurir::with('user')
                            ->where('status', 'tersedia')
                            ->get()
                            ->mapWithKeys(function ($kurir) {
                                return [$kurir->id => $kurir->user->name . ' - ' . $kurir->nama_kota_kabupaten];
                            });
                    })
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($get) => !in_array($get('status'), ['disetujui', 'dalam_perjalanan', 'selesai']))
                    ->visible(fn ($get) => in_array($get('status'), ['disetujui', 'dalam_perjalanan', 'selesai'])),

                TextInput::make('jumlah_pesanan')
                    ->label('Jumlah Pesanan')
                    ->required()
                    ->numeric()
                    ->disabled()
                    ->dehydrated(true)
                    ->suffix('unit'),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu Persetujuan',
                        'disetujui' => 'Siap Kirim',
                        'dalam_perjalanan' => 'Dalam Perjalanan',
                        'ditolak' => 'Ditolak',
                        'selesai' => 'Selesai',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (in_array($state, ['disetujui', 'ditolak'])) {
                            $set('tanggal_diproses', now());
                        }
                    }),

                DateTimePicker::make('tanggal_pesanan')
                    ->label('Tanggal Pesanan')
                    ->disabled()
                    ->dehydrated(true),

                DateTimePicker::make('tanggal_diproses')
                    ->label('Tanggal Diproses')
                    ->disabled()
                    ->dehydrated(true),

                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                
                TextColumn::make('penjual.user.name')
                    ->label('Penjual')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('penjual.kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Agen' => 'success',
                        'Pangkalan' => 'warning',
                        'Sub-Pangkalan' => 'info',
                        default => 'secondary',
                    }),
                
                TextColumn::make('kurir.user.name')
                    ->label('Kurir')
                    ->placeholder('-')
                    ->tooltip(fn ($record) => $record->kurir ? 
                        $record->kurir->user->name . ' (' . $record->kurir->nama_kota_kabupaten . ')' : 
                        'Belum ditentukan')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('jumlah_pesanan')
                    ->label('Jumlah')
                    ->sortable()
                    ->suffix(' unit'),
                
                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'Menunggu',
                        'disetujui' => 'Siap Kirim',
                        'dalam_perjalanan' => 'Dalam Perjalanan',
                        'ditolak' => 'Ditolak',
                        'selesai' => 'Selesai',
                        default => $state
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'disetujui',
                        'info' => 'dalam_perjalanan',
                        'danger' => 'ditolak',
                        'primary' => 'selesai',
                    ]),
                
                TextColumn::make('tanggal_pesanan')
                    ->label('Tanggal Pesanan')
                    ->dateTime()
                    ->sortable(),
                
                TextColumn::make('tanggal_diproses')
                    ->label('Tanggal Diproses')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'disetujui' => 'Siap Kirim',
                        'dalam_perjalanan' => 'Dalam Perjalanan',
                        'ditolak' => 'Ditolak',
                        'selesai' => 'Selesai',
                    ]),
                Tables\Filters\SelectFilter::make('penjual.kategori')
                    ->label('Kategori Penjual')
                    ->options([
                        'Agen' => 'Agen',
                        'Pangkalan' => 'Pangkalan',
                        'Sub-Pangkalan' => 'Sub-Pangkalan',
                    ]),
                Tables\Filters\SelectFilter::make('kurir_id')
                    ->label('Kurir')
                    ->relationship('kurir.user', 'name')
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Action::make('setuju')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Pemesanan $record) => $record->status === 'pending')
                    ->form([
                        Select::make('kurir_id')
                            ->label('Pilih Kurir')
                            ->options(function () {
                                return Kurir::with('user')
                                    ->where('status', 'tersedia')
                                    ->get()
                                    ->mapWithKeys(function ($kurir) {
                                        return [$kurir->id => $kurir->user->name . ' - ' . $kurir->nama_kota_kabupaten];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->placeholder('Pilih kurir yang tersedia'),
                        Textarea::make('keterangan')
                            ->label('Keterangan (Opsional)')
                            ->rows(3)
                            ->placeholder('Tambahkan keterangan jika diperlukan')
                    ])
                    ->action(function (Pemesanan $record, array $data) {
                        // Update status pemesanan dan assign kurir
                        $record->update([
                            'status' => 'disetujui',
                            'kurir_id' => $data['kurir_id'],
                            'keterangan' => $data['keterangan'] ?? $record->keterangan,
                            'tanggal_diproses' => now(),
                        ]);
                        
                        // STOK TIDAK DITAMBAH DI SINI - akan ditambah saat selesai pengiriman
                        
                        // Update status kurir menjadi sedang bertugas
                        $kurir = Kurir::find($data['kurir_id']);
                        $kurir->update(['status' => 'sedang_bertugas']);
                        
                        Notification::make()
                            ->success()
                            ->title('Pemesanan disetujui')
                            ->body("Pesanan telah disetujui dan ditugaskan ke kurir {$kurir->user->name}. Barang akan dikirim dan stok akan bertambah setelah pengiriman selesai.")
                            ->send();
                    }),
                
                Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Pemesanan $record) => $record->status === 'pending')
                    ->form([
                        Textarea::make('keterangan')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3)
                            ->placeholder('Berikan alasan penolakan pemesanan')
                    ])
                    ->action(function (Pemesanan $record, array $data) {
                        $record->update([
                            'status' => 'ditolak',
                            'keterangan' => $data['keterangan'],
                            'tanggal_diproses' => now(),
                        ]);
                        
                        Notification::make()
                            ->warning()
                            ->title('Pemesanan ditolak')
                            ->body('Pemesanan telah ditolak dengan alasan: ' . $data['keterangan'])
                            ->send();
                    }),

                // Action::make('ganti_kurir')
                //     ->label('Ganti Kurir')
                //     ->icon('heroicon-o-arrow-path')
                //     ->color('warning')
                //     ->visible(fn (Pemesanan $record) => $record->status === 'disetujui' && $record->kurir_id)
                //     ->form([
                //         Select::make('kurir_id')
                //             ->label('Pilih Kurir Baru')
                //             ->options(function () {
                //                 return Kurir::with('user')
                //                     ->where('status', 'tersedia')
                //                     ->get()
                //                     ->mapWithKeys(function ($kurir) {
                //                         return [$kurir->id => $kurir->user->name . ' - ' . $kurir->nama_kota_kabupaten];
                //                     });
                //             })
                //             ->required()
                //             ->searchable()
                //             ->placeholder('Pilih kurir yang tersedia'),
                //         Textarea::make('keterangan')
                //             ->label('Alasan Pergantian Kurir')
                //             ->rows(3)
                //             ->placeholder('Berikan alasan pergantian kurir')
                //     ])
                //     ->action(function (Pemesanan $record, array $data) {
                //         // Set kurir lama kembali tersedia
                //         if ($record->kurir) {
                //             $record->kurir->update(['status' => 'tersedia']);
                //         }
                        
                //         // Update kurir baru
                //         $kurirBaru = Kurir::find($data['kurir_id']);
                //         $record->update([
                //             'kurir_id' => $data['kurir_id'],
                //             'keterangan' => $data['keterangan'] ?? $record->keterangan,
                //         ]);
                        
                //         // Set kurir baru menjadi sedang bertugas
                //         $kurirBaru->update(['status' => 'sedang_bertugas']);
                        
                //         Notification::make()
                //             ->success()
                //             ->title('Kurir berhasil diganti')
                //             ->body("Kurir berhasil diganti ke {$kurirBaru->user->name}.")
                //             ->send();
                //     }),
            ])
            ->bulkActions([
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto refresh setiap 30 detik
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPemesanans::route('/'),
            'create' => Pages\CreatePemesanan::route('/create'),
            'view' => Pages\ViewPemesanan::route('/{record}'),
            'edit' => Pages\EditPemesanan::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();
        return $pendingCount > 0 ? 'warning' : null;
    }
}