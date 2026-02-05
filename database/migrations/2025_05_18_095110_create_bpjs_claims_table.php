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
        Schema::create('bpjs_claims', function (Blueprint $table) {
            $table->id();
            $table->string('no_rm')->nullable();
            $table->string('nama_pasien');
            $table->string('no_kartu_bpjs')->nullable();
            $table->string('no_sep')->unique();
            $table->string('jenis_rawatan');
            $table->string('kelas_rawatan')->nullable();
            $table->string('file_path')->nullable();
            $table->date('tanggal_rawatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bpjs_claims');
    }
};
