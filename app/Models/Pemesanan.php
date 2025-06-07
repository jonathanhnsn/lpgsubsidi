<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pemesanan extends Model
{
    use HasFactory;

    protected $fillable = [
        'penjual_id',
        'kurir_id',
        'jumlah_pesanan',
        'status',
        'keterangan',
        'tanggal_pesanan',
        'tanggal_diproses',
    ];

    protected $casts = [
        'tanggal_pesanan' => 'datetime',
        'tanggal_diproses' => 'datetime',
    ];

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
}