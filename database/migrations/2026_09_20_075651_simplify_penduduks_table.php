<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penduduks', function (Blueprint $table) {
            $table->dropColumn([
                'nik',
                'no_kk',
                'jenis_kelamin',
                'tempat_lahir',
                'tanggal_lahir',
                'alamat',
                'rt',
                'rw',
                'agama',
                'status_perkawinan',
                'pekerjaan',
                'status_keluarga',
                'aktif',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('penduduks', function (Blueprint $table) {
            $table->string('nik', 16)->unique();
            $table->string('no_kk', 16)->index();
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('alamat')->nullable();
            $table->string('rt', 3)->nullable();
            $table->string('rw', 3)->nullable();
            $table->string('agama', 50)->nullable();
            $table->string('status_perkawinan', 50)->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->string('status_keluarga', 50)->nullable();
            $table->boolean('aktif')->default(true);
        });
    }
};
