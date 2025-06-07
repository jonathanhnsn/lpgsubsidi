<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropForeign(['gas_jenis']);
            $table->unsignedBigInteger('gas_jenis')->change();
            $table->foreign('gas_jenis')->references('id')->on('gases')->onDelete('cascade');
            $table->unsignedBigInteger('penjual_id')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropForeign(['gas_jenis']);
            $table->string('gas_jenis')->change();
            $table->foreign('gas_jenis')->references('jenis')->on('gases')->onDelete('cascade');
        });
    }
};