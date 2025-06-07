<?php

namespace App\Filament\Pembeli\Resources\TransaksiResource\Pages;

use App\Filament\Pembeli\Resources\TransaksiResource;
use App\Models\Pembeli;
use App\Models\Transaksi;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreateTransaksi extends CreateRecord
{
    protected static string $resource = TransaksiResource::class;

    public function mount(): void
    {
        $user = auth()->user();
        $pembeli = Pembeli::where('user_id', $user->id)->first();
        
        // Cek apakah pembeli ada
        if (!$pembeli) {
            Notification::make()
                ->title('Profil Diperlukan')
                ->body('Anda harus membuat profil pembeli terlebih dahulu sebelum dapat melakukan transaksi.')
                ->warning()
                ->persistent()
                ->send();

            $this->redirect(TransaksiResource::getUrl('index'));
            return;
        }

        // Cek status pembeli
        if ($pembeli->status !== 'accepted') {
            $message = match($pembeli->status) {
                'pending' => 'Profil Anda masih menunggu persetujuan admin. Anda belum dapat melakukan transaksi.',
                'rejected' => 'Profil Anda ditolak. Hubungi admin untuk informasi lebih lanjut.',
                default => 'Status profil Anda tidak valid untuk melakukan transaksi.'
            };

            Notification::make()
                ->title('Tidak Dapat Membuat Transaksi')
                ->body($message)
                ->warning()
                ->persistent()
                ->send();

            $this->redirect(TransaksiResource::getUrl('index'));
            return;
        }

        // Cek kuota
        if ($pembeli->kuota <= 0) {
            Notification::make()
                ->title('Kuota Habis')
                ->body('Kuota transaksi Anda sudah habis. Tunggu hingga ada transaksi yang diselesaikan.')
                ->warning()
                ->persistent()
                ->send();

            $this->redirect(TransaksiResource::getUrl('index'));
            return;
        }

        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $pembeli = Pembeli::where('user_id', $user->id)->first();
        
        if (!$pembeli) {
            Notification::make()
                ->title('Error')
                ->body('Anda tidak memiliki profil pembeli. Silakan lengkapi profil terlebih dahulu.')
                ->danger()
                ->send();
                
            $this->halt();
        }

        // Validasi status pembeli sekali lagi sebelum create
        if ($pembeli->status !== 'accepted') {
            $message = match($pembeli->status) {
                'pending' => 'Profil Anda masih menunggu persetujuan admin.',
                'rejected' => 'Profil Anda ditolak. Hubungi admin untuk informasi lebih lanjut.',
                default => 'Status profil Anda tidak valid.'
            };

            Notification::make()
                ->title('Transaksi Ditolak')
                ->body($message)
                ->danger()
                ->send();
            
            $this->halt();
        }

        // Validasi kuota dengan qty yang diminta
        $qty = $data['qty'] ?? 1;
        if ($pembeli->kuota < $qty) {
            Notification::make()
                ->title('Kuota Tidak Mencukupi')
                ->body("Anda meminta {$qty} transaksi, tetapi kuota Anda hanya {$pembeli->kuota}. Silakan kurangi jumlah transaksi.")
                ->danger()
                ->send();
            
            $this->halt();
        }

        $data['pembeli_id'] = $pembeli->id;
        $data['tgl_beli'] = now()->toDateString();
        $data['status'] = 'Pending';
        
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            // Ambil qty dari data dan hapus dari array (karena tidak ada di tabel)
            $qty = $data['qty'] ?? 1;
            unset($data['qty']);
            unset($data['total_harga']); // Hapus total_harga juga karena tidak ada di tabel

            // Validasi kuota pembeli sekali lagi
            $pembeli = Pembeli::where('user_id', auth()->id())->first();
            if (!$pembeli) {
                throw new \Exception('Profil pembeli tidak ditemukan.');
            }

            if ($pembeli->kuota < $qty) {
                throw new \Exception("Kuota tidak mencukupi. Anda hanya memiliki {$pembeli->kuota} kuota tersisa.");
            }

            if ($pembeli->status !== 'accepted') {
                throw new \Exception('Profil Anda belum disetujui. Tidak dapat membuat transaksi.');
            }

            $createdTransactions = [];
            $failedCount = 0;
            $lastCreatedTransaction = null;

            // Buat transaksi sebanyak qty
            for ($i = 0; $i < $qty; $i++) {
                try {
                    // Cek kuota sebelum setiap transaksi
                    $pembeli->refresh();
                    if ($pembeli->kuota <= 0) {
                        $failedCount = $qty - $i;
                        break;
                    }

                    $transaction = static::getModel()::create($data);
                    $createdTransactions[] = $transaction;
                    $lastCreatedTransaction = $transaction;
                } catch (\Exception $e) {
                    $failedCount++;
                    // Jika ada error, hentikan loop
                    break;
                }
            }

            $successCount = count($createdTransactions);

            // Set record untuk keperluan notification
            $this->record = $lastCreatedTransaction;

            // Buat notifikasi sesuai hasil
            if ($successCount > 0 && $failedCount === 0) {
                // Semua berhasil - notifikasi akan dihandle oleh getCreatedNotification()
                $this->creationMessage = "Berhasil membuat {$successCount} transaksi.";
            } elseif ($successCount > 0 && $failedCount > 0) {
                Notification::make()
                    ->title('Transaksi Sebagian Berhasil')
                    ->body("Berhasil membuat {$successCount} transaksi. {$failedCount} transaksi gagal dibuat karena kuota tidak mencukupi.")
                    ->warning()
                    ->persistent()
                    ->send();
            } else {
                throw new \Exception('Gagal membuat transaksi. Periksa kuota Anda.');
            }

            // Return transaksi terakhir yang berhasil dibuat (untuk redirect)
            return $lastCreatedTransaction ?? new Transaksi();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Membuat Transaksi')
                ->body($e->getMessage())
                ->danger()
                ->send();
            
            $this->halt();
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        $user = auth()->user();
        $pembeli = Pembeli::where('user_id', $user->id)->first();
        $sisaKuota = $pembeli ? $pembeli->fresh()->kuota : 0;
        
        // Jika ada custom message dari multiple creation
        if (isset($this->creationMessage)) {
            return Notification::make()
                ->success()
                ->title('Transaksi Berhasil Dibuat')
                ->body($this->creationMessage . " Kode transaksi terakhir: {$this->record->kode_transaksi}. Sisa kuota Anda: {$sisaKuota}")
                ->persistent();
        }
        
        // Default single transaction notification
        return Notification::make()
            ->success()
            ->title('Transaksi Berhasil Dibuat')
            ->body("Transaksi Anda telah dibuat dengan kode: {$this->record->kode_transaksi}. Simpan kode ini untuk verifikasi dengan penjual. Sisa kuota Anda: {$sisaKuota}")
            ->persistent();
    }

    protected function beforeCreate(): void
    {
        $user = auth()->user();
        $pembeli = Pembeli::where('user_id', $user->id)->first();
        
        if (!$pembeli) {
            Notification::make()
                ->title('Profil Tidak Lengkap')
                ->body('Anda harus melengkapi profil pembeli terlebih dahulu sebelum dapat membuat transaksi.')
                ->danger()
                ->send();
                
            $this->halt();
        }

        // Final check sebelum create
        if ($pembeli->status !== 'accepted') {
            Notification::make()
                ->title('Status Profil Tidak Valid')
                ->body('Hanya pembeli dengan status "Disetujui" yang dapat membuat transaksi.')
                ->danger()
                ->send();
                
            $this->halt();
        }
    }

    // Property untuk menyimpan custom creation message
    protected $creationMessage = null;
}