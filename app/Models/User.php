<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function pembelis(): HasMany
    {
        return $this->hasMany(Pembeli::class);
    }

    public function penjuals(): HasMany
    {
        return $this->hasMany(Penjual::class);
    }

    // Perbaiki relasi kurir - seharusnya HasOne karena satu user hanya bisa punya satu profil kurir
    public function kurir(): HasOne
    {
        return $this->hasOne(Kurir::class);
    }

    // Tambahkan method untuk mengecek role
    public function isKurir(): bool
    {
        return $this->kurir !== null;
    }

    public function isPenjual(): bool
    {
        return $this->penjuals()->exists();
    }

    public function isPembeli(): bool
    {
        return $this->pembelis()->exists();
    }
}