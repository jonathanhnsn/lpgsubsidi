<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penjual extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nama_pemilik',
        'nik',
        'nama_provinsi',
        'provinsi_id',
        'nama_kota_kabupaten',
        'kabupaten_id',
        'nama_kecamatan',
        'kecamatan_id',
        'alamat',
        'kategori',
        'stok',
        'kuota',
        'foto_ktp',
        'foto_selfie',
        'foto_izin',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gas(): BelongsTo
    {
        return $this->belongsTo(Gas::class, 'kategori', 'jenis');
    }

    public function getNamaAttribute(): string
    {
        return $this->user->name ?? '';
    }

    public function canCreateTransaction(): bool
    {
        return $this->status === 'accepted';
    }

    public function getRemainingQuota(): int
    {
        return max(0, $this->kuota - $this->stok);
    }

    public function hasRemainingQuota(): bool
    {
        return $this->getRemainingQuota() > 0;
    }
}