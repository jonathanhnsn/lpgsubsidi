<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PenjualSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('penjuals')->insert([
            'user_id' => 4,
            'nama_pemilik' => 'sutarman',
            'nik' => '3273021111111111',
            'nama_provinsi' => 'JAWA BARAT',
            'provinsi_id' => '32',
            'nama_kota_kabupaten' => 'KOTA BANDUNG',
            'kabupaten_id' => '73',
            'nama_kecamatan' => 'COBLONG',
            'kecamatan_id' => '02',
            'alamat' => 'Jalan jalan',
            'kategori' => 'Sub-Pangkalan',
            'stok' => 20,
            'kuota' => 125,
            'foto_ktp' => null,
            'foto_selfie' => null,
            'foto_izin' => null,
            'status' => 'accepted',
        ]);
        DB::table('penjuals')->insert([
            'user_id' => 7,
            'nama_pemilik' => 'ahmad',
            'nik' => '3273021111221133',
            'nama_provinsi' => 'JAWA BARAT',
            'provinsi_id' => '32',
            'nama_kota_kabupaten' => 'KOTA BANDUNG',
            'kabupaten_id' => '73',
            'nama_kecamatan' => 'COBLONG',
            'kecamatan_id' => '02',
            'alamat' => 'Jalan jalan',
            'kategori' => 'Pangkalan',
            'stok' => 0,
            'kuota' => 800,
            'foto_ktp' => null,
            'foto_selfie' => null,
            'foto_izin' => null,
            'status' => 'accepted',
        ]);
    }
}
