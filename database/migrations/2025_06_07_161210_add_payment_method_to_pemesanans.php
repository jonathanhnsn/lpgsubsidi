<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemesanans', function (Blueprint $table) {
            $table->enum('metode_pembayaran', [
                'transfer_bank',
                'dana',
                'gopay',
                'ovo',
                'shopee_pay',
                'qris'
            ])->after('jumlah_pesanan');
            
            $table->decimal('harga_per_tabung', 10, 2)->default(15000.00)->after('metode_pembayaran');
            $table->decimal('total_harga', 15, 2)->after('harga_per_tabung');
            
            $table->string('bukti_pembayaran')->nullable()->after('status_pembayaran');
            $table->timestamp('tanggal_pembayaran')->nullable()->after('bukti_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::table('pemesanans', function (Blueprint $table) {
            $table->dropColumn([
                'metode_pembayaran',
                'harga_per_tabung',
                'total_harga',
                'status_pembayaran',
                'bukti_pembayaran',
                'tanggal_pembayaran'
            ]);
        });
    }
};