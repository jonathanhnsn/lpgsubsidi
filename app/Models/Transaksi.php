<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Transaksi extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_transaksi',
        'pembeli_id',
        'gas_jenis',
        'harga',
        'tgl_beli',
        'tgl_kembali',
        'status',
        'penjual_id'
    ];

    protected $casts = [
        'tgl_beli' => 'date',
        'tgl_kembali' => 'date',
        'harga' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($transaksi) {
            $transaksi->kode_transaksi = self::generateKodeTransaksi();
            if (auth()->check()) {
                $user = auth()->user();
                $pembeli = Pembeli::where('user_id', $user->id)->first();
                if ($pembeli) {
                    $transaksi->pembeli_id = $pembeli->id;
                    if ($pembeli->kuota <= 0) {
                        throw new \Exception('Kuota transaksi Anda sudah habis. Tidak dapat membuat transaksi baru.');
                    }
                }
            }
        });
        static::created(function ($transaksi) {
            if ($transaksi->pembeli) {
                $transaksi->pembeli->decrement('kuota', 1);
            }
        });
        static::updating(function ($transaksi) {
            $originalStatus = $transaksi->getOriginal('status');
            $newStatus = $transaksi->status;
            $originalPenjualId = $transaksi->getOriginal('penjual_id');
            $newPenjualId = $transaksi->penjual_id;
            if ($originalStatus === 'Pending' && $newStatus === 'Confirmed' && $newPenjualId) {
                $penjual = Penjual::find($newPenjualId);
                if ($penjual) {
                    if ($penjual->stok <= 0) {
                        throw new \Exception('Stok penjual tidak mencukupi untuk menangani transaksi ini.');
                    }
                    $penjual->decrement('stok', 1);
                }
            }
            if ($originalStatus !== 'Completed' && $newStatus === 'Completed') {
                if ($transaksi->pembeli) {
                    $transaksi->pembeli->increment('kuota', 1);
                }
            }
            if ($originalStatus === 'Completed' && $newStatus !== 'Completed') {
                if ($transaksi->pembeli && $transaksi->pembeli->kuota > 0) {
                    $transaksi->pembeli->decrement('kuota', 1);
                }
                if ($transaksi->penjual_id) {
                    $penjual = Penjual::find($transaksi->penjual_id);
                    if ($penjual) {
                        $penjual->increment('stok', 1);
                    }
                }
            }
        });
        static::addGlobalScope('own_transactions', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                $pembeli = Pembeli::where('user_id', $user->id)->first();
                if ($pembeli) {
                    $builder->where('pembeli_id', $pembeli->id);
                }
            }
        });
    }

    public static function generateKodeTransaksi()
    {
        do {
            $kode = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
        } while (self::withoutGlobalScopes()->where('kode_transaksi', $kode)->exists());
        
        return $kode;
    }

    public function pembeli(): BelongsTo
    {
        return $this->belongsTo(Pembeli::class);
    }

    public function penjual(): BelongsTo
    {
        return $this->belongsTo(Penjual::class);
    }

    public function gas(): BelongsTo
    {
        return $this->belongsTo(Gas::class, 'gas_jenis', 'id');
    }

    public function getNamaPembeliAttribute(): string
    {
        return $this->pembeli->user->name ?? '';
    }

    public function getNamaPenjualAttribute(): string
    {
        return $this->penjual->user->name ?? 'Belum ada penjual';
    }

    public function scopeWithoutUserRestriction($query)
    {
        return $query->withoutGlobalScopes(['own_transactions']);
    }

    public function scopeForPembeli($query, $pembeliId)
    {
        return $query->where('pembeli_id', $pembeliId);
    }

    public function scopeForPenjual($query, $penjualId)
    {
        return $query->where('penjual_id', $penjualId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'Confirmed');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    public function isOwnedByUser($userId): bool
    {
        return $this->pembeli && $this->pembeli->user_id === $userId;
    }

    public function canBeAccessedBy($userId): bool
    {
        if ($this->isOwnedByUser($userId)) {
            return true;
        }
        if ($this->penjual && $this->penjual->user_id === $userId) {
            return true;
        }
        return false;
    }

    public function completeTransaction()
    {
        $this->update([
            'status' => 'Completed',
            'tgl_kembali' => now()->toDateString()
        ]);
    }

    public function cancelTransaction()
    {
        $this->delete();
    }

    public function confirmTransactionWithStockValidation($penjualId)
    {
        $penjual = Penjual::find($penjualId);
        if (!$penjual) {
            throw new \Exception('Penjual tidak ditemukan.');
        }
        if ($penjual->stok <= 0) {
            throw new \Exception('Stok penjual tidak mencukupi untuk menangani transaksi ini.');
        }
        $this->update([
            'penjual_id' => $penjualId,
            'status' => 'Confirmed'
        ]);
        return true;
    }
}