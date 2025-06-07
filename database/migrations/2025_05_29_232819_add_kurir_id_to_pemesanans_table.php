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
        Schema::table('pemesanans', function (Blueprint $table) {
            if (!Schema::hasColumn('pemesanans', 'kurir_id')) {
                $table->foreignId('kurir_id')->nullable()->after('penjual_id')->constrained('kurirs')->onDelete('set null');
            }
            
            $table->enum('status', ['pending', 'disetujui', 'dalam_perjalanan', 'ditolak', 'selesai'])
                  ->default('pending')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pemesanans', function (Blueprint $table) {
            if (Schema::hasColumn('pemesanans', 'kurir_id')) {
                $table->dropForeign(['kurir_id']);
                $table->dropColumn('kurir_id');
            }
            
            $table->enum('status', ['pending', 'disetujui', 'ditolak', 'selesai'])
                  ->default('pending')
                  ->change();
        });
    }
};