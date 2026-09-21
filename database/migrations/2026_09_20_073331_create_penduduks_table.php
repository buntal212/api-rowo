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
        Schema::create('penduduks', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | IDENTITAS
            |--------------------------------------------------------------------------
            */

            $table->string('nik', 16)->unique();

            $table->string('no_kk', 16)->index();

            $table->string('nama', 150);

            $table->enum('jenis_kelamin', [
                'L',
                'P',
            ]);

            /*
            |--------------------------------------------------------------------------
            | TEMPAT & TANGGAL LAHIR
            |--------------------------------------------------------------------------
            */

            $table->string(
                'tempat_lahir',
                100
            )->nullable();

            $table->date(
                'tanggal_lahir'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | ALAMAT
            |--------------------------------------------------------------------------
            */

            $table->text(
                'alamat'
            )->nullable();

            $table->string(
                'rt',
                3
            )->nullable();

            $table->string(
                'rw',
                3
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | DATA PRIBADI
            |--------------------------------------------------------------------------
            */

            $table->string(
                'agama',
                50
            )->nullable();

            $table->string(
                'status_perkawinan',
                50
            )->nullable();

            $table->string(
                'pekerjaan',
                100
            )->nullable();

            $table->string(
                'status_keluarga',
                50
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | STATUS DATA
            |--------------------------------------------------------------------------
            */

            $table->boolean(
                'aktif'
            )->default(true);

            /*
            |--------------------------------------------------------------------------
            | TIMESTAMP
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | INDEX
            |--------------------------------------------------------------------------
            */

            $table->index('nama');

            $table->index([
                'rt',
                'rw',
            ]);

            $table->index(
                'jenis_kelamin'
            );

            $table->index(
                'status_keluarga'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penduduks');
    }
};
