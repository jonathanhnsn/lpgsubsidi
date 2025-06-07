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
        Schema::create('kebijakan_kuotas', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe', ['penjual', 'pembeli']);
            $table->string('kategori_atau_pekerjaan');
            $table->string('sub_kategori')->nullable();
            $table->integer('kuota');
            $table->boolean('is_aktif')->default(true);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->index(['tipe', 'kategori_atau_pekerjaan', 'sub_kategori']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kebijakan_kuotas');
    }
};