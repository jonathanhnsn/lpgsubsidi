<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Pemesanan extends Model
{
    use HasFactory;

    protected $fillable = [
        'penjual_id',
        'kurir_id',
        'kode_pesanan',
        'jumlah_pesanan',
        'metode_pembayaran',
        'harga_per_tabung',
        'total_harga',
        'bukti_pembayaran',
        'tanggal_pembayaran',
        'status',
        'keterangan',
        'tanggal_pesanan',
        'tanggal_diproses',
    ];

    protected $casts = [
        'tanggal_pesanan' => 'datetime',
        'tanggal_diproses' => 'datetime',
        'tanggal_pembayaran' => 'datetime',
        'harga_per_tabung' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->kode_pesanan)) {
                $model->kode_pesanan = static::generateUniqueKode();
            }
            
            // Auto calculate total harga
            if ($model->jumlah_pesanan && $model->harga_per_tabung) {
                $model->total_harga = $model->jumlah_pesanan * $model->harga_per_tabung;
            }
        });

        static::updating(function ($model) {
            // Auto calculate total harga when updating
            if ($model->isDirty(['jumlah_pesanan', 'harga_per_tabung'])) {
                $model->total_harga = $model->jumlah_pesanan * $model->harga_per_tabung;
            }
        });
    }

    public static function generateUniqueKode(): string
    {
        do {
            $kode = strtoupper(Str::random(6));
        } while (self::where('kode_pesanan', $kode)->exists());

        return $kode;
    }

    public function penjual(): BelongsTo
    {
        return $this->belongsTo(Penjual::class);
    }

    public function kurir(): BelongsTo
    {
        return $this->belongsTo(Kurir::class);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'disetujui' => 'success',
            'dalam_perjalanan' => 'info',
            'ditolak' => 'danger',
            'selesai' => 'primary',
            default => 'secondary'
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Menunggu Persetujuan',
            'disetujui' => 'Siap Kirim',
            'dalam_perjalanan' => 'Dalam Perjalanan',
            'ditolak' => 'Ditolak',
            'selesai' => 'Selesai',
            default => 'Unknown'
        };
    }

    public function getMetodePembayaranLabelAttribute(): string
    {
        return match($this->metode_pembayaran) {
            'transfer_bank' => 'Transfer Bank',
            'dana' => 'DANA',
            'gopay' => 'GoPay',
            'ovo' => 'OVO',
            'shopee_pay' => 'ShopeePay',
            'qris' => 'QRIS',
            default => 'Unknown'
        };
    }
    
    public function scopeDisetujui($query)
    {
        return $query->where('status', 'disetujui');
    }
    
    public function scopeDalamPerjalanan($query)
    {
        return $query->where('status', 'dalam_perjalanan');
    }
    
    public function scopeAktif($query)
    {
        return $query->whereIn('status', ['disetujui', 'dalam_perjalanan']);
    }
    
    public function scopeForKurir($query, $kurirId)
    {
        return $query->where('kurir_id', $kurirId);
    }

    public function getTotalHargaFormattedAttribute(): string
    {
        return 'Rp ' . number_format($this->total_harga, 0, ',', '.');
    }

    public function getHargaPerTabungFormattedAttribute(): string
    {
        return 'Rp ' . number_format($this->harga_per_tabung, 0, ',', '.');
    }
}