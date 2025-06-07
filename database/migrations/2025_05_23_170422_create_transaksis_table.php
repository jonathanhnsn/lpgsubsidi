<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi')->unique();
            $table->foreignId('pembeli_id')->constrained()->onDelete('cascade');
            $table->foreignId('gas_jenis')->constrained()->onDelete('cascade');
            $table->decimal('harga');
            $table->date('tgl_beli');
            $table->date('tgl_kembali')->nullable();
            $table->string('status');
            $table->foreignId('penjual_id')->constrained()->onDelete('cascade')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
