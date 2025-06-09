<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembeli extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nik',
        'nama_provinsi',
        'provinsi_id',
        'nama_kota_kabupaten',
        'kabupaten_id',
        'nama_kecamatan',
        'kecamatan_id',
        'alamat',
        'pekerjaan',
        'gaji',
        'kuota',
        'foto_ktp',
        'foto_selfie',
        'foto_kk',
        'foto_usaha',
        'foto_izin',
        'status',
        'rejection_note',
    ];

    protected $casts = [
        'kuota' => 'integer',
    ];

    public function resubmitForReview(): bool
    {
        if ($this->status === 'rejected') {
            return $this->update(['status' => 'pending']);
        }
        return false;
    }

    public function canBeEditedByUser(): bool
    {
        return in_array($this->status, ['pending', 'accepted', 'rejected']);
    }

    public function needsReview(): bool
    {
        return $this->status === 'pending' && !empty($this->rejection_note);
    }

    public function getDetailedStatusMessage(): string
    {
        $baseMessage = $this->getApprovalStatusMessage();
        
        if ($this->status === 'rejected' && $this->rejection_note) {
            $baseMessage .= "\n\nCatatan dari Admin: " . $this->rejection_note;
            $baseMessage .= "\n\nSilakan perbaiki data sesuai catatan di atas, kemudian simpan untuk ditinjau ulang.";
        } elseif ($this->status === 'pending' && $this->rejection_note) {
            $baseMessage .= "\n\nProfil telah diperbaiki dan sedang menunggu tinjauan ulang dari admin.";
        }
        
        return $baseMessage;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    public function getNamaAttribute(): string
    {
        return $this->user->name ?? '';
    }

    public function getKuotaInfoAttribute(): array
    {
        $transaksiAktif = $this->transaksis()
            ->whereIn('status', ['Pending', 'Confirmed'])
            ->count();   
        $transaksiSelesai = $this->transaksis()
            ->where('status', 'Completed')
            ->count();
        $totalTransaksi = $this->transaksis()->count();
        return [
            'sisa_kuota' => $this->kuota,
            'transaksi_aktif' => $transaksiAktif,
            'transaksi_selesai' => $transaksiSelesai,
            'total_transaksi' => $totalTransaksi,
            'kuota_terpakai' => $transaksiAktif
        ];
    }

    public function canCreateTransaction(): bool
    {
        return $this->status === 'accepted' && $this->kuota > 0;
    }

    public function getKuotaStatusMessage(): string
    {
        if ($this->status === 'pending') {
            return 'Status profil masih pending. Menunggu persetujuan admin untuk dapat melakukan transaksi.';
        } elseif ($this->status === 'rejected') {
            return 'Profil ditolak oleh admin. Tidak dapat membuat transaksi. Silakan hubungi admin untuk informasi lebih lanjut.';
        } elseif ($this->status === 'accepted' && $this->kuota <= 0) {
            return 'Profil sudah disetujui, tetapi kuota transaksi habis. Tidak dapat membuat transaksi baru.';
        } elseif ($this->status === 'accepted' && $this->kuota <= 3) {
            return "Profil disetujui. Kuota hampir habis. Sisa {$this->kuota} transaksi.";
        } elseif ($this->status === 'accepted') {
            return "Profil disetujui. Kuota tersedia: {$this->kuota} transaksi.";
        } else {
            return 'Status profil tidak dikenal. Hubungi admin.';
        }
    }

    public function getApprovalStatusMessage(): string
    {
        return match($this->status) {
            'pending' => 'Profil Anda sedang menunggu persetujuan admin. Anda akan dapat melakukan transaksi setelah profil disetujui.',
            'accepted' => 'Profil Anda telah disetujui. Anda dapat melakukan transaksi.',
            'rejected' => 'Profil Anda ditolak oleh admin. Silakan hubungi admin untuk informasi lebih lanjut.',
            default => 'Status profil tidak dikenal. Hubungi admin.'
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Menunggu Persetujuan',
            'accepted' => 'Diterima',
            'rejected' => 'Ditolak',
            default => 'Tidak Diketahui'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'accepted' => 'success',
            'rejected' => 'danger',
            default => 'gray'
        };
    }

    public function decreaseQuota(int $amount = 1): bool
    {
        if ($this->status === 'accepted' && $this->kuota >= $amount) {
            return $this->decrement('kuota', $amount);
        }
        return false;
    }

    public function increaseQuota(int $amount = 1): bool
    {
        return $this->increment('kuota', $amount);
    }

    public function canAccessTransactionFeatures(): bool
    {
        return $this->status === 'accepted';
    }

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

    public function scopeWithoutQuota($query)
    {
        return $query->where('kuota', '<=', 0);
    }

    public function scopeWithQuota($query)
    {
        return $query->where('kuota', '>', 0);
    }

    public function scopeLowQuota($query, int $threshold = 3)
    {
        return $query->where('kuota', '<=', $threshold)->where('kuota', '>', 0);
    }

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

    public function scopeCanTransact($query)
    {
        return $query->where('status', 'accepted')->where('kuota', '>', 0);
    }
}