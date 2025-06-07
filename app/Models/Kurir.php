<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kurir extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nik',
        'no_telp',
        'nama_provinsi',
        'provinsi_id',
        'nama_kota_kabupaten',
        'kabupaten_id',
        'nama_kecamatan',
        'kecamatan_id',
        'alamat',
        'foto_ktp',
        'foto_sim',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Status methods untuk approval system
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isTersedia(): bool
    {
        return $this->status === 'tersedia';
    }

    public function isSedangBertugas(): bool
    {
        return $this->status === 'sedang_bertugas';
    }

    // Scope methods untuk approval system
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeTersedia($query)
    {
        return $query->where('status', 'tersedia');
    }

    public function scopeSedangBertugas($query)
    {
        return $query->where('status', 'sedang_bertugas');
    }

    // Scope untuk kurir yang sudah disetujui dan bisa bertugas
    public function scopeCanWork($query)
    {
        return $query->whereIn('status', ['accepted', 'tersedia', 'sedang_bertugas']);
    }

    // Scope untuk kurir yang bisa menerima tugas baru
    public function scopeAvailableForWork($query)
    {
        return $query->where('status', 'tersedia');
    }
}