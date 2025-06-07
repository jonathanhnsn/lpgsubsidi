<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjuals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nama_pemilik');
            $table->string('nik');
            $table->string('nama_provinsi');
            $table->string('provinsi_id');
            $table->string('nama_kota_kabupaten');
            $table->string('kabupaten_id');
            $table->string('nama_kecamatan');
            $table->string('kecamatan_id');
            $table->string('alamat');
            $table->string('kategori');
            $table->integer('stok');
            $table->integer('kuota');
            $table->string('foto_ktp')->nullable();
            $table->string('foto_selfie')->nullable();
            $table->string('foto_izin')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjuals');
    }
};
