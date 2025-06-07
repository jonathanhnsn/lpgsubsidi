<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KurirSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('kurirs')->insert([
            'user_id'=>5,
            'nik'=>'3273050000000023',
            'no_telp'=>'081555555555',
            'nama_provinsi'=>'JAWA BARAT',
            'provinsi_id'=>'32',
            'nama_kota_kabupaten'=>'KOTA BANDUNG',
            'provinsi_id'=>'73',
            'nama_kecamatan'=>'ASTANAANYAR',
            'provinsi_id'=>'05',
            'alamat'=>'Jl. Inhoftank No. 64',
            'foto_ktp'=>null,
            'foto_sim'=>null,
            'status'=>'tersedia',
        ]);
    }
}
