<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KebijakanKuota;

class KebijakanKuotaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data default untuk penjual
        $penjualData = [
            [
                'tipe' => 'penjual',
                'kategori_atau_pekerjaan' => 'Agen',
                'sub_kategori' => null,
                'kuota' => 1000,
                'is_aktif' => true,
                'keterangan' => 'Kuota default untuk kategori Agen'
            ],
            [
                'tipe' => 'penjual',
                'kategori_atau_pekerjaan' => 'Pangkalan',
                'sub_kategori' => null,
                'kuota' => 800,
                'is_aktif' => true,
                'keterangan' => 'Kuota default untuk kategori Pangkalan'
            ],
            [
                'tipe' => 'penjual',
                'kategori_atau_pekerjaan' => 'Sub-Pangkalan',
                'sub_kategori' => null,
                'kuota' => 125,
                'is_aktif' => true,
                'keterangan' => 'Kuota default untuk kategori Sub-Pangkalan'
            ],
        ];

        // Data default untuk pembeli
        $pembeliData = [
            [
                'tipe' => 'pembeli',
                'kategori_atau_pekerjaan' => 'Ibu Rumah Tangga',
                'sub_kategori' => '< 3 juta',
                'kuota' => 4,
                'is_aktif' => true,
                'keterangan' => 'Kuota untuk Ibu Rumah Tangga dengan pendapatan < 3 juta per bulan'
            ],
            [
                'tipe' => 'pembeli',
                'kategori_atau_pekerjaan' => 'Ibu Rumah Tangga',
                'sub_kategori' => '> 3 juta',
                'kuota' => 4,
                'is_aktif' => true,
                'keterangan' => 'Kuota untuk Ibu Rumah Tangga dengan pendapatan > 3 juta per bulan'
            ],
            [
                'tipe' => 'pembeli',
                'kategori_atau_pekerjaan' => 'UMKM',
                'sub_kategori' => '< 3 juta',
                'kuota' => 8,
                'is_aktif' => true,
                'keterangan' => 'Kuota untuk UMKM dengan pendapatan < 3 juta per hari'
            ],
            [
                'tipe' => 'pembeli',
                'kategori_atau_pekerjaan' => 'UMKM',
                'sub_kategori' => '> 3 juta',
                'kuota' => 12,
                'is_aktif' => true,
                'keterangan' => 'Kuota untuk UMKM dengan pendapatan > 3 juta per hari'
            ],
            [
                'tipe' => 'pembeli',
                'kategori_atau_pekerjaan' => 'Swasta',
                'sub_kategori' => '< 3 juta',
                'kuota' => 4,
                'is_aktif' => true,
                'keterangan' => 'Kuota untuk Karyawan Swasta dengan gaji < 3 juta per bulan'
            ],
            [
                'tipe' => 'pembeli',
                'kategori_atau_pekerjaan' => 'Swasta',
                'sub_kategori' => '> 3 juta',
                'kuota' => 4,
                'is_aktif' => true,
                'keterangan' => 'Kuota untuk Karyawan Swasta dengan gaji > 3 juta per bulan'
            ],
            [
                'tipe' => 'pembeli',
                'kategori_atau_pekerjaan' => 'Negeri',
                'sub_kategori' => '< 3 juta',
                'kuota' => 4,
                'is_aktif' => true,
                'keterangan' => 'Kuota untuk PNS dengan gaji < 3 juta per bulan'
            ],
            [
                'tipe' => 'pembeli',
                'kategori_atau_pekerjaan' => 'Negeri',
                'sub_kategori' => '> 3 juta',
                'kuota' => 4,
                'is_aktif' => true,
                'keterangan' => 'Kuota untuk PNS dengan gaji > 3 juta per bulan'
            ],
        ];

        // Insert data penjual
        foreach ($penjualData as $data) {
            KebijakanKuota::updateOrCreate(
                [
                    'tipe' => $data['tipe'],
                    'kategori_atau_pekerjaan' => $data['kategori_atau_pekerjaan'],
                    'sub_kategori' => $data['sub_kategori']
                ],
                $data
            );
        }

        // Insert data pembeli
        foreach ($pembeliData as $data) {
            KebijakanKuota::updateOrCreate(
                [
                    'tipe' => $data['tipe'],
                    'kategori_atau_pekerjaan' => $data['kategori_atau_pekerjaan'],
                    'sub_kategori' => $data['sub_kategori']
                ],
                $data
            );
        }

        $this->command->info('Kebijakan kuota berhasil di-seed!');
    }
}