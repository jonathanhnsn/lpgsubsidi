<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kurirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nik');
            $table->string('no_telp');
            $table->string('nama_provinsi');
            $table->string('provinsi_id')->nullable();
            $table->string('nama_kota_kabupaten');
            $table->string('kabupaten_id')->nullable();
            $table->string('nama_kecamatan');
            $table->string('kecamatan_id')->nullable();
            $table->string('alamat');
            $table->string('foto_ktp')->nullable();
            $table->string('foto_sim')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kurirs');
    }
};
