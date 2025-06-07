<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PembeliSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('pembelis')->insert([
            'user_id' => 3,
            'nik' => '3171011234567890',
            'nama_provinsi' => 'DKI JAKARTA',
            'provinsi_id' => '31',
            'nama_kota_kabupaten' => 'JAKARTA PUSAT',
            'kabupaten_id' => '71',
            'nama_kecamatan' => 'GAMBIR',
            'kecamatan_id' => '01',
            'alamat' => 'Jl. Merdeka No. 10, Jakarta Pusat',
            'pekerjaan' => 'UMKM',
            'gaji' => '< 3 juta',
            'kuota' => 8,
            'foto_ktp' => null,
            'foto_selfie' => null,
            'foto_kk' => null,
            'foto_usaha' => null,
            'foto_izin' => null,
            'status' => 'accepted',
        ]);
        DB::table('pembelis')->insert([
            'user_id' => 6,
            'nik' => '3275010129384756',
            'nama_provinsi' => 'JAWA BARAT',
            'provinsi_id' => '32',
            'nama_kota_kabupaten' => 'KOTA BEKASI',
            'kabupaten_id' => '75',
            'nama_kecamatan' => 'PONDOK GEDE',
            'kecamatan_id' => '01',
            'alamat' => 'Jl. Sangata Asri No. 57',
            'pekerjaan' => 'Swasta',
            'gaji' => '< 3 juta',
            'kuota' => 4,
            'foto_ktp' => null,
            'foto_selfie' => null,
            'foto_kk' => null,
            'foto_usaha' => null,
            'foto_izin' => null,
            'status' => 'accepted',
        ]);
    }
}
