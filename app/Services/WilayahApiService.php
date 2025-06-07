<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WilayahApiService
{
    private const BASE_URL = 'https://emsifa.github.io/api-wilayah-indonesia/api';
    private const CACHE_TTL = 3600; // 1 hour
    private const NIK_CACHE_TTL = 1800; // 30 minutes
    private const REQUEST_TIMEOUT = 5;

    /**
     * Get all provinces
     */
    public static function getProvinsiOptions(): array
    {
        return Cache::remember('api_provinces', self::CACHE_TTL, function () {
            try {
                $response = Http::timeout(self::REQUEST_TIMEOUT)
                    ->get(self::BASE_URL . '/provinces.json');
                
                if ($response->successful()) {
                    $provinces = $response->json();
                    return collect($provinces)->pluck('name', 'id')->toArray();
                }
            } catch (\Exception $e) {
                Log::error('Error fetching provinces: ' . $e->getMessage());
            }
            return [];
        });
    }

    /**
     * Get regencies by province ID
     */
    public static function getKabupatenOptions($provinceId): array
    {
        if (empty($provinceId)) {
            return [];
        }

        return Cache::remember("api_regencies_{$provinceId}", self::CACHE_TTL, function () use ($provinceId) {
            try {
                $response = Http::timeout(self::REQUEST_TIMEOUT)
                    ->get(self::BASE_URL . "/regencies/{$provinceId}.json");
                
                if ($response->successful()) {
                    $regencies = $response->json();
                    return collect($regencies)->pluck('name', 'id')->toArray();
                }
            } catch (\Exception $e) {
                Log::error("Error fetching regencies for province {$provinceId}: " . $e->getMessage());
            }
            return [];
        });
    }

    /**
     * Get districts by regency ID
     */
    public static function getKecamatanOptions($regencyId): array
    {
        if (empty($regencyId)) {
            return [];
        }

        return Cache::remember("api_districts_{$regencyId}", self::CACHE_TTL, function () use ($regencyId) {
            try {
                $response = Http::timeout(self::REQUEST_TIMEOUT)
                    ->get(self::BASE_URL . "/districts/{$regencyId}.json");
                
                if ($response->successful()) {
                    $districts = $response->json();
                    return collect($districts)->pluck('name', 'id')->toArray();
                }
            } catch (\Exception $e) {
                Log::error("Error fetching districts for regency {$regencyId}: " . $e->getMessage());
            }
            return [];
        });
    }

    /**
     * Get wilayah information from NIK
     */
    public static function getWilayahFromNik($nik): array
    {
        if (empty($nik) || strlen($nik) < 6) {
            return self::getEmptyWilayahData();
        }

        $nikPrefix = substr($nik, 0, 6);
        
        return Cache::remember("wilayah_nik_{$nikPrefix}", self::NIK_CACHE_TTL, function () use ($nik) {
            $kodeProvinsi = substr($nik, 0, 2);
            $kodeKabupaten = substr($nik, 0, 4);
            $kodeKecamatan = substr($nik, 0, 6);

            try {
                // Get province data
                $provinsiData = self::getProvinsiFromCode($kodeProvinsi);
                if (!$provinsiData['id']) {
                    return self::getEmptyWilayahData();
                }

                // Get regency data
                $kabupatenData = self::getKabupatenFromCode($kodeKabupaten, $provinsiData['id']);
                if (!$kabupatenData['id']) {
                    return [
                        'provinsi_id' => $provinsiData['id'],
                        'kabupaten_id' => '',
                        'kecamatan_id' => '',
                        'nama_provinsi' => $provinsiData['name'],
                        'nama_kabupaten' => '',
                        'nama_kecamatan' => ''
                    ];
                }

                // Get district data
                $kecamatanData = self::getKecamatanFromCode($kodeKecamatan, $kabupatenData['id']);

                return [
                    'provinsi_id' => $provinsiData['id'],
                    'kabupaten_id' => $kabupatenData['id'],
                    'kecamatan_id' => $kecamatanData['id'],
                    'nama_provinsi' => $provinsiData['name'],
                    'nama_kabupaten' => $kabupatenData['name'],
                    'nama_kecamatan' => $kecamatanData['name']
                ];

            } catch (\Exception $e) {
                Log::error('Error fetching wilayah data from NIK: ' . $e->getMessage(), [
                    'nik' => $nik,
                    'kode_provinsi' => $kodeProvinsi,
                    'kode_kabupaten' => $kodeKabupaten,
                    'kode_kecamatan' => $kodeKecamatan
                ]);
                return self::getEmptyWilayahData();
            }
        });
    }

    /**
     * Get province data by code
     */
    private static function getProvinsiFromCode($kodeProvinsi): array
    {
        $response = Http::timeout(self::REQUEST_TIMEOUT)
            ->get(self::BASE_URL . '/provinces.json');

        if ($response->successful()) {
            $provinces = collect($response->json());
            $provinsi = $provinces->firstWhere('id', $kodeProvinsi);
            
            if ($provinsi) {
                return [
                    'id' => $provinsi['id'],
                    'name' => $provinsi['name']
                ];
            }
        }

        return ['id' => '', 'name' => ''];
    }

    /**
     * Get regency data by code
     */
    private static function getKabupatenFromCode($kodeKabupaten, $provinsiId): array
    {
        $response = Http::timeout(self::REQUEST_TIMEOUT)
            ->get(self::BASE_URL . "/regencies/{$provinsiId}.json");

        if ($response->successful()) {
            $regencies = collect($response->json());
            $kabupaten = $regencies->firstWhere('id', $kodeKabupaten);
            
            if ($kabupaten) {
                return [
                    'id' => $kabupaten['id'],
                    'name' => $kabupaten['name']
                ];
            }
        }

        return ['id' => '', 'name' => ''];
    }

    /**
     * Get district data by code
     */
    private static function getKecamatanFromCode($kodeKecamatan, $kabupatenId): array
    {
        $response = Http::timeout(self::REQUEST_TIMEOUT)
            ->get(self::BASE_URL . "/districts/{$kabupatenId}.json");

        if ($response->successful()) {
            $districts = collect($response->json());
            
            // Try exact match first
            $kecamatan = $districts->first(function ($district) use ($kodeKecamatan) {
                return str_starts_with($district['id'], $kodeKecamatan);
            });

            // If not found, try contains match
            if (!$kecamatan) {
                $kecamatan = $districts->first(function ($district) use ($kodeKecamatan) {
                    return str_contains($district['id'], $kodeKecamatan);
                });
            }

            // If still not found, get first district
            if (!$kecamatan && $districts->isNotEmpty()) {
                $kecamatan = $districts->first();
            }

            if ($kecamatan) {
                return [
                    'id' => $kecamatan['id'],
                    'name' => $kecamatan['name']
                ];
            }
        }

        return ['id' => '', 'name' => ''];
    }

    /**
     * Get empty wilayah data structure
     */
    private static function getEmptyWilayahData(): array
    {
        return [
            'provinsi_id' => '',
            'kabupaten_id' => '',
            'kecamatan_id' => '',
            'nama_provinsi' => '',
            'nama_kabupaten' => '',
            'nama_kecamatan' => ''
        ];
    }
}