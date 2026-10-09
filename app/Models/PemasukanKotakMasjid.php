<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemasukanKotakMasjid extends Model
{
    use HasFactory;

    protected $table = 'pemasukan_kotak_masjid';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_masuk' => 'date:Y-m-d',
        'nominal' => 'decimal:2',
    ];
}
