<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name'=>'pemerintah',
            'email'=>'pemerintah@subsidi.com',
            'password'=>bcrypt('password'),
        ]);
        DB::table('users')->insert([
            'name'=>'admin',
            'email'=>'admin@subsidi.com',
            'password'=>bcrypt('password'),
        ]);
        DB::table('users')->insert([
            'name'=>'bambang',
            'email'=>'pembeli@subsidi.com',
            'password'=>bcrypt('password'),
        ]);
        DB::table('users')->insert([
            'name'=>'PT Abadi',
            'email'=>'penjual@subsidi.com',
            'password'=>bcrypt('password'),
        ]);
        DB::table('users')->insert([
            'name'=>'tatang',
            'email'=>'kurir@subsidi.com',
            'password'=>bcrypt('password'),
        ]);
        DB::table('users')->insert([
            'name'=>'iqbal',
            'email'=>'pembeli2@subsidi.com',
            'password'=>bcrypt('password'),
        ]);
        DB::table('users')->insert([
            'name'=>'Pangkalan Buana',
            'email'=>'penjual2@subsidi.com',
            'password'=>bcrypt('password'),
        ]);
    }
}
