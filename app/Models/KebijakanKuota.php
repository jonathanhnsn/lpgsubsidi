<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KebijakanKuota extends Model
{
    protected $fillable = [
        'tipe',
        'kategori_atau_pekerjaan',
        'sub_kategori',
        'kuota',
        'is_aktif',
        'keterangan'
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
        'kuota' => 'integer'
    ];

    public function scopePenjual($query)
    {
        return $query->where('tipe', 'penjual');
    }

    public function scopePembeli($query)
    {
        return $query->where('tipe', 'pembeli');
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }

    public static function getKuotaPenjual($kategori)
    {
        $kebijakan = self::penjual()
            ->aktif()
            ->where('kategori_atau_pekerjaan', $kategori)
            ->first();
            
        return $kebijakan ? $kebijakan->kuota : null;
    }

    public static function getKuotaPembeli($pekerjaan, $gaji = null)
    {
        $query = self::pembeli()
            ->aktif()
            ->where('kategori_atau_pekerjaan', $pekerjaan);
            
        if ($gaji) {
            $query->where('sub_kategori', $gaji);
        }
        
        $kebijakan = $query->first();
        
        return $kebijakan ? $kebijakan->kuota : null;
    }

    public function getTipeLabelAttribute()
    {
        return match($this->tipe) {
            'penjual' => 'Penjual',
            'pembeli' => 'Pembeli',
            default => 'Unknown'
        };
    }

    public function getStatusLabelAttribute()
    {
        return $this->is_aktif ? 'Aktif' : 'Nonaktif';
    }
}