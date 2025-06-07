<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gas extends Model
{
    use HasFactory;

    protected $fillable = [
        'jenis',
        'harga'
    ];

    protected $casts = [
        'harga' => 'decimal:2'
    ];

    public function penjuals(): HasMany
    {
        return $this->hasMany(Penjual::class, 'kategori', 'jenis');
    }

    public function getTotalStokAttribute(): int
    {
        return $this->penjuals()->sum('stok');
    }

    public function getTotalKuotaAttribute(): int
    {
        return $this->penjuals()->sum('kuota');
    }
}